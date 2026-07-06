<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Baris shotlist per episode. Nilai kolom disimpan di `data` (JSON), fleksibel
 * mengikuti definisi kolom studio. shot_id terisi setelah di-generate ke Produksi.
 */
class Shotlist extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'kf_shotlist';

    protected $fillable = ['project_id', 'urutan', 'data', 'shot_id'];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'urutan' => 'integer',
        ];
    }

    public function sudahDigenerate(): bool
    {
        return $this->shot_id !== null;
    }

    /** @return BelongsTo<Proyek, $this> */
    public function proyek(): BelongsTo
    {
        return $this->belongsTo(Proyek::class, 'project_id');
    }

    /** @return BelongsTo<Shot, $this> */
    public function shot(): BelongsTo
    {
        return $this->belongsTo(Shot::class, 'shot_id');
    }
}
