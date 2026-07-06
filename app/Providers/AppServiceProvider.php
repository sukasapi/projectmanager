<?php

namespace App\Providers;

use App\Enums\EmploymentType;
use App\Models\Perusahaan;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Throwable;

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

        // Pemantauan (monitoring, review logbook) & Tim & Artis: Supervisor/Super Admin ATAU Team Lead.
        // Team Lead sebagai perwakilan Supervisor, cakupan studio-wide. Lihat 2026-07-01_peran-team-lead-hak-akses.md.
        Gate::define('review-logbook', fn (User $user) => $user->bisaPemantauan());
        Gate::define('monitor-kehadiran', fn (User $user) => $user->bisaPemantauan());
        Gate::define('view-tim', fn (User $user) => $user->bisaPemantauan());

        // Setup/kelola episode (assign artis, scene/shot): tetap Supervisor/Super Admin.
        // (Kewenangan Team Lead per-episode ditangani Proyek::dapatDikelola via team_lead_id.)
        Gate::define('manage-tim', fn (User $user) => $user->isSupervisory());

        // Konfigurasi aplikasi (template pipeline global, profil & kebijakan perusahaan): khusus Super Admin.
        Gate::define('manage-config', fn (User $user) => $user->isSuperAdmin());

        // Terapkan SMTP dari DB (Konfigurasi Website) menimpa .env, bila tabel ada & terisi.
        // Read-only (first, bukan firstOrCreate) agar boot tidak menulis baris tiap request.
        try {
            if (Schema::hasTable('kf_perusahaan')) {
                Perusahaan::query()->first()?->terapkanMail();
            }
        } catch (Throwable) {
            // Abaikan (mis. saat migrasi belum dijalankan).
        }
    }
}
