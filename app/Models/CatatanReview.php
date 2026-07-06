<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Catatan review terstruktur (Tier C2): rentang frame + status open/resolved.
 */
class CatatanReview extends Model
{
    use HasFactory;

    protected $table = 'kf_catatan_review';

    protected $fillable = [
        'shot_task_id',
        'frame_start',
        'frame_end',
        'body',
        'status',
        'author_id',
    ];

    protected function casts(): array
    {
        return [
            'frame_start' => 'integer',
            'frame_end' => 'integer',
        ];
    }

    public function selesai(): bool
    {
        return $this->status === 'resolved';
    }

    /** @return BelongsTo<TugasShot, $this> */
    public function tugasShot(): BelongsTo
    {
        return $this->belongsTo(TugasShot::class, 'shot_task_id');
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
