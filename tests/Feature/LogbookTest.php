<?php

namespace Tests\Feature;

use App\Actions\ReviewLogbook;
use App\Enums\EmploymentType;
use App\Enums\StatusLogbook;
use App\Livewire\Logbook\Harian;
use App\Livewire\Logbook\Review;
use App\Models\Logbook;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class LogbookTest extends TestCase
{
    use RefreshDatabase;

    private function freelancer(): User
    {
        return User::factory()->create(['employment_type' => EmploymentType::FREELANCE->value, 'role' => 'Animator']);
    }

    private function supervisor(): User
    {
        return User::factory()->create(['employment_type' => EmploymentType::CONTRACT->value, 'role' => 'Supervisor']);
    }

    private function buatEntri(User $user, string $status = StatusLogbook::DRAFT->value): Logbook
    {
        return Logbook::create([
            'user_id' => $user->id,
            'tanggal' => '2026-06-26',
            'jam_mulai' => '2026-06-26 02:00:00',
            'jam_selesai' => '2026-06-26 04:00:00',
            'deskripsi' => 'Blocking animasi.',
            'status' => $status,
        ]);
    }

    public function test_komponen_membuat_entri_draft_dan_menghitung_durasi(): void
    {
        $user = $this->freelancer();

        Livewire::actingAs($user)->test(Harian::class)
            ->set('tanggal', '2026-06-26')
            ->set('jamMulai', '09:00')
            ->set('jamSelesai', '12:00')
            ->set('deskripsi', 'Animasi SC01_SH01')
            ->call('save')
            ->assertHasNoErrors();

        $entri = Logbook::where('user_id', $user->id)->first();
        $this->assertNotNull($entri);
        $this->assertSame(StatusLogbook::DRAFT, $entri->status);
        $this->assertSame(180, $entri->durasi_menit); // 3 jam (turunan Observer)
    }

    public function test_submit_mengunci_entri(): void
    {
        $user = $this->freelancer();
        $entri = $this->buatEntri($user);

        Livewire::actingAs($user)->test(Harian::class)->call('submit', $entri->id);

        $entri->refresh();
        $this->assertSame(StatusLogbook::DIKIRIM, $entri->status);
        $this->assertFalse($entri->status->dapatDiedit());
    }

    public function test_supervisor_menyetujui_logbook(): void
    {
        $user = $this->freelancer();
        $entri = $this->buatEntri($user, StatusLogbook::DIKIRIM->value);
        $sup = $this->supervisor();

        $hasil = app(ReviewLogbook::class)->handle($sup, $entri, true, 'bagus');

        $this->assertSame(StatusLogbook::DISETUJUI, $hasil->status);
        $this->assertSame($sup->id, $hasil->reviewed_by);
        $this->assertSame('bagus', $hasil->review_notes);
    }

    public function test_magang_tidak_bisa_mereview(): void
    {
        $intern = User::factory()->create(['employment_type' => EmploymentType::INTERN->value, 'role' => 'Lighting']);
        $entri = $this->buatEntri($this->freelancer(), StatusLogbook::DIKIRIM->value);

        $this->expectException(ValidationException::class);
        app(ReviewLogbook::class)->handle($intern, $entri, true);
    }

    public function test_tidak_bisa_mereview_logbook_sendiri(): void
    {
        $sup = $this->supervisor();
        $entri = $this->buatEntri($sup, StatusLogbook::DIKIRIM->value);

        $this->expectException(ValidationException::class);
        app(ReviewLogbook::class)->handle($sup, $entri, true);
    }

    public function test_logbook_belum_dikirim_tidak_bisa_direview(): void
    {
        $entri = $this->buatEntri($this->freelancer(), StatusLogbook::DRAFT->value);
        $sup = $this->supervisor();

        $this->expectException(ValidationException::class);
        app(ReviewLogbook::class)->handle($sup, $entri, true);
    }

    public function test_halaman_review_terlarang_untuk_non_supervisor(): void
    {
        $this->actingAs($this->freelancer())->get('/logbook/review')->assertForbidden();
        $this->actingAs($this->supervisor())->get('/logbook/review')->assertOk();
    }

    public function test_komponen_review_menyetujui_via_ui(): void
    {
        $entri = $this->buatEntri($this->freelancer(), StatusLogbook::DIKIRIM->value);
        $sup = $this->supervisor();

        Livewire::actingAs($sup)->test(Review::class)
            ->set("catatan.{$entri->id}", 'oke lanjut')
            ->call('setujui', $entri->id)
            ->assertHasNoErrors();

        $this->assertSame(StatusLogbook::DISETUJUI, $entri->fresh()->status);
    }
}
