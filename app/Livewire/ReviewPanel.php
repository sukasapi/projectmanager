<?php

namespace App\Livewire;

use App\Actions\RecordShotRevision;
use App\Actions\TransitionShotTaskStatus;
use App\Enums\RevisionStatus;
use App\Enums\TaskStatus;
use App\Exceptions\InvalidShotTaskTransition;
use App\Models\CatatanReview;
use App\Models\KolomShotlist;
use App\Models\Notifikasi;
use App\Models\Proyek;
use App\Models\TugasShot;
use App\Models\User;
use App\Services\GeminiService;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

class ReviewPanel extends Component
{
    public ?int $tugasShotId = null;

    public bool $terbuka = false;

    public string $catatan = '';

    public string $previewUrl = '';

    public ?string $startDate = null;

    public ?string $deadline = null;

    /** Estimasi lama pengerjaan (hari) — untuk perencanaan kapasitas (Tier C3). */
    public ?int $estimasiHari = null;

    /** Penugasan artis (many-to-many: satu tahap bisa banyak artis). */
    public array $artisIds = [];

    /** Deskripsi shot (level Shot, dipakai bersama semua tahap shot itu). */
    public string $deskripsiShot = '';

    /** Catatan opsional untuk versi deliverable yang baru disimpan. */
    public string $versiCatatan = '';

    /** Catatan review terstruktur (Tier C2): rentang frame + isi. */
    public ?int $noteFrameStart = null;

    public ?int $noteFrameEnd = null;

    public string $noteBody = '';

    /** Referensi shotlist (metadata shot) yang dapat diedit pengelola dari panel review. */
    public array $metaEdit = [];

    #[On('buka-review')]
    public function buka(int $tugasShotId): void
    {
        $this->resetErrorBag();
        $this->tugasShotId = $tugasShotId;
        $task = $this->task();

        // Anggota biasa hanya boleh membuka shot-task yang ditugaskan padanya;
        // pengelola (Supervisor/Super Admin/Team Lead) boleh membuka semua.
        if (! $this->bolehBuka($task)) {
            $this->tugasShotId = null;

            return;
        }

        $this->previewUrl = $task?->preview_url ?? '';
        $this->startDate = $task?->start_date?->toDateString();
        $this->deadline = $task?->deadline?->toDateString();
        $this->estimasiHari = $task?->estimasi_hari;
        $this->artisIds = $task?->artists->pluck('id')->map(fn ($i) => (string) $i)->all() ?? [];
        $this->deskripsiShot = $task?->shot?->description ?? '';
        $this->metaEdit = $task?->shot?->meta ?? [];
        $this->catatan = '';
        $this->terbuka = true;
    }

    /** Boleh membuka panel: pengelola episode atau artis yang ditugaskan pada shot-task ini. */
    private function bolehBuka(?TugasShot $task): bool
    {
        if (! $task) {
            return false;
        }

        $u = auth()->user();
        $projectId = $task->shot?->adegan?->project_id;
        $proyek = $projectId ? Proyek::find($projectId) : null;

        if ($u && ($u->isSupervisory() || $proyek?->team_lead_id === $u->id)) {
            return true;
        }

        return $task->artists->contains($u?->id);
    }

    /** Simpan deskripsi shot (hanya pengelola). */
    public function simpanDeskripsiShot(): void
    {
        abort_unless($this->dapatKelola(), 403);

        $this->validate(['deskripsiShot' => ['nullable', 'string', 'max:2000']], attributes: ['deskripsiShot' => 'deskripsi shot']);

        $this->task()?->shot?->update(['description' => $this->deskripsiShot ?: null]);
        $this->dispatch('shot-tersimpan');
        $this->dispatch('toast', message: 'Deskripsi shot disimpan.');
    }

    /** Simpan referensi shotlist (metadata shot) — hanya pengelola. */
    public function simpanMeta(): void
    {
        abort_unless($this->dapatKelola(), 403);

        $keys = KolomShotlist::query()->aktif()->whereNull('peran')->pluck('key');
        $meta = collect($this->metaEdit)->only($keys)->map(fn ($v) => trim((string) $v))->filter(fn ($v) => $v !== '')->all();

        $this->task()?->shot?->update(['meta' => $meta ?: null]);
        $this->dispatch('shot-tersimpan');
        $this->dispatch('toast', message: 'Referensi shotlist disimpan.');
    }

    /** Bantu tulis deskripsi shot dengan AI (Gemini). Hanya pengelola & bila key diisi. */
    public function isiDeskripsiShotAi(GeminiService $ai): void
    {
        abort_unless($this->dapatKelola(), 403);

        if (! $ai->aktif()) {
            return;
        }

        $task = $this->task();
        if (! $task?->shot) {
            return;
        }

        $kode = $task->shot->shot_code;
        $scene = $task->shot->adegan?->scene_name;
        $episode = $task->shot->adegan?->proyek?->name;
        $instruksi = 'Tuliskan deskripsi singkat (2-3 kalimat, Bahasa Indonesia, tanpa judul) '
            ."untuk sebuah shot animasi berkode \"{$kode}\" pada scene \"{$scene}\" di episode \"{$episode}\". "
            .'Jelaskan secara ringkas aksi/komposisi yang mungkin terjadi pada shot ini secara profesional.';

        try {
            $this->deskripsiShot = $ai->tulisDeskripsi($instruksi);
        } catch (Throwable $e) {
            $this->addError('deskripsiShot', 'Gagal memanggil AI: '.$e->getMessage());
        }
    }

    /** Setup (assign artis, jadwal, preview): Super Admin/Supervisor/Team Lead episode shot ini. */
    private function dapatKelola(): bool
    {
        $projectId = $this->task()?->shot?->adegan?->project_id;

        return (bool) ($projectId && Proyek::find($projectId)?->dapatDikelola(auth()->user()));
    }

    /** Boleh me-REVIEW (approve/reject): Supervisor & Team Lead (Super Admin read-only). */
    private function dapatReview(): bool
    {
        $projectId = $this->task()?->shot?->adegan?->project_id;

        return (bool) ($projectId && Proyek::find($projectId)?->dapatReview(auth()->user()));
    }

    /** Boleh mengerjakan (mulai/ajukan): artis yang ditugaskan atau reviewer, & episode sudah publish. */
    private function bisaKerja(): bool
    {
        $task = $this->task();
        $projectId = $task?->shot?->adegan?->project_id;
        $proyek = $projectId ? Proyek::find($projectId) : null;

        if (! $task || ! $proyek || ! $proyek->isPublished()) {
            return false;
        }

        return $task->artists->contains(auth()->id()) || $proyek->dapatReview(auth()->user());
    }

    /** Simpan penugasan artis pada tahap shot ini. */
    public function simpanArtis(): void
    {
        abort_unless($this->dapatKelola(), 403);

        $this->validate([
            'artisIds' => ['array'],
            'artisIds.*' => ['integer', 'exists:kf_pengguna,id'],
        ]);

        $task = $this->task();
        if (! $task) {
            return;
        }

        $sebelum = $task->artists->pluck('id')->all();
        $task->artists()->sync($this->artisIds);

        // Beri tahu artis yang baru ditugaskan (bila episode sudah dipublish).
        $projectId = $task->shot?->adegan?->project_id;
        $published = $projectId && Proyek::whereKey($projectId)->whereNotNull('published_at')->whereNull('closed_at')->exists();
        if ($published) {
            foreach (array_diff($this->artisIds, $sebelum) as $uid) {
                Notifikasi::kirim((int) $uid, "Ditugaskan: {$task->shot?->shot_code}", "Tahap {$task->tahap?->name} pada {$task->shot?->shot_code}.", route('shot-matrix'), 'publish');
            }
        }

        $this->dispatch('shot-tersimpan');
        $this->dispatch('toast', message: 'Penugasan artis disimpan.');
    }

    public function tutup(): void
    {
        $this->terbuka = false;
        $this->tugasShotId = null;
    }

    public function task(): ?TugasShot
    {
        return $this->tugasShotId
            ? TugasShot::with(['shot.adegan.proyek', 'shot.aset', 'artists', 'revisi.author', 'versi.author', 'catatanReview.author'])->find($this->tugasShotId)
            : null;
    }

    /** Peninjau/pelaku selalu pengguna yang login (bukan properti client-settable). */
    protected function actor(): ?User
    {
        return auth()->user();
    }

    public function simpanPreview(): void
    {
        $task = $this->task();

        // Hanya pemilik (artis ditugaskan) atau pengelola yang boleh memperbarui kiriman.
        // Pemilik tetap bisa memperbarui meski status REVIEW (menunggu ditinjau).
        abort_unless($this->bolehBuka($task), 403);

        $this->validate([
            'previewUrl' => ['nullable', 'url', 'max:2048'],
            'startDate' => ['nullable', 'date'],
            'deadline' => ['nullable', 'date', 'after_or_equal:startDate'],
            'estimasiHari' => ['nullable', 'integer', 'min:0', 'max:365'],
        ], attributes: ['startDate' => 'tanggal mulai', 'estimasiHari' => 'estimasi hari']);

        $task?->update([
            'preview_url' => $this->previewUrl ?: null,
            'post_date' => now(),
            'start_date' => $this->startDate ?: null,
            'deadline' => $this->deadline ?: null,
            'estimasi_hari' => $this->estimasiHari,
        ]);

        // Catat versi baru bila tautan preview berubah (riwayat deliverable).
        if ($task && $task->catatVersi($this->previewUrl, $this->versiCatatan, auth()->id())) {
            $this->versiCatatan = '';
            $this->dispatch('toast', message: 'Versi baru dicatat.');
        }

        $this->dispatch('shot-tersimpan');
    }

    public function ubahStatus(string $target, TransitionShotTaskStatus $transition): void
    {
        $task = $this->task();
        if (! $task) {
            return;
        }

        $tujuan = TaskStatus::from($target);

        // Guard peran & lifecycle (selaras tracker Pra/Pasca).
        if ($tujuan === TaskStatus::APPROVED) {
            if (! $this->dapatReview()) {
                $this->addError('workflow', 'Hanya supervisor/team lead yang dapat menyetujui.');

                return;
            }
        } elseif (in_array($tujuan, [TaskStatus::IN_PROGRESS, TaskStatus::REVIEW], true)) {
            if (! $this->bisaKerja()) {
                $this->addError('workflow', 'Episode belum dipublish, atau Anda bukan artis yang ditugaskan / pengelola.');

                return;
            }
        }

        try {
            $transition->handle(
                $task,
                $tujuan,
                $this->actor(),
                $this->catatan ?: null,
            );
            $this->notifikasiTransisi($task, $target);
            $this->catatan = '';
            $this->dispatch('shot-tersimpan');
        } catch (InvalidShotTaskTransition $e) {
            $this->addError('workflow', $e->getMessage());
        }
    }

    /** Notifikasi: propose (→REVIEW) ke pengelola; approve (→APPROVED) ke artis. */
    private function notifikasiTransisi(TugasShot $task, string $target): void
    {
        $task->loadMissing(['shot.adegan', 'artists', 'tahap']);
        $kode = $task->shot?->shot_code ?? 'Shot';
        $projectId = $task->shot?->adegan?->project_id;

        if ($target === TaskStatus::REVIEW->value && $projectId) {
            $ids = User::where('is_active', true)
                ->where('role', 'Supervisor')
                ->pluck('id')
                ->push(Proyek::whereKey($projectId)->value('team_lead_id'));

            Notifikasi::kirimBanyak($ids, "Menunggu review: {$kode}", "{$task->tahap?->name} diajukan untuk review.", route('shot-matrix'), 'propose');
        } elseif ($target === TaskStatus::APPROVED->value) {
            Notifikasi::kirimBanyak($task->artists->pluck('id'), "Disetujui: {$kode}", "{$task->tahap?->name} disetujui.", route('shot-matrix'), 'approve');

            // Notif downstream: tahap yang menjadikan tahap ini prasyarat kini bisa dimulai.
            $dependen = TugasShot::where('shot_id', $task->shot_id)
                ->whereHas('tahap', fn ($q) => $q->where('requires_tahap_id', $task->tahap_id))
                ->with(['artists:id', 'tahap:id,name'])->get();
            foreach ($dependen as $d) {
                Notifikasi::kirimBanyak($d->artists->pluck('id'), "Bisa dimulai: {$kode} · {$d->tahap?->name}", "Prasyarat {$task->tahap?->name} sudah disetujui.", route('shot-matrix'), 'info');
            }
        }
    }

    public function mintaRevisi(RecordShotRevision $record): void
    {
        if (! $this->dapatReview()) {
            $this->addError('workflow', 'Hanya supervisor/team lead yang dapat meminta revisi.');

            return;
        }

        $this->validate(['catatan' => ['required', 'string', 'min:3']], attributes: ['catatan' => 'catatan revisi']);

        $task = $this->task();
        if (! $task) {
            return;
        }

        $alasan = $this->catatan;
        $record->handle($task, $alasan, RevisionStatus::NEEDS_REVISION, $this->actor());

        // Kembalikan tugas ke pengerjaan bila sedang direview (transisi sah REVIEW -> IN_PROGRESS).
        if ($task->status === TaskStatus::REVIEW) {
            app(TransitionShotTaskStatus::class)->handle($task, TaskStatus::IN_PROGRESS, $this->actor(), 'Permintaan revisi');
        }

        // Notifikasi minta revisi ke artis yang ditugaskan.
        $task->loadMissing('artists');
        Notifikasi::kirimBanyak($task->artists->pluck('id'), "Perlu revisi: {$task->shot?->shot_code}", "Ditolak: {$alasan}", route('shot-matrix'), 'reject');

        $this->catatan = '';
        $this->dispatch('shot-tersimpan');
    }

    /** Tambah catatan review terstruktur (rentang frame opsional). Hanya peninjau. */
    public function tambahCatatanReview(): void
    {
        if (! $this->dapatReview()) {
            $this->addError('noteBody', 'Hanya supervisor/team lead yang dapat memberi catatan review.');

            return;
        }

        $v = $this->validate([
            'noteFrameStart' => ['nullable', 'integer', 'min:0'],
            'noteFrameEnd' => ['nullable', 'integer', 'min:0', 'gte:noteFrameStart'],
            'noteBody' => ['required', 'string', 'max:1000'],
        ], attributes: ['noteBody' => 'catatan']);

        $task = $this->task();
        if (! $task) {
            return;
        }

        $task->catatanReview()->create([
            'frame_start' => $v['noteFrameStart'],
            'frame_end' => $v['noteFrameEnd'],
            'body' => $v['noteBody'],
            'status' => 'open',
            'author_id' => auth()->id(),
        ]);

        $this->reset(['noteFrameStart', 'noteFrameEnd', 'noteBody']);
        $this->dispatch('shot-tersimpan');
    }

    /** Tandai catatan review selesai/terbuka. Peninjau atau artis yang ditugaskan. */
    public function toggleCatatanReview(int $id): void
    {
        $task = $this->task();
        $boleh = $this->dapatReview() || ($task && $task->artists->contains(auth()->id()));
        abort_unless($boleh, 403);

        $note = CatatanReview::where('shot_task_id', $this->tugasShotId)->find($id);
        $note?->update(['status' => $note->status === 'resolved' ? 'open' : 'resolved']);
        $this->dispatch('shot-tersimpan');
    }

    /** Status prasyarat tahap (dependensi pipeline) untuk task aktif. */
    private function prasyarat(): array
    {
        $task = $this->task();
        if (! $task?->tahap?->requires_tahap_id) {
            return ['ok' => true, 'nama' => null];
        }

        $task->loadMissing('tahap.prasyarat');
        $pre = TugasShot::where('shot_id', $task->shot_id)
            ->where('tahap_id', $task->tahap->requires_tahap_id)
            ->first();

        return [
            'ok' => (bool) ($pre && $pre->status === TaskStatus::APPROVED),
            'nama' => $task->tahap->prasyarat?->name ?? 'tahap sebelumnya',
        ];
    }

    public function render()
    {
        $prasyarat = $this->prasyarat();

        return view('livewire.review-panel', [
            'task' => $this->task(),
            'peninjau' => $this->actor(),
            'bisaKelola' => $this->dapatKelola(),
            'bisaReview' => $this->dapatReview(),
            'bisaKerja' => $this->bisaKerja(),
            'prereqOk' => $prasyarat['ok'],
            'prereqNama' => $prasyarat['nama'],
            'retake' => $this->task()?->jumlahRetake() ?? 0,
            'aiAktif' => app(GeminiService::class)->aktif(),
            'daftarArtis' => User::where('is_active', true)->orderBy('name')->get(['id', 'name', 'role']),
            'shotlistLabel' => KolomShotlist::pluck('label', 'key'),
            'metaKolom' => KolomShotlist::query()->aktif()->urut()->whereNull('peran')->get(['id', 'key', 'label', 'tipe', 'opsi']),
        ]);
    }
}
