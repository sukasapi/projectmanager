<?php

namespace App\Actions;

use App\Enums\FaseProduksi;
use App\Enums\LevelTahap;
use App\Enums\RevisionStatus;
use App\Enums\TaskStatus;
use App\Models\Adegan;
use App\Models\Proyek;
use App\Models\Shot;
use App\Models\Tahap;
use Illuminate\Support\Facades\DB;

/**
 * Membuat Shot baru sekaligus sub-task untuk tiap tahap Produksi level-SHOT yang
 * aktif (configurable — default Animate, Simulate). total_duration scene ditangani
 * ShotObserver. Lihat PIPELINE.md.
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

            // Tahap dari snapshot pipeline EPISODE shot ini (bukan template global).
            $projectId = (int) Adegan::whereKey($data['scene_id'])->value('project_id');

            // Self-heal: pastikan episode punya snapshot pipeline sebelum membuat sub-task.
            Proyek::whereKey($projectId)->first()?->pastikanPipeline();

            $tahapShot = Tahap::milikEpisode($projectId)
                ->aktif()
                ->fase(FaseProduksi::PRODUKSI)
                ->where('level', LevelTahap::SHOT->value)
                ->urut()
                ->get();

            foreach ($tahapShot as $tahap) {
                $shot->tugasShot()->create([
                    'tahap_id' => $tahap->id,
                    'status' => TaskStatus::NOT_STARTED->value,
                    'revision_status' => RevisionStatus::NONE->value,
                ]);
            }

            return $shot;
        });
    }
}
