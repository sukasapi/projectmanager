<?php

namespace Tests\Feature;

use App\Actions\CreateShot;
use App\Livewire\Komentar as KomentarLivewire;
use App\Models\Adegan;
use App\Models\Komentar;
use App\Models\Proyek;
use App\Models\TugasShot;
use App\Models\User;
use Database\Seeders\TahapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KomentarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TahapSeeder::class);
    }

    /** Shot-task nyata pada sebuah episode (subjek komentar). */
    private function subjek(): TugasShot
    {
        $ep = Proyek::create(['name' => 'Ep Komentar', 'published_at' => now()]);
        $scene = Adegan::create(['project_id' => $ep->id, 'scene_name' => 'S1']);
        $shot = app(CreateShot::class)->handle(['scene_id' => $scene->id, 'shot_code' => 'A1', 'duration_seconds' => 40]);

        return $shot->tugasShot()->firstOrFail();
    }

    private function params(TugasShot $task): array
    {
        return ['subjekType' => TugasShot::class, 'subjekId' => $task->id];
    }

    public function test_kirim_komentar_tersimpan(): void
    {
        $task = $this->subjek();
        $sup = User::factory()->create(['role' => 'Supervisor']); // peserta (supervisi)

        Livewire::actingAs($sup)->test(KomentarLivewire::class, $this->params($task))
            ->set('isi', 'Timing di frame 12 terasa lambat.')
            ->call('kirim')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('kf_komentar', [
            'subjek_type' => TugasShot::class, 'subjek_id' => $task->id,
            'author_id' => $sup->id, 'parent_id' => null,
            'body' => 'Timing di frame 12 terasa lambat.',
        ]);
    }

    public function test_balasan_terhubung_ke_induk(): void
    {
        $task = $this->subjek();
        $sup = User::factory()->create(['role' => 'Supervisor']);
        $induk = Komentar::create(['subjek_type' => TugasShot::class, 'subjek_id' => $task->id, 'author_id' => $sup->id, 'body' => 'induk']);

        Livewire::actingAs($sup)->test(KomentarLivewire::class, $this->params($task))
            ->call('balas', $induk->id)
            ->set('isi', 'sudah diperbaiki')
            ->call('kirim');

        $this->assertDatabaseHas('kf_komentar', ['body' => 'sudah diperbaiki', 'parent_id' => $induk->id]);
    }

    public function test_hapus_hanya_pemilik_atau_supervisor(): void
    {
        $task = $this->subjek();
        $pemilik = User::factory()->create(['role' => 'Artis']);
        $orangLain = User::factory()->create(['role' => 'Artis']);
        // Keduanya ditugaskan → boleh membuka komentar, tetapi hanya pemilik yang boleh menghapus.
        $task->artists()->attach([$pemilik->id, $orangLain->id]);

        $k = Komentar::create(['subjek_type' => TugasShot::class, 'subjek_id' => $task->id, 'author_id' => $pemilik->id, 'body' => 'punyaku']);

        Livewire::actingAs($orangLain)->test(KomentarLivewire::class, $this->params($task))->call('hapus', $k->id);
        $this->assertDatabaseHas('kf_komentar', ['id' => $k->id]);

        Livewire::actingAs($pemilik)->test(KomentarLivewire::class, $this->params($task))->call('hapus', $k->id);
        $this->assertDatabaseMissing('kf_komentar', ['id' => $k->id]);
    }
}
