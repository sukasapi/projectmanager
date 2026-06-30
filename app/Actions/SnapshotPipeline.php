<?php

namespace App\Actions;

use App\Models\Proyek;
use App\Models\Tahap;
use Illuminate\Support\Facades\DB;

/**
 * Membekukan (snapshot) template pipeline global ke sebuah episode saat episode
 * dibuat. Setelah ini, perubahan template global TIDAK memengaruhi episode ini —
 * hanya episode baru yang mengikuti template terbaru. Lihat WORKFLOW.md.
 */
class SnapshotPipeline
{
    public function untukEpisode(Proyek $proyek): void
    {
        // Sudah punya snapshot → jangan duplikat.
        if (Tahap::milikEpisode($proyek->id)->exists()) {
            return;
        }

        $global = Tahap::global()->aktif()->orderBy('id')->get();
        if ($global->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($proyek, $global) {
            $map = []; // id template global => id snapshot episode

            foreach ($global as $g) {
                $snap = Tahap::create([
                    'project_id' => $proyek->id,
                    'phase' => $g->phase->value,
                    'level' => $g->level->value,
                    'code' => $g->code,
                    'name' => $g->name,
                    'urutan' => $g->urutan,
                    'requires_tahap_id' => null, // dipetakan di pass kedua
                    'is_active' => true,
                    'description' => $g->description,
                ]);
                $map[$g->id] = $snap->id;
            }

            foreach ($global as $g) {
                if ($g->requires_tahap_id && isset($map[$g->requires_tahap_id])) {
                    Tahap::whereKey($map[$g->id])->update(['requires_tahap_id' => $map[$g->requires_tahap_id]]);
                }
            }
        });
    }
}
