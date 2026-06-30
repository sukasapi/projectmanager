<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notifikasi in-app (cPanel-safe, tanpa daemon). Dibuat saat aksi (publish, propose,
 * approve/reject); lonceng topbar memakai wire:poll. Lihat WORKFLOW.md §3.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kf_notifikasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('kf_pengguna')->cascadeOnDelete();
            $table->string('type')->default('info'); // info|publish|propose|approve|reject
            $table->string('title');
            $table->string('message', 500)->nullable();
            $table->string('url')->nullable();
            $table->dateTime('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kf_notifikasi');
    }
};
