<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_adalah_halaman_beranda(): void
    {
        $user = User::factory()->create(['name' => 'Budi']);

        $this->actingAs($user)->get('/')
            ->assertOk()
            ->assertSee('Halo, Budi')
            ->assertSee('Kehadiran hari ini');
    }

    public function test_panel_supervisor_hanya_untuk_admin(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'Supervisor']))
            ->get('/')->assertSee('Panel Supervisor');

        $this->actingAs(User::factory()->create(['role' => 'Animator']))
            ->get('/')->assertDontSee('Panel Supervisor');
    }
}
