<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Livewire\Produksi\PraProduksi;
use App\Livewire\Produksi\Shotlist;
use App\Models\KolomShotlist;
use App\Models\Proyek;
use App\Models\Shotlist as ShotlistRow;
use App\Models\TugasTahap;
use App\Models\User;
use App\Services\NineRouterService;
use Database\Seeders\KolomShotlistSeeder;
use Database\Seeders\TahapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Fitur Shotlist AI: skenario episode → baris shotlist via 9Router (OpenAI-compatible).
 * Lihat docs/2026-07-09_shotlist-ai.md.
 */
class ShotlistAiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TahapSeeder::class);
        $this->seed(KolomShotlistSeeder::class);

        config([
            'services.ninerouter.base_url' => 'http://ai.test/v1',
            'services.ninerouter.key' => 'dummy-key',
            'services.ninerouter.model' => 'cc/claude-test',
        ]);
    }

    /** Balasan OpenAI-compatible berisi baris shotlist (dibungkus code fence untuk uji parsing). */
    private function fakeAi(array $rows): void
    {
        Http::fake([
            'ai.test/*' => Http::response([
                'choices' => [['message' => ['content' => "```json\n".json_encode(['rows' => $rows])."\n```"]]],
            ]),
        ]);
    }

    public function test_nonaktif_tanpa_api_key(): void
    {
        config(['services.ninerouter.key' => null]);

        $this->assertFalse(app(NineRouterService::class)->aktif());
    }

    public function test_service_memparse_baris_dan_mencatat_log(): void
    {
        $this->fakeAi([
            ['scene' => 'Scene 01', 'shot_no' => 'SC01_SH010', 'dur_animate' => 5, 'visual' => 'Desa pagi hari', 'kolom_asing' => 'dibuang'],
            ['scene' => 'Scene 01', 'shot_no' => 'SC01_SH020', 'dur_animate' => '4', 'visual' => 'Bima berlari'],
        ]);
        $this->actingAs(User::factory()->create(['role' => 'Supervisor']));

        $rows = app(NineRouterService::class)->generateShotlist('Bima bangun pagi lalu berlari ke bukit.', 60, KolomShotlist::aktif()->urut()->get());

        $this->assertCount(2, $rows);
        $this->assertSame('Scene 01', $rows[0]['scene']);
        $this->assertSame('5', $rows[0]['dur_animate']); // angka dinormalkan ke string
        $this->assertArrayNotHasKey('kolom_asing', $rows[0]); // key di luar kolom studio dibuang
        $this->assertDatabaseHas('kf_log_ai', ['status' => 'ok', 'model' => 'cc/claude-test']);
    }

    public function test_generate_ai_menambah_baris_shotlist(): void
    {
        $this->fakeAi([
            ['scene' => 'Scene 01', 'shot_no' => 'SC01_SH010', 'dur_animate' => '5', 'visual' => 'Desa pagi hari'],
            ['scene' => 'Scene 02', 'shot_no' => 'SC02_SH010', 'dur_animate' => '3', 'visual' => 'Bukit hijau'],
        ]);

        $sup = User::factory()->create(['role' => 'Supervisor']);
        $ep = Proyek::create(['name' => 'Ep AI', 'published_at' => now(), 'skenario' => 'Bima berpetualang ke bukit.']);

        // Baris lama harus dikosongkan otomatis saat generate AI (hasil menggantikan, bukan menumpuk).
        ShotlistRow::create(['project_id' => $ep->id, 'urutan' => 1, 'data' => ['scene' => 'Scene 99', 'shot_no' => 'LAMA_SH010']]);

        Livewire::actingAs($sup)->test(Shotlist::class)
            ->set('proyekId', $ep->id)
            ->call('bukaFormAi')
            ->assertSet('showAiForm', true)
            ->set('estimasiMenit', 1)
            ->call('generateAi')
            ->assertHasNoErrors()
            ->assertSet('showAiForm', false);

        $this->assertSame(2, ShotlistRow::where('project_id', $ep->id)->count());
        $row = ShotlistRow::where('project_id', $ep->id)->orderBy('urutan')->first();
        $this->assertSame('SC01_SH010', $row->data['shot_no']);
        $this->assertSame('Desa pagi hari', $row->data['visual']);
        $this->assertNull($row->shot_id); // belum di-generate ke Produksi (masih bisa disunting)
        // Baris lama tak lagi tampil (soft delete).
        $this->assertSame(0, ShotlistRow::where('project_id', $ep->id)->where('data->shot_no', 'LAMA_SH010')->count());
    }

    public function test_instruksi_tambahan_diteruskan_ke_prompt_ai(): void
    {
        $this->fakeAi([
            ['scene' => 'Scene 01', 'shot_no' => 'SC01_SH010', 'dur_animate' => '5', 'visual' => 'Desa'],
        ]);

        $sup = User::factory()->create(['role' => 'Supervisor']);
        $ep = Proyek::create(['name' => 'Ep Arahan', 'published_at' => now(), 'skenario' => 'Bima di desa.']);

        Livewire::actingAs($sup)->test(Shotlist::class)
            ->set('proyekId', $ep->id)
            ->set('estimasiMenit', 1)
            ->set('instruksiAi', 'Detail Visual wajib menyebut kostum & pencahayaan.')
            ->call('generateAi')
            ->assertHasNoErrors();

        Http::assertSent(fn ($req) => str_contains($req->body(), 'Detail Visual wajib menyebut kostum & pencahayaan.')
            && str_contains($req->body(), 'INSTRUKSI TAMBAHAN DARI PETUGAS'));
    }

    public function test_generate_ai_butuh_skenario_terisi(): void
    {
        $sup = User::factory()->create(['role' => 'Supervisor']);
        $ep = Proyek::create(['name' => 'Ep Kosong', 'published_at' => now()]);

        Livewire::actingAs($sup)->test(Shotlist::class)
            ->set('proyekId', $ep->id)
            ->set('estimasiMenit', 5)
            ->call('generateAi')
            ->assertHasErrors('ai');

        $this->assertSame(0, ShotlistRow::where('project_id', $ep->id)->count());
    }

    public function test_simpan_skenario(): void
    {
        $sup = User::factory()->create(['role' => 'Supervisor']);
        $ep = Proyek::create(['name' => 'Ep Naskah', 'published_at' => now()]);

        Livewire::actingAs($sup)->test(Shotlist::class)
            ->set('proyekId', $ep->id)
            ->set('skenario', 'INT. RUMAH BIMA - PAGI. Bima bangun.')
            ->call('simpanSkenario')
            ->assertHasNoErrors();

        $this->assertSame('INT. RUMAH BIMA - PAGI. Bima bangun.', $ep->fresh()->skenario);
    }

    public function test_petugas_script_mengisi_skenario_dari_pra_produksi(): void
    {
        $penulis = User::factory()->create(['role' => 'Artis']);
        $ep = Proyek::create(['name' => 'Ep Script', 'published_at' => now()]);
        $script = $ep->tahap()->where('code', 'script')->firstOrFail();
        TugasTahap::create(['project_id' => $ep->id, 'tahap_id' => $script->id, 'artist_id' => $penulis->id, 'status' => TaskStatus::NOT_STARTED->value]);

        Livewire::actingAs($penulis)->test(PraProduksi::class)
            ->call('pilihEpisode', $ep->id)
            ->assertSee('Skenario')
            ->call('bukaSkenario')
            ->assertSet('showSkenario', true)
            ->set('skenario', 'INT. HUTAN - SIANG. Bima menyusuri sungai.')
            ->call('simpanSkenario')
            ->assertHasNoErrors();

        $this->assertSame('INT. HUTAN - SIANG. Bima menyusuri sungai.', $ep->fresh()->skenario);
    }

    public function test_artis_non_script_tidak_bisa_buka_skenario(): void
    {
        $artis = User::factory()->create(['role' => 'Artis']);
        $ep = Proyek::create(['name' => 'Ep NonScript', 'published_at' => now()]);
        // Ditugaskan pada tahap lain (bukan script) agar punya akses episode.
        $stb = $ep->tahap()->where('code', 'stb-storyboard')->firstOrFail();
        TugasTahap::create(['project_id' => $ep->id, 'tahap_id' => $stb->id, 'artist_id' => $artis->id, 'status' => TaskStatus::NOT_STARTED->value]);

        Livewire::actingAs($artis)->test(PraProduksi::class)
            ->call('pilihEpisode', $ep->id)
            ->call('bukaSkenario')
            ->assertForbidden();
    }

    public function test_artis_biasa_tidak_bisa_generate_ai(): void
    {
        $artis = User::factory()->create(['role' => 'Artis']);
        $ep = Proyek::create(['name' => 'Ep X', 'published_at' => now(), 'skenario' => 'Naskah.']);

        Livewire::actingAs($artis)->test(Shotlist::class)
            ->set('proyekId', $ep->id)
            ->call('bukaFormAi')
            ->assertForbidden();
    }

    public function test_render_dengan_status_tahap_shotlist(): void
    {
        // Regresi: kolom status di-cast ke enum — value('status') mengembalikan TaskStatus,
        // dulu dipaksa TaskStatus::from() lagi → TypeError.
        $sup = User::factory()->create(['role' => 'Supervisor']);
        $ep = Proyek::create(['name' => 'Ep Status', 'published_at' => now()]);
        $tahapShotlist = $ep->tahap()->where('code', 'shotlist')->firstOrFail();
        TugasTahap::create(['project_id' => $ep->id, 'tahap_id' => $tahapShotlist->id, 'status' => TaskStatus::APPROVED->value]);

        Livewire::actingAs($sup)->test(Shotlist::class)
            ->set('proyekId', $ep->id)
            ->assertSee(TaskStatus::APPROVED->label());
    }

    public function test_tombol_ai_tersembunyi_tanpa_key(): void
    {
        config(['services.ninerouter.key' => null]);
        $sup = User::factory()->create(['role' => 'Supervisor']);
        $ep = Proyek::create(['name' => 'Ep Tanpa AI', 'published_at' => now()]);

        Livewire::actingAs($sup)->test(Shotlist::class)
            ->set('proyekId', $ep->id)
            ->assertDontSee('Buat dengan AI');
    }
}
