<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Riwayat versi deliverable (tautan file/preview) untuk shot-task atau tahap.
 */
class VersiKiriman extends Model
{
    protected $table = 'kf_versi_kiriman';

    protected $fillable = [
        'subjek_type',
        'subjek_id',
        'version',
        'url',
        'catatan',
        'author_id',
    ];

    /** @return MorphTo<Model, $this> */
    public function subjek(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
