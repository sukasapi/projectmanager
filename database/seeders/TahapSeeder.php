<?php

namespace Database\Seeders;

use App\Enums\FaseProduksi;
use App\Enums\LevelTahap;
use App\Models\Tahap;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Tahap produksi default sesuai proses nyata studio animasi (permintaan #7).
 * Idempotent (firstOrCreate) — aman dijalankan ulang saat deploy. Lihat PIPELINE.md §4.
 */
class TahapSeeder extends Seeder
{
    public function run(): void
    {
        // Pra-produksi (level EPISODE).
        $pra = [
            'Script', 'Shotlist', 'STB (Storyboard)', 'Animatic', 'VO/VG',
            'Standar Produksi & Referensi', 'Desain Karakter', 'Mapping',
            'Desain Environment', 'Layout Animation Test', 'Perancangan SLRC', 'Scoring',
        ];
        foreach ($pra as $i => $name) {
            $this->tahap(FaseProduksi::PRA, LevelTahap::EPISODE, $name, $i + 1);
        }

        // Produksi (level SHOT) — kolom matriks shot.
        $animate = $this->tahap(FaseProduksi::PRODUKSI, LevelTahap::SHOT, 'Animate', 1);
        $this->tahap(FaseProduksi::PRODUKSI, LevelTahap::SHOT, 'Simulate', 2, $animate->id);

        // Pasca-produksi (level EPISODE).
        $this->tahap(FaseProduksi::PASCA, LevelTahap::EPISODE, 'SLRC', 1);
        $this->tahap(FaseProduksi::PASCA, LevelTahap::EPISODE, 'Editing', 2);
    }

    private function tahap(FaseProduksi $fase, LevelTahap $level, string $name, int $urutan, ?int $requires = null): Tahap
    {
        return Tahap::firstOrCreate(
            ['project_id' => null, 'phase' => $fase->value, 'code' => Str::slug($name)],
            [
                'level' => $level->value,
                'name' => $name,
                'urutan' => $urutan,
                'requires_tahap_id' => $requires,
                'is_active' => true,
            ]
        );
    }
}
