<?php

namespace App\Providers;

use App\Enums\EmploymentType;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Magang tidak boleh menyetujui tugas (selaras dengan TransitionShotTaskStatus).
        Gate::define('approve-shot-task', fn (User $user) => $user->employment_type !== EmploymentType::INTERN);

        // Review logbook & pemantauan kehadiran: hanya supervisor (peran 'Supervisor').
        // Lihat ABSENSI.md §8. (Penolakan review logbook sendiri ditangani di Action.)
        Gate::define('review-logbook', fn (User $user) => $user->role === 'Supervisor');
        Gate::define('monitor-kehadiran', fn (User $user) => $user->role === 'Supervisor');

        // Kelola anggota tim (buat/edit/aktif-nonaktif artis): hanya supervisor.
        Gate::define('manage-tim', fn (User $user) => $user->role === 'Supervisor');
    }
}
