<?php

namespace Tests\Feature;

use App\Actions\CreateShot;
use App\Enums\FaseProduksi;
use App\Enums\TaskStatus;
use App\Livewire\Produksi\PraProduksi;
use App\Livewire\ReviewPanel;
use App\Models\Adegan;
use App\Models\Proyek;
use App\Models\TugasShot;
use App\Models\TugasTahap;
use App\Models\User;
use Database\Seeders\TahapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tier C5 — notifikasi downstream: saat prasyarat disetujui, artis tahap dependen diberi tahu.
 */
class DownstreamTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TahapSeeder::class);
    }

    public function test_shot_downstream_dinotifikasi_saat_prasyarat_disetujui(): void
    {
        $sup = User::factory()->create(['role' => 'Supervisor', 'employment_type' => \App\Enums\EmploymentType::CONTRACT->value]);
        $artisSim = User::factory()->create(['name' => 'Artis Simulate']);

        $ep = Proyek::create(['name' => 'Ep DS', 'published_at' => now()]);
        $scene = Adegan::create(['project_id' => $ep->id, 'scene_name' => 'S1']);
        $shot = app(CreateShot::class)->handle(['scene_id' => $scene->id, 'shot_code' => 'A1', 'duration_seconds' => 40]);

        $animate = TugasShot::where('shot_id', $shot->id)->whereHas('tahap', fn ($q) => $q->where('code', 'animate'))->firstOrFail();
        $simulate = TugasShot::where('shot_id', $shot->id)->whereHas('tahap', fn ($q) => $q->where('code', 'simulate'))->firstOrFail();
        $simulate->artists()->attach($artisSim->id);

        // Setujui Animate → artis Simulate diberi tahu bisa mulai.
        $animate->update(['status' => TaskStatus::REVIEW->value]);
        Livewire::actingAs($sup)->test(ReviewPanel::class)->call('buka', $animate->id)->call('ubahStatus', 'APPROVED');

        $this->assertDatabaseHas('kf_notifikasi', ['user_id' => $artisSim->id, 'type' => 'info']);
    }

    public function test_tahap_downstream_dinotifikasi_saat_prasyarat_disetujui(): void
    {
        $sup = User::factory()->create(['role' => 'Supervisor', 'employment_type' => \App\Enums\EmploymentType::CONTRACT->value]);
        $artisB = User::factory()->create(['name' => 'Artis B']);

        $ep = Proyek::create(['name' => 'Ep DS2', 'published_at' => now()]);
        [$tahapA, $tahapB] = $ep->tahap()->fase(FaseProduksi::PRA)->where('level', 'EPISODE')->orderBy('urutan')->take(2)->get()->all();
        $tahapB->update(['requires_tahap_id' => $tahapA->id]);

        $rowA = TugasTahap::create(['project_id' => $ep->id, 'tahap_id' => $tahapA->id, 'artist_id' => $sup->id, 'status' => TaskStatus::REVIEW->value]);
        TugasTahap::create(['project_id' => $ep->id, 'tahap_id' => $tahapB->id, 'artist_id' => $artisB->id, 'status' => TaskStatus::NOT_STARTED->value]);

        Livewire::actingAs($sup)->test(PraProduksi::class)->set('proyekId', $ep->id)->call('setujui', $rowA->id);

        $this->assertDatabaseHas('kf_notifikasi', ['user_id' => $artisB->id, 'type' => 'info']);
    }
}
