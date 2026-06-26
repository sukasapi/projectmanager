<?php

namespace Tests\Feature;

use App\Actions\CreateShot;
use App\Enums\EmploymentType;
use App\Enums\ShotTaskType;
use App\Enums\TaskStatus;
use App\Livewire\ReviewPanel;
use App\Livewire\ShotMatrix;
use App\Models\Adegan;
use App\Models\Proyek;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LivewireMatrixTest extends TestCase
{
    use RefreshDatabase;

    private function scene(): Adegan
    {
        $proyek = Proyek::create(['name' => 'Cerita 23']);

        return Adegan::create(['project_id' => $proyek->id, 'scene_name' => 'Scene 01']);
    }

    public function test_komponen_matriks_menambah_shot(): void
    {
        $scene = $this->scene();

        Livewire::test(ShotMatrix::class)
            ->set('newSceneId', $scene->id)
            ->set('newShotCode', 'SC01_SH01')
            ->set('newDurationSeconds', 96)
            ->call('addShot')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('kf_shot', ['shot_code' => 'SC01_SH01']);
        $this->assertSame(96, $scene->fresh()->total_duration);
    }

    public function test_komponen_matriks_menolak_durasi_negatif(): void
    {
        $scene = $this->scene();

        Livewire::test(ShotMatrix::class)
            ->set('newSceneId', $scene->id)
            ->set('newShotCode', 'SC01_SH01')
            ->set('newDurationSeconds', -5)
            ->call('addShot')
            ->assertHasErrors('newDurationSeconds');
    }

    public function test_panel_review_menolak_persetujuan_oleh_magang(): void
    {
        $scene = $this->scene();
        $shot = app(CreateShot::class)->handle([
            'scene_id' => $scene->id, 'shot_code' => 'SC01_SH01', 'duration_seconds' => 50,
        ]);
        $layout = $shot->tugasShot()->where('task_type', ShotTaskType::LAYOUT->value)->first();
        $layout->update(['status' => TaskStatus::REVIEW->value]);

        $intern = User::factory()->create(['employment_type' => EmploymentType::INTERN->value]);

        Livewire::test(ReviewPanel::class)
            ->call('buka', $layout->id)
            ->set('actorId', $intern->id)
            ->call('ubahStatus', 'APPROVED')
            ->assertHasErrors('workflow');

        $this->assertSame(TaskStatus::REVIEW, $layout->fresh()->status);
    }

    public function test_panel_review_mengizinkan_persetujuan_oleh_kontrak(): void
    {
        $scene = $this->scene();
        $shot = app(CreateShot::class)->handle([
            'scene_id' => $scene->id, 'shot_code' => 'SC01_SH01', 'duration_seconds' => 50,
        ]);
        $layout = $shot->tugasShot()->where('task_type', ShotTaskType::LAYOUT->value)->first();
        $layout->update(['status' => TaskStatus::REVIEW->value]);

        $kontrak = User::factory()->create(['employment_type' => EmploymentType::CONTRACT->value]);

        Livewire::test(ReviewPanel::class)
            ->call('buka', $layout->id)
            ->set('actorId', $kontrak->id)
            ->call('ubahStatus', 'APPROVED')
            ->assertHasNoErrors();

        $this->assertSame(TaskStatus::APPROVED, $layout->fresh()->status);
    }
}
