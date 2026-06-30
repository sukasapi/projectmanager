<?php

namespace Tests\Feature;

use App\Livewire\Pengaturan\Indeks;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class PengaturanTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_pengaturan_tampil(): void
    {
        $this->actingAs(User::factory()->create())->get('/pengaturan')->assertOk();
    }

    public function test_pengguna_memperbarui_profil(): void
    {
        $user = User::factory()->create(['name' => 'Lama']);

        Livewire::actingAs($user)->test(Indeks::class)
            ->set('name', 'Nama Baru')
            ->set('email', 'barupengaturan@animtrack.test')
            ->call('simpanProfil')
            ->assertHasNoErrors();

        $this->assertSame('Nama Baru', $user->fresh()->name);
        $this->assertSame('barupengaturan@animtrack.test', $user->fresh()->email);
    }

    public function test_pengguna_mengganti_kata_sandi(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);

        Livewire::actingAs($user)->test(Indeks::class)
            ->set('currentPassword', 'password')
            ->set('newPassword', 'sandibaru123')
            ->set('newPassword_confirmation', 'sandibaru123')
            ->call('ubahPassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('sandibaru123', $user->fresh()->password));
    }

    public function test_ganti_kata_sandi_gagal_jika_sandi_lama_salah(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);

        Livewire::actingAs($user)->test(Indeks::class)
            ->set('currentPassword', 'salah')
            ->set('newPassword', 'sandibaru123')
            ->set('newPassword_confirmation', 'sandibaru123')
            ->call('ubahPassword')
            ->assertHasErrors('currentPassword');
    }

    public function test_super_admin_menyimpan_profil_dan_jam_pulang(): void
    {
        $admin = User::factory()->create(['role' => 'Super Admin']);

        Livewire::actingAs($admin)->test(Indeks::class)
            ->set('comp_name', 'Studio Kita')
            ->set('comp_email', 'halo@studiokita.test')
            ->set('comp_jam_masuk', '08:30')
            ->set('comp_jam_pulang', '16:30')
            ->set('comp_toleransi', 10)
            ->set('comp_app_name', 'TrackerKu')
            ->set('comp_footer', '© Studio Kita')
            ->call('simpanPerusahaan')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('kf_perusahaan', [
            'name' => 'Studio Kita', 'jam_pulang' => '16:30', 'toleransi_menit' => 10,
            'app_name' => 'TrackerKu', 'footer_text' => '© Studio Kita',
        ]);
    }

    public function test_log_viewer_khusus_super_admin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'Animator']))->get('/pengaturan/log')->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'Supervisor']))->get('/pengaturan/log')->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'Super Admin']))->get('/pengaturan/log')->assertOk();
    }

    public function test_supervisor_biasa_tidak_bisa_simpan_perusahaan(): void
    {
        // Supervisor produksi bukan Super Admin → tak boleh konfigurasi perusahaan.
        Livewire::actingAs(User::factory()->create(['role' => 'Supervisor']))->test(Indeks::class)
            ->set('comp_name', 'Bajakan')
            ->call('simpanPerusahaan')
            ->assertForbidden();
    }

    public function test_non_admin_tidak_bisa_simpan_perusahaan(): void
    {
        Livewire::actingAs(User::factory()->create(['role' => 'Animator']))->test(Indeks::class)
            ->set('comp_name', 'Bajakan')
            ->call('simpanPerusahaan')
            ->assertForbidden();

        $this->assertDatabaseMissing('kf_perusahaan', ['name' => 'Bajakan']);
    }
}
