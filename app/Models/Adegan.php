<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Scene — total_duration adalah nilai turunan (SUM durasi shot), dihitung server.
 */
class Adegan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'kf_adegan';

    protected $fillable = [
        'project_id',
        'scene_name',
        // total_duration sengaja TIDAK fillable: hanya boleh diubah lewat kalkulasi server (Langkah 2).
    ];

    protected function casts(): array
    {
        return [
            'total_duration' => 'integer',
        ];
    }

    /** @return BelongsTo<Proyek, $this> */
    public function proyek(): BelongsTo
    {
        return $this->belongsTo(Proyek::class, 'project_id');
    }

    /** @return HasMany<Shot, $this> */
    public function shot(): HasMany
    {
        return $this->hasMany(Shot::class, 'scene_id');
    }

    /** @return HasMany<TugasPascaproduksi, $this> */
    public function tugasPascaproduksi(): HasMany
    {
        return $this->hasMany(TugasPascaproduksi::class, 'scene_id');
    }
}
