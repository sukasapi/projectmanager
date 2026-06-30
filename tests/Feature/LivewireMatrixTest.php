<?php

namespace Tests\Feature;

use App\Actions\CreateShot;
use App\Enums\EmploymentType;
use App\Enums\TaskStatus;
use App\Livewire\ReviewPanel;
use App\Livewire\ShotMatrix;
use App\Models\Adegan;
use App\Models\Proyek;
use App\Models\TugasShot;
use App\Models\User;
use Database\Seeders\TahapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class LivewireMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TahapSeeder::class);
    }

    private function scene(): Adegan
    {
        $proyek = Proyek::create(['name' => 'Cerita 23']);

        return Adegan::create(['project_id' => $proyek->id, 'scene_name' => 'Scene 01']);
    }

    /** Sub-task tahap pertama (Animate) sebuah shot. */
    private function tahapPertama(int $shotId): TugasShot
    {
        return TugasShot::where('shot_id', $shotId)
            ->whereHas('tahap', fn ($q) => $q->where('code', 'animate'))
            ->first();
    }

    public function test_komponen_matriks_menambah_shot(): void
    {
        $scene = $this->scene();
        $sup = User::factory()->create(['role' => 'Supervisor']);

        Livewire::actingAs($sup)->test(ShotMatrix::class)
            ->set('proyekId', $scene->project_id)
            ->set('newSceneId', $scene->id)
            ->set('newShotCode', 'SC01_SH01')
            ->set('newDurationSeconds', 96)
            ->call('addShot')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('kf_shot', ['shot_code' => 'SC01_SH01']);
        $this->assertSame(96, $scene->fresh()->total_duration);
    }

    public function test_pengelola_menambah_scene(): void
    {
        $scene = $this->scene();
        $sup = User::factory()->create(['role' => 'Supervisor']);

        Livewire::actingAs($sup)->test(ShotMatrix::class)
            ->set('proyekId', $scene->project_id)
            ->call('bukaSceneForm')
            ->set('newSceneName', 'Scene 02')
            ->call('addScene')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('kf_adegan', ['project_id' => $scene->project_id, 'scene_name' => 'Scene 02']);
    }

    public function test_kode_shot_digenerate_otomatis_berurutan(): void
    {
        $scene = $this->scene(); // "Scene 01"
        $sup = User::factory()->create(['role' => 'Supervisor']);

        Livewire::actingAs($sup)->test(ShotMatrix::class)
            ->set('proyekId', $scene->project_id)
            ->call('bukaShotForm')
            ->set('newDurationSeconds', 50)
            ->call('addShot')
            ->call('bukaShotForm')
            ->set('newDurationSeconds', 40)
            ->call('addShot');

        $this->assertDatabaseHas('kf_shot', ['scene_id' => $scene->id, 'shot_code' => 'SC01_SH01']);
        $this->assertDatabaseHas('kf_shot', ['scene_id' => $scene->id, 'shot_code' => 'SC01_SH02']);
    }

    public function test_non_pengelola_tidak_bisa_menambah_scene(): void
    {
        $scene = $this->scene();
        $artis = User::factory()->create(['role' => 'Animator']);

        Livewire::actingAs($artis)->test(ShotMatrix::class)
            ->set('proyekId', $scene->project_id)
            ->call('addScene')
            ->assertForbidden();
    }

    public function test_supervisor_melihat_semua_episode_di_pemilih(): void
    {
        Proyek::create(['name' => 'Episode Alpha']);
        Proyek::create(['name' => 'Episode Beta']);
        $sup = User::factory()->create(['role' => 'Supervisor']);

        Livewire::actingAs($sup)->test(ShotMatrix::class)
            ->assertSee('Episode Alpha')
            ->assertSee('Episode Beta');
    }

    public function test_non_supervisor_hanya_melihat_episode_yang_ditugaskan(): void
    {
        $epA = Proyek::create(['name' => 'Episode Alpha']);
        $sceneA = Adegan::create(['project_id' => $epA->id, 'scene_name' => 'S1']);
        $shotA = app(CreateShot::class)->handle(['scene_id' => $sceneA->id, 'shot_code' => 'A1', 'duration_seconds' => 10]);

        Proyek::create(['name' => 'Episode Beta']); // tidak ditugaskan

        $user = User::factory()->create(['role' => 'Animator']);
        $this->tahapPertama($shotA->id)->artists()->attach($user->id);

        Livewire::actingAs($user)->test(ShotMatrix::class)
            ->assertSee('Episode Alpha')
            ->assertDontSee('Episode Beta');
    }

    public function test_komponen_matriks_menolak_durasi_negatif(): void
    {
        $scene = $this->scene();
        $sup = User::factory()->create(['role' => 'Supervisor']);

        Livewire::actingAs($sup)->test(ShotMatrix::class)
            ->set('proyekId', $scene->project_id)
            ->set('newSceneId', $scene->id)
            ->set('newShotCode', 'SC01_SH01')
            ->set('newDurationSeconds', -5)
            ->call('addShot')
            ->assertHasErrors('newDurationSeconds');
    }

    public function test_pengelola_menugaskan_artis_ke_shot(): void
    {
        $scene = $this->scene();
        $shot = app(CreateShot::class)->handle(['scene_id' => $scene->id, 'shot_code' => 'SC01_SH01', 'duration_seconds' => 50]);
        $tugas = $this->tahapPertama($shot->id);
        $sup = User::factory()->create(['role' => 'Supervisor']);
        $artis = User::factory()->create();

        Livewire::actingAs($sup)->test(ReviewPanel::class)
            ->call('buka', $tugas->id)
            ->set('artisIds', [$artis->id])
            ->call('simpanArtis')
            ->assertHasNoErrors();

        $this->assertTrue($tugas->fresh()->artists->contains($artis->id));
    }

    public function test_non_pengelola_tidak_bisa_menugaskan_artis(): void
    {
        $scene = $this->scene();
        $shot = app(CreateShot::class)->handle(['scene_id' => $scene->id, 'shot_code' => 'SC01_SH01', 'duration_seconds' => 50]);
        $tugas = $this->tahapPertama($shot->id);
        $artis = User::factory()->create(['role' => 'Animator']);

        Livewire::actingAs($artis)->test(ReviewPanel::class)
            ->call('buka', $tugas->id)
            ->set('artisIds', [$artis->id])
            ->call('simpanArtis')
            ->assertForbidden();

        $this->assertCount(0, $tugas->fresh()->artists);
    }

    public function test_artis_tidak_bisa_menyetujui_shot(): void
    {
        $scene = $this->scene();
        $shot = app(CreateShot::class)->handle([
            'scene_id' => $scene->id, 'shot_code' => 'SC01_SH01', 'duration_seconds' => 50,
        ]);
        $task = $this->tahapPertama($shot->id);
        $task->update(['status' => TaskStatus::REVIEW->value]);

        // Artis (bukan pengelola) tidak boleh menyetujui — sekalipun ditugaskan.
        $artis = User::factory()->create(['role' => 'Animator', 'employment_type' => EmploymentType::CONTRACT->value]);
        $task->artists()->attach($artis->id);

        Livewire::actingAs($artis)->test(ReviewPanel::class)
            ->call('buka', $task->id)
            ->call('ubahStatus', 'APPROVED')
            ->assertHasErrors('workflow');

        $this->assertSame(TaskStatus::REVIEW, $task->fresh()->status);
    }

    public function test_pengelola_menyetujui_shot(): void
    {
        $scene = $this->scene();
        $shot = app(CreateShot::class)->handle([
            'scene_id' => $scene->id, 'shot_code' => 'SC01_SH01', 'duration_seconds' => 50,
        ]);
        $task = $this->tahapPertama($shot->id);
        $task->update(['status' => TaskStatus::REVIEW->value]);

        $sup = User::factory()->create(['role' => 'Supervisor', 'employment_type' => EmploymentType::CONTRACT->value]);

        Livewire::actingAs($sup)->test(ReviewPanel::class)
            ->call('buka', $task->id)
            ->call('ubahStatus', 'APPROVED')
            ->assertHasNoErrors();

        $this->assertSame(TaskStatus::APPROVED, $task->fresh()->status);
    }

    public function test_assign_massal_ke_semua_shot_pada_tahap(): void
    {
        $scene = $this->scene();
        $shot1 = app(CreateShot::class)->handle(['scene_id' => $scene->id, 'shot_code' => 'SC01_SH01', 'duration_seconds' => 30]);
        $shot2 = app(CreateShot::class)->handle(['scene_id' => $scene->id, 'shot_code' => 'SC01_SH02', 'duration_seconds' => 30]);
        $artis = User::factory()->create();
        $sup = User::factory()->create(['role' => 'Supervisor']);

        Livewire::actingAs($sup)->test(ShotMatrix::class)
            ->set('proyekId', $scene->project_id)
            ->call('bukaBulk')
            ->set('bulkArtisIds', [$artis->id])
            ->call('assignMassal')
            ->assertHasNoErrors();

        $this->assertTrue($this->tahapPertama($shot1->id)->fresh()->artists->contains($artis->id));
        $this->assertTrue($this->tahapPertama($shot2->id)->fresh()->artists->contains($artis->id));
    }

    public function test_non_pengelola_tidak_bisa_assign_massal(): void
    {
        $scene = $this->scene();
        app(CreateShot::class)->handle(['scene_id' => $scene->id, 'shot_code' => 'SC01_SH01', 'duration_seconds' => 30]);
        $artis = User::factory()->create(['role' => 'Animator']);

        Livewire::actingAs($artis)->test(ShotMatrix::class)
            ->set('proyekId', $scene->project_id)
            ->set('bulkTahapId', 1)
            ->set('bulkArtisIds', [$artis->id])
            ->call('assignMassal')
            ->assertForbidden();
    }

    public function test_deskripsi_shot_disimpan_oleh_pengelola(): void
    {
        $scene = $this->scene();
        $shot = app(CreateShot::class)->handle([
            'scene_id' => $scene->id, 'shot_code' => 'SC01_SH01', 'duration_seconds' => 50,
        ]);
        $task = $this->tahapPertama($shot->id);
        $sup = User::factory()->create(['role' => 'Supervisor']);

        Livewire::actingAs($sup)->test(ReviewPanel::class)
            ->call('buka', $task->id)
            ->set('deskripsiShot', 'Wide shot pembuka memperkenalkan lokasi.')
            ->call('simpanDeskripsiShot')
            ->assertHasNoErrors();

        $this->assertSame('Wide shot pembuka memperkenalkan lokasi.', $shot->fresh()->description);
    }

    public function test_artis_tidak_bisa_simpan_deskripsi_shot(): void
    {
        $scene = $this->scene();
        $shot = app(CreateShot::class)->handle([
            'scene_id' => $scene->id, 'shot_code' => 'SC01_SH01', 'duration_seconds' => 50,
        ]);
        $task = $this->tahapPertama($shot->id);
        $artis = User::factory()->create(['role' => 'Animator']);

        Livewire::actingAs($artis)->test(ReviewPanel::class)
            ->call('buka', $task->id)
            ->set('deskripsiShot', 'coba')
            ->call('simpanDeskripsiShot')
            ->assertForbidden();
    }

    public function test_ai_mengisi_deskripsi_shot(): void
    {
        config(['services.gemini.key' => 'test-key']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'Deskripsi shot dari AI.']]]]],
            ], 200),
        ]);

        $scene = $this->scene();
        $shot = app(CreateShot::class)->handle([
            'scene_id' => $scene->id, 'shot_code' => 'SC01_SH01', 'duration_seconds' => 50,
        ]);
        $task = $this->tahapPertama($shot->id);
        $sup = User::factory()->create(['role' => 'Supervisor']);

        Livewire::actingAs($sup)->test(ReviewPanel::class)
            ->call('buka', $task->id)
            ->call('isiDeskripsiShotAi')
            ->assertSet('deskripsiShot', 'Deskripsi shot dari AI.');
    }

    public function test_preview_link_drive_jadi_iframe(): void
    {
        $scene = $this->scene();
        $shot = app(CreateShot::class)->handle([
            'scene_id' => $scene->id, 'shot_code' => 'SC01_SH01', 'duration_seconds' => 50,
        ]);
        $task = $this->tahapPertama($shot->id);
        $task->update(['preview_url' => 'https://drive.google.com/file/d/1AbC_dEfGhIjKlmnopQRstu/view?usp=sharing']);

        $sup = User::factory()->create(['role' => 'Supervisor']);

        Livewire::actingAs($sup)->test(ReviewPanel::class)
            ->call('buka', $task->id)
            ->assertSeeHtml('<iframe')
            ->assertSeeHtml('https://drive.google.com/file/d/1AbC_dEfGhIjKlmnopQRstu/preview');
    }

    public function test_preview_link_mp4_langsung_jadi_video(): void
    {
        $scene = $this->scene();
        $shot = app(CreateShot::class)->handle([
            'scene_id' => $scene->id, 'shot_code' => 'SC01_SH01', 'duration_seconds' => 50,
        ]);
        $task = $this->tahapPertama($shot->id);
        $task->update(['preview_url' => 'https://cdn.example.com/render/animate.mp4']);

        $sup = User::factory()->create(['role' => 'Supervisor']);

        Livewire::actingAs($sup)->test(ReviewPanel::class)
            ->call('buka', $task->id)
            ->assertSeeHtml('<video')
            ->assertDontSeeHtml('<iframe');
    }

    public function test_super_admin_read_only_tidak_bisa_menyetujui_shot(): void
    {
        $scene = $this->scene();
        $shot = app(CreateShot::class)->handle([
            'scene_id' => $scene->id, 'shot_code' => 'SC01_SH01', 'duration_seconds' => 50,
        ]);
        $task = $this->tahapPertama($shot->id);
        $task->update(['status' => TaskStatus::REVIEW->value]);

        // Super Admin hanya read-only untuk review (hanya Supervisor & Team Lead).
        $admin = User::factory()->create(['role' => 'Super Admin']);

        Livewire::actingAs($admin)->test(ReviewPanel::class)
            ->call('buka', $task->id)
            ->call('ubahStatus', 'APPROVED')
            ->assertHasErrors('workflow');

        $this->assertSame(TaskStatus::REVIEW, $task->fresh()->status);
    }

    public function test_assign_shot_episode_publish_memberi_notifikasi_artis(): void
    {
        $scene = $this->scene();
        Proyek::whereKey($scene->project_id)->update(['published_at' => now()]);
        $shot = app(CreateShot::class)->handle([
            'scene_id' => $scene->id, 'shot_code' => 'SC01_SH01', 'duration_seconds' => 50,
        ]);
        $task = $this->tahapPertama($shot->id);
        $sup = User::factory()->create(['role' => 'Supervisor']);
        $artis = User::factory()->create();

        Livewire::actingAs($sup)->test(ReviewPanel::class)
            ->call('buka', $task->id)
            ->set('artisIds', [$artis->id])
            ->call('simpanArtis')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('kf_notifikasi', ['user_id' => $artis->id]);
    }

    public function test_setujui_shot_memberi_notifikasi_artis(): void
    {
        $scene = $this->scene();
        $shot = app(CreateShot::class)->handle([
            'scene_id' => $scene->id, 'shot_code' => 'SC01_SH01', 'duration_seconds' => 50,
        ]);
        $task = $this->tahapPertama($shot->id);
        $task->update(['status' => TaskStatus::REVIEW->value]);
        $artis = User::factory()->create();
        $task->artists()->attach($artis->id);

        $sup = User::factory()->create(['role' => 'Supervisor', 'employment_type' => EmploymentType::CONTRACT->value]);

        Livewire::actingAs($sup)->test(ReviewPanel::class)
            ->call('buka', $task->id)
            ->call('ubahStatus', 'APPROVED')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('kf_notifikasi', ['user_id' => $artis->id, 'type' => 'approve']);
    }
}
