<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Konfigurasi tampilan aplikasi (Super Admin): nama aplikasi, footer, gambar login.
 * (logo_path sudah ada.) Lihat permintaan: nama aplikasi/logo/gambar login/footer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kf_perusahaan', function (Blueprint $table) {
            $table->string('app_name')->nullable()->after('name');
            $table->string('footer_text')->nullable()->after('tagline');
            $table->string('login_image_path')->nullable()->after('logo_path');
        });
    }

    public function down(): void
    {
        Schema::table('kf_perusahaan', function (Blueprint $table) {
            $table->dropColumn(['app_name', 'footer_text', 'login_image_path']);
        });
    }
};
