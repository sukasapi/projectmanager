<?php

namespace Tests\Feature;

use App\Enums\EmploymentType;
use App\Livewire\Tim\DaftarTim;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TimTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'Super Admin']);
    }

    public function test_akses_tim_untuk_pemantauan(): void
    {
        // Artis biasa tidak boleh; Supervisor, Team Lead, dan Super Admin boleh (view-tim).
        $this->actingAs(User::factory()->create(['role' => 'Animator']))->get('/tim')->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'Supervisor']))->get('/tim')->assertOk();
        $this->actingAs(User::factory()->create(['role' => 'Team Lead']))->get('/tim')->assertOk();
        $this->actingAs($this->admin())->get('/tim')->assertOk();
    }

    public function test_super_admin_menambah_artis(): void
    {
        Livewire::actingAs($this->admin())->test(DaftarTim::class)
            ->set('name', 'Artis Baru')
            ->set('email', 'baru@animtrack.test')
            ->set('role', 'Animator')
            ->set('phone', '0812345678')
            ->set('whatsapp', '62812345678')
            ->set('address', 'Yogyakarta')
            ->set('latitude', '-7.7956')
            ->set('longitude', '110.3695')
            ->set('employmentType', EmploymentType::FREELANCE->value)
            ->set('password', 'rahasia123')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('kf_pengguna', [
            'email' => 'baru@animtrack.test',
            'employment_type' => EmploymentType::FREELANCE->value,
            'phone' => '0812345678',
            'whatsapp' => '62812345678',
        ]);
    }

    public function test_super_admin_menonaktifkan_artis(): void
    {
        $artis = User::factory()->create(['is_active' => true]);

        Livewire::actingAs($this->admin())->test(DaftarTim::class)
            ->call('toggleAktif', $artis->id);

        $this->assertFalse($artis->fresh()->is_active);
    }

    public function test_non_admin_tidak_bisa_mengelola(): void
    {
        Livewire::actingAs(User::factory()->create(['role' => 'Animator']))->test(DaftarTim::class)
            ->assertForbidden();

        $this->assertDatabaseMissing('kf_pengguna', ['email' => 'x@animtrack.test']);
    }

    public function test_detail_artis_terlarang_untuk_non_admin_yang_bukan_dirinya(): void
    {
        $a = User::factory()->create(['role' => 'Animator']);
        $b = User::factory()->create();

        $this->actingAs($a)->get("/tim/{$b->id}")->assertForbidden();
        $this->actingAs($a)->get("/tim/{$a->id}")->assertOk(); // dirinya sendiri boleh
        $this->actingAs($this->admin())->get("/tim/{$b->id}")->assertOk();
    }
}
