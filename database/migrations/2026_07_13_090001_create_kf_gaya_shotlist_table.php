<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Gaya (style) Shotlist — tiap gaya punya susunan kolom sendiri. Seri memilih satu
 * gaya; seluruh episode seri itu memakai kolom milik gaya tsb. Episode tanpa seri /
 * seri tanpa pilihan memakai gaya default. Lihat docs/2026-07-13_shotlist-style.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kf_gaya_shotlist', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        // Kolom shotlist menjadi milik satu gaya.
        Schema::table('kf_kolom_shotlist', function (Blueprint $table) {
            $table->foreignId('style_id')->nullable()->after('id')
                ->constrained('kf_gaya_shotlist')->nullOnDelete();
        });

        // Seri memilih gaya shotlist (berlaku seluruh episodenya).
        Schema::table('kf_seri', function (Blueprint $table) {
            $table->foreignId('shotlist_style_id')->nullable()->after('description')
                ->constrained('kf_gaya_shotlist')->nullOnDelete();
        });

        // Data lama: buat gaya "Standar Studio" sebagai default dan tautkan semua kolom yang ada.
        if (DB::table('kf_kolom_shotlist')->whereNull('style_id')->exists()) {
            $id = DB::table('kf_gaya_shotlist')->insertGetId([
                'name' => 'Standar Studio',
                'description' => 'Gaya bawaan — kolom shotlist yang sudah ada sebelum fitur multi-gaya.',
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('kf_kolom_shotlist')->whereNull('style_id')->update(['style_id' => $id]);
        }

        // Key kolom kini unik per gaya (bukan global).
        Schema::table('kf_kolom_shotlist', function (Blueprint $table) {
            $table->dropUnique(['key']);
            $table->unique(['style_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::table('kf_kolom_shotlist', function (Blueprint $table) {
            $table->dropUnique(['style_id', 'key']);
            $table->unique('key');
            $table->dropConstrainedForeignId('style_id');
        });
        Schema::table('kf_seri', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shotlist_style_id');
        });
        Schema::dropIfExists('kf_gaya_shotlist');
    }
};
