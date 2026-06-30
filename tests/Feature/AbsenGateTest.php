<?php

namespace Tests\Feature;

use App\Enums\ModeKerja;
use App\Enums\StatusKehadiran;
use App\Livewire\Kehadiran\AbsenGate;
use App\Models\Kehadiran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class AbsenGateTest extends TestCase
{
    use RefreshDatabase;

    private function hariIni(): string
    {
        return Carbon::now(config('kehadiran.timezone'))->toDateString();
    }

    public function test_non_supervisor_belum_absen_dikunci(): void
    {
        $user = User::factory()->create(['role' => 'Animator']);

        $this->actingAs($user)->get('/')->assertSee('Absen Dulu untuk Mulai');
    }

    public function test_supervisor_tidak_dikunci(): void
    {
        $sup = User::factory()->create(['role' => 'Supervisor']);

        $this->actingAs($sup)->get('/')->assertDontSee('Absen Dulu untuk Mulai');
    }

    public function test_setelah_clock_in_tidak_dikunci(): void
    {
        $user = User::factory()->create(['role' => 'Animator']);
        Kehadiran::create([
            'user_id' => $user->id, 'tanggal' => $this->hariIni(),
            'clock_in' => now(), 'status' => StatusKehadiran::HADIR->value,
        ]);

        $this->actingAs($user)->get('/')->assertDontSee('Absen Dulu untuk Mulai');
    }

    public function test_izin_tidak_dikunci(): void
    {
        $user = User::factory()->create(['role' => 'Animator']);
        Kehadiran::create([
            'user_id' => $user->id, 'tanggal' => $this->hariIni(),
            'status' => StatusKehadiran::IZIN->value, // tanpa clock_in
        ]);

        $this->actingAs($user)->get('/')->assertDontSee('Absen Dulu untuk Mulai');
    }

    public function test_clock_in_via_gate_membuat_kehadiran(): void
    {
        $user = User::factory()->create(['role' => 'Animator', 'employment_type' => 'CONTRACT']);

        Livewire::actingAs($user)->test(AbsenGate::class)
            ->set('mode', ModeKerja::ONSITE->value)
            ->call('clockIn')
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('kf_kehadiran', ['user_id' => $user->id]);
        $this->assertTrue(Kehadiran::sudahTercatatHariIni($user->id));
    }
}
