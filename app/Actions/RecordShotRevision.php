<?php

namespace App\Actions;

use App\Enums\RevisionStatus;
use App\Models\RevisiShot;
use App\Models\TugasShot;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Mencatat satu entri revisi (append-only) dan memperbarui status revisi terkini.
 * Riwayat lama tidak ditimpa — tersimpan di kf_revisi_shot.
 */
class RecordShotRevision
{
    public function handle(
        TugasShot $task,
        string $note,
        RevisionStatus $revisionStatus,
        ?User $actor = null,
        string $kind = 'REVISION_REQUEST',
    ): RevisiShot {
        return DB::transaction(function () use ($task, $note, $revisionStatus, $actor, $kind) {
            $entry = RevisiShot::create([
                'shot_task_id' => $task->id,
                'author_id' => $actor?->id,
                'kind' => $kind,
                'note' => $note,
                'status_from' => $task->status->value,
                'status_to' => $task->status->value,
            ]);

            // Snapshot ringkas untuk tampilan cepat di matriks (riwayat tetap di kf_revisi_shot).
            $task->update([
                'revision_status' => $revisionStatus->value,
                'revision_notes' => $note,
            ]);

            return $entry;
        });
    }
}
