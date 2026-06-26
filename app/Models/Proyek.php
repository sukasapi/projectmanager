<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Project/Episode — akar hierarki produksi.
 */
class Proyek extends Model
{
    use HasFactory;

    protected $table = 'kf_proyek';

    protected $fillable = [
        'name',
        'description',
        'status',
        'client_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
        ];
    }

    /** @return BelongsTo<Klien, $this> */
    public function klien(): BelongsTo
    {
        return $this->belongsTo(Klien::class, 'client_id');
    }

    /** @return HasMany<Adegan, $this> */
    public function adegan(): HasMany
    {
        return $this->hasMany(Adegan::class, 'project_id');
    }

    /** @return HasMany<Aset, $this> */
    public function aset(): HasMany
    {
        return $this->hasMany(Aset::class, 'project_id');
    }

    /** @return HasMany<TugasPraproduksi, $this> */
    public function tugasPraproduksi(): HasMany
    {
        return $this->hasMany(TugasPraproduksi::class, 'project_id');
    }

    /** @return HasMany<TugasPascaproduksi, $this> */
    public function tugasPascaproduksi(): HasMany
    {
        return $this->hasMany(TugasPascaproduksi::class, 'project_id');
    }
}
