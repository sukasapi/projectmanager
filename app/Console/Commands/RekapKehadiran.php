<?php

namespace App\Console\Commands;

use App\Enums\StatusKehadiran;
use App\Models\Kehadiran;
use App\Models\Perusahaan;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Rekap kehadiran harian — dijalankan via cron `schedule:run` (TANPA daemon).
 *
 * Tugas (untuk tanggal target, default HARI INI WIB — dijalankan malam hari):
 * 1. Lengkapi clock-out yang kosong: user yang clock-in tapi lupa clock-out dianggap
 *    pulang TEPAT WAKTU pada jam pulang perusahaan. Durasi dihitung ulang (Observer).
 * 2. Tandai ALPHA untuk pengguna aktif yang tidak punya baris kehadiran.
 *
 * Status TERLAMBAT sudah ditetapkan saat clock-in. Lihat ABSENSI.md, permintaan #A.
 */
class RekapKehadiran extends Command
{
    protected $signature = 'kehadiran:rekap {--tanggal= : Tanggal WIB (Y-m-d), default hari ini}';

    protected $description = 'Rekap harian: lengkapi clock-out tepat waktu & tandai ALPHA.';

    public function handle(): int
    {
        $tz = config('kehadiran.timezone');
        $tanggal = $this->option('tanggal')
            ? Carbon::createFromFormat('Y-m-d', $this->option('tanggal'), $tz)->toDateString()
            : Carbon::now($tz)->toDateString();

        // (1) Clock-out otomatis = jam pulang (tepat waktu) untuk yang lupa clock-out.
        $jamPulangWib = Carbon::createFromFormat('Y-m-d H:i', $tanggal.' '.Perusahaan::current()->jamPulang(), $tz)->utc();

        $diisi = 0;
        Kehadiran::where('tanggal', $tanggal)
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->each(function (Kehadiran $k) use ($jamPulangWib, &$diisi) {
                $k->fill(['clock_out' => $jamPulangWib])->save(); // Observer hitung ulang durasi
                $diisi++;
            });

        // (2) ALPHA untuk pengguna aktif tanpa baris kehadiran.
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

        $this->info("Rekap {$tanggal}: {$diisi} clock-out dilengkapi (tepat waktu), {$alpha} ditandai ALPHA.");

        return self::SUCCESS;
    }
}
