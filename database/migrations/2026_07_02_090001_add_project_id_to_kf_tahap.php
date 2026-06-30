<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pipeline per-episode (snapshot). project_id NULL = template global (Konfigurasi
 * Pipeline). project_id terisi = salinan milik episode tertentu, dibekukan saat
 * episode dibuat — perubahan template berikutnya hanya untuk episode baru.
 * Lihat WORKFLOW.md / PIPELINE.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kf_tahap', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('id')->constrained('kf_proyek')->cascadeOnDelete();
        });

        // Kode unik kini per (project_id, phase) — tiap episode boleh punya kode yang sama.
        Schema::table('kf_tahap', function (Blueprint $table) {
            $table->dropUnique(['phase', 'code']);
            $table->unique(['project_id', 'phase', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('kf_tahap', function (Blueprint $table) {
            $table->dropUnique(['project_id', 'phase', 'code']);
            $table->dropConstrainedForeignId('project_id');
            $table->unique(['phase', 'code']);
        });
    }
};
