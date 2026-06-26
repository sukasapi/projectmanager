<?php

namespace App\Livewire\Kehadiran;

use App\Actions\ClockIn;
use App\Actions\ClockOut;
use App\Enums\ModeKerja;
use App\Models\Kehadiran;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Halaman Absen (clock-in / clock-out) untuk pengguna login.
 * Mode kerja onsite/offsite ditentukan saat clock-in (offsite wajib alasan).
 * Lihat UI.md §8.21, ABSENSI.md §7.
 */
#[Layout('components.layouts.app')]
class Absen extends Component
{
    public string $mode = ModeKerja::ONSITE->value;

    public string $alasan = '';

    public function mount(): void
    {
        $tipe = auth()->user()->employment_type?->value ?? 'CONTRACT';
        $this->mode = config('kehadiran.mode_default_per_tipe')[$tipe] ?? ModeKerja::ONSITE->value;
    }

    public function clockIn(): void
    {
        try {
            app(ClockIn::class)->handle(
                auth()->user(),
                ModeKerja::from($this->mode),
                $this->alasan,
                request()->ip(),
            );
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->getMessageBag());

            return;
        }

        $this->alasan = '';
        $this->dispatch('kehadiran-tersimpan');
    }

    public function clockOut(): void
    {
        try {
            app(ClockOut::class)->handle(auth()->user(), request()->ip());
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->getMessageBag());

            return;
        }

        $this->dispatch('kehadiran-tersimpan');
    }

    public function render()
    {
        $tanggal = Carbon::now(config('kehadiran.timezone'))->toDateString();

        $kehadiran = Kehadiran::where('user_id', auth()->id())
            ->where('tanggal', $tanggal)
            ->first();

        return view('livewire.kehadiran.absen', [
            'kehadiran' => $kehadiran,
            'daftarMode' => ModeKerja::cases(),
            'jamMasuk' => config('kehadiran.jam_masuk'),
            'jamPulang' => config('kehadiran.jam_pulang'),
            'tz' => config('kehadiran.timezone'),
        ]);
    }
}
