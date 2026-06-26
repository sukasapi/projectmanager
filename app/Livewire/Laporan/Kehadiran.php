<?php

namespace App\Livewire\Laporan;

use App\Enums\ModeKerja;
use App\Enums\StatusKehadiran;
use App\Models\Kehadiran as ModelKehadiran;
use App\Models\Logbook;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Laporan rekap kehadiran per periode (bulan) per artis — admin only. Read-only.
 * Lihat UI.md §8.26.
 */
#[Layout('components.layouts.app')]
class Kehadiran extends Component
{
    public string $bulan = '';

    public function mount(): void
    {
        abort_unless(Gate::allows('monitor-kehadiran'), 403);
        $this->bulan = Carbon::now(config('kehadiran.timezone'))->format('Y-m');
    }

    public function render()
    {
        $tz = config('kehadiran.timezone');
        $awal = Carbon::createFromFormat('Y-m', $this->bulan, $tz)->startOfMonth();
        $akhir = (clone $awal)->endOfMonth();
        [$d1, $d2] = [$awal->toDateString(), $akhir->toDateString()];

        $kehadiran = ModelKehadiran::whereBetween('tanggal', [$d1, $d2])->get()->groupBy('user_id');
        $logbookCount = Logbook::whereBetween('tanggal', [$d1, $d2])
            ->selectRaw('user_id, COUNT(*) as jml')
            ->groupBy('user_id')
            ->pluck('jml', 'user_id');

        $baris = User::where('is_active', true)->orderBy('name')->get()->map(function (User $u) use ($kehadiran, $logbookCount) {
            $rows = $kehadiran->get($u->id, collect());

            return [
                'user' => $u,
                'hadir' => $rows->filter(fn ($k) => $k->status->isMasuk())->count(),
                'terlambat' => $rows->where('status', StatusKehadiran::TERLAMBAT)->count(),
                'alpha' => $rows->where('status', StatusKehadiran::ALPHA)->count(),
                'onsite' => $rows->where('work_mode', ModeKerja::ONSITE)->count(),
                'offsite' => $rows->where('work_mode', ModeKerja::OFFSITE)->count(),
                'menit' => (int) $rows->sum('work_duration_minutes'),
                'logbook' => (int) ($logbookCount[$u->id] ?? 0),
            ];
        });

        return view('livewire.laporan.kehadiran', [
            'baris' => $baris,
            'periode' => $awal->translatedFormat('F Y'),
        ]);
    }
}
