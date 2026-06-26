<?php

namespace App\Actions;

use App\Enums\RevisionStatus;
use App\Enums\ShotTaskType;
use App\Enums\TaskStatus;
use App\Models\Shot;
use Illuminate\Support\Facades\DB;

/**
 * Membuat Shot baru sekaligus 4 sub-pipeline (Layout, Animate, Simulate, LRC)
 * dalam satu transaksi. Kalkulasi total_duration scene ditangani ShotObserver.
 */
class CreateShot
{
    /**
     * @param  array{scene_id:int, shot_code:string, duration_seconds:int, notes?:string|null}  $data
     */
    public function handle(array $data): Shot
    {
        return DB::transaction(function () use ($data) {
            $shot = Shot::create($data);

            foreach (ShotTaskType::cases() as $type) {
                $shot->tugasShot()->create([
                    'task_type' => $type->value,
                    'status' => TaskStatus::NOT_STARTED->value,
                    'revision_status' => RevisionStatus::NONE->value,
                ]);
            }

            return $shot;
        });
    }
}
