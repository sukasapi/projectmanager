<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Metadata shotlist yang dibawa ke shot Produksi (VO, Visual, Detail Visual, Type of Shot,
 * Movement, Karakter, dll) agar tampil sebagai referensi bagi tim produksi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kf_shot', function (Blueprint $table) {
            $table->json('meta')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('kf_shot', function (Blueprint $table) {
            $table->dropColumn('meta');
        });
    }
};
