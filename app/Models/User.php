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
        'employment_type',
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
        ];
    }

    /** Presence ringan: dianggap online bila heartbeat masih dalam ambang (config). */
    public function isOnline(): bool
    {
        return $this->last_active_at !== null
            && $this->last_active_at->gt(now()->subMinutes((int) config('kehadiran.ambang_online_menit', 5)));
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
