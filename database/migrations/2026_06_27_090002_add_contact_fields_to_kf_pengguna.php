<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Data kontak & lokasi artis: telepon/WhatsApp, alamat, posisi, dan koordinat
 * (opsional — untuk pemilihan titik di peta). Lihat permintaan #10.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kf_pengguna', function (Blueprint $table) {
            $table->string('phone', 30)->nullable()->after('role');
            $table->string('whatsapp', 30)->nullable()->after('phone');
            $table->text('address')->nullable()->after('whatsapp');
            $table->decimal('latitude', 10, 7)->nullable()->after('address');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('kf_pengguna', function (Blueprint $table) {
            $table->dropColumn(['phone', 'whatsapp', 'address', 'latitude', 'longitude']);
        });
    }
};
