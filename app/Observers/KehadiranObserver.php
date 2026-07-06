<?php

namespace App\Observers;

use App\Models\Kehadiran;

/**
 * Menjaga invariant durasi kerja harian:
 * kf_kehadiran.work_duration_minutes = (clock_out - clock_in) dalam menit.
 * Nilai turunan — tidak boleh ditulis langsung dari UI (selaras ShotObserver).
 */
class KehadiranObserver
{
    public function saving(Kehadiran $kehadiran): void
    {
        if ($kehadiran->clock_in && $kehadiran->clock_out) {
            // Durasi bertanda: clock_out sebelum clock_in (data anomali) → 0, bukan disamarkan abs().
            $menit = $kehadiran->clock_in->diffInMinutes($kehadiran->clock_out, false);
            $kehadiran->work_duration_minutes = (int) max(0, $menit);
        } else {
            $kehadiran->work_duration_minutes = 0;
        }
    }
}
