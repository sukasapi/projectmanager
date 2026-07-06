<?php

namespace Tests\Feature;

use App\Actions\CreateShot;
use App\Actions\TransitionShotTaskStatus;
use App\Enums\TaskStatus;
use App\Livewire\ReviewPanel;
use App\Models\Adegan;
use App\Models\Proyek;
use App\Models\TugasShot;
use App\Models\User;
use Database\Seeders\TahapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tier C2 — catatan review per-frame + retake counter.
 */
class ReviewNotesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TahapSeeder::class);
    }

    private function taskDenganArtis(User $artis): TugasShot
    {
        $ep = Proyek::create(['name' => 'Ep Review', 'published_at' => now()]);
        $scene = Adegan::create(['project_id' => $ep->id, 'scene_name' => 'S1']);
        $shot = app(CreateShot::class)->handle(['scene_id' => $scene->id, 'shot_code' => 'A1', 'duration_seconds' => 40]);
        $task = $shot->tugasShot()->firstOrFail();
        $task->artists()->attach($artis->id);

        return $task;
    }

    public function test_peninjau_menambah_catatan_review_per_frame(): void
    {
        $artis = User::factory()->create(['role' => 'Artis']);
        $task = $this->taskDenganArtis($artis);
        $sup = User::factory()->create(['role' => 'Supervisor']);

        Livewire::actingAs($sup)->test(ReviewPanel::class)
            ->call('buka', $task->id)
            ->set('noteFrameStart', 88)
            ->set('noteFrameEnd', 96)
            ->set('noteBody', 'Retime arc tangan.')
            ->call('tambahCatatanReview')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('kf_catatan_review', [
            'shot_task_id' => $task->id, 'frame_start' => 88, 'frame_end' => 96, 'status' => 'open', 'author_id' => $sup->id,
        ]);
    }

    public function test_artis_tidak_bisa_menambah_catatan_review(): void
    {
        $artis = User::factory()->create(['role' => 'Artis']);
        $task = $this->taskDenganArtis($artis);

        Livewire::actingAs($artis)->test(ReviewPanel::class)
            ->call('buka', $task->id)
            ->set('noteBody', 'coba')
            ->call('tambahCatatanReview')
            ->assertHasErrors('noteBody');

        $this->assertDatabaseMissing('kf_catatan_review', ['shot_task_id' => $task->id]);
    }

    public function test_retake_counter_menghitung_pengembalian(): void
    {
        $artis = User::factory()->create(['role' => 'Artis']);
        $task = $this->taskDenganArtis($artis);
        $sup = User::factory()->create(['role' => 'Supervisor']);

        // Simulasikan siklus: IN_PROGRESS → REVIEW → (dikembalikan) IN_PROGRESS.
        $task->update(['status' => TaskStatus::REVIEW->value]);
        app(TransitionShotTaskStatus::class)->handle($task, TaskStatus::IN_PROGRESS, $sup, 'revisi');

        $this->assertSame(1, $task->fresh()->jumlahRetake());
    }
}
