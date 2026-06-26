<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kaitkan Episode dengan Klien (nullable — episode boleh tanpa klien).
     */
    public function up(): void
    {
        Schema::table('kf_proyek', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->after('status')
                ->constrained('kf_klien')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('kf_proyek', function (Blueprint $table) {
            $table->dropConstrainedForeignId('client_id');
        });
    }
};
