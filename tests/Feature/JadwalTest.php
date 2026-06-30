<?php

namespace Tests\Feature;

use App\Actions\CreateShot;
use App\Livewire\Jadwal;
use App\Models\Adegan;
use App\Models\Proyek;
use App\Models\TugasShot;
use App\Models\User;
use Database\Seeders\TahapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class JadwalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TahapSeeder::class);
    }

    private function shotBerjadwal(Proyek $ep): void
    {
        $scene = Adegan::create(['project_id' => $ep->id, 'scene_name' => 'Scene 01']);
        $shot = app(CreateShot::class)->handle(['scene_id' => $scene->id, 'shot_code' => 'SC01_SH01', 'duration_seconds' => 50]);
        TugasShot::where('shot_id', $shot->id)->whereHas('tahap', fn ($q) => $q->where('code', 'animate'))->first()
            ->update(['start_date' => now()->toDateString(), 'deadline' => now()->addDays(5)->toDateString()]);
    }

    public function test_supervisor_melihat_timeline_dengan_bar(): void
    {
        $sup = User::factory()->create(['role' => 'Supervisor']);
        $ep = Proyek::create(['name' => 'Cerita Jadwal', 'published_at' => now()]);
        $this->shotBerjadwal($ep);

        Livewire::actingAs($sup)->test(Jadwal::class)
            ->call('pilihEpisode', $ep->id)
            ->assertSet('proyekId', $ep->id)
            ->assertSee('SC01_SH01');
    }

    public function test_team_lead_melihat_timeline_episodenya(): void
    {
        $lead = User::factory()->create(['role' => 'Animator']);
        $ep = Proyek::create(['name' => 'Cerita Lead', 'team_lead_id' => $lead->id, 'published_at' => now()]);
        $this->shotBerjadwal($ep);

        Livewire::actingAs($lead)->test(Jadwal::class)
            ->call('pilihEpisode', $ep->id)
            ->assertSet('proyekId', $ep->id);
    }

    public function test_artis_biasa_tidak_bisa_membuka_timeline_episode(): void
    {
        $artis = User::factory()->create(['role' => 'Animator']);
        $ep = Proyek::create(['name' => 'Bukan Punya Saya', 'published_at' => now()]);

        Livewire::actingAs($artis)->test(Jadwal::class)
            ->call('pilihEpisode', $ep->id)
            ->assertSet('proyekId', null);
    }
}
