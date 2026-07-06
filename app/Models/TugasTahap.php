<?php

namespace App\Models;

use App\Enums\TaskStatus;
use App\Models\Concerns\PunyaVersi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Tugas tahap level-EPISODE (Pra/Pasca). Satu baris per (episode, tahap).
 * Penugasan tunggal (artist_id). Lihat PIPELINE.md §3.1.
 */
class TugasTahap extends Model
{
    use HasFactory, PunyaVersi, SoftDeletes;

    protected $table = 'kf_tugas_tahap';

    protected $fillable = [
        'project_id',
        'tahap_id',
        'artist_id',
        'status',
        'deskripsi',
        'file_url',
        'start_date',
        'deadline',
        'estimasi_hari',
    ];

    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'start_date' => 'date',
            'deadline' => 'date',
            'estimasi_hari' => 'integer',
        ];
    }

    /** @return BelongsTo<Proyek, $this> */
    public function proyek(): BelongsTo
    {
        return $this->belongsTo(Proyek::class, 'project_id');
    }

    /** @return BelongsTo<Tahap, $this> */
    public function tahap(): BelongsTo
    {
        return $this->belongsTo(Tahap::class, 'tahap_id');
    }

    /** @return BelongsTo<User, $this> */
    public function artis(): BelongsTo
    {
        return $this->belongsTo(User::class, 'artist_id');
    }

    /** Riwayat perjalanan pekerjaan (terbaru dulu). @return HasMany<AktivitasTahap, $this> */
    public function aktivitas(): HasMany
    {
        return $this->hasMany(AktivitasTahap::class, 'tugas_tahap_id')->latest();
    }
}
