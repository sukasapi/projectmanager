<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Rekap kehadiran harian via cron `schedule:run` (cPanel-safe, tanpa daemon).
// Jalan tiap malam 23:55 WIB: lengkapi clock-out tepat waktu + tandai ALPHA hari itu.
Schedule::command('kehadiran:rekap')->dailyAt('23:55')->timezone('Asia/Jakarta');
