<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tier C3 — kapasitas & estimasi untuk perencanaan.
 * - estimasi_hari: perkiraan lama pengerjaan per tugas (shot & tahap).
 * - kapasitas_hari: kapasitas hari kerja per minggu tiap artis (default 5).
 * Alokasi vs kapasitas bersifat INFORMASIONAL (overload diperbolehkan, hanya ditandai).
 * Lihat 2026-07-01_roadmap-produksi-tier-c.md §C3.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kf_tugas_shot', function (Blueprint $table) {
            $table->unsignedSmallInteger('estimasi_hari')->nullable()->after('deadline');
        });
        Schema::table('kf_tugas_tahap', function (Blueprint $table) {
            $table->unsignedSmallInteger('estimasi_hari')->nullable()->after('deadline');
        });
        Schema::table('kf_pengguna', function (Blueprint $table) {
            $table->unsignedSmallInteger('kapasitas_hari')->default(5)->after('employment_type');
        });
    }

    public function down(): void
    {
        Schema::table('kf_tugas_shot', fn (Blueprint $t) => $t->dropColumn('estimasi_hari'));
        Schema::table('kf_tugas_tahap', fn (Blueprint $t) => $t->dropColumn('estimasi_hari'));
        Schema::table('kf_pengguna', fn (Blueprint $t) => $t->dropColumn('kapasitas_hari'));
    }
};
