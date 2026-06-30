<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Soft delete untuk model CRUD inti — penghapusan tidak permanen (dapat dipulihkan),
 * menjaga riwayat & integritas penugasan. Lihat permintaan fleksibilitas studio.
 */
return new class extends Migration
{
    private array $tabel = [
        'kf_proyek',
        'kf_adegan',
        'kf_shot',
        'kf_tugas_shot',
        'kf_aset',
        'kf_tugas_praproduksi',
        'kf_tugas_pascaproduksi',
        'kf_klien',
        'kf_logbook',
        'kf_kehadiran',
    ];

    public function up(): void
    {
        foreach ($this->tabel as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tabel as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
