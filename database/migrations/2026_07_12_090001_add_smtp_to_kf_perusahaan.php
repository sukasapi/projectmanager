<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Konfigurasi SMTP di UI (Konfigurasi Website). Password disimpan terenkripsi
 * (cast 'encrypted' di model). Diterapkan runtime menimpa .env bila diisi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kf_perusahaan', function (Blueprint $table) {
            $table->string('smtp_host')->nullable()->after('toleransi_menit');
            $table->unsignedInteger('smtp_port')->nullable()->after('smtp_host');
            $table->string('smtp_username')->nullable()->after('smtp_port');
            $table->text('smtp_password')->nullable()->after('smtp_username'); // terenkripsi
            $table->string('smtp_encryption')->nullable()->after('smtp_password'); // tls | ssl | null
            $table->string('smtp_from_address')->nullable()->after('smtp_encryption');
            $table->string('smtp_from_name')->nullable()->after('smtp_from_address');
        });
    }

    public function down(): void
    {
        Schema::table('kf_perusahaan', function (Blueprint $table) {
            $table->dropColumn(['smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption', 'smtp_from_address', 'smtp_from_name']);
        });
    }
};
