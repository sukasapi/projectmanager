<?php

namespace Tests\Feature;

use App\Actions\CreateShot;
use App\Livewire\Pencarian;
use App\Models\Adegan;
use App\Models\Proyek;
use App\Models\User;
use Database\Seeders\TahapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PencarianTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TahapSeeder::class);
    }

    public function test_supervisor_menemukan_episode_dan_shot(): void
    {
        $sup = User::factory()->create(['role' => 'Supervisor']);
        $ep = Proyek::create(['name' => 'Petualangan Rubah']);
        $scene = Adegan::create(['project_id' => $ep->id, 'scene_name' => 'Scene Hutan']);
        app(CreateShot::class)->handle(['scene_id' => $scene->id, 'shot_code' => 'SC09_SH09', 'duration_seconds' => 40]);

        Livewire::actingAs($sup)->test(Pencarian::class)->set('q', 'Rubah')->assertSee('Petualangan Rubah');
        Livewire::actingAs($sup)->test(Pencarian::class)->set('q', 'SH09')->assertSee('SC09_SH09');
    }

    public function test_artis_tidak_melihat_episode_yang_bukan_miliknya(): void
    {
        $artis = User::factory()->create(['role' => 'Animator']);
        Proyek::create(['name' => 'Episode Rahasia']);

        Livewire::actingAs($artis)->test(Pencarian::class)
            ->set('q', 'Rahasia')
            ->assertDontSee('Episode Rahasia');
    }

    public function test_pencarian_artis_hanya_untuk_supervisor(): void
    {
        $target = User::factory()->create(['name' => 'Zulkarnain Spesial']);
        $artis = User::factory()->create(['role' => 'Animator']);
        $sup = User::factory()->create(['role' => 'Supervisor']);

        Livewire::actingAs($artis)->test(Pencarian::class)->set('q', 'Zulkarnain')->assertDontSee('Zulkarnain Spesial');
        Livewire::actingAs($sup)->test(Pencarian::class)->set('q', 'Zulkarnain')->assertSee('Zulkarnain Spesial');
    }

    public function test_kurang_dari_dua_karakter_tidak_mencari(): void
    {
        $sup = User::factory()->create(['role' => 'Supervisor']);
        Proyek::create(['name' => 'Aurora']);

        Livewire::actingAs($sup)->test(Pencarian::class)
            ->set('q', 'A')
            ->assertSee('minimal 2 karakter');
    }
}
