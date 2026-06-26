<?php

use App\Enums\RevisionStatus;
use App\Enums\TaskStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kf_tugas_shot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shot_id')->constrained('kf_shot')->cascadeOnDelete();
            $table->string('task_type'); // LAYOUT | ANIMATE | SIMULATE | LRC
            $table->string('status')->default(TaskStatus::NOT_STARTED->value)->index();
            $table->date('post_date')->nullable();
            $table->string('preview_url')->nullable(); // tautan video .mp4/.mov
            $table->text('revision_notes')->nullable();
            $table->string('revision_status')->default(RevisionStatus::NONE->value);
            $table->timestamps();

            // Satu jenis tahap unik per shot (tidak ada dua "ANIMATE" pada shot yang sama).
            $table->unique(['shot_id', 'task_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kf_tugas_shot');
    }
};
