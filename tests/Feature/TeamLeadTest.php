<?php

namespace Tests\Feature;

use App\Actions\CreateShot;
use App\Enums\EmploymentType;
use App\Enums\FaseProduksi;
use App\Enums\LevelTahap;
use App\Livewire\Produksi\PipelineEpisode;
use App\Livewire\Tim\DaftarTim;
use App\Models\Adegan;
use App\Models\Proyek;
use App\Models\Tahap;
use App\Models\User;
use Database\Seeders\TahapSeeder;
use Database\Seeders\TeamMemberSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Perubahan peran Team Lead & hak akses (2026-07-01_peran-team-lead-hak-akses.md).
 */
class TeamLeadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TahapSeeder::class);
    }

    private function peran(string $role): User
    {
        return User::factory()->create(['role' => $role, 'employment_type' => EmploymentType::CONTRACT->value]);
    }

    // --- Matriks peran (User::bolehMenetapkanPeran) ---

    public function test_matriks_penetapan_peran(): void
    {
        $admin = new User(['role' => 'Super Admin']);
        $sup = new User(['role' => 'Supervisor']);
        $lead = new User(['role' => 'Team Lead']);
        $artis = new User(['role' => 'Animator']);

        // Super Admin → semua.
        foreach (['Super Admin', 'Supervisor', 'Team Lead', 'Animator', ''] as $t) {
            $this->assertTrue($admin->bolehMenetapkanPeran($t), "admin gagal set $t");
        }

        // Supervisor → Team Lead + Artis; TIDAK Supervisor/Super Admin.
        $this->assertTrue($sup->bolehMenetapkanPeran('Team Lead'));
        $this->assertTrue($sup->bolehMenetapkanPeran('Artis'));
        $this->assertFalse($sup->bolehMenetapkanPeran('Supervisor'));
        $this->assertFalse($sup->bolehMenetapkanPeran('Super Admin'));

        // Team Lead → Artis saja.
        $this->assertTrue($lead->bolehMenetapkanPeran('Artis'));
        $this->assertFalse($lead->bolehMenetapkanPeran('Team Lead'));
        $this->assertFalse($lead->bolehMenetapkanPeran('Supervisor'));

        // Peran yang boleh ditetapkan (4 tingkat).
        $this->assertSame(['Super Admin', 'Supervisor', 'Team Lead', 'Artis'], $admin->peranDapatDitetapkan());
        $this->assertSame(['Team Lead', 'Artis'], $sup->peranDapatDitetapkan());
        $this->assertSame(['Artis'], $lead->peranDapatDitetapkan());

        // Artis biasa → tidak ada.
        $this->assertFalse($artis->bolehMenetapkanPeran('Artis'));
    }

    // --- Akses menu Pemantauan & Tim & Artis untuk Team Lead ---

    public function test_team_lead_bisa_buka_pemantauan_dan_tim(): void
    {
        $lead = $this->peran('Team Lead');

        $this->actingAs($lead)->get('/tim')->assertOk();
        $this->actingAs($lead)->get('/monitoring')->assertOk();
        $this->actingAs($lead)->get('/logbook/review')->assertOk();
        $this->actingAs($lead)->get('/laporan/kehadiran')->assertOk();
    }

    public function test_artis_biasa_tetap_ditolak_pemantauan(): void
    {
        $artis = $this->peran('Animator');

        $this->actingAs($artis)->get('/tim')->assertForbidden();
        $this->actingAs($artis)->get('/monitoring')->assertForbidden();
        $this->actingAs($artis)->get('/logbook/review')->assertForbidden();
    }

    // --- Jenjang tambah artis di DaftarTim ---

    public function test_supervisor_boleh_tambah_team_lead_tidak_boleh_supervisor(): void
    {
        $sup = $this->peran('Supervisor');

        Livewire::actingAs($sup)->test(DaftarTim::class)
            ->set('name', 'Calon Lead')->set('email', 'lead@t.test')
            ->set('role', 'Team Lead')->set('employmentType', EmploymentType::CONTRACT->value)
            ->set('password', 'rahasia123')->call('save')->assertHasNoErrors();
        $this->assertDatabaseHas('kf_pengguna', ['email' => 'lead@t.test', 'role' => 'Team Lead']);

        Livewire::actingAs($sup)->test(DaftarTim::class)
            ->set('name', 'Calon Sup')->set('email', 'sup2@t.test')
            ->set('role', 'Supervisor')->set('employmentType', EmploymentType::CONTRACT->value)
            ->set('password', 'rahasia123')->call('save')->assertHasErrors('role');
        $this->assertDatabaseMissing('kf_pengguna', ['email' => 'sup2@t.test']);
    }

    public function test_team_lead_boleh_tambah_artis_tidak_boleh_team_lead(): void
    {
        $lead = $this->peran('Team Lead');

        Livewire::actingAs($lead)->test(DaftarTim::class)
            ->set('name', 'Animator Baru')->set('email', 'anim@t.test')
            ->set('role', 'Animator')->set('employmentType', EmploymentType::FREELANCE->value)
            ->set('password', 'rahasia123')->call('save')->assertHasNoErrors();
        $this->assertDatabaseHas('kf_pengguna', ['email' => 'anim@t.test', 'role' => 'Animator']);

        Livewire::actingAs($lead)->test(DaftarTim::class)
            ->set('name', 'Lead Lain')->set('email', 'lead2@t.test')
            ->set('role', 'Team Lead')->set('employmentType', EmploymentType::CONTRACT->value)
            ->set('password', 'rahasia123')->call('save')->assertHasErrors('role');
        $this->assertDatabaseMissing('kf_pengguna', ['email' => 'lead2@t.test']);
    }

    public function test_team_lead_tidak_bisa_edit_user_supervisor(): void
    {
        $lead = $this->peran('Team Lead');
        $sup = $this->peran('Supervisor');

        Livewire::actingAs($lead)->test(DaftarTim::class)
            ->call('edit', $sup->id)->assertForbidden();
    }

    // --- Pipeline per-episode ---

    public function test_team_lead_assigned_bisa_akses_pipeline_episode(): void
    {
        $lead = $this->peran('Team Lead');
        $ep = Proyek::create(['name' => 'Ep Lead', 'team_lead_id' => $lead->id]);

        $this->actingAs($lead)->get("/proyek/{$ep->id}/pipeline")->assertOk();
    }

    public function test_team_lead_lain_ditolak_pipeline_episode(): void
    {
        $leadA = $this->peran('Team Lead');
        $leadB = $this->peran('Team Lead');
        $ep = Proyek::create(['name' => 'Ep A', 'team_lead_id' => $leadA->id]);

        // Team Lead yang tidak memimpin episode ini tidak boleh mengatur pipelinenya.
        $this->actingAs($leadB)->get("/proyek/{$ep->id}/pipeline")->assertForbidden();
    }

    public function test_team_lead_menambah_tahap_episode(): void
    {
        $lead = $this->peran('Team Lead');
        $ep = Proyek::create(['name' => 'Ep Tahap', 'team_lead_id' => $lead->id]);

        Livewire::actingAs($lead)->test(PipelineEpisode::class, ['proyek' => $ep])
            ->set('phase', FaseProduksi::PRA->value)
            ->set('level', LevelTahap::EPISODE->value)
            ->set('name', 'Tahap QA Khusus')
            ->set('urutan', 99)
            ->call('save')->assertHasNoErrors();

        $this->assertDatabaseHas('kf_tahap', [
            'project_id' => $ep->id,
            'name' => 'Tahap QA Khusus',
        ]);
    }

    // --- Skema role + jabatan (LANGKAH 17) ---

    public function test_tambah_artis_dengan_jabatan(): void
    {
        $sup = $this->peran('Supervisor');

        Livewire::actingAs($sup)->test(DaftarTim::class)
            ->set('name', 'Rendi')->set('email', 'rendi@t.test')
            ->set('role', 'Artis')->set('jabatan', 'Modeller')
            ->set('employmentType', EmploymentType::CONTRACT->value)
            ->set('password', 'rahasia123')->call('save')->assertHasNoErrors();

        $this->assertDatabaseHas('kf_pengguna', ['email' => 'rendi@t.test', 'role' => 'Artis', 'jabatan' => 'Modeller']);
    }

    public function test_seeder_csv_memetakan_peran_dan_jabatan(): void
    {
        $this->seed(TeamMemberSeeder::class);

        // Supervisor: role Supervisor, tanpa jabatan.
        $this->assertSame('Supervisor', User::where('email', 'arifiandewi@gmail.com')->value('role'));
        $this->assertNull(User::where('email', 'arifiandewi@gmail.com')->value('jabatan'));

        // "animator,team lead" → role Team Lead, jabatan Animator.
        $lead = User::where('email', 'ramadhani8037@gmail.com')->first();
        $this->assertSame('Team Lead', $lead->role);
        $this->assertSame('Animator', $lead->jabatan);

        // Spesialis tunggal → role Artis + jabatan (SLRC huruf besar).
        $this->assertSame('Artis', User::where('email', 'stefanusfeby02@gmail.com')->value('role'));
        $this->assertSame('SLRC', User::where('email', 'stefanusfeby02@gmail.com')->value('jabatan'));

        // Gabungan spesialisasi → jabatan digabung.
        $this->assertSame('Animator, SLRC', User::where('email', 'fernandolaksana@gmail.com')->value('jabatan'));
    }

    public function test_menambah_tahap_shot_membuat_subtask_shot_lama(): void
    {
        $lead = $this->peran('Team Lead');
        $ep = Proyek::create(['name' => 'Ep Backfill', 'team_lead_id' => $lead->id]);
        $scene = Adegan::create(['project_id' => $ep->id, 'scene_name' => 'S1']);
        $shot = app(CreateShot::class)->handle(['scene_id' => $scene->id, 'shot_code' => 'A1', 'duration_seconds' => 40]);

        Livewire::actingAs($lead)->test(PipelineEpisode::class, ['proyek' => $ep])
            ->set('phase', FaseProduksi::PRODUKSI->value)
            ->set('level', LevelTahap::SHOT->value)
            ->set('name', 'Rendering Test')
            ->set('urutan', 50)
            ->call('save')->assertHasNoErrors();

        $tahap = Tahap::milikEpisode($ep->id)->where('code', 'rendering-test')->first();
        $this->assertNotNull($tahap);
        $this->assertDatabaseHas('kf_tugas_shot', ['shot_id' => $shot->id, 'tahap_id' => $tahap->id]);
    }
}
