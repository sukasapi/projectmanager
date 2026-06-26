<?php

namespace Database\Seeders;

use App\Enums\EmploymentType;
use App\Enums\ModeKerja;
use App\Enums\ProjectStatus;
use App\Enums\RevisionStatus;
use App\Enums\ShotTaskType;
use App\Enums\StatusKehadiran;
use App\Enums\StatusLogbook;
use App\Enums\TaskStatus;
use App\Models\Adegan;
use App\Models\Kehadiran;
use App\Models\Klien;
use App\Models\Logbook;
use App\Models\Proyek;
use App\Models\Shot;
use App\Models\TugasShot;
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
        // --- Artis terdaftar, satu per jenis kepegawaian ---
        $admin = User::create([
            'name' => 'Studio Admin',
            'email' => 'admin@animtrack.test',
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
        $proyek = Proyek::create([
            'name' => 'Cerita 23',
            'description' => 'Episode contoh untuk verifikasi pipeline AnimTrack.',
            'status' => ProjectStatus::IN_PROGRESS->value,
            'client_id' => $klien->id,
        ]);

        // --- Scene 01 dengan beberapa shot ---
        $scene = Adegan::create([
            'project_id' => $proyek->id,
            'scene_name' => 'Scene 01',
        ]);

        $durations = [
            'SC01_SH01' => 96,
            'SC01_SH02' => 64,
            'SC01_SH03' => 36,
        ];

        foreach ($durations as $code => $detik) {
            $shot = Shot::create([
                'scene_id' => $scene->id,
                'shot_code' => $code,
                'duration_seconds' => $detik,
            ]);

            // Buat 4 sub-pipeline kosong untuk tiap shot.
            foreach (ShotTaskType::cases() as $tipe) {
                TugasShot::create([
                    'shot_id' => $shot->id,
                    'task_type' => $tipe->value,
                    'status' => TaskStatus::NOT_STARTED->value,
                    'revision_status' => RevisionStatus::NONE->value,
                ]);
            }
        }

        // Total durasi scene = SUM durasi shot (di Langkah 2 ini otomatis via Observer).
        $scene->update(['total_duration' => array_sum($durations)]);

        // --- Demonstrasi penugasan JAMAK: ANIMATE pada SC01_SH01 = Ikmal + Nando ---
        $animateSh01 = TugasShot::query()
            ->whereHas('shot', fn ($q) => $q->where('shot_code', 'SC01_SH01'))
            ->where('task_type', ShotTaskType::ANIMATE->value)
            ->first();

        $animateSh01->update(['status' => TaskStatus::IN_PROGRESS->value]);
        $animateSh01->artists()->sync([$ikmal->id, $nando->id]);

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

        $this->command->info('Seed selesai: 4 artis, 1 proyek (Cerita 23), 1 scene, 3 shot, 12 shot-task.');
        $this->command->info('Total durasi Scene 01 = '.$scene->fresh()->total_duration.'s (harusnya 196s).');
        $this->command->info('Kehadiran hari ini: 3 baris (Ikmal hadir, Nando offsite, Sari terlambat) + 2 entri logbook.');
    }
}
