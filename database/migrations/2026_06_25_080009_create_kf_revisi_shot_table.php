<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Riwayat revisi per ShotTask — append-only agar iterasi Animator <-> Supervisor
     * tidak menimpa catatan sebelumnya (lihat SRS 3.3).
     */
    public function up(): void
    {
        Schema::create('kf_revisi_shot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shot_task_id')->constrained('kf_tugas_shot')->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('kf_pengguna')->nullOnDelete();
            $table->string('kind')->default('NOTE'); // NOTE | REVISION_REQUEST | APPROVAL
            $table->text('note')->nullable();
            $table->string('status_from')->nullable();
            $table->string('status_to')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kf_revisi_shot');
    }
};
