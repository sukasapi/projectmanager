<?php

namespace App\Models;

use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TugasPascaproduksi extends Model
{
    use HasFactory;

    protected $table = 'kf_tugas_pascaproduksi';

    protected $fillable = [
        'project_id',
        'scene_id',
        'task_type',
        'artist_id',
        'deadline',
        'status',
        'file_url',
    ];

    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'deadline' => 'date',
        ];
    }

    /** @return BelongsTo<Proyek, $this> */
    public function proyek(): BelongsTo
    {
        return $this->belongsTo(Proyek::class, 'project_id');
    }

    /** @return BelongsTo<Adegan, $this> */
    public function adegan(): BelongsTo
    {
        return $this->belongsTo(Adegan::class, 'scene_id');
    }

    /** @return BelongsTo<User, $this> */
    public function artist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'artist_id');
    }
}
