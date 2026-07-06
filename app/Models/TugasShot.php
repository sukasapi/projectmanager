<?php

namespace App\Models;

use App\Enums\RevisionStatus;
use App\Enums\TaskStatus;
use App\Models\Concerns\PunyaVersi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Sub-task per Shot pada satu tahap Produksi (level SHOT, mis. Animate/Simulate)
 * dengan penugasan jamak. Tahap kini berbasis data (kf_tahap), bukan enum.
 */
class TugasShot extends Model
{
    use HasFactory, PunyaVersi, SoftDeletes;

    protected $table = 'kf_tugas_shot';

    protected $fillable = [
        'shot_id',
        'tahap_id',
        'status',
        'start_date',
        'deadline',
        'estimasi_hari',
        'post_date',
        'preview_url',
        'revision_notes',
        'revision_status',
    ];

    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'revision_status' => RevisionStatus::class,
            'post_date' => 'date',
            'start_date' => 'date',
            'deadline' => 'date',
            'estimasi_hari' => 'integer',
        ];
    }

    /** @return BelongsTo<Shot, $this> */
    public function shot(): BelongsTo
    {
        return $this->belongsTo(Shot::class, 'shot_id');
    }

    /** Tahap produksi (configurable) untuk sub-task ini. @return BelongsTo<Tahap, $this> */
    public function tahap(): BelongsTo
    {
        return $this->belongsTo(Tahap::class, 'tahap_id');
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

    /** Catatan review terstruktur (Tier C2). @return HasMany<CatatanReview, $this> */
    public function catatanReview(): HasMany
    {
        return $this->hasMany(CatatanReview::class, 'shot_task_id')->latest();
    }

    /** Jumlah retake = berapa kali dikembalikan dari REVIEW ke IN_PROGRESS. */
    public function jumlahRetake(): int
    {
        return $this->revisi()
            ->where('status_from', TaskStatus::REVIEW->value)
            ->where('status_to', TaskStatus::IN_PROGRESS->value)
            ->count();
    }
}
