<?php

namespace App\Actions;

use App\Enums\ModeKerja;
use App\Enums\StatusKehadiran;
use App\Models\Kehadiran;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Mencatat clock-in harian seorang pengguna.
 *
 * - Satu baris per (user, tanggal WIB); clock-in kedua di hari sama ditolak.
 * - work_mode ditentukan saat ini (onsite/offsite); OFFSITE wajib beralasan.
 * - Status TERLAMBAT dihitung dari jam masuk WIB + toleransi, dan hanya ditegakkan
 *   untuk jenis kepegawaian yang dikonfigurasi (freelance tidak ditegakkan).
 *
 * Lihat ABSENSI.md §4–§6.
 */
class ClockIn
{
    public function handle(User $user, ModeKerja $mode, ?string $alasan = null, ?string $ip = null): Kehadiran
    {
        $tz = config('kehadiran.timezone');
        $nowWib = Carbon::now($tz);
        $tanggal = $nowWib->toDateString();

        if ($mode === ModeKerja::OFFSITE && trim((string) $alasan) === '') {
            throw ValidationException::withMessages([
                'alasan' => 'Alasan wajib diisi saat memilih mode kerja offsite.',
            ]);
        }

        $kehadiran = Kehadiran::firstOrNew([
            'user_id' => $user->id,
            'tanggal' => $tanggal,
        ]);

        if ($kehadiran->clock_in !== null) {
            throw ValidationException::withMessages([
                'kehadiran' => 'Anda sudah melakukan clock-in hari ini.',
            ]);
        }

        $kehadiran->fill([
            'clock_in' => now(),
            'work_mode' => $mode->value,
            'status' => $this->hitungStatus($user, $nowWib)->value,
            'clock_in_ip' => $ip,
            'catatan' => $alasan ?: null,
        ])->save();

        return $kehadiran;
    }

    /** Tentukan HADIR vs TERLAMBAT berdasarkan jam masuk WIB + toleransi. */
    private function hitungStatus(User $user, Carbon $nowWib): StatusKehadiran
    {
        $tipe = $user->employment_type?->value ?? 'CONTRACT';
        $tegakkan = (bool) (config('kehadiran.tegakkan_jam_per_tipe')[$tipe] ?? true);

        if (! $tegakkan) {
            return StatusKehadiran::HADIR;
        }

        $batas = Carbon::parse(
            $nowWib->toDateString().' '.config('kehadiran.jam_masuk'),
            config('kehadiran.timezone')
        )->addMinutes((int) config('kehadiran.toleransi_menit'));

        return $nowWib->greaterThan($batas)
            ? StatusKehadiran::TERLAMBAT
            : StatusKehadiran::HADIR;
    }
}
