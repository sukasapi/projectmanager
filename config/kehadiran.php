<?php

/*
|--------------------------------------------------------------------------
| Konfigurasi Modul Kehadiran / Logbook / Monitoring
|--------------------------------------------------------------------------
|
| Semua waktu disimpan UTC; aturan jam kerja dievaluasi di zona WIB.
| Lihat ABSENSI.md untuk desain lengkap.
|
*/

return [
    // Zona waktu acuan aturan jam kerja (penyimpanan tetap UTC).
    'timezone' => 'Asia/Jakarta',

    // Jam kerja standar (WIB).
    'jam_masuk' => '09:00',
    'jam_pulang' => '17:00',

    // Toleransi keterlambatan (menit) sebelum di-flag TERLAMBAT.
    'toleransi_menit' => 15,

    // Apakah jam masuk 09:00 ditegakkan per jenis kepegawaian.
    // Freelance: tidak ditegakkan (hanya dicatat) — keputusan user 2026-06-26.
    'tegakkan_jam_per_tipe' => [
        'CONTRACT' => true,
        'FREELANCE' => false,
        'INTERN' => true,
    ],

    // Mode kerja default per jenis kepegawaian (boleh ditimpa per hari saat clock-in).
    'mode_default_per_tipe' => [
        'CONTRACT' => 'ONSITE',
        'FREELANCE' => 'OFFSITE',
        'INTERN' => 'ONSITE',
    ],

    // Geotag offsite belum diaktifkan (IP saja). Kolom lat/lng sudah disiapkan.
    'geotag_aktif' => false,

    // Ambang "online sekarang" untuk presence ringan (Opsi C), dalam menit.
    'ambang_online_menit' => 5,

    // Interval heartbeat di sisi klien (ms) — hanya saat tab terlihat.
    'heartbeat_interval_ms' => 120000,
];
