<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringTest extends TestCase
{
    use RefreshDatabase;

    private function supervisor(): User
    {
        return User::factory()->create(['role' => 'Supervisor']);
    }

    public function test_heartbeat_memperbarui_last_active_at(): void
    {
        $user = User::factory()->create(['last_active_at' => null]);

        $this->assertFalse($user->isOnline());

        $this->actingAs($user)->post('/heartbeat')->assertNoContent();

        $user->refresh();
        $this->assertNotNull($user->last_active_at);
        $this->assertTrue($user->isOnline());
    }

    public function test_monitoring_terlarang_untuk_non_supervisor(): void
    {
        $biasa = User::factory()->create(['role' => 'Animator']);

        $this->actingAs($biasa)->get('/monitoring')->assertForbidden();
        $this->actingAs($biasa)->get('/laporan/kehadiran')->assertForbidden();
    }

    public function test_supervisor_bisa_membuka_monitoring_dan_laporan(): void
    {
        $sup = $this->supervisor();

        $this->actingAs($sup)->get('/monitoring')->assertOk();
        $this->actingAs($sup)->get('/laporan/kehadiran')->assertOk();
    }

    public function test_halaman_absen_dan_riwayat_dapat_diakses(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/kehadiran')->assertOk();
        $this->actingAs($user)->get('/kehadiran/riwayat')->assertOk();
    }
}
