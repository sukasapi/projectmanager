<?php

namespace Tests\Feature;

use App\Livewire\Auth\LupaPassword;
use App\Livewire\Auth\ResetPassword;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_lupa_dan_reset_render_untuk_tamu(): void
    {
        $this->get('/forgot-password')->assertOk()->assertSee('Lupa kata sandi');
        $this->get('/reset-password/token-contoh')->assertOk()->assertSee('Atur ulang kata sandi');
    }

    public function test_lupa_password_mengirim_tautan_reset(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'artis@studio.test']);

        Livewire::test(LupaPassword::class)
            ->set('email', 'artis@studio.test')
            ->call('kirim')
            ->assertHasNoErrors();

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_lupa_password_pesan_generik_untuk_email_tak_dikenal(): void
    {
        Notification::fake();

        Livewire::test(LupaPassword::class)
            ->set('email', 'bukan@ada.test')
            ->call('kirim')
            ->assertHasNoErrors()
            ->assertSet('status', fn ($s) => str_contains($s, 'Jika email terdaftar'));

        Notification::assertNothingSent();
    }

    public function test_reset_password_dengan_token_valid(): void
    {
        $user = User::factory()->create(['email' => 'artis@studio.test']);
        $token = Password::createToken($user);

        Livewire::test(ResetPassword::class, ['token' => $token])
            ->set('email', 'artis@studio.test')
            ->set('password', 'rahasiaBaru123')
            ->set('password_confirmation', 'rahasiaBaru123')
            ->call('aturUlang')
            ->assertHasNoErrors()
            ->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('rahasiaBaru123', $user->fresh()->password));
    }

    public function test_reset_password_token_salah_ditolak(): void
    {
        $user = User::factory()->create(['email' => 'artis@studio.test', 'password' => Hash::make('lama12345')]);

        Livewire::test(ResetPassword::class, ['token' => 'token-ngawur'])
            ->set('email', 'artis@studio.test')
            ->set('password', 'rahasiaBaru123')
            ->set('password_confirmation', 'rahasiaBaru123')
            ->call('aturUlang')
            ->assertHasErrors('email');

        $this->assertTrue(Hash::check('lama12345', $user->fresh()->password));
    }

    public function test_reset_password_konfirmasi_tidak_cocok(): void
    {
        $user = User::factory()->create(['email' => 'artis@studio.test']);
        $token = Password::createToken($user);

        Livewire::test(ResetPassword::class, ['token' => $token])
            ->set('email', 'artis@studio.test')
            ->set('password', 'rahasiaBaru123')
            ->set('password_confirmation', 'beda')
            ->call('aturUlang')
            ->assertHasErrors('password');
    }
}
