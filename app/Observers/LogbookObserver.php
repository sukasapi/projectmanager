<?php

namespace App\Observers;

use App\Models\Logbook;

/**
 * Menjaga invariant durasi entri logbook:
 * kf_logbook.durasi_menit = (jam_selesai - jam_mulai) dalam menit. Nilai turunan.
 */
class LogbookObserver
{
    public function saving(Logbook $logbook): void
    {
        if ($logbook->jam_mulai && $logbook->jam_selesai) {
            $logbook->durasi_menit = (int) abs(
                $logbook->jam_mulai->diffInMinutes($logbook->jam_selesai)
            );
        } else {
            $logbook->durasi_menit = 0;
        }
    }
}
