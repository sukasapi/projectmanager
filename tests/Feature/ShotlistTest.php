<?php

namespace Tests\Feature;

use App\Actions\CreateShot;
use App\Livewire\Pengaturan\ShotlistKolom;
use App\Livewire\Produksi\Shotlist;
use App\Livewire\ReviewPanel;
use App\Models\Adegan;
use App\Models\GayaShotlist;
use App\Models\KolomShotlist;
use App\Models\Proyek;
use App\Models\Seri;
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

    public function test_peran_durasi_boleh_dipakai_banyak_kolom(): void
    {
        // 'duration' sudah dipakai kolom dur_animate → kolom baru dengan peran duration tetap diterima.
        Livewire::actingAs(User::factory()->create(['role' => 'Super Admin']))->test(ShotlistKolom::class)
            ->call('create')->set('label', 'Duration Render (s)')->set('tipe', 'number')->set('peran', 'duration')
            ->call('save')->assertHasNoErrors();

        $this->assertSame(2, KolomShotlist::where('peran', 'duration')->count());
    }

    public function test_admin_membuat_style_baru_dengan_kolom_terpisah(): void
    {
        $admin = User::factory()->create(['role' => 'Super Admin']);
        $totalKolomAwal = KolomShotlist::count();

        $lw = Livewire::actingAs($admin)->test(ShotlistKolom::class)
            ->call('createStyle')->set('styleName', 'Simple 2D')
            ->call('saveStyle')->assertHasNoErrors();

        $style = GayaShotlist::where('name', 'Simple 2D')->firstOrFail();
        $this->assertFalse($style->is_default); // default tetap Standar Studio

        // Kolom baru masuk ke style baru; peran scene boleh dipakai karena beda style.
        $lw->call('create')->set('label', 'Scene')->set('peran', 'scene')
            ->call('save')->assertHasNoErrors();

        $this->assertDatabaseHas('kf_kolom_shotlist', ['style_id' => $style->id, 'key' => 'scene']);
        $this->assertSame($totalKolomAwal + 1, KolomShotlist::count());
    }

    public function test_seri_memilih_style_dan_shotlist_pakai_kolom_style_itu(): void
    {
        $sup = User::factory()->create(['role' => 'Supervisor']);

        $style = GayaShotlist::create(['name' => 'Minimal']);
        $style->kolom()->createMany([
            ['key' => 'scene', 'label' => 'Scene', 'tipe' => 'text', 'peran' => 'scene', 'urutan' => 1, 'is_active' => true],
            ['key' => 'catatan', 'label' => 'Catatan', 'tipe' => 'text', 'urutan' => 2, 'is_active' => true],
        ]);

        $seri = Seri::create(['name' => 'Seri Minimal', 'shotlist_style_id' => $style->id]);
        $ep = Proyek::create(['name' => 'Ep Minimal', 'series_id' => $seri->id, 'published_at' => now()]);

        Livewire::actingAs($sup)->test(Shotlist::class)
            ->set('proyekId', $ep->id)
            ->call('gantiTampilan', 'tabel')
            ->assertSee('Catatan')
            ->assertDontSee('Type of Shot - Size'); // kolom style default tak ikut tampil
    }

    public function test_style_default_dipakai_episode_tanpa_seri(): void
    {
        $sup = User::factory()->create(['role' => 'Supervisor']);
        $ep = Proyek::create(['name' => 'Ep Tanpa Seri', 'published_at' => now()]);

        Livewire::actingAs($sup)->test(Shotlist::class)
            ->set('proyekId', $ep->id)
            ->call('gantiTampilan', 'tabel')
            ->assertSee('Type of Shot - Size'); // kolom style default (Standar Studio)
    }

    public function test_hapus_style_dipakai_seri_ditolak(): void
    {
        $admin = User::factory()->create(['role' => 'Super Admin']);
        $style = GayaShotlist::create(['name' => 'Terpakai']);
        Seri::create(['name' => 'Seri X', 'shotlist_style_id' => $style->id]);

        Livewire::actingAs($admin)->test(ShotlistKolom::class)
            ->call('deleteStyle', $style->id);

        $this->assertDatabaseHas('kf_gaya_shotlist', ['id' => $style->id, 'deleted_at' => null]);
    }

    public function test_impor_csv_membuat_baris(): void
    {
        $sup = User::factory()->create(['role' => 'Supervisor']);
        $ep = Proyek::create(['name' => 'Ep SL', 'published_at' => now()]);

        // Baris lama harus dikosongkan otomatis saat konfirmasi impor (hasil menggantikan, bukan menumpuk).
        ShotlistRow::create(['project_id' => $ep->id, 'urutan' => 1, 'data' => ['scene' => 'Scene 99', 'shot_no' => 'LAMA_SH010']]);

        $csv = "Scene,Shot No#,Duration Animate (s),Visual\n01,01,5,Desa tradisional\n01,02,3,Bukit\n";
        $file = UploadedFile::fake()->createWithContent('shotlist.csv', $csv);

        $lw = Livewire::actingAs($sup)->test(Shotlist::class)
            ->set('proyekId', $ep->id)
            ->set('csv', $file)
            ->call('importCsv')
            ->assertHasNoErrors()
            ->assertSet('showPratinjauImpor', true);

        // Belum tersimpan / terhapus sebelum dikonfirmasi (pratinjau dulu).
        $this->assertSame(1, ShotlistRow::where('project_id', $ep->id)->count());

        $lw->call('konfirmasiImpor')->assertHasNoErrors()->assertSet('showPratinjauImpor', false);

        $this->assertSame(2, ShotlistRow::where('project_id', $ep->id)->count());
        $row = ShotlistRow::where('project_id', $ep->id)->orderBy('urutan')->first();
        $this->assertSame('01', $row->data['scene']);
        $this->assertSame('Desa tradisional', $row->data['visual']);
        // Baris lama tak lagi tampil (soft delete).
        $this->assertSame(0, ShotlistRow::where('project_id', $ep->id)->where('data->shot_no', 'LAMA_SH010')->count());
    }

    public function test_impor_csv_ansi_dinormalkan_ke_utf8(): void
    {
        $sup = User::factory()->create(['role' => 'Supervisor']);
        $ep = Proyek::create(['name' => 'Ep SL ANSI', 'published_at' => now()]);

        // CSV ber-encoding Windows-1252 (é = \xE9) + BOM tidak ada — umum dari Excel Windows.
        $csv = mb_convert_encoding("Scene,Shot No#,Visual\n01,01,Caf\u{E9} di tepi jalan\n", 'Windows-1252', 'UTF-8');
        $file = UploadedFile::fake()->createWithContent('shotlist-ansi.csv', $csv);

        $lw = Livewire::actingAs($sup)->test(Shotlist::class)
            ->set('proyekId', $ep->id)
            ->set('csv', $file)
            ->call('importCsv')
            ->assertHasNoErrors()
            ->assertSet('showPratinjauImpor', true);

        $lw->call('konfirmasiImpor')->assertHasNoErrors();

        $row = ShotlistRow::where('project_id', $ep->id)->firstOrFail();
        $this->assertSame("Caf\u{E9} di tepi jalan", $row->data['visual']);
    }

    public function test_impor_csv_batal_tidak_menyimpan(): void
    {
        $sup = User::factory()->create(['role' => 'Supervisor']);
        $ep = Proyek::create(['name' => 'Ep SL Batal', 'published_at' => now()]);

        // Baris lama harus tetap utuh saat impor dibatalkan.
        ShotlistRow::create(['project_id' => $ep->id, 'urutan' => 1, 'data' => ['scene' => 'Scene 99', 'shot_no' => 'LAMA_SH010']]);

        $csv = "Scene,Shot No#,Visual\n01,01,Desa\n";
        $file = UploadedFile::fake()->createWithContent('shotlist.csv', $csv);

        Livewire::actingAs($sup)->test(Shotlist::class)
            ->set('proyekId', $ep->id)
            ->set('csv', $file)
            ->call('importCsv')
            ->assertSet('showPratinjauImpor', true)
            ->call('batalImpor')
            ->assertSet('showPratinjauImpor', false)
            ->assertSet('pratinjauImpor', []);

        $this->assertSame(1, ShotlistRow::where('project_id', $ep->id)->count());
        $this->assertSame('LAMA_SH010', ShotlistRow::where('project_id', $ep->id)->first()->data['shot_no']);
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

    public function test_edit_sel_inline_dblclick(): void
    {
        $sup = User::factory()->create(['role' => 'Supervisor']);
        $ep = Proyek::create(['name' => 'Ep Sel', 'published_at' => now()]);
        $row = ShotlistRow::create(['project_id' => $ep->id, 'urutan' => 1, 'data' => ['scene' => '01', 'visual' => 'Lama']]);

        Livewire::actingAs($sup)->test(Shotlist::class)
            ->set('proyekId', $ep->id)
            ->call('mulaiEditSel', $row->id, 'visual')
            ->assertSet('editCellValue', 'Lama')
            ->set('editCellValue', 'Desa baru yang asri')
            ->call('simpanSel')
            ->assertSet('editCellId', null);

        $fresh = $row->fresh();
        $this->assertSame('Desa baru yang asri', $fresh->data['visual']);
        $this->assertSame('01', $fresh->data['scene']); // sel lain tidak tersentuh
    }

    public function test_edit_sel_isi_panjang_memakai_textarea(): void
    {
        $sup = User::factory()->create(['role' => 'Supervisor']);
        $ep = Proyek::create(['name' => 'Ep Long', 'published_at' => now()]);
        $row = ShotlistRow::create(['project_id' => $ep->id, 'urutan' => 1, 'data' => [
            'visual' => 'Desa tradisional dengan sawah terasering yang luas', // > 20 karakter
            'scene' => '01', // <= 20 karakter
        ]]);

        $c = Livewire::actingAs($sup)->test(Shotlist::class)->set('proyekId', $ep->id);

        $c->call('mulaiEditSel', $row->id, 'visual')->assertSeeHtml('<textarea wire:model="editCellValue"');
        $c->call('mulaiEditSel', $row->id, 'scene')
            ->assertDontSeeHtml('<textarea wire:model="editCellValue"')
            ->assertSeeHtml('<input type="text" wire:model="editCellValue"');
    }

    public function test_edit_sel_batal_dengan_escape(): void
    {
        $sup = User::factory()->create(['role' => 'Supervisor']);
        $ep = Proyek::create(['name' => 'Ep Esc', 'published_at' => now()]);
        $row = ShotlistRow::create(['project_id' => $ep->id, 'urutan' => 1, 'data' => ['visual' => 'Tetap']]);

        Livewire::actingAs($sup)->test(Shotlist::class)
            ->set('proyekId', $ep->id)
            ->call('mulaiEditSel', $row->id, 'visual')
            ->set('editCellValue', 'Diubah lalu batal')
            ->call('batalEditSel')
            ->call('simpanSel'); // blur menyusul setelah Esc — tak boleh menyimpan

        $this->assertSame('Tetap', $row->fresh()->data['visual']);
    }

    public function test_edit_sel_kolom_tak_dikenal_ditolak(): void
    {
        $sup = User::factory()->create(['role' => 'Supervisor']);
        $ep = Proyek::create(['name' => 'Ep Key', 'published_at' => now()]);
        $row = ShotlistRow::create(['project_id' => $ep->id, 'urutan' => 1, 'data' => []]);

        Livewire::actingAs($sup)->test(Shotlist::class)
            ->set('proyekId', $ep->id)
            ->call('mulaiEditSel', $row->id, 'kolom_asing')
            ->assertNotFound();
    }

    public function test_artis_biasa_tidak_bisa_edit_sel(): void
    {
        $artis = User::factory()->create(['role' => 'Artis']);
        $ep = Proyek::create(['name' => 'Ep SelX', 'published_at' => now()]);
        $row = ShotlistRow::create(['project_id' => $ep->id, 'urutan' => 1, 'data' => ['visual' => 'Aman']]);

        Livewire::actingAs($artis)->test(Shotlist::class)
            ->set('proyekId', $ep->id)
            ->call('mulaiEditSel', $row->id, 'visual')
            ->assertForbidden();
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
