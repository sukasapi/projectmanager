<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Skenario (naskah) per episode — sumber untuk menyusun shotlist,
 * baik manual maupun di-generate AI (lihat docs/2026-07-09_shotlist-ai.md).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kf_proyek', function (Blueprint $table) {
            $table->longText('skenario')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('kf_proyek', function (Blueprint $table) {
            $table->dropColumn('skenario');
        });
    }
};
