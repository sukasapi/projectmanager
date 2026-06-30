<?php

namespace Tests\Feature;

use App\Livewire\Produksi\PraProduksi;
use App\Models\Proyek;
use App\Models\User;
use App\Services\GeminiService;
use Database\Seeders\TahapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class GeminiTest extends TestCase
{
    use RefreshDatabase;

    public function test_nonaktif_tanpa_api_key(): void
    {
        config(['services.gemini.key' => null]);

        $this->assertFalse(app(GeminiService::class)->aktif());
    }

    public function test_menulis_deskripsi_via_api(): void
    {
        config(['services.gemini.key' => 'dummy-key']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'Deskripsi hasil AI.']]]]],
            ]),
        ]);

        $teks = app(GeminiService::class)->tulisDeskripsi('Tuliskan deskripsi.');

        $this->assertSame('Deskripsi hasil AI.', $teks);
        $this->assertDatabaseHas('kf_log_ai', ['status' => 'ok']); // pemanggilan tercatat
    }

    public function test_tombol_ai_mengisi_deskripsi_di_tracker(): void
    {
        config(['services.gemini.key' => 'dummy-key']);
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => 'Draf deskripsi tahap.']]]]],
            ]),
        ]);

        $this->seed(TahapSeeder::class);
        $ep = Proyek::create(['name' => 'Episode Alpha']);
        $script = $ep->tahap()->where('phase', 'PRA')->where('code', 'script')->first();
        $sup = User::factory()->create(['role' => 'Supervisor']);

        Livewire::actingAs($sup)->test(PraProduksi::class)
            ->call('pilihEpisode', $ep->id)
            ->call('edit', $script->id)
            ->call('isiDeskripsiAi')
            ->assertSet('deskripsi', 'Draf deskripsi tahap.');
    }

    public function test_tombol_ai_tersembunyi_tanpa_key(): void
    {
        config(['services.gemini.key' => null]);
        $this->seed(TahapSeeder::class);
        $ep = Proyek::create(['name' => 'Episode Alpha']);
        $sup = User::factory()->create(['role' => 'Supervisor']);

        Livewire::actingAs($sup)->test(PraProduksi::class)
            ->call('pilihEpisode', $ep->id)
            ->assertSet('proyekId', $ep->id)
            ->assertDontSee('Bantu tulis (AI)');
    }
}
