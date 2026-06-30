<?php

namespace Database\Seeders;

use App\Actions\CreateShot;
use App\Enums\FaseProduksi;
use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Models\Adegan;
use App\Models\Proyek;
use App\Models\Tahap;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Data demo tambahan: Episode "Cerita 21" & "Cerita 27", masing-masing 5 scene &
 * 31 shot (distribusi 7-6-6-6-6). Tiap shot otomatis memperoleh sub-task tahap
 * Produksi (Animate, Simulate) via CreateShot. Idempotent (skip bila sudah ada).
 *
 * Jalankan: php artisan db:seed --class=EpisodeDemoSeeder
 */
class EpisodeDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan template global tahap tersedia (snapshot dibuat saat episode dibuat).
        if (Tahap::global()->aktif()->fase(FaseProduksi::PRODUKSI)->where('level', 'SHOT')->doesntExist()) {
            $this->call(TahapSeeder::class);
        }

        $createShot = app(CreateShot::class);
        $distribusi = [7, 6, 6, 6, 6]; // total 31 shot per episode
        $artis = User::where('is_active', true)->pluck('id')->all();

        foreach (['Cerita 21', 'Cerita 27'] as $nama) {
            if (Proyek::where('name', $nama)->exists()) {
                $this->command?->warn("{$nama} sudah ada — dilewati.");

                continue;
            }

            $proyek = Proyek::create([
                'name' => $nama,
                'description' => "Episode demo {$nama} — 5 scene, 31 shot.",
                'status' => ProjectStatus::IN_PROGRESS->value,
                'team_lead_id' => $artis[0] ?? null,
                'published_at' => now()->subDays(7), // sudah dipublish (sedang dikerjakan)
            ]);

            foreach ($distribusi as $i => $jumlahShot) {
                $no = $i + 1;
                $scene = Adegan::create([
                    'project_id' => $proyek->id,
                    'scene_name' => 'Scene '.str_pad((string) $no, 2, '0', STR_PAD_LEFT),
                ]);

                for ($s = 1; $s <= $jumlahShot; $s++) {
                    $shot = $createShot->handle([
                        'scene_id' => $scene->id,
                        'shot_code' => sprintf('SC%02d_SH%02d', $no, $s),
                        'duration_seconds' => rand(48, 240),
                    ]);

                    // Variasi status & penugasan agar tracker terlihat hidup.
                    if ($artis !== [] && rand(1, 100) <= 60) {
                        $animate = $shot->tugasShot()->whereHas('tahap', fn ($q) => $q->where('code', 'animate'))->first();
                        $animate?->update(['status' => rand(0, 1) ? TaskStatus::IN_PROGRESS->value : TaskStatus::REVIEW->value]);
                        $animate?->artists()->sync([$artis[array_rand($artis)]]);
                    }
                }
            }

            $totalDurasi = $proyek->adegan()->sum('total_duration');
            $this->command?->info("{$nama}: 5 scene, 31 shot, total durasi {$totalDurasi}s.");
        }
    }
}
