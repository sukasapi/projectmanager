<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cegah nomor versi ganda pada satu subjek (race check-then-insert di PunyaVersi::catatVersi).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kf_versi_kiriman', function (Blueprint $table) {
            $table->unique(['subjek_type', 'subjek_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::table('kf_versi_kiriman', function (Blueprint $table) {
            $table->dropUnique(['subjek_type', 'subjek_id', 'version']);
        });
    }
};
