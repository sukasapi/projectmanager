<?php

namespace App\Models;

use App\Enums\AssetTask;
use App\Enums\AssetType;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Aset extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'kf_aset';

    protected $fillable = [
        'project_id',
        'series_id',
        'type',
        'name',
        'task',
        'artist_id',
        'status',
        'file_url',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => AssetType::class,
            'task' => AssetTask::class,
            'status' => TaskStatus::class,
        ];
    }

    /** @return BelongsTo<Proyek, $this> */
    public function proyek(): BelongsTo
    {
        return $this->belongsTo(Proyek::class, 'project_id');
    }

    /** @return BelongsTo<User, $this> */
    public function artist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'artist_id');
    }

    /** Shot yang memakai aset ini (breakdown). @return BelongsToMany<Shot, $this> */
    public function shots(): BelongsToMany
    {
        return $this->belongsToMany(Shot::class, 'kf_aset_shot', 'aset_id', 'shot_id')->withTimestamps();
    }

    /** @return BelongsTo<Seri, $this> */
    public function seri(): BelongsTo
    {
        return $this->belongsTo(Seri::class, 'series_id');
    }

    /** Aset bersama seri (dapat dipakai lintas episode). */
    public function bersama(): bool
    {
        return $this->series_id !== null;
    }
}
