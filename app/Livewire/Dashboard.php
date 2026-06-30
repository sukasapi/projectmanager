<?php

namespace App\Livewire;

use App\Enums\ProjectStatus;
use App\Enums\StatusLogbook;
use App\Enums\TaskStatus;
use App\Models\Kehadiran;
use App\Models\Logbook;
use App\Models\Notifikasi;
use App\Models\Proyek;
use App\Models\RevisiShot;
use App\Models\TugasShot;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Beranda: ringkasan produksi + status kehadiran hari ini + tugas saya + aktivitas.
 * Halaman pendaratan setelah login. Lihat UI.md §8.4.
 */
#[Layout('components.layouts.app')]
class Dashboard extends Component
{
    /** Picu popup SweetAlert sekali bila ada notifikasi belum dibaca. */
    public function mount(): void
    {
        $belum = Notifikasi::where('user_id', auth()->id())->whereNull('read_at')->count();

        if ($belum > 0) {
            $this->dispatch('notif-popup', count: $belum, url: route('notifikasi'));
        }
    }

    public function render()
    {
        $tz = config('kehadiran.timezone');
        $hari = Carbon::now($tz)->toDateString();
        $user = auth()->user();
        $isAdmin = Gate::allows('monitor-kehadiran');

        // Tugas shot milik saya yang belum selesai.
        $tugasSaya = $user->tugasShot()
            ->with('shot')
            ->where('status', '!=', TaskStatus::APPROVED->value)
            ->get();

        return view('livewire.dashboard', [
            'tz' => $tz,
            'user' => $user,
            'isAdmin' => $isAdmin,
            'kehadiranHariIni' => Kehadiran::where('user_id', $user->id)->where('tanggal', $hari)->first(),
            'notifBelum' => Notifikasi::where('user_id', $user->id)->whereNull('read_at')->count(),
            'tugasSaya' => $tugasSaya,
            'statProyekAktif' => Proyek::where('status', ProjectStatus::IN_PROGRESS->value)->count(),
            'statDikerjakan' => TugasShot::where('status', TaskStatus::IN_PROGRESS->value)->count(),
            'statReview' => TugasShot::where('status', TaskStatus::REVIEW->value)->count(),
            'aktivitas' => RevisiShot::with(['tugasShot.shot', 'author'])->latest()->limit(6)->get(),
            // Ringkasan admin.
            'hadirHariIni' => $isAdmin ? Kehadiran::where('tanggal', $hari)->count() : 0,
            'logbookMenunggu' => $isAdmin ? Logbook::where('status', StatusLogbook::DIKIRIM->value)->count() : 0,
        ]);
    }
}
