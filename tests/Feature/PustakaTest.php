<?php

namespace Tests\Feature;

use App\Actions\CreateShot;
use App\Livewire\Pustaka;
use App\Models\Adegan;
use App\Models\Proyek;
use App\Models\Tahap;
use App\Models\TugasShot;
use App\Models\TugasTahap;
use App\Models\User;
use Database\Seeders\TahapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PustakaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TahapSeeder::class);
    }

    public function test_pustaka_menampilkan_tautan_file_lintas_proses(): void
    {
        $sup = User::factory()->create(['role' => 'Supervisor']);
        $ep = Proyek::create(['name' => 'Cerita Pustaka']);
        $scene = Adegan::create(['project_id' => $ep->id, 'scene_name' => 'Scene 01']);
        $shot = app(CreateShot::class)->handle(['scene_id' => $scene->id, 'shot_code' => 'SC01_SH01', 'duration_seconds' => 50]);

        // Link Produksi (preview shot-task).
        TugasShot::where('shot_id', $shot->id)->first()->update(['preview_url' => 'https://drive.google.com/animate.mp4']);

        // Link Pra-Produksi (file_url tahap level-episode).
        $tahapPra = Tahap::milikEpisode($ep->id)->where('phase', 'PRA')->first();
        TugasTahap::create(['project_id' => $ep->id, 'tahap_id' => $tahapPra->id, 'status' => 'NOT_STARTED', 'file_url' => 'https://drive.google.com/script.pdf']);

        Livewire::actingAs($sup)->test(Pustaka::class)
            ->assertSee('Cerita Pustaka')
            ->assertSee('Produksi')
            ->assertSee('SC01_SH01')
            ->assertSee('Pra-Produksi')
            ->assertSee('https://drive.google.com/animate.mp4')
            ->assertSee('https://drive.google.com/script.pdf');
    }

    public function test_artis_hanya_melihat_episode_yang_ditugaskan(): void
    {
        $artis = User::factory()->create(['role' => 'Animator']);

        // Episode tanpa keterlibatan artis, punya link.
        $lain = Proyek::create(['name' => 'Episode Orang Lain']);
        $sceneLain = Adegan::create(['project_id' => $lain->id, 'scene_name' => 'Scene 01']);
        $shotLain = app(CreateShot::class)->handle(['scene_id' => $sceneLain->id, 'shot_code' => 'SC01_SH01', 'duration_seconds' => 50]);
        TugasShot::where('shot_id', $shotLain->id)->first()->update(['preview_url' => 'https://drive.google.com/lain.mp4']);

        // Episode yang melibatkan artis.
        $milik = Proyek::create(['name' => 'Episode Saya']);
        $sceneMilik = Adegan::create(['project_id' => $milik->id, 'scene_name' => 'Scene 01']);
        $shotMilik = app(CreateShot::class)->handle(['scene_id' => $sceneMilik->id, 'shot_code' => 'SC01_SH01', 'duration_seconds' => 50]);
        $tugas = TugasShot::where('shot_id', $shotMilik->id)->first();
        $tugas->update(['preview_url' => 'https://drive.google.com/milik.mp4']);
        $tugas->artists()->attach($artis->id);

        Livewire::actingAs($artis)->test(Pustaka::class)
            ->assertSee('Episode Saya')
            ->assertDontSee('Episode Orang Lain');
    }
}
