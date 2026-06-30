<?php

namespace Tests\Feature;

use App\Enums\EmploymentType;
use App\Enums\TaskStatus;
use App\Livewire\Produksi\PraProduksi;
use App\Models\Proyek;
use App\Models\TugasTahap;
use App\Models\User;
use Database\Seeders\TahapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TahapWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TahapSeeder::class);
    }

    private function supervisor(): User
    {
        return User::factory()->create(['role' => 'Supervisor', 'employment_type' => EmploymentType::CONTRACT->value]);
    }

    /** @return array{Proyek, TugasTahap, User} */
    private function episodePublished(): array
    {
        $artis = User::factory()->create(['role' => 'Animator']);
        $ep = Proyek::create(['name' => 'Episode X', 'published_at' => now()]);
        $script = $ep->tahap()->where('code', 'script')->first();
        $row = TugasTahap::create([
            'project_id' => $ep->id, 'tahap_id' => $script->id,
            'artist_id' => $artis->id, 'status' => TaskStatus::NOT_STARTED->value,
        ]);

        return [$ep, $row, $artis];
    }

    public function test_artis_mulai_lalu_propose_memberi_notifikasi(): void
    {
        [$ep, $row, $artis] = $this->episodePublished();
        $sup = $this->supervisor();

        Livewire::actingAs($artis)->test(PraProduksi::class)->call('mulai', $row->id);
        $this->assertSame(TaskStatus::IN_PROGRESS, $row->fresh()->status);
        $this->assertDatabaseHas('kf_aktivitas_tahap', ['tugas_tahap_id' => $row->id, 'kind' => 'MULAI']);

        Livewire::actingAs($artis)->test(PraProduksi::class)->call('propose', $row->id);
        $this->assertSame(TaskStatus::REVIEW, $row->fresh()->status);
        $this->assertDatabaseHas('kf_notifikasi', ['user_id' => $sup->id, 'type' => 'propose']);
    }

    public function test_supervisor_menyetujui_memberi_notifikasi_ke_artis(): void
    {
        [$ep, $row, $artis] = $this->episodePublished();
        $row->update(['status' => TaskStatus::REVIEW->value]);

        Livewire::actingAs($this->supervisor())->test(PraProduksi::class)->call('setujui', $row->id);

        $this->assertSame(TaskStatus::APPROVED, $row->fresh()->status);
        $this->assertDatabaseHas('kf_aktivitas_tahap', ['tugas_tahap_id' => $row->id, 'kind' => 'APPROVE']);
        $this->assertDatabaseHas('kf_notifikasi', ['user_id' => $artis->id, 'type' => 'approve']);
    }

    public function test_tolak_wajib_alasan_dan_kembali_dikerjakan(): void
    {
        [$ep, $row, $artis] = $this->episodePublished();
        $row->update(['status' => TaskStatus::REVIEW->value]);

        // Tanpa alasan → error.
        Livewire::actingAs($this->supervisor())->test(PraProduksi::class)
            ->call('tolak', $row->id)
            ->call('konfirmasiTolak')
            ->assertHasErrors('rejectAlasan');

        // Dengan alasan → ditolak, kembali IN_PROGRESS, artis dinotifikasi.
        Livewire::actingAs($this->supervisor())->test(PraProduksi::class)
            ->call('tolak', $row->id)
            ->set('rejectAlasan', 'Perbaiki struktur naskah.')
            ->call('konfirmasiTolak')
            ->assertHasNoErrors();

        $this->assertSame(TaskStatus::IN_PROGRESS, $row->fresh()->status);
        $this->assertDatabaseHas('kf_aktivitas_tahap', ['tugas_tahap_id' => $row->id, 'kind' => 'REJECT', 'note' => 'Perbaiki struktur naskah.']);
        $this->assertDatabaseHas('kf_notifikasi', ['user_id' => $artis->id, 'type' => 'reject']);
    }

    public function test_tidak_bisa_mulai_sebelum_episode_dipublish(): void
    {
        $artis = User::factory()->create(['role' => 'Animator']);
        $ep = Proyek::create(['name' => 'Draft']); // belum publish
        $script = $ep->tahap()->where('code', 'script')->first();
        $row = TugasTahap::create(['project_id' => $ep->id, 'tahap_id' => $script->id, 'artist_id' => $artis->id, 'status' => TaskStatus::NOT_STARTED->value]);

        Livewire::actingAs($artis)->test(PraProduksi::class)
            ->call('mulai', $row->id)
            ->assertForbidden();

        $this->assertSame(TaskStatus::NOT_STARTED, $row->fresh()->status);
    }
}
