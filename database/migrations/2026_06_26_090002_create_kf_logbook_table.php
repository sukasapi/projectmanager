<?php

use App\Enums\StatusLogbook;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kf_logbook', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('kf_pengguna')->cascadeOnDelete();
            $table->date('tanggal');
            $table->dateTime('jam_mulai');
            $table->dateTime('jam_selesai');
            $table->unsignedInteger('durasi_menit')->default(0); // turunan (Observer)
            $table->foreignId('shot_task_id')->nullable()->constrained('kf_tugas_shot')->nullOnDelete();
            $table->text('deskripsi');
            $table->string('output_url')->nullable();
            $table->string('status')->default(StatusLogbook::DRAFT->value)->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('kf_pengguna')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kf_logbook');
    }
};
