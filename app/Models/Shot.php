<?php

namespace App\Models;

use App\Observers\ShotObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([ShotObserver::class])]
class Shot extends Model
{
    use HasFactory;

    protected $table = 'kf_shot';

    protected $fillable = [
        'scene_id',
        'shot_code',
        'duration_seconds',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'duration_seconds' => 'integer',
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
}
