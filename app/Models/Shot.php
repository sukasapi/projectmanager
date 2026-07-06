<?php

namespace App\Models;

use App\Observers\ShotObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([ShotObserver::class])]
class Shot extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'kf_shot';

    protected $fillable = [
        'scene_id',
        'shot_code',
        'description',
        'meta',
        'duration_seconds',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'duration_seconds' => 'integer',
            'meta' => 'array',
        ];
    }

    /** @return BelongsTo<Adegan, $this> */
    public function adegan(): BelongsTo
    {
        return $this->belongsTo(Adegan::class, 'scene_id');
    }

    /** @return HasMany<TugasShot, $this> */
    public function tugasShot(): HasMany
    {
        return $this->hasMany(TugasShot::class, 'shot_id');
    }

    /** Aset (breakdown) yang dipakai shot ini. @return BelongsToMany<Aset, $this> */
    public function aset(): BelongsToMany
    {
        return $this->belongsToMany(Aset::class, 'kf_aset_shot', 'shot_id', 'aset_id')->withTimestamps();
    }
}
