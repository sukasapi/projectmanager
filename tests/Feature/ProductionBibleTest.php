<?php

namespace Tests\Feature;

use App\Actions\CreateShot;
use App\Models\Adegan;
use App\Models\Proyek;
use App\Models\User;
use Database\Seeders\TahapSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionBibleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TahapSeeder::class);
    }

    private function episodeWithData(): Proyek
    {
        $ep = Proyek::create(['name' => 'Cerita 23']);
        $scene = Adegan::create(['project_id' => $ep->id, 'scene_name' => 'Scene 01']);
        app(CreateShot::class)->handle(['scene_id' => $scene->id, 'shot_code' => 'SC01_SH01', 'duration_seconds' => 96]);

        return $ep;
    }

    public function test_supervisor_dapat_mengunduh_production_bible_pdf(): void
    {
        $ep = $this->episodeWithData();
        $sup = User::factory()->create(['role' => 'Supervisor']);

        $response = $this->actingAs($sup)->get("/proyek/{$ep->id}/bible.pdf");

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_non_supervisor_tanpa_akses_ditolak(): void
    {
        $ep = $this->episodeWithData();
        $user = User::factory()->create(['role' => 'Animator']);

        $this->actingAs($user)->get("/proyek/{$ep->id}/bible.pdf")->assertForbidden();
    }
}
