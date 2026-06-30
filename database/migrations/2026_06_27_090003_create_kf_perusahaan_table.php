<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Profil perusahaan/studio (singleton — satu baris). Dipakai untuk identitas
 * aplikasi & header Production Bible PDF. Lihat permintaan #9.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kf_perusahaan', function (Blueprint $table) {
            $table->id();
            $table->string('name');                 // nama brand (mis. Owlorix Creative Lab)
            $table->string('legal_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('website')->nullable();
            $table->text('address')->nullable();
            $table->string('logo_path')->nullable(); // path file logo (opsional)
            $table->string('tagline')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kf_perusahaan');
    }
};
