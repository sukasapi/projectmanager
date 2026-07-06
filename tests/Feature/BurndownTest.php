<?php

namespace Tests\Feature;

use App\Actions\CreateShot;
use App\Enums\TaskStatus;
use App\Livewire\Laporan\Progress;
use App\Models\Adegan;
use App\Models\Proyek;
use App\Models\TugasShot;
use App\Models\User;
use Database\Seeders\TahapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tier C4 — progress berbobot durasi + burndown/velocity + ekspor CSV.
 */
class BurndownTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TahapSeeder::class);
    }

    private function episodeSetengahSelesai(): Proyek
    {
        $ep = Proyek::create(['name' => 'Ep Bobot', 'published_at' => now()]);
        $scene = Adegan::create(['project_id' => $ep->id, 'scene_name' => 'S1']);
        $shot = app(CreateShot::class)->handle(['scene_id' => $scene->id, 'shot_code' => 'A1', 'duration_seconds' => 100]);
        // Setujui salah satu dari dua sub-task → 50% (per tugas & berbobot durasi sama).
        $task = TugasShot::where('shot_id', $shot->id)->firstOrFail();
        $task->update(['status' => TaskStatus::APPROVED->value]);

        return $ep;
    }

    public function test_laporan_menampilkan_bobot_durasi_dan_velocity(): void
    {
        $this->episodeSetengahSelesai();
        $sup = User::factory()->create(['role' => 'Supervisor']);

        Livewire::actingAs($sup)->test(Progress::class)
            ->assertSee('berbobot durasi')
            ->assertSee('Laju penyelesaian')
            ->assertSee('50%');
    }

    public function test_unduh_csv(): void
    {
        $this->episodeSetengahSelesai();
        $sup = User::factory()->create(['role' => 'Supervisor']);

        Livewire::actingAs($sup)->test(Progress::class)
            ->call('unduhCsv')
            ->assertFileDownloaded('laporan-progress.csv');
    }
}
