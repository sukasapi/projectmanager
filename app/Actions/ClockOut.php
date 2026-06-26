<?php

namespace App\Actions;

use App\Models\Kehadiran;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Mencatat clock-out harian. Durasi kerja dihitung otomatis oleh KehadiranObserver.
 */
class ClockOut
{
    public function handle(User $user, ?string $ip = null): Kehadiran
    {
        $tanggal = Carbon::now(config('kehadiran.timezone'))->toDateString();

        $kehadiran = Kehadiran::where('user_id', $user->id)
            ->where('tanggal', $tanggal)
            ->first();

        if ($kehadiran === null || $kehadiran->clock_in === null) {
            throw ValidationException::withMessages([
                'kehadiran' => 'Belum ada clock-in hari ini.',
            ]);
        }

        if ($kehadiran->clock_out !== null) {
            throw ValidationException::withMessages([
                'kehadiran' => 'Anda sudah melakukan clock-out hari ini.',
            ]);
        }

        $kehadiran->fill([
            'clock_out' => now(),
            'clock_out_ip' => $ip,
        ])->save();

        return $kehadiran;
    }
}
