<?php

namespace App\Models;

use App\Enums\StatusLogbook;
use App\Observers\LogbookObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Entri logbook harian (Freelance & Intern). durasi_menit turunan (LogbookObserver).
 * Editable saat DRAFT; terkunci setelah DIKIRIM. Lihat ABSENSI.md §3.2.
 */
#[ObservedBy([LogbookObserver::class])]
class Logbook extends Model
{
    use HasFactory;

    protected $table = 'kf_logbook';

    protected $fillable = [
        'user_id',
        'tanggal',
        'jam_mulai',
        'jam_selesai',
        'shot_task_id',
        'deskripsi',
        'output_url',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    // durasi_menit sengaja tidak fillable — nilai turunan dijaga LogbookObserver.

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'jam_mulai' => 'datetime',
            'jam_selesai' => 'datetime',
            'durasi_menit' => 'integer',
            'status' => StatusLogbook::class,
            'reviewed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function peninjau(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** @return BelongsTo<TugasShot, $this> */
    public function tugasShot(): BelongsTo
    {
        return $this->belongsTo(TugasShot::class, 'shot_task_id');
    }
}
