<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivot many-to-many: satu ShotTask dapat dikerjakan banyak artis
     * (mis. ANIMATE = Ikmal + Nando). Tabel tugas lain memakai artist_id tunggal.
     */
    public function up(): void
    {
        Schema::create('kf_penugasan_shot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shot_task_id')->constrained('kf_tugas_shot')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('kf_pengguna')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['shot_task_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kf_penugasan_shot');
    }
};
