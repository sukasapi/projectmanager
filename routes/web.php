<?php

use App\Livewire\Auth\Login;
use App\Livewire\Kehadiran\Absen;
use App\Livewire\Kehadiran\Riwayat;
use App\Livewire\Laporan\Kehadiran as LaporanKehadiran;
use App\Livewire\Logbook\Harian as LogbookHarian;
use App\Livewire\Logbook\Review as LogbookReview;
use App\Livewire\Monitoring\Dasbor as MonitoringDasbor;
use App\Livewire\Proyek\DaftarProyek;
use App\Livewire\ShotMatrix;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/login', Login::class)->middleware('guest')->name('login');

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', ShotMatrix::class)->name('shot-matrix');
    Route::get('/proyek', DaftarProyek::class)->name('proyek');

    // Modul lain — scaffold halaman (akan diisi pada iterasi berikutnya).
    Route::view('/pra-produksi', 'pages.segera', [
        'judul' => 'Pra-Produksi',
        'deskripsi' => 'Script, Shotlist, Storyboard, Animatic, Scoring, VO.',
    ])->name('pra-produksi');

    Route::view('/aset', 'pages.segera', [
        'judul' => 'Asset Library',
        'deskripsi' => 'Character, Environment, Property — Modeling, Texturing, Rigging.',
    ])->name('aset');

    Route::view('/pasca-produksi', 'pages.segera', [
        'judul' => 'Pasca-Produksi',
        'deskripsi' => 'Editing Offline, Editing Online, Mastering Final Render.',
    ])->name('pasca-produksi');

    Route::view('/tim', 'pages.segera', [
        'judul' => 'Tim & Artis',
        'deskripsi' => 'Manajemen pengguna terdaftar beserta jenis kepegawaian.',
    ])->name('tim');

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
