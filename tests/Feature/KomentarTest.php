<?php

namespace Tests\Feature;

use App\Livewire\Komentar as KomentarLivewire;
use App\Models\Komentar;
use App\Models\TugasShot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KomentarTest extends TestCase
{
    use RefreshDatabase;

    private function params(): array
    {
        return ['subjekType' => TugasShot::class, 'subjekId' => 1];
    }

    public function test_kirim_komentar_tersimpan(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(KomentarLivewire::class, $this->params())
            ->set('isi', 'Timing di frame 12 terasa lambat.')
            ->call('kirim')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('kf_komentar', [
            'subjek_type' => TugasShot::class, 'subjek_id' => 1,
            'author_id' => $user->id, 'parent_id' => null,
            'body' => 'Timing di frame 12 terasa lambat.',
        ]);
    }

    public function test_balasan_terhubung_ke_induk(): void
    {
        $user = User::factory()->create();
        $induk = Komentar::create(['subjek_type' => TugasShot::class, 'subjek_id' => 1, 'author_id' => $user->id, 'body' => 'induk']);

        Livewire::actingAs($user)->test(KomentarLivewire::class, $this->params())
            ->call('balas', $induk->id)
            ->set('isi', 'sudah diperbaiki')
            ->call('kirim');

        $this->assertDatabaseHas('kf_komentar', ['body' => 'sudah diperbaiki', 'parent_id' => $induk->id]);
    }

    public function test_hapus_hanya_pemilik_atau_supervisor(): void
    {
        $pemilik = User::factory()->create(['role' => 'Animator']);
        $orangLain = User::factory()->create(['role' => 'Animator']);
        $k = Komentar::create(['subjek_type' => TugasShot::class, 'subjek_id' => 1, 'author_id' => $pemilik->id, 'body' => 'punyaku']);

        // Orang lain (bukan supervisor) gagal hapus.
        Livewire::actingAs($orangLain)->test(KomentarLivewire::class, $this->params())->call('hapus', $k->id);
        $this->assertDatabaseHas('kf_komentar', ['id' => $k->id]);

        // Pemilik berhasil hapus.
        Livewire::actingAs($pemilik)->test(KomentarLivewire::class, $this->params())->call('hapus', $k->id);
        $this->assertDatabaseMissing('kf_komentar', ['id' => $k->id]);
    }
}
