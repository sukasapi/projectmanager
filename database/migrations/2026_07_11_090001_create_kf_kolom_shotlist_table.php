<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Definisi kolom Shotlist (studio-wide, dapat dimodifikasi admin). Tiap studio bisa
 * punya susunan/kebutuhan kolom berbeda. Kolom ber-peran (scene/shot_code/duration)
 * dipetakan untuk membuat shot; sisanya metadata. Lihat shotlist requirement 2026-07.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kf_kolom_shotlist', function (Blueprint $table) {
            $table->id();
            $table->string('key');                      // slug unik, kunci di JSON data
            $table->string('label');                    // judul kolom tampilan
            $table->string('tipe')->default('text');    // text | number | select
            $table->json('opsi')->nullable();           // pilihan untuk tipe select
            $table->string('peran')->nullable();        // scene | shot_code | duration | null
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique('key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kf_kolom_shotlist');
    }
};
