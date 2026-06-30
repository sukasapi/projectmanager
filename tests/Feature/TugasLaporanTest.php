<?php

namespace Tests\Feature;

use App\Actions\CreateShot;
use App\Enums\TaskStatus;
use App\Livewire\Laporan\Progress;
use App\Livewire\TugasSaya;
use App\Models\Adegan;
use App\Models\Proyek;
use App\Models\TugasShot;
use App\Models\User;
use Database\Seeders\TahapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TugasLaporanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TahapSeeder::class);
    }

    private function shotDi(Proyek $ep): TugasShot
    {
        $scene = Adegan::create(['project_id' => $ep->id, 'scene_name' => 'Scene 01']);
        $shot = app(CreateShot::class)->handle(['scene_id' => $scene->id, 'shot_code' => 'SC01_SH01', 'duration_seconds' => 50]);

        return TugasShot::where('shot_id', $shot->id)->whereHas('tahap', fn ($q) => $q->where('code', 'animate'))->first();
    }

    public function test_tugas_saya_menampilkan_shot_aktif_di_episode_publish(): void
    {
        $artis = User::factory()->create();
        $ep = Proyek::create(['name' => 'Cerita Saya', 'published_at' => now()]);
        $this->shotDi($ep)->artists()->attach($artis->id);

        Livewire::actingAs($artis)->test(TugasSaya::class)
            ->assertSee('Cerita Saya')
            ->assertSee('SC01_SH01');
    }

    public function test_tugas_saya_abaikan_episode_draft_dan_yang_approved(): void
    {
        $artis = User::factory()->create();

        // Draft (belum publish) → tidak tampil.
        $draft = Proyek::create(['name' => 'Episode Draft']);
        $this->shotDi($draft)->artists()->attach($artis->id);

        // Published tapi sudah APPROVED → tidak tampil.
        $done = Proyek::create(['name' => 'Episode Selesai', 'published_at' => now()]);
        $tugasDone = $this->shotDi($done);
        $tugasDone->artists()->attach($artis->id);
        $tugasDone->update(['status' => TaskStatus::APPROVED->value]);

        Livewire::actingAs($artis)->test(TugasSaya::class)
            ->assertDontSee('Episode Draft')
            ->assertDontSee('Episode Selesai')
            ->assertSee('Tidak ada tugas aktif');
    }

    public function test_laporan_progress_render_dan_hitung_persen(): void
    {
        $sup = User::factory()->create(['role' => 'Supervisor']);
        $ep = Proyek::create(['name' => 'Cerita Laporan', 'published_at' => now()]);
        $tugas = $this->shotDi($ep);
        $tugas->update(['status' => TaskStatus::APPROVED->value]);

        Livewire::actingAs($sup)->test(Progress::class)
            ->assertSee('Cerita Laporan')
            ->assertSee('Produksi')
            ->assertSee('%');
    }

    public function test_laporan_progress_menampilkan_overdue_dan_beban(): void
    {
        $sup = User::factory()->create(['role' => 'Supervisor']);
        $artis = User::factory()->create(['name' => 'Budi Animator']);
        $ep = Proyek::create(['name' => 'Cerita Telat', 'published_at' => now()]);
        $tugas = $this->shotDi($ep);
        $tugas->update(['deadline' => now()->subDays(3)->toDateString()]);
        $tugas->artists()->attach($artis->id);

        Livewire::actingAs($sup)->test(Progress::class)
            ->assertSee('Tugas lewat deadline')
            ->assertSee('SC01_SH01')
            ->assertSee('Budi Animator')
            ->assertSee('Beban kerja per artis');
    }
}
