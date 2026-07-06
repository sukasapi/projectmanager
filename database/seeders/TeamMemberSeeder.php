<?php

namespace Database\Seeders;

use App\Enums\EmploymentType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Seeder anggota tim dari CSV nyata studio (database/data/team_members.csv).
 * Delimiter ';' — kolom: Nama;Posisi;Email;Telepon;Status;Dibuat.
 *
 * Idempoten: cocokkan berdasarkan email (tak menyentuh kata sandi user yang sudah ada).
 * Jalankan: php artisan db:seed --class=TeamMemberSeeder
 *
 * DEFAULT (CSV tidak memuat kolom ini — ubah bila perlu):
 * - Kata sandi awal semua user BARU = 'password' → WAJIB minta ganti / reset password.
 * - employment_type default = CONTRACT.
 *
 * Pemetaan Posisi → (role, jabatan):
 * - role (hak akses): ada "team lead" → Team Lead; ada "supervisor" → Supervisor; selain itu → Artis.
 * - jabatan (spesialisasi): sisa posisi non-peran (Animator/Modeller/SLRC/Storyboard Artist, dll),
 *   digabung bila lebih dari satu. Seorang artis yang juga team lead: role Team Lead, jabatan tetap.
 */
class TeamMemberSeeder extends Seeder
{
    /** Normalisasi label jabatan (selain fallback Title Case). */
    private const PETA_JABATAN = [
        'animator' => 'Animator',
        'storyboard artist' => 'Storyboard Artist',
        'modeller' => 'Modeller',
        'modeler' => 'Modeller',
        'slrc' => 'SLRC',
    ];

    public function run(): void
    {
        $path = database_path('data/team_members.csv');

        if (! is_file($path)) {
            $this->command?->warn("CSV tidak ditemukan: {$path}");

            return;
        }

        $baris = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        array_shift($baris); // buang header

        $dibuat = 0;
        $diperbarui = 0;

        foreach ($baris as $row) {
            // str_getcsv menghormati kutip bila suatu field memuat pemisah ';'.
            $kolom = array_map('trim', str_getcsv($row, ';'));
            $nama = $kolom[0] ?? '';
            $posisi = $kolom[1] ?? '';
            $email = Str::lower($kolom[2] ?? '');
            $telepon = $kolom[3] ?? '';
            $status = $kolom[4] ?? 'Aktif';
            $dibuatStr = $kolom[5] ?? '';

            if ($email === '' || $nama === '') {
                continue;
            }

            [$role, $jabatan] = $this->petakanPosisi($posisi);

            $atribut = [
                'name' => $nama,
                'role' => $role,
                'jabatan' => $jabatan,
                'phone' => $telepon !== '' ? $telepon : null,
                'employment_type' => EmploymentType::CONTRACT->value,
                'is_active' => Str::lower($status) === 'aktif',
            ];

            $user = User::firstWhere('email', $email);

            if ($user) {
                $user->fill($atribut)->save(); // jangan reset kata sandi user lama
                $diperbarui++;
            } else {
                $user = User::create($atribut + [
                    'email' => $email,
                    'password' => Hash::make('password'),
                ]);
                $dibuat++;
            }

            // Pertahankan tanggal pembuatan asli dari CSV bila format valid.
            if ($dibuatStr !== '' && ($tgl = $this->parseTanggal($dibuatStr))) {
                $user->created_at = $tgl;
                $user->saveQuietly();
            }
        }

        $this->command?->info("TeamMemberSeeder: {$dibuat} dibuat, {$diperbarui} diperbarui. Kata sandi awal user baru = 'password'.");
    }

    /**
     * Petakan string posisi (bisa gabungan dipisah koma) menjadi [role, jabatan].
     *
     * @return array{0: string, 1: ?string}
     */
    private function petakanPosisi(string $posisi): array
    {
        $bagian = array_values(array_filter(array_map(
            fn ($p) => Str::lower(trim($p)),
            explode(',', $posisi),
        )));

        $role = 'Artis';
        if (in_array('team lead', $bagian, true)) {
            $role = 'Team Lead';
        } elseif (in_array('supervisor', $bagian, true)) {
            $role = 'Supervisor';
        }

        // Jabatan = sisa posisi yang bukan penanda peran, dinormalisasi & digabung.
        $jabatan = collect($bagian)
            ->reject(fn ($p) => in_array($p, ['team lead', 'supervisor'], true))
            ->map(fn ($p) => self::PETA_JABATAN[$p] ?? Str::title($p))
            ->unique()
            ->implode(', ');

        return [$role, $jabatan !== '' ? $jabatan : null];
    }

    private function parseTanggal(string $s): ?Carbon
    {
        try {
            return Carbon::createFromFormat('d/m/Y H:i', trim($s), 'Asia/Jakarta')->utc();
        } catch (\Throwable) {
            return null;
        }
    }
}
