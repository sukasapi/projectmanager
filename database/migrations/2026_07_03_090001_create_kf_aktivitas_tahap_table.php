<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riwayat (history) perjalanan pekerjaan tahap Pra/Pasca — append-only.
 * Mulai/Propose/Approve/Reject dengan alasan. Lihat WORKFLOW.md §4.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kf_aktivitas_tahap', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tugas_tahap_id')->constrained('kf_tugas_tahap')->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('kf_pengguna')->nullOnDelete();
            $table->string('kind'); // MULAI | PROPOSE | APPROVE | REJECT
            $table->text('note')->nullable();
            $table->string('status_from')->nullable();
            $table->string('status_to')->nullable();
            $table->timestamps();

            $table->index('tugas_tahap_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kf_aktivitas_tahap');
    }
};
