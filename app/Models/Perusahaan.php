<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Profil perusahaan/studio (singleton). Gunakan Perusahaan::current() untuk
 * mengambil/menyiapkan satu-satunya baris. Lihat permintaan #9.
 */
class Perusahaan extends Model
{
    use HasFactory;

    protected $table = 'kf_perusahaan';

    protected $fillable = [
        'name',
        'app_name',
        'legal_name',
        'email',
        'phone',
        'website',
        'address',
        'logo_path',
        'login_image_path',
        'tagline',
        'footer_text',
        'jam_masuk',
        'jam_pulang',
        'toleransi_menit',
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',
        'smtp_encryption',
        'smtp_from_address',
        'smtp_from_name',
    ];

    protected function casts(): array
    {
        return [
            'toleransi_menit' => 'integer',
            'smtp_port' => 'integer',
            'smtp_password' => 'encrypted',
        ];
    }

    /**
     * Ambil baris profil tunggal (singleton) — baris pertama, buat default bila belum ada.
     * CATATAN: memakai firstOrCreate([]) (baris pertama), BUKAN id=1, karena `id` tidak
     * fillable sehingga create tak bisa memaksa id=1 (auto-increment) → dulu menimbulkan
     * baris ganda saat counter sudah maju. Lihat perbaikan SMTP 2026-07.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], ['name' => config('app.name', 'AnimTrack')]);
    }

    // --- Kebijakan kehadiran (DB → fallback config) ---

    public function jamMasuk(): string
    {
        return $this->jam_masuk ?: config('kehadiran.jam_masuk', '09:00');
    }

    public function jamPulang(): string
    {
        return $this->jam_pulang ?: config('kehadiran.jam_pulang', '17:00');
    }

    public function toleransiMenit(): int
    {
        return (int) ($this->toleransi_menit ?? config('kehadiran.toleransi_menit', 15));
    }

    // --- Tampilan aplikasi (DB → fallback) ---

    public function appName(): string
    {
        return $this->app_name ?: config('app.name', 'AnimTrack');
    }

    public function footer(): string
    {
        return $this->footer_text ?: '© '.date('Y').' '.$this->name;
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path ? Storage::url($this->logo_path) : null;
    }

    public function loginImageUrl(): ?string
    {
        return $this->login_image_path ? Storage::url($this->login_image_path) : null;
    }

    // --- SMTP (DB → menimpa .env saat runtime) ---

    public function smtpAktif(): bool
    {
        return filled($this->smtp_host);
    }

    /** Terapkan konfigurasi SMTP dari DB ke config mail runtime (bila diisi). */
    public function terapkanMail(): void
    {
        if (! $this->smtpAktif()) {
            return;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => $this->smtp_host,
            'mail.mailers.smtp.port' => (int) ($this->smtp_port ?: 587),
            'mail.mailers.smtp.username' => $this->smtp_username ?: null,
            'mail.mailers.smtp.password' => $this->smtp_password ?: null,
            'mail.mailers.smtp.encryption' => $this->smtp_encryption ?: null,
        ]);

        if ($this->smtp_from_address) {
            config([
                'mail.from.address' => $this->smtp_from_address,
                'mail.from.name' => $this->smtp_from_name ?: $this->appName(),
            ]);
        }
    }
}
