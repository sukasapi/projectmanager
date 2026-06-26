<?php

use App\Enums\TaskStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kf_tugas_pascaproduksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('kf_proyek')->cascadeOnDelete();
            // scene_id opsional: sebagian tugas pasca berskala proyek (mis. Mastering Final Render).
            $table->foreignId('scene_id')->nullable()->constrained('kf_adegan')->nullOnDelete();
            $table->string('task_type'); // mis. Editing Offline, Editing Online, Mastering
            $table->foreignId('artist_id')->nullable()->constrained('kf_pengguna')->nullOnDelete();
            $table->date('deadline')->nullable();
            $table->string('status')->default(TaskStatus::NOT_STARTED->value)->index();
            $table->string('file_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kf_tugas_pascaproduksi');
    }
};
