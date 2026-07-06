<?php

namespace Tests\Feature;

use App\Actions\CreateShot;
use App\Livewire\Pengaturan\ShotlistKolom;
use App\Livewire\Produksi\Shotlist;
use App\Livewire\ReviewPanel;
use App\Models\Adegan;
use App\Models\KolomShotlist;
use App\Models\Proyek;
use App\Models\Shot;
use App\Models\Shotlist as ShotlistRow;
use App\Models\User;
use Database\Seeders\KolomShotlistSeeder;
use Database\Seeders\TahapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Fitur Shotlist: kolom konfigurabel + impor CSV + generate ke Produksi.
 */
class ShotlistTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TahapSeeder::class);
        $this->seed(KolomShotlistSeeder::class);
    }

    public function test_admin_menambah_kolom_shotlist(): void
    {
        Livewire::actingAs(User::factory()->create(['role' => 'Super Admin']))->test(ShotlistKolom::class)
            ->call('create')->set('label', 'Catatan Sutradara')->set('tipe', 'text')
            ->call('save')->assertHasNoErrors();

        $this->assertDatabaseHas('kf_kolom_shotlist', ['label' => 'Catatan Sutradara', 'key' => 'catatan_sutradara']);
    }

    public function test_peran_ganda_ditolak(): void
    {
        // 'scene' sudah dipakai kolom default → menandai kolom lain sebagai scene ditolak.
        $lain = KolomShotlist::where('key', 'location')->firstOrFail();

        Livewire::actingAs(User::factory()->create(['role' => 'Super Admin']))->test(ShotlistKolom::class)
            ->call('edit', $lain->id)->set('peran', 'scene')
            ->call('save')->assertHasErrors('peran');
    }

    public function test_impor_csv_membuat_baris(): void
    {
        $sup = User::factory()->create(['role' => 'Supervisor']);
        $ep = Proyek::create(['name' => 'Ep SL', 'published_at' => now()]);

        $csv = "Scene,Shot No#,Duration Animate (s),Visual\n01,01,5,Desa tradisional\n01,02,3,Bukit\n";
        $file = UploadedFile::fake()->createWithContent('shotlist.csv', $csv);

        Livewire::actingAs($sup)->test(Shotlist::class)
            ->set('proyekId', $ep->id)
            ->set('csv', $file)
            ->call('importCsv')
            ->assertHasNoErrors();

        $this->assertSame(2, ShotlistRow::where('project_id', $ep->id)->count());
        $row = ShotlistRow::where('project_id', $ep->id)->orderBy('urutan')->first();
        $this->assertSame('01', $row->data['scene']);
        $this->assertSame('Desa tradisional', $row->data['visual']);
    }

    public function test_generate_ke_produksi_membuat_shot_dengan_meta(): void
    {
        $sup = User::factory()->create(['role' => 'Supervisor']);
        $ep = Proyek::create(['name' => 'Ep Gen', 'published_at' => now()]);

        ShotlistRow::create(['project_id' => $ep->id, 'urutan' => 1, 'data' => [
            'scene' => 'Scene 01', 'shot_no' => 'SC01_SH01', 'dur_animate' => '5', 'visual' => 'Desa asri', 'karakter' => 'Bima',
        ]]);

        Livewire::actingAs($sup)->test(Shotlist::class)->set('proyekId', $ep->id)->call('generate');

        $shot = Shot::whereHas('adegan', fn ($q) => $q->where('project_id', $ep->id))->where('shot_code', 'SC01_SH01')->first();
        $this->assertNotNull($shot);
        $this->assertSame(5, $shot->duration_seconds);
        $this->assertSame('Desa asri', $shot->meta['visual'] ?? null);
        // scene/shot_no/dur_animate (kolom peran) tidak ikut ke meta.
        $this->assertArrayNotHasKey('scene', $shot->meta);
        // Baris tertaut ke shot + sub-task produksi terbuat.
        $this->assertSame($shot->id, ShotlistRow::where('project_id', $ep->id)->first()->shot_id);
        $this->assertTrue($shot->tugasShot()->exists());
    }

    public function test_unduh_template_csv(): void
    {
        $sup = User::factory()->create(['role' => 'Supervisor']);
        $ep = Proyek::create(['name' => 'Ep Tpl', 'published_at' => now()]);

        Livewire::actingAs($sup)->test(Shotlist::class)
            ->set('proyekId', $ep->id)
            ->call('unduhTemplate')
            ->assertFileDownloaded('template-shotlist.csv');
    }

    public function test_pengelola_edit_referensi_shotlist_dari_review_panel(): void
    {
        $sup = User::factory()->create(['role' => 'Supervisor']);
        $ep = Proyek::create(['name' => 'Ep Meta', 'published_at' => now()]);
        $scene = Adegan::create(['project_id' => $ep->id, 'scene_name' => 'S1']);
        $shot = app(CreateShot::class)->handle(['scene_id' => $scene->id, 'shot_code' => 'A1', 'duration_seconds' => 40]);
        $shot->update(['meta' => ['visual' => 'Lama']]);
        $task = $shot->tugasShot()->firstOrFail();

        Livewire::actingAs($sup)->test(ReviewPanel::class)
            ->call('buka', $task->id)
            ->set('metaEdit.visual', 'Desa baru')
            ->call('simpanMeta')
            ->assertHasNoErrors();

        $this->assertSame('Desa baru', $shot->fresh()->meta['visual'] ?? null);
    }

    public function test_artis_biasa_tidak_bisa_kelola_shotlist(): void
    {
        $artis = User::factory()->create(['role' => 'Artis']);
        $ep = Proyek::create(['name' => 'Ep X', 'published_at' => now()]);

        Livewire::actingAs($artis)->test(Shotlist::class)
            ->set('proyekId', $ep->id)
            ->call('tambah')
            ->assertForbidden();
    }
}
