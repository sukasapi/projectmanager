<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kf_versi_kiriman', function (Blueprint $table) {
            $table->id();
            $table->morphs('subjek'); // TugasShot / TugasTahap
            $table->unsignedInteger('version');
            $table->string('url', 2048);
            $table->string('catatan', 500)->nullable();
            $table->foreignId('author_id')->nullable()->constrained('kf_pengguna')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kf_versi_kiriman');
    }
};
