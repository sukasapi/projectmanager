<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Rekap kehadiran harian via cron `schedule:run` (cPanel-safe, tanpa daemon).
// Jalan tiap hari pukul 18:00 WIB (setelah jam kerja berakhir) untuk menandai ALPHA.
Schedule::command('kehadiran:rekap')->dailyAt('18:00')->timezone('Asia/Jakarta');
