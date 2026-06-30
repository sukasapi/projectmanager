<?php

namespace App\Observers;

use App\Actions\SnapshotPipeline;
use App\Models\Proyek;

/**
 * Saat episode dibuat, bekukan template pipeline global ke episode (snapshot).
 * Episode selalu memakai pipeline-nya sendiri sejak lahir. Lihat WORKFLOW.md.
 */
class ProyekObserver
{
    public function created(Proyek $proyek): void
    {
        app(SnapshotPipeline::class)->untukEpisode($proyek);
    }
}
