<?php

namespace App\Actions;

use App\Enums\EmploymentType;
use App\Enums\RevisionStatus;
use App\Enums\TaskStatus;
use App\Exceptions\InvalidShotTaskTransition;
use App\Models\RevisiShot;
use App\Models\TugasShot;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * State machine perpindahan status ShotTask (QA & Workflow Automation Agent).
 *
 * Aturan yang dijaga:
 *  1. Transisi harus sah menurut TaskStatus::allowedTransitions().
 *  2. Dependensi tahap: sebuah tahap tidak boleh dimulai sebelum tahap
 *     sebelumnya pada shot yang sama berstatus APPROVED
 *     (mis. SIMULATE tak bisa mulai jika ANIMATE belum disetujui).
 *  3. Hak akses: pengguna magang (INTERN) tidak boleh menyetujui (APPROVED).
 */
class TransitionShotTaskStatus
{
    public function handle(TugasShot $task, TaskStatus $target, ?User $actor = null, ?string $note = null): TugasShot
    {
        $current = $task->status;

        // (1) Transisi sah?
        if (! $current->canTransitionTo($target)) {
            throw new InvalidShotTaskTransition(
                "Transisi status \"{$current->label()}\" → \"{$target->label()}\" tidak diizinkan."
            );
        }

        // (2) Dependensi tahap: untuk MEMULAI tahap ini, tahap sebelumnya harus APPROVED.
        if ($current === TaskStatus::NOT_STARTED && $target === TaskStatus::IN_PROGRESS) {
            $this->assertPreviousStageApproved($task);
        }

        // (3) Guard approval untuk magang.
        if ($target === TaskStatus::APPROVED && $actor && $actor->employment_type === EmploymentType::INTERN) {
            throw new InvalidShotTaskTransition('Pengguna magang tidak diizinkan menyetujui tugas.');
        }

        return DB::transaction(function () use ($task, $current, $target, $actor, $note) {
            $perubahan = ['status' => $target->value];

            // Saat disetujui, bersihkan penanda revisi agar tidak ada badge "perlu revisi" basi.
            if ($target === TaskStatus::APPROVED) {
                $perubahan['revision_status'] = RevisionStatus::OK->value;
            }

            $task->update($perubahan);

            RevisiShot::create([
                'shot_task_id' => $task->id,
                'author_id' => $actor?->id,
                'kind' => $target === TaskStatus::APPROVED ? 'APPROVAL' : 'NOTE',
                'note' => $note,
                'status_from' => $current->value,
                'status_to' => $target->value,
            ]);

            return $task->refresh();
        });
    }

    /**
     * Pastikan tahap prasyarat (pada shot yang sama) sudah APPROVED.
     * Dependensi kini berbasis data: kf_tahap.requires_tahap_id.
     */
    protected function assertPreviousStageApproved(TugasShot $task): void
    {
        $task->loadMissing('tahap.prasyarat');
        $prasyarat = $task->tahap?->prasyarat;

        if ($prasyarat === null) {
            return; // Tahap pertama (tanpa prasyarat) boleh langsung dimulai.
        }

        $previous = TugasShot::where('shot_id', $task->shot_id)
            ->where('tahap_id', $prasyarat->id)
            ->first();

        if (! $previous || $previous->status !== TaskStatus::APPROVED) {
            throw new InvalidShotTaskTransition(
                "Tahap \"{$prasyarat->name}\" harus disetujui dulu sebelum memulai \"{$task->tahap->name}\"."
            );
        }
    }
}
