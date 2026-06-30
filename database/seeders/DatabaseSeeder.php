<?php

namespace Database\Seeders;

use App\Enums\AssetTask;
use App\Enums\AssetType;
use App\Enums\EmploymentType;
use App\Enums\FaseProduksi;
use App\Enums\ModeKerja;
use App\Enums\ProjectStatus;
use App\Enums\RevisionStatus;
use App\Enums\StatusKehadiran;
use App\Enums\StatusLogbook;
use App\Enums\TaskStatus;
use App\Models\Adegan;
use App\Models\Aset;
use App\Models\Kehadiran;
use App\Models\Klien;
use App\Models\Logbook;
use App\Models\Notifikasi;
use App\Models\Perusahaan;
use App\Models\Proyek;
use App\Models\Shot;
use App\Models\Tahap;
use App\Models\TugasShot;
use App\Models\TugasTahap;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Data contoh untuk memverifikasi hierarki & relasi Langkah 1.
     */
    public function run(): void
    {
        // --- Tahap produksi default (configurable, proses #7) ---
        $this->call(TahapSeeder::class);

        // --- Profil perusahaan/studio (singleton) ---
        Perusahaan::create([
            'name' => 'Owlorix Creative Lab',
            'legal_name' => 'PT Owlorix Kreatif Nusantara',
            'email' => 'studio@owlorix.test',
            'phone' => '0274-555-0100',
            'website' => 'https://owlorix.test',
            'address' => 'Yogyakarta, Indonesia',
            'tagline' => 'Animation Production Studio',
        ]);

        // --- Artis terdaftar, satu per jenis kepegawaian ---
        $admin = User::create([
            'name' => 'Studio Admin',
            'email' => 'admin@animtrack.test',
            'password' => Hash::make('password'),
            'role' => 'Super Admin', // semua hak supervisor + konfigurasi aplikasi
            'employment_type' => EmploymentType::CONTRACT->value,
        ]);

        // Supervisor produksi (tanpa akses konfigurasi aplikasi).
        User::create([
            'name' => 'Rina (Supervisor)',
            'email' => 'supervisor@animtrack.test',
            'password' => Hash::make('password'),
            'role' => 'Supervisor',
            'employment_type' => EmploymentType::CONTRACT->value,
        ]);

        $ikmal = User::create([
            'name' => 'Ikmal',
            'email' => 'ikmal@animtrack.test',
            'password' => Hash::make('password'),
            'role' => 'Animator',
            'employment_type' => EmploymentType::CONTRACT->value,
        ]);

        $nando = User::create([
            'name' => 'Nando',
            'email' => 'nando@animtrack.test',
            'password' => Hash::make('password'),
            'role' => 'Animator',
            'employment_type' => EmploymentType::FREELANCE->value,
        ]);

        $sari = User::create([
            'name' => 'Sari',
            'email' => 'sari@animtrack.test',
            'password' => Hash::make('password'),
            'role' => 'Lighting',
            'employment_type' => EmploymentType::INTERN->value,
        ]);

        // --- Klien contoh ---
        $klien = Klien::create([
            'name' => 'MNC Animation',
            'contact_person' => 'Budi Santoso',
            'email' => 'budi@mncanimation.test',
            'phone' => '021-555-0100',
        ]);

        // --- Project/Episode contoh (dikaitkan dengan klien) ---
        // Cerita 23 = episode LENGKAP & SELESAI (semua tahap APPROVED).
        $proyek = Proyek::create([
            'name' => 'Cerita 23',
            'description' => 'Episode lengkap & selesai — semua tahap pra, produksi, dan pasca telah disetujui.',
            'status' => ProjectStatus::COMPLETED->value,
            'client_id' => $klien->id,
            'team_lead_id' => $ikmal->id,
            'published_at' => now()->subDays(20),
            'closed_at' => now()->subDays(1), // sudah closed
        ]);

        // Tahap Produksi level-SHOT dari SNAPSHOT episode ini (dibuat otomatis oleh ProyekObserver).
        $tahapShot = $proyek->tahap()->aktif()->fase(FaseProduksi::PRODUKSI)->where('level', 'SHOT')->urut()->get();

        // Helper: buat shot + semua sub-task tahap APPROVED, dengan penugasan opsional per kode tahap.
        $buatShotSelesai = function (Adegan $scene, string $code, int $detik, array $artisPerTahap = []) use ($tahapShot): Shot {
            $shot = Shot::create(['scene_id' => $scene->id, 'shot_code' => $code, 'duration_seconds' => $detik]);
            foreach ($tahapShot as $tahap) {
                $tg = TugasShot::create([
                    'shot_id' => $shot->id,
                    'tahap_id' => $tahap->id,
                    'status' => TaskStatus::APPROVED->value,
                    'revision_status' => RevisionStatus::OK->value,
                    'preview_url' => 'https://example.test/preview/'.strtolower($code).'-'.$tahap->code.'.mp4',
                    'post_date' => now(),
                ]);
                if (! empty($artisPerTahap[$tahap->code])) {
                    $tg->artists()->sync($artisPerTahap[$tahap->code]);
                }
            }

            return $shot;
        };

        // Scene 01 (196s) — penugasan jamak Animate SH01 = Ikmal + Nando.
        $scene = Adegan::create(['project_id' => $proyek->id, 'scene_name' => 'Scene 01']);
        $buatShotSelesai($scene, 'SC01_SH01', 96, ['animate' => [$ikmal->id, $nando->id], 'simulate' => [$sari->id]]);
        $buatShotSelesai($scene, 'SC01_SH02', 64, ['animate' => [$ikmal->id], 'simulate' => [$nando->id]]);
        $buatShotSelesai($scene, 'SC01_SH03', 36, ['animate' => [$nando->id]]);

        // Scene 02 (200s).
        $scene2 = Adegan::create(['project_id' => $proyek->id, 'scene_name' => 'Scene 02']);
        $buatShotSelesai($scene2, 'SC02_SH01', 120, ['animate' => [$ikmal->id], 'simulate' => [$sari->id]]);
        $buatShotSelesai($scene2, 'SC02_SH02', 80, ['animate' => [$nando->id]]);

        // Aset (semua selesai).
        $animate = $tahapShot->firstWhere('code', 'animate');
        $animateSh01 = TugasShot::query()
            ->whereHas('shot', fn ($q) => $q->where('shot_code', 'SC01_SH01'))
            ->where('tahap_id', $animate->id)
            ->first();

        // --- Contoh kehadiran HARI INI (untuk dasbor Monitoring & Laporan) ---
        $tz = config('kehadiran.timezone');
        $hari = Carbon::now($tz)->toDateString();
        $jam = fn (string $hm) => Carbon::createFromFormat('Y-m-d H:i', $hari.' '.$hm, $tz)->utc();

        // Ikmal (kontrak) hadir tepat waktu onsite, masih bekerja.
        Kehadiran::create([
            'user_id' => $ikmal->id, 'tanggal' => $hari, 'clock_in' => $jam('08:55'),
            'work_mode' => ModeKerja::ONSITE->value, 'status' => StatusKehadiran::HADIR->value, 'clock_in_ip' => '127.0.0.1',
        ]);
        // Nando (freelance) kerja offsite — jam tidak ditegakkan (tetap HADIR).
        Kehadiran::create([
            'user_id' => $nando->id, 'tanggal' => $hari, 'clock_in' => $jam('10:20'), 'clock_out' => $jam('16:30'),
            'work_mode' => ModeKerja::OFFSITE->value, 'status' => StatusKehadiran::HADIR->value,
            'catatan' => 'Kerja remote dari Bandung', 'clock_in_ip' => '127.0.0.1',
        ]);
        // Sari (magang) terlambat, onsite.
        Kehadiran::create([
            'user_id' => $sari->id, 'tanggal' => $hari, 'clock_in' => $jam('09:35'),
            'work_mode' => ModeKerja::ONSITE->value, 'status' => StatusKehadiran::TERLAMBAT->value, 'clock_in_ip' => '127.0.0.1',
        ]);

        // Presence "online sekarang" untuk sebagian artis.
        $ikmal->update(['last_active_at' => now()]);
        $sari->update(['last_active_at' => now()->subMinutes(2)]);

        // --- Contoh logbook (Freelance & Intern) ---
        Logbook::create([
            'user_id' => $nando->id, 'tanggal' => $hari, 'jam_mulai' => $jam('10:30'), 'jam_selesai' => $jam('12:30'),
            'shot_task_id' => $animateSh01->id, 'deskripsi' => 'Blocking animasi SC01_SH01.',
            'status' => StatusLogbook::DIKIRIM->value, // menunggu review
        ]);
        Logbook::create([
            'user_id' => $sari->id, 'tanggal' => $hari, 'jam_mulai' => $jam('09:40'), 'jam_selesai' => $jam('11:00'),
            'deskripsi' => 'Belajar pipeline lighting, setup file latihan.',
            'status' => StatusLogbook::DRAFT->value,
        ]);

        // --- Aset Cerita 23 (semua selesai) ---
        Aset::create(['project_id' => $proyek->id, 'type' => AssetType::CHARACTER->value, 'name' => 'Karakter Utama - Bima', 'task' => AssetTask::RIGGING->value, 'artist_id' => $ikmal->id, 'status' => TaskStatus::APPROVED->value]);
        Aset::create(['project_id' => $proyek->id, 'type' => AssetType::ENVIRONMENT->value, 'name' => 'Desa Tepi Sungai', 'task' => AssetTask::TEXTURING->value, 'artist_id' => $sari->id, 'status' => TaskStatus::APPROVED->value]);
        Aset::create(['project_id' => $proyek->id, 'type' => AssetType::PROPERTY->value, 'name' => 'Perahu Kayu', 'task' => AssetTask::MODELING->value, 'artist_id' => $nando->id, 'status' => TaskStatus::APPROVED->value]);

        // --- Pra & Pasca Cerita 23: SEMUA tahap APPROVED (episode selesai) ---
        $rotasi = [$ikmal->id, $nando->id, $sari->id];

        foreach ($proyek->tahap()->aktif()->fase(FaseProduksi::PRA)->where('level', 'EPISODE')->urut()->get()->values() as $i => $t) {
            TugasTahap::create([
                'project_id' => $proyek->id, 'tahap_id' => $t->id, 'artist_id' => $rotasi[$i % 3],
                'status' => TaskStatus::APPROVED->value,
                'deskripsi' => $t->name.' telah diselesaikan dan disetujui.',
                'file_url' => 'https://example.test/pra/'.$t->code.'.pdf',
            ]);
        }

        foreach ($proyek->tahap()->aktif()->fase(FaseProduksi::PASCA)->where('level', 'EPISODE')->urut()->get()->values() as $i => $t) {
            TugasTahap::create([
                'project_id' => $proyek->id, 'tahap_id' => $t->id, 'artist_id' => $rotasi[$i % 3],
                'status' => TaskStatus::APPROVED->value,
                'deskripsi' => $t->name.' final & disetujui.',
                'file_url' => 'https://example.test/pasca/'.$t->code.'.mov',
            ]);
        }

        // --- Episode demo tambahan (Cerita 21 & 27, masing-masing 5 scene/31 shot) ---
        $this->call(EpisodeDemoSeeder::class);

        // Contoh notifikasi (mis. hasil publish episode).
        Notifikasi::kirim($ikmal->id, 'Episode dipublish: Cerita 21', 'Anda ditugaskan pada episode ini.', '/', 'publish');
        Notifikasi::kirim($nando->id, 'Episode dipublish: Cerita 27', 'Anda ditugaskan pada episode ini.', '/', 'publish');

        $this->command->info('Seed selesai: 4 artis, 3 proyek (Cerita 23/21/27), shot + tugas Pra/Pasca.');
        $this->command->info('Total durasi Scene 01 = '.$scene->fresh()->total_duration.'s (harusnya 196s).');
        $this->command->info('Kehadiran hari ini: 3 baris (Ikmal hadir, Nando offsite, Sari terlambat) + 2 entri logbook.');
    }
}
