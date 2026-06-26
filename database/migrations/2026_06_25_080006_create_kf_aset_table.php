<?php

use App\Enums\TaskStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kf_aset', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('kf_proyek')->cascadeOnDelete();
            $table->string('type');  // CHARACTER | ENVIRONMENT | PROPERTY
            $table->string('name');
            $table->string('task');  // MODELING | TEXTURING | RIGGING
            // artist_id tunggal; nullable + nullOnDelete agar riwayat aset tetap utuh jika artis dihapus.
            $table->foreignId('artist_id')->nullable()->constrained('kf_pengguna')->nullOnDelete();
            $table->string('status')->default(TaskStatus::NOT_STARTED->value)->index();
            $table->string('file_url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kf_aset');
    }
};
