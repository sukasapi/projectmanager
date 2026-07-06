<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Baris shotlist per episode. Nilai tiap kolom disimpan di `data` (JSON) — fleksibel
 * mengikuti definisi kolom studio. shot_id terisi setelah "Generate ke Produksi".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kf_shotlist', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('kf_proyek')->cascadeOnDelete();
            $table->unsignedInteger('urutan')->default(0);
            $table->json('data')->nullable();           // {kolomKey: nilai, ...}
            $table->foreignId('shot_id')->nullable()->constrained('kf_shot')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kf_shotlist');
    }
};
