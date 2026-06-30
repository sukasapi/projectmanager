<?php

namespace Tests\Feature;

use App\Actions\CreateShot;
use App\Enums\FaseProduksi;
use App\Enums\LevelTahap;
use App\Livewire\Pengaturan\Pipeline;
use App\Models\Adegan;
use App\Models\Proyek;
use App\Models\Tahap;
use App\Models\User;
use Database\Seeders\TahapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TahapTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'Super Admin']);
    }

    public function test_seeder_membuat_tahap_default_proses_nyata(): void
    {
        $this->seed(TahapSeeder::class);

        // Produksi/shot = Animate + Simulate (proses #7).
        $shot = Tahap::fase(FaseProduksi::PRODUKSI)->where('level', LevelTahap::SHOT->value)->urut()->get();
        $this->assertSame(['Animate', 'Simulate'], $shot->pluck('name')->all());

        // Simulate berprasyarat Animate.
        $simulate = $shot->firstWhere('name', 'Simulate');
        $this->assertSame('Animate', $simulate->prasyarat->name);

        $this->assertSame(12, Tahap::fase(FaseProduksi::PRA)->count());
        $this->assertSame(2, Tahap::fase(FaseProduksi::PASCA)->count());
    }

    public function test_seeder_idempotent(): void
    {
        $this->seed(TahapSeeder::class);
        $this->seed(TahapSeeder::class);

        $this->assertSame(16, Tahap::count()); // 12 + 2 + 2, tidak dobel
    }

    public function test_admin_menambah_tahap(): void
    {
        Livewire::actingAs($this->admin())->test(Pipeline::class)
            ->set('phase', FaseProduksi::PRODUKSI->value)
            ->set('level', LevelTahap::SHOT->value)
            ->set('name', 'Crowd Sim')
            ->set('urutan', 3)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('kf_tahap', ['name' => 'Crowd Sim', 'code' => 'crowd-sim', 'phase' => 'PRODUKSI']);
    }

    public function test_tahap_duplikat_ditolak(): void
    {
        Tahap::create(['phase' => 'PRA', 'level' => 'EPISODE', 'code' => 'script', 'name' => 'Script', 'urutan' => 1]);

        Livewire::actingAs($this->admin())->test(Pipeline::class)
            ->set('phase', FaseProduksi::PRA->value)
            ->set('name', 'Script')
            ->call('save')
            ->assertHasErrors('name');
    }

    public function test_non_admin_dilarang(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'Animator']))
            ->get('/pengaturan/pipeline')->assertForbidden();
    }

    public function test_supervisor_biasa_tidak_bisa_konfigurasi_pipeline(): void
    {
        // Supervisor (bukan Super Admin) tak boleh konfigurasi aplikasi.
        $this->actingAs(User::factory()->create(['role' => 'Supervisor']))
            ->get('/pengaturan/pipeline')->assertForbidden();
    }

    public function test_perubahan_template_tidak_mengubah_episode_lama_hanya_episode_baru(): void
    {
        $this->seed(TahapSeeder::class);

        // Episode lama → snapshot Animate + Simulate; shot punya 2 tahap.
        $lama = Proyek::create(['name' => 'Episode Lama']);
        $sceneL = Adegan::create(['project_id' => $lama->id, 'scene_name' => 'S1']);
        $shotL = app(CreateShot::class)->handle(['scene_id' => $sceneL->id, 'shot_code' => 'A1', 'duration_seconds' => 50]);
        $this->assertSame(2, $shotL->tugasShot()->count());

        // Super Admin menambah tahap global "Rigging" ke template.
        Livewire::actingAs($this->admin())->test(Pipeline::class)
            ->set('phase', FaseProduksi::PRODUKSI->value)
            ->set('level', LevelTahap::SHOT->value)
            ->set('name', 'Rigging')
            ->call('save')
            ->assertHasNoErrors();

        // Episode LAMA tidak berubah (snapshot beku): shot tetap 2, snapshot tanpa Rigging.
        $this->assertSame(2, $shotL->fresh()->tugasShot()->count());
        $this->assertFalse($lama->tahap()->where('code', 'rigging')->exists());

        // Episode BARU mengikuti template terbaru → snapshot punya Rigging.
        $baru = Proyek::create(['name' => 'Episode Baru']);
        $this->assertTrue($baru->tahap()->where('code', 'rigging')->exists());

        $sceneB = Adegan::create(['project_id' => $baru->id, 'scene_name' => 'S1']);
        $shotB = app(CreateShot::class)->handle(['scene_id' => $sceneB->id, 'shot_code' => 'B1', 'duration_seconds' => 50]);
        $this->assertSame(3, $shotB->tugasShot()->count()); // Animate, Simulate, Rigging
    }
}
