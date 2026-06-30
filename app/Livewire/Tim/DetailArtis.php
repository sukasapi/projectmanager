<?php

namespace App\Livewire\Tim;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Detail seorang artis: beban kerja (penugasan lintas pipeline) + kehadiran &
 * logbook terbaru. Akses: Supervisor (manage-tim) atau artis itu sendiri. Lihat UI.md §8.15.
 */
#[Layout('components.layouts.app')]
class DetailArtis extends Component
{
    public User $artis;

    public function mount(User $user): void
    {
        abort_unless(Gate::allows('manage-config') || $user->id === auth()->id(), 403);
        $this->artis = $user;
    }

    public function render()
    {
        $tz = config('kehadiran.timezone');

        $this->artis->loadCount(['tugasShot', 'tugasPraproduksi', 'aset', 'tugasPascaproduksi']);

        return view('livewire.tim.detail-artis', [
            'tz' => $tz,
            'tugasShot' => $this->artis->tugasShot()->with('shot')->get(),
            'tugasPra' => $this->artis->tugasPraproduksi()->get(),
            'aset' => $this->artis->aset()->get(),
            'tugasPasca' => $this->artis->tugasPascaproduksi()->get(),
            'kehadiranTerbaru' => $this->artis->kehadiran()->orderByDesc('tanggal')->limit(7)->get(),
            'logbookTerbaru' => $this->artis->logbook()->orderByDesc('tanggal')->limit(7)->get(),
        ]);
    }
}
