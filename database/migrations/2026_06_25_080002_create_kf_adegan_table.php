<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kf_adegan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('kf_proyek')->cascadeOnDelete();
            $table->string('scene_name');
            // Nilai turunan: akumulasi durasi seluruh shot (detik). Dihitung server, jangan diisi UI.
            $table->unsignedInteger('total_duration')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kf_adegan');
    }
};
