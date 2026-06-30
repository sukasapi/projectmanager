<?php

namespace App\Livewire\Produksi;

use App\Enums\EmploymentType;
use App\Enums\FaseProduksi;
use App\Enums\TaskStatus;
use App\Models\AktivitasTahap;
use App\Models\Notifikasi;
use App\Models\Proyek;
use App\Models\Tahap;
use App\Models\TugasTahap;
use App\Models\User;
use App\Services\GeminiService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Throwable;

/**
 * Tracker tahap level-EPISODE (basis untuk Pra-Produksi & Pasca-Produksi).
 * Pola sama dengan Produksi: pilih episode (kartu) dulu, lalu lacak tiap tahap.
 * Supervisor melihat semua episode; lainnya hanya yang ditugaskan. Lihat PIPELINE.md §3.1.
 */
abstract class TrackerTahap extends Component
{
    public ?int $proyekId = null;

    public bool $showForm = false;

    public ?int $editingId = null;

    public ?int $artistId = null;

    public string $fileUrl = '';

    public string $deskripsi = '';

    public ?string $startDate = null;

    public ?string $deadline = null;

    // Tolak (reject) & riwayat (history).
    public bool $showReject = false;

    public ?int $rejectId = null;

    public string $rejectAlasan = '';

    public bool $showHistory = false;

    public ?int $historyId = null;

    /** Fase produksi yang dilacak komponen ini. */
    abstract protected function fase(): FaseProduksi;

    private function bolehAkses(int $proyekId): bool
    {
        return Gate::allows('manage-tim')
            || Proyek::whereKey($proyekId)->untukUser(auth()->id() ?? 0)->exists();
    }

    public function pilihEpisode(int $id): void
    {
        if ($this->bolehAkses($id)) {
            $this->proyekId = $id;
        }
    }

    public function gantiEpisode(): void
    {
        $this->proyekId = null;
        $this->showForm = false;
    }

    /** Snapshot pipeline episode terpilih untuk fase ini. @return Collection<int, Tahap> */
    private function tahapAktif()
    {
        return Tahap::milikEpisode((int) $this->proyekId)
            ->aktif()->fase($this->fase())->where('level', 'EPISODE')->urut()->get();
    }

    public function edit(int $tahapId): void
    {
        abort_unless($this->proyekId && $this->bolehAkses($this->proyekId), 403);

        $row = TugasTahap::firstOrCreate(
            ['project_id' => $this->proyekId, 'tahap_id' => $tahapId],
            ['status' => TaskStatus::NOT_STARTED->value],
        );

        $this->editingId = $row->id;
        $this->artistId = $row->artist_id;
        $this->fileUrl = $row->file_url ?? '';
        $this->deskripsi = $row->deskripsi ?? '';
        $this->startDate = $row->start_date?->toDateString();
        $this->deadline = $row->deadline?->toDateString();
        $this->resetErrorBag();
        $this->showForm = true;
    }

    /** Setup tahap (assign artis, jadwal, deskripsi) — hanya pengelola (Supervisor/Lead). Status lewat workflow. */
    public function save(): void
    {
        abort_unless($this->editingId && $this->dapatKelola(), 403);

        $validated = $this->validate([
            'artistId' => ['nullable', 'integer', 'exists:kf_pengguna,id'],
            'fileUrl' => ['nullable', 'url', 'max:2048'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'startDate' => ['nullable', 'date'],
            'deadline' => ['nullable', 'date', 'after_or_equal:startDate'],
        ]);

        $row = TugasTahap::with(['proyek', 'tahap'])->where('project_id', $this->proyekId)->whereKey($this->editingId)->first();
        $artisLama = $row?->artist_id;
        $artisBaru = $validated['artistId'] ?: null;

        $row?->update([
            'artist_id' => $artisBaru,
            'file_url' => $validated['fileUrl'] ?: null,
            'deskripsi' => $validated['deskripsi'] ?: null,
            'start_date' => $validated['startDate'] ?: null,
            'deadline' => $validated['deadline'] ?: null,
        ]);

        // Catat versi deliverable bila tautan file berubah.
        $row?->catatVersi($validated['fileUrl'] ?? '', null, auth()->id());

        // Notifikasi penugasan ke artis baru (bila episode sudah dipublish).
        if ($row && $artisBaru && $artisBaru !== $artisLama && $row->proyek->isPublished()) {
            Notifikasi::kirim($artisBaru, "Ditugaskan: {$row->tahap?->name}", "Anda ditugaskan pada {$row->proyek->name} — {$row->tahap?->name}.", route($this->routeName()), 'publish');
        }

        $this->showForm = false;
        $this->reset(['editingId', 'artistId', 'fileUrl', 'deskripsi', 'startDate', 'deadline']);
        $this->dispatch('tahap-tugas-tersimpan');
        $this->dispatch('toast', message: 'Penugasan disimpan.');
    }

    public function cancel(): void
    {
        $this->showForm = false;
        $this->reset(['editingId', 'artistId', 'fileUrl', 'deskripsi', 'startDate', 'deadline']);
        $this->resetErrorBag();
    }

    // ---------- Workflow: Mulai → Propose → Approve / Reject ----------

    private function dapatKelola(): bool
    {
        $proyek = $this->proyekId ? Proyek::find($this->proyekId) : null;

        return (bool) ($proyek && $proyek->dapatDikelola(auth()->user()));
    }

    /** Boleh me-review (approve/reject): Supervisor & Team Lead (Super Admin read-only). */
    private function dapatReview(): bool
    {
        $proyek = $this->proyekId ? Proyek::find($this->proyekId) : null;

        return (bool) ($proyek && $proyek->dapatReview(auth()->user()));
    }

    /** Artis yang ditugaskan ATAU reviewer (Supervisor/Lead), dan episode sudah dipublish. */
    private function bisaKerja(TugasTahap $row): bool
    {
        $milikSaya = $row->artist_id === auth()->id();

        return ($milikSaya || $row->proyek->dapatReview(auth()->user())) && $row->proyek->isPublished();
    }

    private function transisi(TugasTahap $row, TaskStatus $target, string $kind, ?string $note = null): void
    {
        $from = $row->status;
        $row->update(['status' => $target->value]);

        AktivitasTahap::create([
            'tugas_tahap_id' => $row->id,
            'author_id' => auth()->id(),
            'kind' => $kind,
            'note' => $note,
            'status_from' => $from->value,
            'status_to' => $target->value,
        ]);
    }

    /** Notifikasi ke reviewer: Supervisor + Team Lead (Super Admin tidak me-review). */
    private function notifPengelola(TugasTahap $row, string $title, string $message): void
    {
        $ids = User::where('is_active', true)
            ->where('role', 'Supervisor')
            ->pluck('id')
            ->push($row->proyek->team_lead_id);

        Notifikasi::kirimBanyak($ids, $title, $message, route($this->routeName()), 'propose');
    }

    abstract protected function routeName(): string;

    public function mulai(int $id): void
    {
        $row = TugasTahap::with('proyek')->findOrFail($id);
        abort_unless($this->bisaKerja($row), 403);

        if ($row->status === TaskStatus::NOT_STARTED) {
            $this->transisi($row, TaskStatus::IN_PROGRESS, 'MULAI');
        }
    }

    public function propose(int $id): void
    {
        $row = TugasTahap::with(['proyek', 'tahap'])->findOrFail($id);
        abort_unless($this->bisaKerja($row), 403);

        if ($row->status === TaskStatus::IN_PROGRESS) {
            $this->transisi($row, TaskStatus::REVIEW, 'PROPOSE');
            $this->notifPengelola(
                $row,
                "Menunggu review: {$row->tahap?->name}",
                "{$row->proyek->name} — diajukan oleh ".(auth()->user()->name ?? '').'.',
            );
            $this->dispatch('toast', message: 'Pekerjaan diajukan untuk review.');
        }
    }

    public function setujui(int $id): void
    {
        $row = TugasTahap::with(['proyek', 'tahap'])->findOrFail($id);
        abort_unless($row->proyek->dapatReview(auth()->user()), 403);
        abort_if(auth()->user()->employment_type === EmploymentType::INTERN, 403);

        if ($row->status === TaskStatus::REVIEW) {
            $this->transisi($row, TaskStatus::APPROVED, 'APPROVE');
            if ($row->artist_id) {
                Notifikasi::kirim($row->artist_id, "Disetujui: {$row->tahap?->name}", "{$row->proyek->name} — pekerjaan Anda disetujui.", route($this->routeName()), 'approve');
            }
            $this->dispatch('toast', message: 'Pekerjaan disetujui.');
        }
    }

    public function tolak(int $id): void
    {
        $row = TugasTahap::with('proyek')->findOrFail($id);
        abort_unless($row->proyek->dapatReview(auth()->user()), 403);

        $this->rejectId = $id;
        $this->rejectAlasan = '';
        $this->resetErrorBag();
        $this->showReject = true;
    }

    public function konfirmasiTolak(): void
    {
        $this->validate(['rejectAlasan' => ['required', 'string', 'min:3', 'max:1000']], attributes: ['rejectAlasan' => 'alasan']);

        $row = TugasTahap::with(['proyek', 'tahap'])->findOrFail($this->rejectId);
        abort_unless($row->proyek->dapatReview(auth()->user()), 403);

        if ($row->status === TaskStatus::REVIEW) {
            $this->transisi($row, TaskStatus::IN_PROGRESS, 'REJECT', $this->rejectAlasan);
            if ($row->artist_id) {
                Notifikasi::kirim($row->artist_id, "Perlu revisi: {$row->tahap?->name}", "Ditolak: {$this->rejectAlasan}", route($this->routeName()), 'reject');
            }
        }

        $this->showReject = false;
        $this->reset(['rejectId', 'rejectAlasan']);
        $this->dispatch('toast', message: 'Dikembalikan untuk revisi.', icon: 'info');
    }

    public function bukaHistory(int $id): void
    {
        $this->historyId = $id;
        $this->showHistory = true;
    }

    public function tutupHistory(): void
    {
        $this->showHistory = false;
        $this->historyId = null;
    }

    public bool $showDiskusi = false;

    public ?int $diskusiId = null;

    public function bukaDiskusi(int $id): void
    {
        abort_unless($this->proyekId && $this->bolehAkses($this->proyekId), 403);
        $this->diskusiId = $id;
        $this->showDiskusi = true;
    }

    public function tutupDiskusi(): void
    {
        $this->showDiskusi = false;
        $this->diskusiId = null;
    }

    /** Bantu tulis deskripsi tahap dengan AI (Gemini). Hanya jalan bila key diisi. */
    public function isiDeskripsiAi(GeminiService $ai): void
    {
        if (! $ai->aktif() || ! $this->editingId) {
            return;
        }

        $row = TugasTahap::with(['tahap', 'proyek'])->find($this->editingId);
        if (! $row) {
            return;
        }

        $instruksi = 'Tuliskan deskripsi singkat (2-3 kalimat, Bahasa Indonesia, tanpa judul) '
            ."untuk tahap produksi animasi \"{$row->tahap?->name}\" pada episode \"{$row->proyek?->name}\". "
            .'Jelaskan deliverable dan lingkup pekerjaan tahap ini secara ringkas dan profesional.';

        try {
            $this->deskripsi = $ai->tulisDeskripsi($instruksi);
        } catch (Throwable $e) {
            $this->addError('deskripsi', 'Gagal memanggil AI: '.$e->getMessage());
        }
    }

    public function render()
    {
        $fase = $this->fase();
        $supervisor = Gate::allows('manage-tim');

        if ($this->proyekId && ! $this->bolehAkses($this->proyekId)) {
            $this->proyekId = null;
        }

        $proyek = $this->proyekId ? Proyek::with('klien')->find($this->proyekId) : null;

        $tahapList = $proyek ? $this->tahapAktif() : collect();
        $rows = $proyek
            ? TugasTahap::where('project_id', $proyek->id)->with('artis')->get()->keyBy('tahap_id')
            : collect();

        $episodes = $this->proyekId ? collect() : Proyek::query()
            ->with('klien')
            ->withCount('adegan')
            ->when(! $supervisor, fn ($q) => $q->untukUser(auth()->id() ?? 0))
            ->orderByDesc('id')
            ->get();

        return view('livewire.produksi.tracker-tahap', [
            'fase' => $fase,
            'proyek' => $proyek,
            'dapatKelola' => $proyek ? $proyek->dapatDikelola(auth()->user()) : false,
            'dapatReview' => $proyek ? $proyek->dapatReview(auth()->user()) : false,
            'tahapList' => $tahapList,
            'rows' => $rows,
            'episodes' => $episodes,
            'daftarArtis' => User::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'aiAktif' => app(GeminiService::class)->aktif(),
            'historyItems' => $this->showHistory && $this->historyId
                ? AktivitasTahap::where('tugas_tahap_id', $this->historyId)->with('author')->latest()->get()
                : collect(),
            'versiTahap' => $this->editingId
                ? (TugasTahap::find($this->editingId)?->versi()->with('author')->get() ?? collect())
                : collect(),
        ]);
    }
}
