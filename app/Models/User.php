<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\EmploymentType;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Tabel artis terdaftar (prefiks kf_, Bahasa Indonesia).
     */
    protected $table = 'kf_pengguna';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'jabatan',
        'phone',
        'whatsapp',
        'address',
        'latitude',
        'longitude',
        'employment_type',
        'kapasitas_hari',
        'is_active',
        'last_active_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'employment_type' => EmploymentType::class,
            'is_active' => 'boolean',
            'last_active_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    /** Presence ringan: dianggap online bila heartbeat masih dalam ambang (config). */
    public function isOnline(): bool
    {
        return $this->last_active_at !== null
            && $this->last_active_at->gt(now()->subMinutes((int) config('kehadiran.ambang_online_menit', 5)));
    }

    /**
     * Peran "elevated"/reserved yang dikunci. Semua nilai `role` lain diperlakukan
     * sebagai peran keartisan yang bebas ditambah untuk keperluan produksi.
     * Lihat 2026-07-01_peran-team-lead-hak-akses.md.
     *
     * @var list<string>
     */
    public const PERAN_ELEVATED = ['Super Admin', 'Supervisor', 'Team Lead'];

    /**
     * Empat tingkat PERAN (hak akses). "Admin" = Super Admin. Peran keartisan = 'Artis'
     * (spesialisasi disimpan di kolom `jabatan`, bukan role).
     *
     * @var list<string>
     */
    public const PERAN_TERSEDIA = ['Super Admin', 'Supervisor', 'Team Lead', 'Artis'];

    /**
     * Contoh JABATAN (spesialisasi) untuk datalist — bebas ditambah sesuai kebutuhan produksi.
     *
     * @var list<string>
     */
    public const JABATAN_UMUM = ['Animator', 'Modeller', 'Rigger', 'Storyboard Artist', 'SLRC', 'Lighting', 'Compositor', 'Editor', 'VFX Artist', 'Layout Artist'];

    /** Super Admin: semua hak supervisor + konfigurasi aplikasi (pipeline, perusahaan). */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'Super Admin';
    }

    /** Punya hak supervisi (produksi, tim, monitoring): Supervisor atau Super Admin. */
    public function isSupervisory(): bool
    {
        return in_array($this->role, ['Supervisor', 'Super Admin'], true);
    }

    /** Team Lead (peran): identitas lintas-episode. Kewenangan produksi per-episode tetap via kf_proyek.team_lead_id. */
    public function isTeamLead(): bool
    {
        return $this->role === 'Team Lead';
    }

    /** Peran keartisan (bukan elevated). Spesialisasi ada di kolom `jabatan`. */
    public function isArtis(): bool
    {
        return ! in_array($this->role, self::PERAN_ELEVATED, true);
    }

    /** Boleh melihat/membuka menu Pemantauan & Tim & Artis: Supervisor/Super Admin atau Team Lead. */
    public function bisaPemantauan(): bool
    {
        return $this->isSupervisory() || $this->isTeamLead();
    }

    /**
     * Apakah user ini boleh MENETAPKAN peran $target ke user lain (matriks jenjang).
     * Super Admin → semua; Supervisor → Team Lead + keartisan; Team Lead → keartisan saja.
     * Nilai kosong/null dianggap peran keartisan (tanpa hak khusus).
     */
    public function bolehMenetapkanPeran(?string $target): bool
    {
        $target = trim((string) $target);
        $elevated = in_array($target, self::PERAN_ELEVATED, true);

        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->role === 'Supervisor') {
            // Boleh Team Lead + keartisan; TIDAK boleh Supervisor/Super Admin.
            return $target === 'Team Lead' || ! $elevated;
        }

        if ($this->isTeamLead()) {
            // Hanya peran keartisan.
            return ! $elevated;
        }

        return false;
    }

    /**
     * Empat tingkat peran yang boleh ditetapkan aktor ini (untuk pilihan form; validasi server otoritatif).
     *
     * @return list<string>
     */
    public function peranDapatDitetapkan(): array
    {
        return array_values(array_filter(self::PERAN_TERSEDIA, fn (string $p) => $this->bolehMenetapkanPeran($p)));
    }

    // ----- Relasi penugasan (semua berbasis artist_id / pivot) -----

    /** @return HasMany<TugasPraproduksi, $this> */
    public function tugasPraproduksi(): HasMany
    {
        return $this->hasMany(TugasPraproduksi::class, 'artist_id');
    }

    /** @return HasMany<Aset, $this> */
    public function aset(): HasMany
    {
        return $this->hasMany(Aset::class, 'artist_id');
    }

    /** @return HasMany<TugasPascaproduksi, $this> */
    public function tugasPascaproduksi(): HasMany
    {
        return $this->hasMany(TugasPascaproduksi::class, 'artist_id');
    }

    /**
     * Penugasan jamak pada ShotTask (mis. ANIMATE = Ikmal + Nando).
     *
     * @return BelongsToMany<TugasShot, $this>
     */
    public function tugasShot(): BelongsToMany
    {
        return $this->belongsToMany(TugasShot::class, 'kf_penugasan_shot', 'user_id', 'shot_task_id')
            ->withTimestamps();
    }

    /** @return HasMany<Kehadiran, $this> */
    public function kehadiran(): HasMany
    {
        return $this->hasMany(Kehadiran::class, 'user_id');
    }

    /** @return HasMany<Logbook, $this> */
    public function logbook(): HasMany
    {
        return $this->hasMany(Logbook::class, 'user_id');
    }
}
