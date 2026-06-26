<?php

namespace App\Models;

use App\Enums\ModeKerja;
use App\Enums\StatusKehadiran;
use App\Observers\KehadiranObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kehadiran harian (1 baris per user per tanggal). work_mode ditentukan per hari.
 * work_duration_minutes adalah nilai turunan (KehadiranObserver). Lihat ABSENSI.md §3.1.
 */
#[ObservedBy([KehadiranObserver::class])]
class Kehadiran extends Model
{
    use HasFactory;

    protected $table = 'kf_kehadiran';

    protected $fillable = [
        'user_id',
        'tanggal',
        'clock_in',
        'clock_out',
        'work_mode',
        'status',
        'clock_in_ip',
        'clock_out_ip',
        'latitude',
        'longitude',
        'catatan',
        'approved_by',
    ];

    // Catatan: work_duration_minutes sengaja TIDAK ada di $fillable — nilai turunan
    // yang hanya ditulis KehadiranObserver, tidak dapat diisi via mass assignment/UI.

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'clock_in' => 'datetime',
            'clock_out' => 'datetime',
            'work_mode' => ModeKerja::class,
            'status' => StatusKehadiran::class,
            'work_duration_minutes' => 'integer',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function penyetuju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** Apakah sudah clock-in tapi belum clock-out (sedang berjalan). */
    public function sedangBerjalan(): bool
    {
        return $this->clock_in !== null && $this->clock_out === null;
    }
}
