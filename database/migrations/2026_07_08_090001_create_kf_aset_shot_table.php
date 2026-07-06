<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Breakdown Aset ⇄ Shot (Tier C1): daftar aset (karakter/env/prop) yang dipakai
 * sebuah shot. Jadi shot bisa menampilkan kesiapan aset yang menjadi prasyaratnya.
 * Lihat 2026-07-01_roadmap-produksi-tier-c.md §C1.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kf_aset_shot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aset_id')->constrained('kf_aset')->cascadeOnDelete();
            $table->foreignId('shot_id')->constrained('kf_shot')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['aset_id', 'shot_id']);
            $table->index('shot_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kf_aset_shot');
    }
};
