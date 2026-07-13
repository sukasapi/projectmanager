<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Gaya (style) Shotlist — paket susunan kolom. Seri memilih satu gaya; seluruh
 * episodenya memakai kolom gaya tsb. Lihat docs/2026-07-13_shotlist-style.md.
 */
class GayaShotlist extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'kf_gaya_shotlist';

    protected $fillable = ['name', 'description', 'is_default'];

    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    /** @return HasMany<KolomShotlist, $this> */
    public function kolom(): HasMany
    {
        return $this->hasMany(KolomShotlist::class, 'style_id');
    }

    /** @return HasMany<Seri, $this> */
    public function seri(): HasMany
    {
        return $this->hasMany(Seri::class, 'shotlist_style_id');
    }

    /** Gaya default studio (fallback episode tanpa seri / seri tanpa pilihan). */
    public static function bawaan(): ?self
    {
        return static::where('is_default', true)->first() ?? static::orderBy('id')->first();
    }

    /**
     * Gaya yang berlaku untuk sebuah episode: gaya seri episode tsb,
     * atau gaya default bila episode tanpa seri / seri belum memilih.
     */
    public static function untukProyek(?Proyek $proyek): ?self
    {
        return $proyek?->seri?->gayaShotlist ?? static::bawaan();
    }
}
