<?php

use App\Enums\LevelTahap;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Definisi tahap produksi yang dapat dikonfigurasi admin (template studio global).
 * Menggantikan enum ShotTaskType yang hardcoded. Lihat PIPELINE.md §2.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kf_tahap', function (Blueprint $table) {
            $table->id();
            $table->string('phase');                 // PRA | PRODUKSI | PASCA (App\Enums\FaseProduksi)
            $table->string('level')->default(LevelTahap::EPISODE->value); // EPISODE | SHOT
            $table->string('code');                  // slug unik per phase
            $table->string('name');
            $table->unsignedInteger('urutan')->default(0);
            $table->foreignId('requires_tahap_id')->nullable()->constrained('kf_tahap')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['phase', 'code']);
            $table->index(['phase', 'level', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kf_tahap');
    }
};
