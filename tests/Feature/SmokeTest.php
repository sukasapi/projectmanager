<?php

namespace Tests\Feature;

use App\Livewire\Produksi\PascaProduksi;
use App\Livewire\Produksi\PraProduksi;
use App\Livewire\ReviewPanel;
use App\Livewire\ShotMatrix;
use App\Models\Kehadiran;
use App\Models\Proyek;
use App\Models\Shot;
use App\Models\TugasShot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Smoke test: render seluruh halaman & komponen utama dengan data nyata (seeder penuh)
 * untuk menangkap error render / variabel tak terdefinisi / relasi rusak.
 */
class SmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    public function test_semua_halaman_render_untuk_super_admin(): void
    {
        $admin = $this->user('admin@animtrack.test'); // Super Admin

        $routes = [
            '/', '/shot-matrix', '/proyek', '/pra-produksi', '/pasca-produksi', '/aset', '/notifikasi',
            '/tugas-saya', '/jadwal', '/laporan/progress', '/cari',
            '/tim', '/pengaturan', '/pengaturan/pipeline', '/pengaturan/log',
            '/kehadiran', '/kehadiran/riwayat', '/logbook', '/logbook/review',
            '/monitoring', '/laporan/kehadiran',
        ];
        foreach ($routes as $r) {
            $this->actingAs($admin)->get($r)->assertOk();
        }

        // Halaman berparameter.
        $this->actingAs($admin)->get('/tim/'.$this->user('ikmal@animtrack.test')->id)->assertOk();
        $ep = Proyek::where('name', 'Cerita 23')->firstOrFail();
        $this->actingAs($admin)->get("/proyek/{$ep->id}/bible.pdf")->assertOk();
    }

    public function test_matriks_dan_tracker_render_dengan_episode(): void
    {
        $admin = $this->user('admin@animtrack.test');
        $ep = Proyek::where('name', 'Cerita 23')->firstOrFail();

        Livewire::actingAs($admin)->test(ShotMatrix::class)->set('proyekId', $ep->id)->assertSee('Scene 01');
        Livewire::actingAs($admin)->test(PraProduksi::class)->set('proyekId', $ep->id)->assertSee('Script');
        Livewire::actingAs($admin)->test(PascaProduksi::class)->set('proyekId', $ep->id)->assertSee('Editing');

        // Review panel pada sebuah shot-task nyata.
        $tugas = TugasShot::whereIn('shot_id', Shot::whereIn('scene_id', $ep->adegan()->pluck('id'))->pluck('id'))->firstOrFail();
        Livewire::actingAs($admin)->test(ReviewPanel::class)->call('buka', $tugas->id)->assertSee('Artis Ditugaskan');
    }

    public function test_halaman_inti_render_untuk_artis(): void
    {
        $artis = $this->user('ikmal@animtrack.test'); // Animator (non-supervisor)
        // Pastikan sudah absen agar tidak terkunci absence gate (Ikmal di-seed hadir).
        $this->assertTrue(Kehadiran::sudahTercatatHariIni($artis->id));

        foreach (['/', '/shot-matrix', '/pra-produksi', '/pasca-produksi', '/kehadiran', '/logbook', '/tugas-saya', '/notifikasi'] as $r) {
            $this->actingAs($artis)->get($r)->assertOk();
        }

        // Artis tidak boleh buka master tim / konfigurasi.
        $this->actingAs($artis)->get('/tim')->assertForbidden();
        $this->actingAs($artis)->get('/pengaturan/pipeline')->assertForbidden();
        $this->actingAs($artis)->get('/monitoring')->assertForbidden();
    }

    public function test_episode_demo_punya_struktur_lengkap(): void
    {
        foreach (['Cerita 21', 'Cerita 27'] as $nama) {
            $ep = Proyek::where('name', $nama)->firstOrFail();
            $this->assertSame(5, $ep->adegan()->count(), "{$nama} harus 5 scene");
            $shotCount = Shot::whereIn('scene_id', $ep->adegan()->pluck('id'))->count();
            $this->assertSame(31, $shotCount, "{$nama} harus 31 shot");
            // Snapshot pipeline ada.
            $this->assertTrue($ep->tahap()->where('level', 'SHOT')->exists());
        }
    }
}
