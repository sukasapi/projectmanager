<?php

namespace Tests\Feature;

use App\Actions\ClockIn;
use App\Actions\CreateShot;
use App\Actions\TransitionShotTaskStatus;
use App\Enums\EmploymentType;
use App\Enums\FaseProduksi;
use App\Enums\ModeKerja;
use App\Enums\RevisionStatus;
use App\Enums\StatusKehadiran;
use App\Enums\TaskStatus;
use App\Livewire\Komentar;
use App\Livewire\Produksi\PraProduksi;
use App\Livewire\Proyek\DaftarProyek;
use App\Models\Adegan;
use App\Models\Kehadiran;
use App\Models\Proyek;
use App\Models\TugasShot;
use App\Models\TugasTahap;
use App\Models\User;
use Database\Seeders\TahapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Perbaikan hasil audit (LANGKAH 18 — Tier A).
 */
class AuditFixesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TahapSeeder::class);
    }

    private function artis(): User
    {
        return User::factory()->create(['role' => 'Artis', 'employment_type' => EmploymentType::CONTRACT->value]);
    }

    private function supervisor(): User
    {
        return User::factory()->create(['role' => 'Supervisor', 'employment_type' => EmploymentType::CONTRACT->value]);
    }

    private function shotTask(Proyek $ep): TugasShot
    {
        $scene = Adegan::create(['project_id' => $ep->id, 'scene_name' => 'S1']);
        $shot = app(CreateShot::class)->handle(['scene_id' => $scene->id, 'shot_code' => 'A1', 'duration_seconds' => 40]);

        return $shot->tugasShot()->firstOrFail();
    }

    // A1 — Komentar tak boleh dibuka lintas-episode oleh yang tak berkepentingan.

    public function test_komentar_ditolak_untuk_non_peserta(): void
    {
        $ep = Proyek::create(['name' => 'Ep K', 'published_at' => now()]);
        $task = $this->shotTask($ep);

        Livewire::actingAs($this->artis()) // artis acak, tidak ditugaskan
            ->test(Komentar::class, ['subjekType' => TugasShot::class, 'subjekId' => $task->id])
            ->assertForbidden();
    }

    public function test_komentar_diizinkan_untuk_artis_ditugaskan(): void
    {
        $ep = Proyek::create(['name' => 'Ep K2', 'published_at' => now()]);
        $task = $this->shotTask($ep);
        $artis = $this->artis();
        $task->artists()->attach($artis->id);

        Livewire::actingAs($artis)
            ->test(Komentar::class, ['subjekType' => TugasShot::class, 'subjekId' => $task->id])
            ->assertSet('subjekId', $task->id);
    }

    public function test_komentar_menolak_tipe_subjek_tak_dikenal(): void
    {
        Livewire::actingAs($this->supervisor())
            ->test(Komentar::class, ['subjekType' => User::class, 'subjekId' => 1])
            ->assertStatus(404);
    }

    // A3 — dependensi tahap Pra/Pasca ditegakkan.

    public function test_tahap_pra_tidak_bisa_mulai_sebelum_prasyarat_disetujui(): void
    {
        $artis = $this->artis();
        $ep = Proyek::create(['name' => 'Ep Dep', 'published_at' => now()]);

        [$tahapA, $tahapB] = $ep->tahap()->fase(FaseProduksi::PRA)->where('level', 'EPISODE')->orderBy('urutan')->take(2)->get()->all();
        $tahapB->update(['requires_tahap_id' => $tahapA->id]);

        $rowA = TugasTahap::create(['project_id' => $ep->id, 'tahap_id' => $tahapA->id, 'artist_id' => $artis->id, 'status' => TaskStatus::NOT_STARTED->value]);
        $rowB = TugasTahap::create(['project_id' => $ep->id, 'tahap_id' => $tahapB->id, 'artist_id' => $artis->id, 'status' => TaskStatus::NOT_STARTED->value]);

        // Prasyarat belum disetujui → B tetap NOT_STARTED.
        Livewire::actingAs($artis)->test(PraProduksi::class)->set('proyekId', $ep->id)->call('mulai', $rowB->id);
        $this->assertSame(TaskStatus::NOT_STARTED, $rowB->fresh()->status);

        // Setujui A → B boleh dimulai.
        $rowA->update(['status' => TaskStatus::APPROVED->value]);
        Livewire::actingAs($artis)->test(PraProduksi::class)->set('proyekId', $ep->id)->call('mulai', $rowB->id);
        $this->assertSame(TaskStatus::IN_PROGRESS, $rowB->fresh()->status);
    }

    // A4 — penugasan Team Lead episode dibatasi peran.

    public function test_artis_biasa_tidak_bisa_jadi_team_lead_episode(): void
    {
        $artis = $this->artis();

        Livewire::actingAs($this->supervisor())->test(DaftarProyek::class)
            ->call('create')->set('name', 'Ep TL')->set('teamLeadId', $artis->id)
            ->call('save')->assertHasErrors('teamLeadId');

        $lead = User::factory()->create(['role' => 'Team Lead']);
        Livewire::actingAs($this->supervisor())->test(DaftarProyek::class)
            ->call('create')->set('name', 'Ep TL2')->set('teamLeadId', $lead->id)
            ->call('save')->assertHasNoErrors();
        $this->assertDatabaseHas('kf_proyek', ['name' => 'Ep TL2', 'team_lead_id' => $lead->id]);
    }

    // A5 — revision_status dibersihkan saat APPROVED.

    public function test_approve_membersihkan_revision_status(): void
    {
        $ep = Proyek::create(['name' => 'Ep Rev', 'published_at' => now()]);
        $task = $this->shotTask($ep);
        $task->update(['status' => TaskStatus::REVIEW->value, 'revision_status' => RevisionStatus::NEEDS_REVISION->value]);

        app(TransitionShotTaskStatus::class)->handle($task, TaskStatus::APPROVED, $this->supervisor());

        $this->assertSame(RevisionStatus::OK, $task->fresh()->revision_status);
    }

    // A6 — setup tahap (edit) hanya pengelola.

    public function test_edit_tahap_ditolak_untuk_artis(): void
    {
        $artis = $this->artis();
        $ep = Proyek::create(['name' => 'Ep Edit', 'published_at' => now()]);
        $tahap = $ep->tahap()->fase(FaseProduksi::PRA)->where('level', 'EPISODE')->first();

        Livewire::actingAs($artis)->test(PraProduksi::class)
            ->set('proyekId', $ep->id)
            ->call('edit', $tahap->id)
            ->assertForbidden();
    }

    // B1 — clock-in setelah baris kehadiran di-soft-delete tidak boleh bentrok unique.

    public function test_clock_in_setelah_soft_delete_tidak_error(): void
    {
        $user = $this->artis();
        $tz = config('kehadiran.timezone');
        $tanggal = Carbon::now($tz)->toDateString();

        $lama = Kehadiran::create([
            'user_id' => $user->id, 'tanggal' => $tanggal, 'clock_in' => now(),
            'work_mode' => ModeKerja::ONSITE->value, 'status' => StatusKehadiran::HADIR->value,
        ]);
        $lama->delete(); // soft delete

        app(ClockIn::class)->handle($user, ModeKerja::ONSITE);

        // Satu baris hidup, tidak ada duplikat.
        $this->assertSame(1, Kehadiran::where('user_id', $user->id)->where('tanggal', $tanggal)->count());
    }

    // B2 — self-heal: episode tanpa snapshot pipeline dipulihkan.

    public function test_pastikan_pipeline_memulihkan_snapshot_hilang(): void
    {
        $ep = Proyek::create(['name' => 'Ep Heal', 'published_at' => now()]);
        // Simulasikan kegagalan snapshot: hapus semua tahap episode.
        $ep->tahap()->forceDelete();
        $this->assertSame(0, $ep->tahap()->count());

        $ep->pastikanPipeline();

        $this->assertGreaterThan(0, $ep->tahap()->count());
    }
}
