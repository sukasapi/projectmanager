<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catatan review terstruktur per shot-task (Tier C2): note dengan rentang frame
 * (opsional) + status open/addressed/resolved. Menggantikan catatan teks bebas
 * saat dailies. Lihat 2026-07-01_roadmap-produksi-tier-c.md §C2.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kf_catatan_review', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shot_task_id')->constrained('kf_tugas_shot')->cascadeOnDelete();
            $table->unsignedInteger('frame_start')->nullable();
            $table->unsignedInteger('frame_end')->nullable();
            $table->text('body');
            $table->string('status')->default('open'); // open | resolved
            $table->foreignId('author_id')->nullable()->constrained('kf_pengguna')->nullOnDelete();
            $table->timestamps();

            $table->index(['shot_task_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kf_catatan_review');
    }
};
