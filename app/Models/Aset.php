<?php

namespace App\Models;

use App\Enums\AssetTask;
use App\Enums\AssetType;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Aset extends Model
{
    use HasFactory;

    protected $table = 'kf_aset';

    protected $fillable = [
        'project_id',
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
}
