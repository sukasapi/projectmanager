<?php

namespace Tests\Feature;

use App\Actions\CreateShot;
use App\Actions\RecordShotRevision;
use App\Actions\TransitionShotTaskStatus;
use App\Enums\EmploymentType;
use App\Enums\RevisionStatus;
use App\Enums\ShotTaskType;
use App\Enums\TaskStatus;
use App\Exceptions\InvalidShotTaskTransition;
use App\Models\Adegan;
use App\Models\Proyek;
use App\Models\Shot;
use App\Models\TugasShot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShotWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private TransitionShotTaskStatus $transition;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transition = app(TransitionShotTaskStatus::class);
    }

    private function shot(): Shot
    {
        $proyek = Proyek::create(['name' => 'Cerita Uji']);
        $scene = Adegan::create(['project_id' => $proyek->id, 'scene_name' => 'Scene 01']);

        return app(CreateShot::class)->handle([
            'scene_id' => $scene->id, 'shot_code' => 'SC01_SH01', 'duration_seconds' => 50,
        ]);
    }

    private function task(Shot $shot, ShotTaskType $type): TugasShot
    {
        return $shot->tugasShot()->where('task_type', $type->value)->first();
    }

    private function user(EmploymentType $type): User
    {
        return User::factory()->create(['employment_type' => $type->value]);
    }

    /** Setujui penuh sebuah tahap (NOT_STARTED -> APPROVED). */
    private function approve(TugasShot $task, User $actor): void
    {
        $this->transition->handle($task, TaskStatus::IN_PROGRESS, $actor);
        $this->transition->handle($task, TaskStatus::REVIEW, $actor);
        $this->transition->handle($task, TaskStatus::APPROVED, $actor);
    }

    public function test_layout_boleh_langsung_dimulai(): void
    {
        $task = $this->task($this->shot(), ShotTaskType::LAYOUT);

        $this->transition->handle($task, TaskStatus::IN_PROGRESS, $this->user(EmploymentType::CONTRACT));

        $this->assertSame(TaskStatus::IN_PROGRESS, $task->fresh()->status);
    }

    public function test_simulate_tidak_bisa_mulai_sebelum_animate_disetujui(): void
    {
        $shot = $this->shot();
        $simulate = $this->task($shot, ShotTaskType::SIMULATE);

        $this->expectException(InvalidShotTaskTransition::class);
        $this->transition->handle($simulate, TaskStatus::IN_PROGRESS, $this->user(EmploymentType::CONTRACT));
    }

    public function test_simulate_bisa_mulai_setelah_animate_disetujui(): void
    {
        $shot = $this->shot();
        $actor = $this->user(EmploymentType::CONTRACT);

        $this->approve($this->task($shot, ShotTaskType::LAYOUT), $actor);
        $this->approve($this->task($shot, ShotTaskType::ANIMATE), $actor);

        $simulate = $this->task($shot, ShotTaskType::SIMULATE);
        $this->transition->handle($simulate, TaskStatus::IN_PROGRESS, $actor);

        $this->assertSame(TaskStatus::IN_PROGRESS, $simulate->fresh()->status);
    }

    public function test_transisi_meloncat_ditolak(): void
    {
        $task = $this->task($this->shot(), ShotTaskType::LAYOUT);

        $this->expectException(InvalidShotTaskTransition::class);
        // NOT_STARTED -> APPROVED bukan transisi sah.
        $this->transition->handle($task, TaskStatus::APPROVED, $this->user(EmploymentType::CONTRACT));
    }

    public function test_magang_tidak_boleh_menyetujui(): void
    {
        $task = $this->task($this->shot(), ShotTaskType::LAYOUT);
        $intern = $this->user(EmploymentType::INTERN);

        $this->transition->handle($task, TaskStatus::IN_PROGRESS, $intern);
        $this->transition->handle($task, TaskStatus::REVIEW, $intern);

        $this->expectException(InvalidShotTaskTransition::class);
        $this->transition->handle($task, TaskStatus::APPROVED, $intern);
    }

    public function test_karyawan_kontrak_boleh_menyetujui(): void
    {
        $task = $this->task($this->shot(), ShotTaskType::LAYOUT);
        $actor = $this->user(EmploymentType::CONTRACT);

        $this->approve($task, $actor);

        $this->assertSame(TaskStatus::APPROVED, $task->fresh()->status);
    }

    public function test_riwayat_revisi_bersifat_append_only(): void
    {
        $shot = $this->shot();
        $task = $this->task($shot, ShotTaskType::LAYOUT);
        $actor = $this->user(EmploymentType::CONTRACT);
        $record = app(RecordShotRevision::class);

        $record->handle($task, 'Perbaiki timing', RevisionStatus::NEEDS_REVISION, $actor);
        $record->handle($task, 'Masih kurang halus', RevisionStatus::NEEDS_REVISION, $actor);

        // Dua entri tersimpan — riwayat tidak ditimpa.
        $this->assertSame(2, $task->revisi()->count());
        $this->assertSame(RevisionStatus::NEEDS_REVISION, $task->fresh()->revision_status);
    }

    public function test_setiap_transisi_tercatat_di_riwayat(): void
    {
        $task = $this->task($this->shot(), ShotTaskType::LAYOUT);
        $actor = $this->user(EmploymentType::CONTRACT);

        $this->transition->handle($task, TaskStatus::IN_PROGRESS, $actor);

        $this->assertDatabaseHas('kf_revisi_shot', [
            'shot_task_id' => $task->id,
            'status_from' => 'NOT_STARTED',
            'status_to' => 'IN_PROGRESS',
        ]);
    }
}
