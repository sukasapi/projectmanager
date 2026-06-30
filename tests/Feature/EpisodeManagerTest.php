<?php

namespace Tests\Feature;

use App\Enums\ProjectStatus;
use App\Livewire\Proyek\DaftarProyek;
use App\Models\Klien;
use App\Models\Proyek;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EpisodeManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Kelola episode kini butuh hak supervisi (Supervisor/Super Admin).
        $this->actingAs(User::factory()->create(['role' => 'Supervisor']));
    }

    public function test_halaman_proyek_dilindungi_auth(): void
    {
        auth()->logout();
        $this->get('/proyek')->assertRedirect('/login');
    }

    public function test_dapat_menambah_episode(): void
    {
        Livewire::test(DaftarProyek::class)
            ->call('create')
            ->set('name', 'Cerita 24')
            ->set('description', 'Episode baru')
            ->set('status', ProjectStatus::PLANNING->value)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('kf_proyek', ['name' => 'Cerita 24', 'description' => 'Episode baru']);
    }

    public function test_episode_dapat_dikaitkan_dengan_klien_yang_ada(): void
    {
        $klien = Klien::create(['name' => 'MNC Animation']);

        Livewire::test(DaftarProyek::class)
            ->call('create')
            ->set('name', 'Cerita 25')
            ->set('clientId', $klien->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('kf_proyek', ['name' => 'Cerita 25', 'client_id' => $klien->id]);
    }

    public function test_menambah_episode_dengan_klien_baru_membuat_klien(): void
    {
        Livewire::test(DaftarProyek::class)
            ->call('create')
            ->set('name', 'Cerita 26')
            ->set('newClientName', 'Studio Baru')
            ->call('save')
            ->assertHasNoErrors();

        $klien = Klien::where('name', 'Studio Baru')->first();
        $this->assertNotNull($klien);
        $this->assertDatabaseHas('kf_proyek', ['name' => 'Cerita 26', 'client_id' => $klien->id]);
    }

    public function test_dapat_mengedit_episode(): void
    {
        $klien = Klien::create(['name' => 'Klien Lama']);
        $proyek = Proyek::create(['name' => 'Lama', 'status' => ProjectStatus::PLANNING->value]);

        Livewire::test(DaftarProyek::class)
            ->call('edit', $proyek->id)
            ->assertSet('name', 'Lama')
            ->set('name', 'Baru')
            ->set('status', ProjectStatus::COMPLETED->value)
            ->set('clientId', $klien->id)
            ->call('save')
            ->assertHasNoErrors();

        $proyek->refresh();
        $this->assertSame('Baru', $proyek->name);
        $this->assertSame(ProjectStatus::COMPLETED, $proyek->status);
        $this->assertSame($klien->id, $proyek->client_id);
    }

    public function test_nama_episode_wajib_diisi(): void
    {
        Livewire::test(DaftarProyek::class)
            ->call('create')
            ->set('name', '')
            ->call('save')
            ->assertHasErrors(['name' => 'required']);
    }

    public function test_dapat_menghapus_episode(): void
    {
        $proyek = Proyek::create(['name' => 'Hapus Saya', 'status' => ProjectStatus::PLANNING->value]);

        Livewire::test(DaftarProyek::class)->call('delete', $proyek->id);

        // Soft delete: baris tetap ada dengan deleted_at terisi (dapat dipulihkan).
        $this->assertSoftDeleted('kf_proyek', ['id' => $proyek->id]);
    }

    public function test_relasi_klien_proyek(): void
    {
        $klien = Klien::create(['name' => 'MNC']);
        $proyek = Proyek::create(['name' => 'Cerita 23', 'status' => ProjectStatus::PLANNING->value, 'client_id' => $klien->id]);

        $this->assertSame('MNC', $proyek->klien->name);
        $this->assertTrue($klien->proyek->contains($proyek));
    }
}
