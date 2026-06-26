<?php

namespace Tests\Feature;

use App\Actions\CreateShot;
use App\Models\Adegan;
use App\Models\Proyek;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShotDurationTest extends TestCase
{
    use RefreshDatabase;

    private function scene(): Adegan
    {
        $proyek = Proyek::create(['name' => 'Cerita Uji']);

        return Adegan::create([
            'project_id' => $proyek->id,
            'scene_name' => 'Scene 01',
        ]);
    }

    public function test_menambah_shot_mengakumulasi_total_durasi_scene(): void
    {
        $scene = $this->scene();
        $action = app(CreateShot::class);

        $action->handle(['scene_id' => $scene->id, 'shot_code' => 'SC01_SH01', 'duration_seconds' => 96]);
        $action->handle(['scene_id' => $scene->id, 'shot_code' => 'SC01_SH02', 'duration_seconds' => 64]);
        $action->handle(['scene_id' => $scene->id, 'shot_code' => 'SC01_SH03', 'duration_seconds' => 36]);

        $this->assertSame(196, $scene->fresh()->total_duration);
    }

    public function test_membuat_shot_otomatis_membuat_empat_sub_pipeline(): void
    {
        $scene = $this->scene();

        $shot = app(CreateShot::class)->handle([
            'scene_id' => $scene->id, 'shot_code' => 'SC01_SH01', 'duration_seconds' => 50,
        ]);

        $this->assertSame(4, $shot->tugasShot()->count());
        $this->assertEqualsCanonicalizing(
            ['LAYOUT', 'ANIMATE', 'SIMULATE', 'LRC'],
            $shot->tugasShot->map(fn ($t) => $t->task_type->value)->all()
        );
    }

    public function test_menghapus_shot_menghitung_ulang_total_durasi(): void
    {
        $scene = $this->scene();
        $action = app(CreateShot::class);

        $a = $action->handle(['scene_id' => $scene->id, 'shot_code' => 'A', 'duration_seconds' => 100]);
        $action->handle(['scene_id' => $scene->id, 'shot_code' => 'B', 'duration_seconds' => 50]);
        $this->assertSame(150, $scene->fresh()->total_duration);

        $a->delete();

        $this->assertSame(50, $scene->fresh()->total_duration);
    }

    public function test_mengubah_durasi_shot_menghitung_ulang_total(): void
    {
        $scene = $this->scene();
        $shot = app(CreateShot::class)->handle([
            'scene_id' => $scene->id, 'shot_code' => 'A', 'duration_seconds' => 40,
        ]);
        $this->assertSame(40, $scene->fresh()->total_duration);

        $shot->update(['duration_seconds' => 75]);

        $this->assertSame(75, $scene->fresh()->total_duration);
    }

    public function test_memindah_shot_ke_scene_lain_menghitung_ulang_kedua_scene(): void
    {
        $sceneA = $this->scene();
        $sceneB = Adegan::create(['project_id' => $sceneA->project_id, 'scene_name' => 'Scene 02']);

        $shot = app(CreateShot::class)->handle([
            'scene_id' => $sceneA->id, 'shot_code' => 'A', 'duration_seconds' => 80,
        ]);
        $this->assertSame(80, $sceneA->fresh()->total_duration);
        $this->assertSame(0, $sceneB->fresh()->total_duration);

        $shot->update(['scene_id' => $sceneB->id]);

        $this->assertSame(0, $sceneA->fresh()->total_duration);
        $this->assertSame(80, $sceneB->fresh()->total_duration);
    }

    public function test_endpoint_api_menambah_shot_dan_mengembalikan_total_durasi(): void
    {
        $scene = $this->scene();

        $response = $this->postJson('/api/shots', [
            'scene_id' => $scene->id,
            'shot_code' => 'SC01_SH01',
            'duration_seconds' => 120,
        ]);

        $response->assertCreated()
            ->assertJsonPath('scene_total_duration', 120);

        $this->assertDatabaseHas('kf_shot', ['shot_code' => 'SC01_SH01', 'duration_seconds' => 120]);
    }

    public function test_endpoint_api_menolak_kode_shot_duplikat_dalam_scene(): void
    {
        $scene = $this->scene();
        app(CreateShot::class)->handle([
            'scene_id' => $scene->id, 'shot_code' => 'SC01_SH01', 'duration_seconds' => 10,
        ]);

        $this->postJson('/api/shots', [
            'scene_id' => $scene->id,
            'shot_code' => 'SC01_SH01',
            'duration_seconds' => 20,
        ])->assertStatus(422)->assertJsonValidationErrorFor('shot_code');
    }
}
