<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Presence ringan (Opsi C): kolom tunggal di-update heartbeat untuk indikator
 * "online sekarang". Tanpa tabel sampel/timeline. Lihat ABSENSI.md §3.3.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kf_pengguna', function (Blueprint $table) {
            $table->dateTime('last_active_at')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('kf_pengguna', function (Blueprint $table) {
            $table->dropColumn('last_active_at');
        });
    }
};
