<?php

namespace Tests\Feature;

use App\Actions\CreateShot;
use App\Livewire\ReviewPanel;
use App\Models\Adegan;
use App\Models\Proyek;
use App\Models\TugasShot;
use App\Models\User;
use Database\Seeders\TahapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class VersiKirimanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TahapSeeder::class);
    }

    private function task(): TugasShot
    {
        $proyek = Proyek::create(['name' => 'Cerita Versi']);
        $scene = Adegan::create(['project_id' => $proyek->id, 'scene_name' => 'Scene 01']);
        $shot = app(CreateShot::class)->handle(['scene_id' => $scene->id, 'shot_code' => 'SC01_SH01', 'duration_seconds' => 50]);

        return TugasShot::where('shot_id', $shot->id)->whereHas('tahap', fn ($q) => $q->where('code', 'animate'))->first();
    }

    public function test_simpan_preview_mencatat_versi(): void
    {
        $sup = User::factory()->create(['role' => 'Supervisor']);
        $task = $this->task();

        Livewire::actingAs($sup)->test(ReviewPanel::class)
            ->call('buka', $task->id)
            ->set('previewUrl', 'https://drive.google.com/file/d/AAA/view')
            ->set('versiCatatan', 'v1 awal')
            ->call('simpanPreview');

        $this->assertDatabaseHas('kf_versi_kiriman', [
            'subjek_type' => TugasShot::class,
            'subjek_id' => $task->id,
            'version' => 1,
            'catatan' => 'v1 awal',
        ]);
    }

    public function test_url_sama_tidak_buat_versi_baru_url_beda_naik(): void
    {
        $sup = User::factory()->create(['role' => 'Supervisor']);
        $task = $this->task();

        $komp = Livewire::actingAs($sup)->test(ReviewPanel::class)->call('buka', $task->id);

        $komp->set('previewUrl', 'https://drive.google.com/file/d/AAA/view')->call('simpanPreview');
        $komp->set('previewUrl', 'https://drive.google.com/file/d/AAA/view')->call('simpanPreview'); // sama → tidak nambah
        $this->assertSame(1, $task->versi()->count());

        $komp->set('previewUrl', 'https://drive.google.com/file/d/BBB/view')->call('simpanPreview'); // beda → v2
        $this->assertSame(2, $task->versi()->count());
        $this->assertSame(2, $task->versi()->first()->version);
    }
}
