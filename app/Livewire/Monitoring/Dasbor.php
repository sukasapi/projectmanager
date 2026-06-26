<?php

namespace App\Livewire\Monitoring;

use App\Enums\ModeKerja;
use App\Enums\StatusKehadiran;
use App\Enums\StatusLogbook;
use App\Models\Kehadiran;
use App\Models\Logbook;
use App\Models\RevisiShot;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Dasbor pemantauan admin (Opsi C ringan): online sekarang + kehadiran hari ini +
 * onsite/offsite + bukti kerja (progres tugas & antrean logbook). Tanpa timeline detail.
 * Lihat UI.md §8.25, ABSENSI.md §4.
 */
#[Layout('components.layouts.app')]
class Dasbor extends Component
{
    public function mount(): void
    {
        abort_unless(Gate::allows('monitor-kehadiran'), 403);
    }

    public function render()
    {
        $tz = config('kehadiran.timezone');
        $hari = Carbon::now($tz)->toDateString();
        $ambang = now()->subMinutes((int) config('kehadiran.ambang_online_menit', 5));

        $kehadiranHariIni = Kehadiran::with('pengguna')
            ->where('tanggal', $hari)
            ->get();

        $pengguna = User::where('is_active', true)->orderBy('name')->get();
        $sudahAbsen = $kehadiranHariIni->keyBy('user_id');

        return view('livewire.monitoring.dasbor', [
            'tz' => $tz,
            'hari' => $hari,
            'pengguna' => $pengguna,
            'sudahAbsen' => $sudahAbsen,
            'online' => $pengguna->filter(fn (User $u) => $u->last_active_at && $u->last_active_at->gt($ambang)),
            'jmlHadir' => $kehadiranHariIni->filter(fn ($k) => $k->status->isMasuk())->count(),
            'jmlTerlambat' => $kehadiranHariIni->where('status', StatusKehadiran::TERLAMBAT)->count(),
            'jmlAlpha' => $kehadiranHariIni->where('status', StatusKehadiran::ALPHA)->count(),
            'jmlOnsite' => $kehadiranHariIni->where('work_mode', ModeKerja::ONSITE)->count(),
            'jmlOffsite' => $kehadiranHariIni->where('work_mode', ModeKerja::OFFSITE)->count(),
            'logbookMenunggu' => Logbook::where('status', StatusLogbook::DIKIRIM->value)->count(),
            'progresTerbaru' => RevisiShot::with(['tugasShot.shot', 'author'])
                ->latest()
                ->limit(8)
                ->get(),
        ]);
    }
}
