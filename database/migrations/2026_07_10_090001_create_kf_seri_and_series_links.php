<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tier C6 — Seri/Judul sebagai induk Episode + reuse aset lintas-episode.
 * - kf_seri: wadah judul (banyak episode).
 * - kf_proyek.series_id: episode bernaung dalam seri.
 * - kf_aset.series_id: aset "bersama" milik seri (dapat dipakai lintas episode via kf_aset_shot).
 * Lihat 2026-07-01_roadmap-produksi-tier-c.md §C6.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kf_seri', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('kf_proyek', function (Blueprint $table) {
            $table->foreignId('series_id')->nullable()->after('client_id')->constrained('kf_seri')->nullOnDelete();
        });

        Schema::table('kf_aset', function (Blueprint $table) {
            $table->foreignId('series_id')->nullable()->after('project_id')->constrained('kf_seri')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('kf_aset', function (Blueprint $table) {
            $table->dropConstrainedForeignId('series_id');
        });
        Schema::table('kf_proyek', function (Blueprint $table) {
            $table->dropConstrainedForeignId('series_id');
        });
        Schema::dropIfExists('kf_seri');
    }
};
