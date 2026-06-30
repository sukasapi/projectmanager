<?php

namespace App\Livewire;

use App\Actions\RecordShotRevision;
use App\Actions\TransitionShotTaskStatus;
use App\Enums\RevisionStatus;
use App\Enums\TaskStatus;
use App\Exceptions\InvalidShotTaskTransition;
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

    /** Identitas peninjau (untuk mendemonstrasikan guard approval magang). */
    public ?int $actorId = null;

    public string $catatan = '';

    public string $previewUrl = '';

    public ?string $startDate = null;

    public ?string $deadline = null;

    /** Penugasan artis (many-to-many: satu tahap bisa banyak artis). */
    public array $artisIds = [];

    /** Deskripsi shot (level Shot, dipakai bersama semua tahap shot itu). */
    public string $deskripsiShot = '';

    /** Catatan opsional untuk versi deliverable yang baru disimpan. */
    public string $versiCatatan = '';

    public function mount(): void
    {
        // Identitas peninjau = pengguna yang sedang login.
        $this->actorId ??= auth()->id();
    }

    #[On('buka-review')]
    public function buka(int $tugasShotId): void
    {
        $this->resetErrorBag();
        $this->tugasShotId = $tugasShotId;
        $task = $this->task();
        $this->previewUrl = $task?->preview_url ?? '';
        $this->startDate = $task?->start_date?->toDateString();
        $this->deadline = $task?->deadline?->toDateString();
        $this->artisIds = $task?->artists->pluck('id')->map(fn ($i) => (string) $i)->all() ?? [];
        $this->deskripsiShot = $task?->shot?->description ?? '';
        $this->catatan = '';
        $this->terbuka = true;
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
            ? TugasShot::with(['shot.adegan.proyek', 'artists', 'revisi.author', 'versi.author'])->find($this->tugasShotId)
            : null;
    }

    protected function actor(): ?User
    {
        return $this->actorId ? User::find($this->actorId) : null;
    }

    public function simpanPreview(): void
    {
        $this->validate([
            'previewUrl' => ['nullable', 'url', 'max:2048'],
            'startDate' => ['nullable', 'date'],
            'deadline' => ['nullable', 'date', 'after_or_equal:startDate'],
        ], attributes: ['startDate' => 'tanggal mulai']);

        $task = $this->task();
        $task?->update([
            'preview_url' => $this->previewUrl ?: null,
            'post_date' => now(),
            'start_date' => $this->startDate ?: null,
            'deadline' => $this->deadline ?: null,
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
        $task->loadMissing(['shot.adegan', 'artists']);
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

    public function render()
    {
        return view('livewire.review-panel', [
            'task' => $this->task(),
            'peninjau' => $this->actor(),
            'bisaKelola' => $this->dapatKelola(),
            'bisaReview' => $this->dapatReview(),
            'bisaKerja' => $this->bisaKerja(),
            'aiAktif' => app(GeminiService::class)->aktif(),
            'daftarArtis' => User::where('is_active', true)->orderBy('name')->get(['id', 'name', 'role']),
        ]);
    }
}
