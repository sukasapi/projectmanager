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
            $kehadiran->work_duration_minutes = (int) abs(
                $kehadiran->clock_in->diffInMinutes($kehadiran->clock_out)
            );
        } else {
            $kehadiran->work_duration_minutes = 0;
        }
    }
}
