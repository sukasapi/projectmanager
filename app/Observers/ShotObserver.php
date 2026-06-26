<?php

namespace App\Observers;

use App\Models\Adegan;
use App\Models\Shot;
use Illuminate\Support\Facades\DB;

/**
 * Menjaga invariant: kf_adegan.total_duration = SUM(kf_shot.duration_seconds).
 * Dipicu otomatis pada setiap create/update/delete/restore Shot — sumber kebenaran
 * tunggal agar durasi scene tidak pernah stale (lihat CLAUDE.md & QA Agent).
 */
class ShotObserver
{
    public function created(Shot $shot): void
    {
        $this->recalculate($shot->scene_id);
    }

    public function updated(Shot $shot): void
    {
        $this->recalculate($shot->scene_id);

        // Bila shot dipindah ke scene lain, scene asal juga harus dihitung ulang.
        if ($shot->wasChanged('scene_id')) {
            $this->recalculate($shot->getOriginal('scene_id'));
        }
    }

    public function deleted(Shot $shot): void
    {
        $this->recalculate($shot->scene_id);
    }

    public function restored(Shot $shot): void
    {
        $this->recalculate($shot->scene_id);
    }

    /**
     * Hitung ulang total durasi sebuah scene dari seluruh shot-nya (transaksional).
     */
    protected function recalculate(?int $sceneId): void
    {
        if ($sceneId === null) {
            return;
        }

        DB::transaction(function () use ($sceneId) {
            $total = (int) Shot::where('scene_id', $sceneId)->sum('duration_seconds');

            Adegan::whereKey($sceneId)->update(['total_duration' => $total]);
        });
    }
}
