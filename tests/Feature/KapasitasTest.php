<?php

namespace Tests\Feature;

use App\Actions\CreateShot;
use App\Enums\EmploymentType;
use App\Livewire\Laporan\Progress;
use App\Livewire\ReviewPanel;
use App\Livewire\Tim\DaftarTim;
use App\Models\Adegan;
use App\Models\Proyek;
use App\Models\TugasShot;
use App\Models\User;
use Database\Seeders\TahapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tier C3 — kapasitas & estimasi + alokasi vs kapasitas (informasional, boleh overload).
 */
class KapasitasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TahapSeeder::class);
    }

    public function test_estimasi_tersimpan_via_review_panel(): void
    {
        $ep = Proyek::create(['name' => 'Ep Est', 'published_at' => now()]);
        $scene = Adegan::create(['project_id' => $ep->id, 'scene_name' => 'S1']);
        $shot = app(CreateShot::class)->handle(['scene_id' => $scene->id, 'shot_code' => 'A1', 'duration_seconds' => 40]);
        $task = $shot->tugasShot()->firstOrFail();
        $sup = User::factory()->create(['role' => 'Supervisor']);

        Livewire::actingAs($sup)->test(ReviewPanel::class)
            ->call('buka', $task->id)
            ->set('estimasiHari', 4)
            ->call('simpanPreview')
            ->assertHasNoErrors();

        $this->assertSame(4, $task->fresh()->estimasi_hari);
    }

    public function test_kapasitas_tersimpan_via_daftar_tim(): void
    {
        $admin = User::factory()->create(['role' => 'Super Admin']);

        Livewire::actingAs($admin)->test(DaftarTim::class)
            ->set('name', 'Artis Kap')->set('email', 'kap@t.test')
            ->set('role', 'Artis')->set('employmentType', EmploymentType::CONTRACT->value)
            ->set('kapasitasHari', 3)->set('password', 'rahasia123')
            ->call('save')->assertHasNoErrors();

        $this->assertSame(3, (int) User::where('email', 'kap@t.test')->value('kapasitas_hari'));
    }

    public function test_beban_menandai_overload(): void
    {
        $sup = User::factory()->create(['role' => 'Supervisor']);
        $artis = User::factory()->create(['name' => 'Sinta Overload', 'kapasitas_hari' => 2]);

        $ep = Proyek::create(['name' => 'Ep Load', 'published_at' => now()]);
        $scene = Adegan::create(['project_id' => $ep->id, 'scene_name' => 'S1']);
        $shot = app(CreateShot::class)->handle(['scene_id' => $scene->id, 'shot_code' => 'A1', 'duration_seconds' => 40]);

        // Dua sub-task aktif, masing-masing estimasi 3 hari → 6 hari / kapasitas 2 = 300%.
        foreach (TugasShot::where('shot_id', $shot->id)->get() as $t) {
            $t->update(['estimasi_hari' => 3]);
            $t->artists()->attach($artis->id);
        }

        Livewire::actingAs($sup)->test(Progress::class)
            ->assertSee('Sinta Overload')
            ->assertSee('300%')
            ->assertSee('Bottleneck per tahap');
    }
}
