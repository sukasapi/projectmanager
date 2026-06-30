<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu entri riwayat (append-only) perjalanan tugas tahap. Lihat WORKFLOW.md §4.
 */
class AktivitasTahap extends Model
{
    use HasFactory;

    protected $table = 'kf_aktivitas_tahap';

    protected $fillable = [
        'tugas_tahap_id',
        'author_id',
        'kind',
        'note',
        'status_from',
        'status_to',
    ];

    /** @return BelongsTo<TugasTahap, $this> */
    public function tugasTahap(): BelongsTo
    {
        return $this->belongsTo(TugasTahap::class, 'tugas_tahap_id');
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function label(): string
    {
        return match ($this->kind) {
            'MULAI' => 'Mulai dikerjakan',
            'PROPOSE' => 'Diajukan (propose)',
            'APPROVE' => 'Disetujui',
            'REJECT' => 'Ditolak',
            default => $this->kind,
        };
    }
}
