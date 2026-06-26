<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu entri riwayat revisi (append-only) milik sebuah ShotTask.
 */
class RevisiShot extends Model
{
    use HasFactory;

    protected $table = 'kf_revisi_shot';

    protected $fillable = [
        'shot_task_id',
        'author_id',
        'kind',
        'note',
        'status_from',
        'status_to',
    ];

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
