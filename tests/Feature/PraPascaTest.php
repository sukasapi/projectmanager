<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Livewire\Produksi\PascaProduksi;
use App\Livewire\Produksi\PraProduksi;
use App\Models\Proyek;
use App\Models\Tahap;
use App\Models\TugasTahap;
use App\Models\User;
use Database\Seeders\TahapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PraPascaTest extends TestCase
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

    public function test_supervisor_membuka_episode_dan_melihat_tahap_pra(): void
    {
        $ep = Proyek::create(['name' => 'Episode Alpha']);

        Livewire::actingAs($this->supervisor())->test(PraProduksi::class)
            ->call('pilihEpisode', $ep->id)
            ->assertSet('proyekId', $ep->id)
            ->assertSee('Script')      // tahap Pra
            ->assertSee('Animatic');
    }

    public function test_pasca_menampilkan_tahap_pasca(): void
    {
        $ep = Proyek::create(['name' => 'Episode Alpha']);

        Livewire::actingAs($this->supervisor())->test(PascaProduksi::class)
            ->call('pilihEpisode', $ep->id)
            ->assertSee('SLRC')
            ->assertSee('Editing');
    }

    public function test_edit_membuat_dan_menyimpan_tugas_tahap(): void
    {
        $ep = Proyek::create(['name' => 'Episode Alpha']);
        $artis = User::factory()->create();
        $script = $ep->tahap()->where('phase', 'PRA')->where('code', 'script')->first();

        Livewire::actingAs($this->supervisor())->test(PraProduksi::class)
            ->call('pilihEpisode', $ep->id)
            ->call('edit', $script->id)        // firstOrCreate baris + buka modal
            ->set('artistId', $artis->id)
            ->set('deskripsi', 'Menyusun naskah')
            ->call('save')
            ->assertHasNoErrors();

        // Setup hanya assign artis & detail; status tetap NOT_STARTED (diubah lewat workflow).
        $this->assertDatabaseHas('kf_tugas_tahap', [
            'project_id' => $ep->id,
            'tahap_id' => $script->id,
            'artist_id' => $artis->id,
            'status' => TaskStatus::NOT_STARTED->value,
        ]);
    }

    public function test_non_supervisor_tidak_bisa_membuka_episode_tak_ditugaskan(): void
    {
        $ep = Proyek::create(['name' => 'Episode Alpha']);
        $user = User::factory()->create(['role' => 'Animator']);

        Livewire::actingAs($user)->test(PraProduksi::class)
            ->call('pilihEpisode', $ep->id)
            ->assertSet('proyekId', null); // ditolak (tidak ditugaskan)
    }

    public function test_episode_dengan_tugas_tahap_muncul_untuk_artis(): void
    {
        $ep = Proyek::create(['name' => 'Episode Beta']);
        $script = $ep->tahap()->where('phase', 'PRA')->where('code', 'script')->first();
        $user = User::factory()->create(['role' => 'Animator']);
        TugasTahap::create(['project_id' => $ep->id, 'tahap_id' => $script->id, 'artist_id' => $user->id, 'status' => TaskStatus::IN_PROGRESS->value]);

        Livewire::actingAs($user)->test(PraProduksi::class)
            ->assertSee('Episode Beta'); // muncul di pemilih karena ditugaskan
    }

    public function test_halaman_pra_dan_pasca_dapat_diakses(): void
    {
        $sup = $this->supervisor();
        $this->actingAs($sup)->get('/pra-produksi')->assertOk();
        $this->actingAs($sup)->get('/pasca-produksi')->assertOk();
    }
}
