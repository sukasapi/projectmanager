<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kf_komentar', function (Blueprint $table) {
            $table->id();
            $table->morphs('subjek'); // TugasShot / TugasTahap
            $table->foreignId('parent_id')->nullable()->constrained('kf_komentar')->cascadeOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('kf_pengguna')->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kf_komentar');
    }
};
