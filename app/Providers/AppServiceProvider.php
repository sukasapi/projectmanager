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

        // Hak supervisi (produksi, tim, monitoring, review logbook): Supervisor atau Super Admin.
        Gate::define('review-logbook', fn (User $user) => $user->isSupervisory());
        Gate::define('monitor-kehadiran', fn (User $user) => $user->isSupervisory());
        Gate::define('manage-tim', fn (User $user) => $user->isSupervisory());

        // Konfigurasi aplikasi (pipeline, profil & kebijakan perusahaan): khusus Super Admin.
        Gate::define('manage-config', fn (User $user) => $user->isSuperAdmin());
    }
}
