<?php

use App\Models\Tahap;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Produksi dinamis (LANGKAH 9): kf_tugas_shot.task_type (enum) → tahap_id (FK ke kf_tahap).
 * Kolom matriks shot kini berasal dari tahap PRODUKSI/SHOT yang aktif. Lihat PIPELINE.md §2,§4.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Pastikan tahap shot default ada (untuk backfill yang aman, lepas dari urutan seeder).
        $animate = Tahap::firstOrCreate(
            ['phase' => 'PRODUKSI', 'code' => 'animate'],
            ['level' => 'SHOT', 'name' => 'Animate', 'urutan' => 1, 'is_active' => true],
        );
        Tahap::firstOrCreate(
            ['phase' => 'PRODUKSI', 'code' => 'simulate'],
            ['level' => 'SHOT', 'name' => 'Simulate', 'urutan' => 2, 'requires_tahap_id' => $animate->id, 'is_active' => true],
        );

        Schema::table('kf_tugas_shot', function (Blueprint $table) {
            $table->foreignId('tahap_id')->nullable()->after('shot_id')->constrained('kf_tahap')->nullOnDelete();
        });

        // Backfill: petakan task_type lama → tahap PRODUKSI berdasarkan kode (animate/simulate).
        if (Schema::hasColumn('kf_tugas_shot', 'task_type')) {
            $map = Tahap::where('phase', 'PRODUKSI')->pluck('id', 'code');
            foreach (DB::table('kf_tugas_shot')->get() as $row) {
                $id = $map[Str::slug($row->task_type)] ?? null;
                DB::table('kf_tugas_shot')->where('id', $row->id)->update(['tahap_id' => $id]);
            }

            // Tugas lama yang tak punya padanan tahap Produksi (mis. LAYOUT/LRC) dibuang.
            DB::table('kf_tugas_shot')->whereNull('tahap_id')->delete();

            // Tambah unique baru DULU (indeks ini melayani FK shot_id), baru drop yang lama —
            // jika tidak, MySQL menolak drop karena indeks lama dipakai FK shot_id.
            Schema::table('kf_tugas_shot', function (Blueprint $table) {
                $table->unique(['shot_id', 'tahap_id']);
            });
            Schema::table('kf_tugas_shot', function (Blueprint $table) {
                $table->dropUnique(['shot_id', 'task_type']);
                $table->dropColumn('task_type');
            });
        }
    }

    public function down(): void
    {
        Schema::table('kf_tugas_shot', function (Blueprint $table) {
            $table->dropUnique(['shot_id', 'tahap_id']);
            $table->string('task_type')->nullable()->after('shot_id');
            $table->dropConstrainedForeignId('tahap_id');
        });
    }
};
