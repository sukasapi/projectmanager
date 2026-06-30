<?php

namespace App\Models;

use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TugasPraproduksi extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'kf_tugas_praproduksi';

    protected $fillable = [
        'project_id',
        'content_name',
        'artist_id',
        'deadline',
        'status',
        'file_url',
        'notes',
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

    /** @return BelongsTo<User, $this> */
    public function artist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'artist_id');
    }
}
