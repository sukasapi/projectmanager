<?php

namespace Tests\Feature;

use App\Livewire\Pengaturan\Indeks;
use App\Models\Perusahaan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Konfigurasi SMTP di Konfigurasi Website (DB-backed + kirim uji).
 */
class SmtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_menyimpan_smtp_terenkripsi(): void
    {
        $admin = User::factory()->create(['role' => 'Super Admin']);

        Livewire::actingAs($admin)->test(Indeks::class)
            ->set('smtp_host', 'mail.owlorix.test')
            ->set('smtp_port', 587)
            ->set('smtp_username', 'noreply@owlorix.test')
            ->set('smtp_password', 'rahasia123')
            ->set('smtp_encryption', 'tls')
            ->set('smtp_from_address', 'noreply@owlorix.test')
            ->call('simpanSmtp')
            ->assertHasNoErrors();

        $p = Perusahaan::current();
        $this->assertSame('mail.owlorix.test', $p->smtp_host);
        $this->assertSame('rahasia123', $p->smtp_password); // cast 'encrypted' → didekripsi kembali
        $this->assertTrue($p->smtpAktif());
        // Tersimpan terenkripsi di DB (bukan plaintext).
        $this->assertNotSame('rahasia123', $p->getRawOriginal('smtp_password'));
    }

    public function test_password_tidak_direset_bila_dikosongkan(): void
    {
        $admin = User::factory()->create(['role' => 'Super Admin']);

        // Simpan pertama dengan password.
        Livewire::actingAs($admin)->test(Indeks::class)
            ->set('smtp_host', 'mail.a.test')->set('smtp_password', 'lama')
            ->call('simpanSmtp')->assertHasNoErrors();
        $this->assertSame('lama', Perusahaan::current()->smtp_password);

        // Simpan kedua: ubah host, password dikosongkan → password lama tetap.
        Livewire::actingAs($admin)->test(Indeks::class)
            ->set('smtp_host', 'mail.b.test')->set('smtp_password', '')
            ->call('simpanSmtp')->assertHasNoErrors();
        $this->assertSame('lama', Perusahaan::current()->smtp_password);
    }

    public function test_kirim_email_uji(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'Super Admin']);

        Livewire::actingAs($admin)->test(Indeks::class)
            ->set('smtp_host', 'smtp.test')
            ->set('smtp_port', 587)
            ->set('ujiEmail', 'tujuan@test.com')
            ->call('kirimUji')
            ->assertHasNoErrors()
            ->assertSet('ujiHasil', 'ok');
    }

    public function test_non_super_admin_tidak_bisa_simpan_smtp(): void
    {
        Livewire::actingAs(User::factory()->create(['role' => 'Supervisor']))->test(Indeks::class)
            ->set('smtp_host', 'x')
            ->call('simpanSmtp')
            ->assertForbidden();
    }
}
