<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Seri / Judul — induk beberapa Episode (Tier C6).
 */
class Seri extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'kf_seri';

    protected $fillable = ['name', 'description'];

    /** @return HasMany<Proyek, $this> */
    public function episode(): HasMany
    {
        return $this->hasMany(Proyek::class, 'series_id');
    }

    /** Aset bersama milik seri (dapat dipakai lintas episode). @return HasMany<Aset, $this> */
    public function aset(): HasMany
    {
        return $this->hasMany(Aset::class, 'series_id');
    }
}
