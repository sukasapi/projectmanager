<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tanggal mulai & deadline tiap pekerjaan (shot-task & tahap-task). Lihat WORKFLOW.md §5.
 * (kf_tugas_tahap sudah punya 'deadline'; tinggal tambah 'start_date'.)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kf_tugas_shot', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('status');
            $table->date('deadline')->nullable()->after('start_date');
        });

        Schema::table('kf_tugas_tahap', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('kf_tugas_shot', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'deadline']);
        });
        Schema::table('kf_tugas_tahap', function (Blueprint $table) {
            $table->dropColumn('start_date');
        });
    }
};
