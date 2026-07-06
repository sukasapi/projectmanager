<?php

namespace Tests\Feature;

use App\Livewire\Aset\Manager;
use App\Livewire\Proyek\DaftarProyek;
use App\Livewire\Seri\Daftar;
use App\Models\Proyek;
use App\Models\Seri;
use App\Models\User;
use Database\Seeders\TahapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tier C6 — seri induk + reuse aset lintas-episode.
 */
class SeriTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TahapSeeder::class);
    }

    private function supervisor(): User
    {
        return User::factory()->create(['role' => 'Supervisor']);
    }

    public function test_supervisor_membuat_seri(): void
    {
        Livewire::actingAs($this->supervisor())->test(Daftar::class)
            ->call('create')->set('name', 'Petualangan Bima')
            ->call('save')->assertHasNoErrors();

        $this->assertDatabaseHas('kf_seri', ['name' => 'Petualangan Bima']);
    }

    public function test_artis_tidak_bisa_buka_seri(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'Artis']))->get('/seri')->assertForbidden();
    }

    public function test_episode_ditautkan_ke_seri(): void
    {
        $seri = Seri::create(['name' => 'Seri A']);

        Livewire::actingAs($this->supervisor())->test(DaftarProyek::class)
            ->call('create')->set('name', 'Ep Dalam Seri')->set('seriId', $seri->id)
            ->call('save')->assertHasNoErrors();

        $this->assertDatabaseHas('kf_proyek', ['name' => 'Ep Dalam Seri', 'series_id' => $seri->id]);
    }

    public function test_aset_bersama_terlihat_di_episode_lain_dalam_seri(): void
    {
        $sup = $this->supervisor();
        $seri = Seri::create(['name' => 'Seri Reuse']);
        $epA = Proyek::create(['name' => 'Ep A', 'series_id' => $seri->id, 'published_at' => now()]);
        $epB = Proyek::create(['name' => 'Ep B', 'series_id' => $seri->id, 'published_at' => now()]);

        // Buat aset BERSAMA di episode A.
        Livewire::actingAs($sup)->test(Manager::class)
            ->set('proyekId', $epA->id)
            ->call('create')
            ->set('name', 'BimaShared')->set('type', 'CHARACTER')->set('task', 'RIGGING')
            ->set('bersama', true)
            ->call('save')->assertHasNoErrors();

        $this->assertDatabaseHas('kf_aset', ['name' => 'BimaShared', 'series_id' => $seri->id]);

        // Episode B (dalam seri sama) melihat aset bersama itu.
        Livewire::actingAs($sup)->test(Manager::class)
            ->set('proyekId', $epB->id)
            ->assertSee('BimaShared')
            ->assertSee('bersama');
    }
}
