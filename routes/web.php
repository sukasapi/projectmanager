<?php

use App\Http\Controllers\ProductionBibleController;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\LupaPassword;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Dashboard;
use App\Livewire\Jadwal;
use App\Livewire\Kehadiran\Absen;
use App\Livewire\Kehadiran\Riwayat;
use App\Livewire\Laporan\Kehadiran as LaporanKehadiran;
use App\Livewire\Laporan\Progress as LaporanProgress;
use App\Livewire\Logbook\Harian as LogbookHarian;
use App\Livewire\Logbook\Review as LogbookReview;
use App\Livewire\Monitoring\Dasbor as MonitoringDasbor;
use App\Livewire\Notifikasi\Daftar as NotifikasiDaftar;
use App\Livewire\Pencarian;
use App\Livewire\Pengaturan\Indeks as PengaturanIndeks;
use App\Livewire\Pengaturan\Log as PengaturanLog;
use App\Livewire\Pengaturan\Pipeline as PengaturanPipeline;
use App\Livewire\Produksi\PascaProduksi;
use App\Livewire\Produksi\PraProduksi;
use App\Livewire\Proyek\DaftarProyek;
use App\Livewire\Pustaka;
use App\Livewire\ShotMatrix;
use App\Livewire\Tim\DaftarTim;
use App\Livewire\Tim\DetailArtis;
use App\Livewire\TugasSaya;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/login', Login::class)->middleware('guest')->name('login');

Route::middleware('guest')->group(function () {
    Route::get('/forgot-password', LupaPassword::class)->name('password.request');
    Route::get('/reset-password/{token}', ResetPassword::class)->name('password.reset');
});

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', Dashboard::class)->name('dashboard');
    Route::get('/shot-matrix', ShotMatrix::class)->name('shot-matrix');
    Route::get('/proyek', DaftarProyek::class)->name('proyek');
    Route::get('/proyek/{proyek}/bible.pdf', ProductionBibleController::class)->name('proyek.bible');

    Route::get('/pra-produksi', PraProduksi::class)->name('pra-produksi');
    Route::get('/pasca-produksi', PascaProduksi::class)->name('pasca-produksi');

    // Asset Library — daftar tautan file seluruh proses (tree per episode).
    Route::get('/aset', Pustaka::class)->name('aset');

    Route::get('/notifikasi', NotifikasiDaftar::class)->name('notifikasi');

    Route::get('/tugas-saya', TugasSaya::class)->name('tugas-saya');
    Route::get('/cari', Pencarian::class)->name('cari');
    Route::get('/jadwal', Jadwal::class)->name('jadwal');
    Route::get('/laporan/progress', LaporanProgress::class)->name('laporan-progress');

    Route::get('/tim', DaftarTim::class)->name('tim');
    Route::get('/tim/{user}', DetailArtis::class)->name('tim.detail');

    Route::get('/pengaturan', PengaturanIndeks::class)->name('pengaturan');
    Route::get('/pengaturan/pipeline', PengaturanPipeline::class)->name('pengaturan.pipeline');
    Route::get('/pengaturan/log', PengaturanLog::class)->name('pengaturan.log');

    // --- Modul Kehadiran / Logbook / Monitoring (ABSENSI.md) ---

    // Heartbeat presence ringan (Opsi C): endpoint kecil, hanya update last_active_at.
    // Bukan komponen Livewire — minim overhead di shared hosting.
    Route::post('/heartbeat', function (Request $request) {
        User::whereKey($request->user()->id)->update(['last_active_at' => now()]);

        return response()->noContent();
    })->name('heartbeat');

    Route::get('/kehadiran', Absen::class)->name('kehadiran');
    Route::get('/kehadiran/riwayat', Riwayat::class)->name('kehadiran.riwayat');

    Route::get('/logbook', LogbookHarian::class)->name('logbook');
    Route::get('/logbook/review', LogbookReview::class)->name('logbook.review');

    Route::get('/monitoring', MonitoringDasbor::class)->name('monitoring');
    Route::get('/laporan/kehadiran', LaporanKehadiran::class)->name('laporan-kehadiran');
});
