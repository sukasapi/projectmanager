<?php

namespace App\Models;

use App\Enums\PeranKolomShotlist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Definisi kolom Shotlist — milik satu gaya (GayaShotlist). Lihat requirement
 * Shotlist 2026-07 & docs/2026-07-13_shotlist-style.md.
 */
class KolomShotlist extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'kf_kolom_shotlist';

    protected $fillable = ['style_id', 'key', 'label', 'tipe', 'opsi', 'peran', 'urutan', 'is_active'];

    protected function casts(): array
    {
        return [
            'opsi' => 'array',
            'is_active' => 'boolean',
            'urutan' => 'integer',
            'peran' => PeranKolomShotlist::class,
        ];
    }

    /** @return BelongsTo<GayaShotlist, $this> */
    public function gayaShotlist(): BelongsTo
    {
        return $this->belongsTo(GayaShotlist::class, 'style_id');
    }

    /** @param  Builder<KolomShotlist>  $q */
    public function scopeAktif(Builder $q): void
    {
        $q->where('is_active', true);
    }

    /** @param  Builder<KolomShotlist>  $q */
    public function scopeUrut(Builder $q): void
    {
        $q->orderBy('urutan')->orderBy('id');
    }

    /** @param  Builder<KolomShotlist>  $q */
    public function scopeGaya(Builder $q, ?int $styleId): void
    {
        $q->where('style_id', $styleId);
    }

    /** Key kolom yang berperan tertentu (scene/shot_code/duration) pada satu gaya, atau null. */
    public static function keyBerperan(PeranKolomShotlist $peran, ?int $styleId = null): ?string
    {
        return static::aktif()
            ->when($styleId !== null, fn (Builder $q) => $q->where('style_id', $styleId))
            ->where('peran', $peran->value)
            ->value('key');
    }
}
