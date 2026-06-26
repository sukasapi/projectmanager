<?php

namespace App\Livewire\Kehadiran;

use App\Models\Kehadiran;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Riwayat kehadiran pribadi pengguna login, difilter per bulan. Read-only.
 * Lihat UI.md §8.22.
 */
#[Layout('components.layouts.app')]
class Riwayat extends Component
{
    /** Bulan terpilih dalam format Y-m (WIB). */
    public string $bulan = '';

    public function mount(): void
    {
        $this->bulan = Carbon::now(config('kehadiran.timezone'))->format('Y-m');
    }

    public function render()
    {
        $tz = config('kehadiran.timezone');
        $awal = Carbon::createFromFormat('Y-m', $this->bulan, $tz)->startOfMonth();
        $akhir = (clone $awal)->endOfMonth();

        $riwayat = Kehadiran::where('user_id', auth()->id())
            ->whereBetween('tanggal', [$awal->toDateString(), $akhir->toDateString()])
            ->orderByDesc('tanggal')
            ->get();

        return view('livewire.kehadiran.riwayat', [
            'riwayat' => $riwayat,
            'tz' => $tz,
            'totalMenit' => (int) $riwayat->sum('work_duration_minutes'),
            'jumlahMasuk' => $riwayat->filter(fn ($k) => $k->status->isMasuk())->count(),
        ]);
    }
}
