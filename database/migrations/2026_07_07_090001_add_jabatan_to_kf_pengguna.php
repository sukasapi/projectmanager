<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pisahkan PERAN (role: hak akses — Super Admin/Supervisor/Team Lead/Artis) dari
 * JABATAN (spesialisasi artis: Animator/Modeller/SLRC/Storyboard Artist, dll).
 * Lihat 2026-07-01_peran-team-lead-hak-akses.md §10.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kf_pengguna', function (Blueprint $table) {
            $table->string('jabatan')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('kf_pengguna', function (Blueprint $table) {
            $table->dropColumn('jabatan');
        });
    }
};
