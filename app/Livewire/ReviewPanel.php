<?php

namespace App\Livewire;

use App\Actions\RecordShotRevision;
use App\Actions\TransitionShotTaskStatus;
use App\Enums\RevisionStatus;
use App\Enums\TaskStatus;
use App\Exceptions\InvalidShotTaskTransition;
use App\Models\TugasShot;
use App\Models\User;
use Livewire\Attributes\On;
use Livewire\Component;

class ReviewPanel extends Component
{
    public ?int $tugasShotId = null;

    public bool $terbuka = false;

    /** Identitas peninjau (untuk mendemonstrasikan guard approval magang). */
    public ?int $actorId = null;

    public string $catatan = '';

    public string $previewUrl = '';

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
        $this->previewUrl = $this->task()?->preview_url ?? '';
        $this->catatan = '';
        $this->terbuka = true;
    }

    public function tutup(): void
    {
        $this->terbuka = false;
        $this->tugasShotId = null;
    }

    public function task(): ?TugasShot
    {
        return $this->tugasShotId
            ? TugasShot::with(['shot.adegan', 'artists', 'revisi.author'])->find($this->tugasShotId)
            : null;
    }

    protected function actor(): ?User
    {
        return $this->actorId ? User::find($this->actorId) : null;
    }

    public function simpanPreview(): void
    {
        $this->validate(['previewUrl' => ['nullable', 'url', 'max:2048']]);

        $task = $this->task();
        $task?->update(['preview_url' => $this->previewUrl ?: null, 'post_date' => now()]);

        $this->dispatch('shot-tersimpan');
    }

    public function ubahStatus(string $target, TransitionShotTaskStatus $transition): void
    {
        $task = $this->task();
        if (! $task) {
            return;
        }

        try {
            $transition->handle(
                $task,
                TaskStatus::from($target),
                $this->actor(),
                $this->catatan ?: null,
            );
            $this->catatan = '';
            $this->dispatch('shot-tersimpan');
        } catch (InvalidShotTaskTransition $e) {
            $this->addError('workflow', $e->getMessage());
        }
    }

    public function mintaRevisi(RecordShotRevision $record): void
    {
        $this->validate(['catatan' => ['required', 'string', 'min:3']], attributes: ['catatan' => 'catatan revisi']);

        $task = $this->task();
        if (! $task) {
            return;
        }

        $record->handle($task, $this->catatan, RevisionStatus::NEEDS_REVISION, $this->actor());

        // Kembalikan tugas ke pengerjaan bila sedang direview (transisi sah REVIEW -> IN_PROGRESS).
        if ($task->status === TaskStatus::REVIEW) {
            app(TransitionShotTaskStatus::class)->handle($task, TaskStatus::IN_PROGRESS, $this->actor(), 'Permintaan revisi');
        }

        $this->catatan = '';
        $this->dispatch('shot-tersimpan');
    }

    public function render()
    {
        return view('livewire.review-panel', [
            'task' => $this->task(),
            'peninjau' => $this->actor(),
        ]);
    }
}
