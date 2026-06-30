<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kebijakan kehadiran yang dapat dikonfigurasi Super Admin lewat Profil Perusahaan
 * (jam masuk/pulang & toleransi). Sebelumnya hanya di config/kehadiran.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kf_perusahaan', function (Blueprint $table) {
            $table->string('jam_masuk', 5)->default('09:00')->after('tagline');
            $table->string('jam_pulang', 5)->default('17:00')->after('jam_masuk');
            $table->unsignedSmallInteger('toleransi_menit')->default(15)->after('jam_pulang');
        });
    }

    public function down(): void
    {
        Schema::table('kf_perusahaan', function (Blueprint $table) {
            $table->dropColumn(['jam_masuk', 'jam_pulang', 'toleransi_menit']);
        });
    }
};
