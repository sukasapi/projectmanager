<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Catatan pemanggilan AI (Gemini) untuk audit Super Admin.
 */
class LogAi extends Model
{
    use HasFactory;

    protected $table = 'kf_log_ai';

    protected $fillable = [
        'user_id',
        'model',
        'status',
        'prompt',
        'response',
        'error',
    ];

    /** @return BelongsTo<User, $this> */
    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
