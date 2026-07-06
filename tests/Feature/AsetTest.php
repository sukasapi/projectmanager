<?php

namespace Tests\Feature;

use App\Enums\EmploymentType;
use App\Enums\TaskStatus;
use App\Livewire\Aset\Manager;
use App\Models\Adegan;
use App\Models\Aset;
use App\Models\Proyek;
use App\Models\Shot;
use App\Models\User;
use Database\Seeders\TahapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tier C1 — manajemen aset + breakdown aset↔shot.
 */
class AsetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TahapSeeder::class);
    }

    /** @return array{Proyek, Shot} */
    private function episodeDenganShot(): array
    {
        $ep = Proyek::create(['name' => 'Ep Aset', 'published_at' => now()]);
        $scene = Adegan::create(['project_id' => $ep->id, 'scene_name' => 'S1']);
        $shot = Shot::create(['scene_id' => $scene->id, 'shot_code' => 'SC01_SH01', 'duration_seconds' => 40]);

        return [$ep, $shot];
    }

    public function test_supervisor_membuat_aset_dengan_breakdown(): void
    {
        [$ep, $shot] = $this->episodeDenganShot();
        $sup = User::factory()->create(['role' => 'Supervisor']);

        Livewire::actingAs($sup)->test(Manager::class)
            ->set('proyekId', $ep->id)
            ->call('create')
            ->set('name', 'Bima')
            ->set('type', 'CHARACTER')
            ->set('task', 'RIGGING')
            ->set('status', 'IN_PROGRESS')
            ->set('shotIds', [$shot->id])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('kf_aset', ['project_id' => $ep->id, 'name' => 'Bima', 'type' => 'CHARACTER']);
        $aset = Aset::where('name', 'Bima')->first();
        $this->assertDatabaseHas('kf_aset_shot', ['aset_id' => $aset->id, 'shot_id' => $shot->id]);
        $this->assertTrue($shot->fresh()->aset->contains($aset->id));
    }

    public function test_artis_biasa_tidak_bisa_kelola_aset(): void
    {
        [$ep] = $this->episodeDenganShot();
        $artis = User::factory()->create(['role' => 'Artis']);

        Livewire::actingAs($artis)->test(Manager::class)
            ->set('proyekId', $ep->id)
            ->call('create')
            ->assertForbidden();
    }

    public function test_magang_tidak_bisa_menyetujui_aset(): void
    {
        [$ep] = $this->episodeDenganShot();
        // Magang yang juga team lead episode (agar lolos dapatKelola) tetap tak boleh APPROVED.
        $magang = User::factory()->create(['role' => 'Team Lead', 'employment_type' => EmploymentType::INTERN->value]);
        $ep->update(['team_lead_id' => $magang->id]);

        Livewire::actingAs($magang)->test(Manager::class)
            ->set('proyekId', $ep->id)
            ->call('create')
            ->set('name', 'Env')
            ->set('type', 'ENVIRONMENT')
            ->set('task', 'MODELING')
            ->set('status', TaskStatus::APPROVED->value)
            ->call('save')
            ->assertHasErrors('status');

        $this->assertDatabaseMissing('kf_aset', ['name' => 'Env']);
    }

    public function test_breakdown_menolak_shot_luar_episode(): void
    {
        [$ep, $shot] = $this->episodeDenganShot();
        // Shot milik episode lain.
        $epLain = Proyek::create(['name' => 'Ep Lain']);
        $sceneLain = Adegan::create(['project_id' => $epLain->id, 'scene_name' => 'X']);
        $shotLain = Shot::create(['scene_id' => $sceneLain->id, 'shot_code' => 'Z1', 'duration_seconds' => 10]);
        $sup = User::factory()->create(['role' => 'Supervisor']);

        Livewire::actingAs($sup)->test(Manager::class)
            ->set('proyekId', $ep->id)
            ->call('create')
            ->set('name', 'Prop')
            ->set('type', 'PROPERTY')
            ->set('task', 'MODELING')
            ->set('shotIds', [$shotLain->id]) // bukan milik episode ini
            ->call('save')
            ->assertHasNoErrors();

        $aset = Aset::where('name', 'Prop')->first();
        // Shot luar episode tidak tertaut.
        $this->assertDatabaseMissing('kf_aset_shot', ['aset_id' => $aset->id, 'shot_id' => $shotLain->id]);
    }
}
