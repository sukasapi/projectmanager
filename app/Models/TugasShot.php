<?php

namespace App\Models;

use App\Enums\RevisionStatus;
use App\Enums\ShotTaskType;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Sub-task per Shot (Layout/Animate/Simulate/LRC) dengan penugasan jamak.
 */
class TugasShot extends Model
{
    use HasFactory;

    protected $table = 'kf_tugas_shot';

    protected $fillable = [
        'shot_id',
        'task_type',
        'status',
        'post_date',
        'preview_url',
        'revision_notes',
        'revision_status',
    ];

    protected function casts(): array
    {
        return [
            'task_type' => ShotTaskType::class,
            'status' => TaskStatus::class,
            'revision_status' => RevisionStatus::class,
            'post_date' => 'date',
        ];
    }

    /** @return BelongsTo<Shot, $this> */
    public function shot(): BelongsTo
    {
        return $this->belongsTo(Shot::class, 'shot_id');
    }

    /**
     * Artis-artis yang ditugaskan pada tahap ini (many-to-many).
     *
     * @return BelongsToMany<User, $this>
     */
    public function artists(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'kf_penugasan_shot', 'shot_task_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * Riwayat revisi (append-only) — terbaru lebih dulu.
     *
     * @return HasMany<RevisiShot, $this>
     */
    public function revisi(): HasMany
    {
        return $this->hasMany(RevisiShot::class, 'shot_task_id')->latest();
    }
}
