<?php

namespace App\Console\Commands;

use App\Enums\StatusKehadiran;
use App\Models\Kehadiran;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Rekap kehadiran harian — dijalankan via cron `schedule:run` (TANPA daemon).
 *
 * Tugas:
 * - Auto-tandai ALPHA untuk pengguna aktif yang tidak punya baris kehadiran pada
 *   tanggal target (default: kemarin WIB, agar hari sudah berakhir).
 *
 * Catatan: status TERLAMBAT sudah ditetapkan saat clock-in (ClockIn::hitungStatus),
 * jadi command ini fokus pada yang tidak hadir sama sekali. Tidak ada pemangkasan
 * sampel karena Opsi C tidak menyimpan tabel sampel (lihat ABSENSI.md §4).
 */
class RekapKehadiran extends Command
{
    protected $signature = 'kehadiran:rekap {--tanggal= : Tanggal WIB (Y-m-d), default kemarin}';

    protected $description = 'Rekap kehadiran harian: tandai ALPHA bagi yang tidak absen.';

    public function handle(): int
    {
        $tz = config('kehadiran.timezone');
        $tanggal = $this->option('tanggal')
            ? Carbon::createFromFormat('Y-m-d', $this->option('tanggal'), $tz)->toDateString()
            : Carbon::now($tz)->subDay()->toDateString();

        $sudahAbsen = Kehadiran::where('tanggal', $tanggal)->pluck('user_id')->all();

        $alpha = 0;
        User::where('is_active', true)
            ->whereNotIn('id', $sudahAbsen)
            ->each(function (User $user) use ($tanggal, &$alpha) {
                Kehadiran::create([
                    'user_id' => $user->id,
                    'tanggal' => $tanggal,
                    'status' => StatusKehadiran::ALPHA->value,
                ]);
                $alpha++;
            });

        $this->info("Rekap {$tanggal}: {$alpha} pengguna ditandai ALPHA (tanpa kabar).");

        return self::SUCCESS;
    }
}
