<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Komentar berulir (1 tingkat balasan) untuk shot-task atau tahap.
 */
class Komentar extends Model
{
    protected $table = 'kf_komentar';

    protected $fillable = [
        'subjek_type',
        'subjek_id',
        'parent_id',
        'author_id',
        'body',
    ];

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** @return HasMany<Komentar, $this> */
    public function balasan(): HasMany
    {
        return $this->hasMany(Komentar::class, 'parent_id')->oldest()->with('author');
    }

    /** @return MorphTo<Model, $this> */
    public function subjek(): MorphTo
    {
        return $this->morphTo();
    }
}
