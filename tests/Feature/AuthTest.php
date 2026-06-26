<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_halaman_login_tampil(): void
    {
        $this->get('/login')->assertStatus(200)->assertSeeLivewire(Login::class);
    }

    public function test_pengguna_terautentikasi_melihat_shot_matrix(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/')->assertStatus(200)->assertSee('Shot Pipeline Matrix');
    }

    public function test_login_berhasil_dengan_kredensial_benar(): void
    {
        $user = User::factory()->create(['password' => Hash::make('rahasia123')]);

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'rahasia123')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_gagal_dengan_kata_sandi_salah(): void
    {
        $user = User::factory()->create(['password' => Hash::make('rahasia123')]);

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'salah')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_logout_mengakhiri_sesi(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect('/login');

        $this->assertGuest();
    }
}
