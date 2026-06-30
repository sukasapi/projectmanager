<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Livewire\Notifikasi\Daftar;
use App\Models\Notifikasi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotifikasiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_menampilkan_pemberitahuan_notifikasi_belum_dibaca(): void
    {
        $user = User::factory()->create();
        Notifikasi::kirim($user->id, 'Disetujui: SC01_SH01', 'Animate disetujui.', route('shot-matrix'), 'approve');

        Livewire::actingAs($user)->test(Dashboard::class)
            ->assertSee('1 notifikasi belum dibaca');
    }

    public function test_dashboard_tanpa_pemberitahuan_bila_semua_terbaca(): void
    {
        $user = User::factory()->create();
        Notifikasi::kirim($user->id, 'Lama', null, null, 'info');
        Notifikasi::where('user_id', $user->id)->update(['read_at' => now()]);

        Livewire::actingAs($user)->test(Dashboard::class)
            ->assertDontSee('notifikasi belum dibaca')
            ->assertNotDispatched('notif-popup');
    }

    public function test_dashboard_memicu_popup_sweetalert_bila_ada_belum_dibaca(): void
    {
        $user = User::factory()->create();
        Notifikasi::kirim($user->id, 'Disetujui', null, route('shot-matrix'), 'approve');

        Livewire::actingAs($user)->test(Dashboard::class)
            ->assertDispatched('notif-popup');
    }

    public function test_buka_notifikasi_menandai_dibaca_dan_redirect(): void
    {
        $user = User::factory()->create();
        $n = Notifikasi::kirim($user->id, 'Perlu revisi', 'Ditolak', route('shot-matrix'), 'reject');

        Livewire::actingAs($user)->test(Daftar::class)
            ->call('buka', $n->id)
            ->assertRedirect(route('shot-matrix'));

        $this->assertNotNull($n->fresh()->read_at);
    }

    public function test_tandai_semua_dibaca(): void
    {
        $user = User::factory()->create();
        Notifikasi::kirim($user->id, 'A', null, null, 'info');
        Notifikasi::kirim($user->id, 'B', null, null, 'info');

        Livewire::actingAs($user)->test(Daftar::class)->call('tandaiSemua');

        $this->assertSame(0, Notifikasi::where('user_id', $user->id)->whereNull('read_at')->count());
    }

    public function test_notifikasi_milik_user_lain_tidak_bisa_dibuka(): void
    {
        $user = User::factory()->create();
        $lain = User::factory()->create();
        $n = Notifikasi::kirim($lain->id, 'Rahasia', null, route('shot-matrix'), 'info');

        Livewire::actingAs($user)->test(Daftar::class)->call('buka', $n->id);

        $this->assertNull($n->fresh()->read_at);
    }
}
