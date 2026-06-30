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
    ];

    protected function casts(): array
    {
        return [
            'toleransi_menit' => 'integer',
        ];
    }

    /** Ambil baris profil tunggal (buat default bila belum ada). */
    public static function current(): self
    {
        return static::query()->firstOrCreate(
            ['id' => 1],
            ['name' => config('app.name', 'AnimTrack')]
        );
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
}
