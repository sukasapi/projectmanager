<?php

use App\Enums\ModeKerja;
use App\Enums\StatusKehadiran;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kf_kehadiran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('kf_pengguna')->cascadeOnDelete();
            $table->date('tanggal'); // tanggal kerja (WIB)
            $table->dateTime('clock_in')->nullable();
            $table->dateTime('clock_out')->nullable();
            $table->string('work_mode')->default(ModeKerja::ONSITE->value); // ONSITE | OFFSITE
            $table->string('status')->default(StatusKehadiran::HADIR->value)->index();
            $table->unsignedInteger('work_duration_minutes')->default(0); // turunan (Observer)
            $table->string('clock_in_ip', 45)->nullable();
            $table->string('clock_out_ip', 45)->nullable();
            // Geotag offsite disiapkan tapi belum diaktifkan (config kehadiran.geotag_aktif).
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('catatan')->nullable(); // alasan offsite/izin/sakit
            $table->foreignId('approved_by')->nullable()->constrained('kf_pengguna')->nullOnDelete();
            $table->timestamps();

            // Satu baris kehadiran per user per hari.
            $table->unique(['user_id', 'tanggal']);
            $table->index('tanggal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kf_kehadiran');
    }
};
