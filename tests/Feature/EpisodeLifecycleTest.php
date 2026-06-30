<?php

namespace Tests\Feature;

use App\Actions\CreateShot;
use App\Livewire\Proyek\DaftarProyek;
use App\Livewire\ShotMatrix;
use App\Models\Adegan;
use App\Models\Proyek;
use App\Models\User;
use Database\Seeders\TahapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EpisodeLifecycleTest extends TestCase
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

    public function test_supervisor_menugaskan_team_lead(): void
    {
        $lead = User::factory()->create();

        Livewire::actingAs($this->supervisor())->test(DaftarProyek::class)
            ->call('create')
            ->set('name', 'Episode Baru')
            ->set('teamLeadId', $lead->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('kf_proyek', ['name' => 'Episode Baru', 'team_lead_id' => $lead->id]);
    }

    public function test_publish_memberi_notifikasi_ke_artis_yang_diassign(): void
    {
        $sup = $this->supervisor();
        $artis = User::factory()->create();

        // Episode draft dengan satu shot, artis ditugaskan ke Animate.
        $ep = Proyek::create(['name' => 'Cerita X']);
        $scene = Adegan::create(['project_id' => $ep->id, 'scene_name' => 'S1']);
        $shot = app(CreateShot::class)->handle(['scene_id' => $scene->id, 'shot_code' => 'A1', 'duration_seconds' => 50]);
        $shot->tugasShot()->first()->artists()->attach($artis->id);

        $this->assertTrue($ep->isDraft());

        Livewire::actingAs($sup)->test(DaftarProyek::class)->call('publish', $ep->id);

        $ep->refresh();
        $this->assertTrue($ep->isPublished());
        $this->assertDatabaseHas('kf_notifikasi', ['user_id' => $artis->id, 'type' => 'publish']);
    }

    public function test_team_lead_boleh_publish_dan_tutup(): void
    {
        $lead = User::factory()->create(['role' => 'Animator']); // bukan supervisor
        $ep = Proyek::create(['name' => 'Cerita Y', 'team_lead_id' => $lead->id]);

        Livewire::actingAs($lead)->test(DaftarProyek::class)->call('publish', $ep->id);
        $this->assertTrue($ep->refresh()->isPublished());

        Livewire::actingAs($lead)->test(DaftarProyek::class)->call('tutup', $ep->id);
        $this->assertTrue($ep->refresh()->isClosed());
    }

    public function test_non_pengelola_tidak_bisa_publish(): void
    {
        $ep = Proyek::create(['name' => 'Cerita Z']);
        $orang = User::factory()->create(['role' => 'Animator']);

        Livewire::actingAs($orang)->test(DaftarProyek::class)
            ->call('publish', $ep->id)
            ->assertForbidden();

        $this->assertTrue($ep->refresh()->isDraft());
    }

    public function test_episode_closed_read_only_tidak_bisa_tambah_scene(): void
    {
        $sup = $this->supervisor();
        $ep = Proyek::create(['name' => 'Cerita Tutup', 'published_at' => now(), 'closed_at' => now()]);

        Livewire::actingAs($sup)->test(ShotMatrix::class)
            ->set('proyekId', $ep->id)
            ->call('addScene')
            ->assertForbidden();

        $this->assertSame(0, $ep->adegan()->count());
    }

    public function test_supervisor_buka_kembali_episode_closed(): void
    {
        $sup = $this->supervisor();
        $ep = Proyek::create(['name' => 'Cerita Tutup', 'published_at' => now(), 'closed_at' => now()]);
        $this->assertTrue($ep->isClosed());

        Livewire::actingAs($sup)->test(DaftarProyek::class)
            ->call('bukaKembali', $ep->id)
            ->assertHasNoErrors();

        $this->assertFalse($ep->refresh()->isClosed());
    }

    public function test_team_lead_tidak_bisa_buka_kembali_episode(): void
    {
        $lead = User::factory()->create(['role' => 'Animator']);
        $ep = Proyek::create(['name' => 'Cerita Tutup', 'team_lead_id' => $lead->id, 'published_at' => now(), 'closed_at' => now()]);

        Livewire::actingAs($lead)->test(DaftarProyek::class)
            ->call('bukaKembali', $ep->id)
            ->assertForbidden();

        $this->assertTrue($ep->refresh()->isClosed());
    }
}
