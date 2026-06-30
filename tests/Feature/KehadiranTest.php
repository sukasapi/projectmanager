<?php

namespace Tests\Feature;

use App\Actions\ClockIn;
use App\Actions\ClockOut;
use App\Enums\EmploymentType;
use App\Enums\ModeKerja;
use App\Enums\StatusKehadiran;
use App\Livewire\Kehadiran\Absen;
use App\Models\Kehadiran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class KehadiranTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** Set "sekarang" pada jam WIB tertentu di tanggal uji (app tz = UTC, WIB = UTC+7). */
    private function setWaktuWib(string $jamWib): void
    {
        $utc = Carbon::createFromFormat('Y-m-d H:i', '2026-06-26 '.$jamWib, 'Asia/Jakarta')->utc();
        Carbon::setTestNow($utc);
    }

    public function test_clock_in_tepat_waktu_berstatus_hadir(): void
    {
        $this->setWaktuWib('08:50');
        $user = User::factory()->create(['employment_type' => EmploymentType::CONTRACT->value]);

        $kehadiran = app(ClockIn::class)->handle($user, ModeKerja::ONSITE);

        $this->assertSame(StatusKehadiran::HADIR, $kehadiran->status);
        $this->assertSame(ModeKerja::ONSITE, $kehadiran->work_mode);
        $this->assertNotNull($kehadiran->clock_in);
    }

    public function test_clock_in_lewat_toleransi_berstatus_terlambat(): void
    {
        $this->setWaktuWib('09:30'); // > 09:15 (toleransi 15 menit)
        $user = User::factory()->create(['employment_type' => EmploymentType::CONTRACT->value]);

        $kehadiran = app(ClockIn::class)->handle($user, ModeKerja::ONSITE);

        $this->assertSame(StatusKehadiran::TERLAMBAT, $kehadiran->status);
    }

    public function test_freelance_terlambat_tetap_hadir_karena_tidak_ditegakkan(): void
    {
        $this->setWaktuWib('11:00');
        $user = User::factory()->create(['employment_type' => EmploymentType::FREELANCE->value]);

        $kehadiran = app(ClockIn::class)->handle($user, ModeKerja::OFFSITE, 'remote');

        $this->assertSame(StatusKehadiran::HADIR, $kehadiran->status);
    }

    public function test_kontrak_onsite_boleh_clock_in_offsite_dengan_alasan(): void
    {
        $this->setWaktuWib('08:50');
        $user = User::factory()->create(['employment_type' => EmploymentType::CONTRACT->value]);

        $kehadiran = app(ClockIn::class)->handle($user, ModeKerja::OFFSITE, 'kerja dari rumah');

        $this->assertSame(ModeKerja::OFFSITE, $kehadiran->work_mode);
        $this->assertSame('kerja dari rumah', $kehadiran->catatan);
    }

    public function test_offsite_tanpa_alasan_ditolak(): void
    {
        $this->setWaktuWib('08:50');
        $user = User::factory()->create();

        $this->expectException(ValidationException::class);
        app(ClockIn::class)->handle($user, ModeKerja::OFFSITE, '');
    }

    public function test_clock_in_dua_kali_di_hari_sama_ditolak(): void
    {
        $this->setWaktuWib('08:50');
        $user = User::factory()->create();
        app(ClockIn::class)->handle($user, ModeKerja::ONSITE);

        $this->expectException(ValidationException::class);
        app(ClockIn::class)->handle($user, ModeKerja::ONSITE);
    }

    public function test_clock_out_menghitung_durasi_kerja(): void
    {
        $this->setWaktuWib('09:00');
        $user = User::factory()->create();
        app(ClockIn::class)->handle($user, ModeKerja::ONSITE);

        $this->setWaktuWib('17:00'); // 8 jam kemudian
        $kehadiran = app(ClockOut::class)->handle($user);

        $this->assertSame(480, $kehadiran->work_duration_minutes);
        $this->assertNotNull($kehadiran->clock_out);
    }

    public function test_komponen_absen_clock_in_dan_offsite_wajib_alasan(): void
    {
        $this->setWaktuWib('08:50');
        $user = User::factory()->create(['employment_type' => EmploymentType::CONTRACT->value]);

        // Offsite tanpa alasan → error pada field 'alasan'.
        Livewire::actingAs($user)->test(Absen::class)
            ->set('mode', ModeKerja::OFFSITE->value)
            ->set('alasan', '')
            ->call('clockIn')
            ->assertHasErrors('alasan');

        $this->assertDatabaseCount('kf_kehadiran', 0);

        // Onsite → sukses.
        Livewire::actingAs($user)->test(Absen::class)
            ->set('mode', ModeKerja::ONSITE->value)
            ->call('clockIn')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('kf_kehadiran', ['user_id' => $user->id]);
    }

    public function test_rekap_melengkapi_clock_out_tepat_waktu(): void
    {
        $this->setWaktuWib('20:00'); // malam, setelah jam kerja
        $tz = 'Asia/Jakarta';
        $hari = Carbon::now($tz)->toDateString();
        $user = User::factory()->create();

        // Clock-in 09:00 tanpa clock-out.
        $k = Kehadiran::create([
            'user_id' => $user->id, 'tanggal' => $hari,
            'clock_in' => Carbon::createFromFormat('Y-m-d H:i', $hari.' 09:00', $tz)->utc(),
            'status' => StatusKehadiran::HADIR->value,
        ]);
        $this->assertNull($k->clock_out);

        $this->artisan('kehadiran:rekap')->assertSuccessful();

        $k->refresh();
        $this->assertNotNull($k->clock_out);                    // diisi jam pulang
        $this->assertSame(480, $k->work_duration_minutes);      // 09:00–17:00 = 8 jam
    }

    public function test_rekap_menandai_alpha_bagi_yang_tidak_absen(): void
    {
        $this->setWaktuWib('08:50');
        $hadir = User::factory()->create();
        $bolos = User::factory()->create();
        app(ClockIn::class)->handle($hadir, ModeKerja::ONSITE);

        $tanggal = Carbon::now('Asia/Jakarta')->toDateString();
        $this->artisan('kehadiran:rekap', ['--tanggal' => $tanggal])->assertSuccessful();

        $this->assertDatabaseHas('kf_kehadiran', [
            'user_id' => $bolos->id, 'status' => StatusKehadiran::ALPHA->value,
        ]);
        // Yang sudah hadir tidak ditimpa jadi ALPHA.
        $this->assertSame(StatusKehadiran::HADIR, $hadir->kehadiran()->first()->status);
    }
}
