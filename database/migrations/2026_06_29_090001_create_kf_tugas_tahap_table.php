<?php

use App\Enums\TaskStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tugas tahap level-EPISODE (Pra-Produksi & Pasca-Produksi) — satu baris per
 * (episode, tahap). Bentuk tracker yang sama dengan Produksi, namun per episode
 * (bukan per shot). Lihat PIPELINE.md §3, §3.1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kf_tugas_tahap', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('kf_proyek')->cascadeOnDelete();
            $table->foreignId('tahap_id')->constrained('kf_tahap')->cascadeOnDelete();
            $table->foreignId('artist_id')->nullable()->constrained('kf_pengguna')->nullOnDelete();
            $table->string('status')->default(TaskStatus::NOT_STARTED->value)->index();
            $table->text('deskripsi')->nullable();
            $table->string('file_url')->nullable();
            $table->date('deadline')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['project_id', 'tahap_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kf_tugas_tahap');
    }
};
