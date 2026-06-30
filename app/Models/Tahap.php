<?php

namespace App\Models;

use App\Enums\FaseProduksi;
use App\Enums\LevelTahap;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Tahap produksi yang dapat dikonfigurasi (template studio global). Lihat PIPELINE.md.
 */
class Tahap extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'kf_tahap';

    protected $fillable = [
        'project_id',
        'phase',
        'level',
        'code',
        'name',
        'urutan',
        'requires_tahap_id',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'phase' => FaseProduksi::class,
            'level' => LevelTahap::class,
            'is_active' => 'boolean',
            'urutan' => 'integer',
        ];
    }

    /** Prasyarat tahap ini (mis. Simulate butuh Animate). @return BelongsTo<Tahap, $this> */
    public function prasyarat(): BelongsTo
    {
        return $this->belongsTo(Tahap::class, 'requires_tahap_id');
    }

    /** Episode pemilik snapshot ini (null = template global). @return BelongsTo<Proyek, $this> */
    public function proyek(): BelongsTo
    {
        return $this->belongsTo(Proyek::class, 'project_id');
    }

    /** Template global (Konfigurasi Pipeline). @param  Builder<Tahap>  $query */
    public function scopeGlobal(Builder $query): void
    {
        $query->whereNull('project_id');
    }

    /** Snapshot milik sebuah episode. @param  Builder<Tahap>  $query */
    public function scopeMilikEpisode(Builder $query, int $projectId): void
    {
        $query->where('project_id', $projectId);
    }

    /** @param  Builder<Tahap>  $query */
    public function scopeAktif(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** @param  Builder<Tahap>  $query */
    public function scopeFase(Builder $query, FaseProduksi $fase): void
    {
        $query->where('phase', $fase->value);
    }

    /** @param  Builder<Tahap>  $query */
    public function scopeUrut(Builder $query): void
    {
        $query->orderBy('urutan')->orderBy('id');
    }
}
