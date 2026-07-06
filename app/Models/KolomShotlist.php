<?php

namespace App\Models;

use App\Enums\PeranKolomShotlist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Definisi kolom Shotlist (studio-wide). Lihat requirement Shotlist 2026-07.
 */
class KolomShotlist extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'kf_kolom_shotlist';

    protected $fillable = ['key', 'label', 'tipe', 'opsi', 'peran', 'urutan', 'is_active'];

    protected function casts(): array
    {
        return [
            'opsi' => 'array',
            'is_active' => 'boolean',
            'urutan' => 'integer',
            'peran' => PeranKolomShotlist::class,
        ];
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

    /** Key kolom yang berperan tertentu (scene/shot_code/duration), atau null. */
    public static function keyBerperan(PeranKolomShotlist $peran): ?string
    {
        return static::aktif()->where('peran', $peran->value)->value('key');
    }
}
