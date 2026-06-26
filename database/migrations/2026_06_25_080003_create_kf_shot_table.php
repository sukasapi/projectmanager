<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kf_shot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scene_id')->constrained('kf_adegan')->cascadeOnDelete();
            $table->string('shot_code'); // mis. SC01_SH01
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['scene_id', 'shot_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kf_shot');
    }
};
