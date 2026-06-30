<?php

namespace App\Livewire\Kehadiran;

use App\Actions\ClockIn;
use App\Enums\ModeKerja;
use App\Models\Perusahaan;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * Kartu clock-in yang ditanam pada modal "absence gate" (tak bisa ditutup).
 * Setelah clock-in berhasil, halaman dimuat ulang agar gerbang terbuka.
 * Lihat permintaan: non-supervisor wajib absen sebelum memakai aplikasi.
 */
class AbsenGate extends Component
{
    public string $mode = ModeKerja::ONSITE->value;

    public string $alasan = '';

    public function mount(): void
    {
        $tipe = auth()->user()->employment_type?->value ?? 'CONTRACT';
        $this->mode = config('kehadiran.mode_default_per_tipe')[$tipe] ?? ModeKerja::ONSITE->value;
    }

    public function clockIn(ClockIn $clockIn)
    {
        try {
            $clockIn->handle(
                auth()->user(),
                ModeKerja::from($this->mode),
                $this->alasan,
                request()->ip(),
            );
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->getMessageBag());

            return null;
        }

        // Muat ulang penuh agar layout mengevaluasi ulang "absence gate".
        return $this->redirect(route('dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.kehadiran.absen-gate', [
            'daftarMode' => ModeKerja::cases(),
            'jamMasuk' => Perusahaan::current()->jamMasuk(),
            'jamPulang' => Perusahaan::current()->jamPulang(),
            'tz' => config('kehadiran.timezone'),
        ]);
    }
}
