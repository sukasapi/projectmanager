<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Notifikasi in-app per pengguna. Lihat WORKFLOW.md §3.
 */
class Notifikasi extends Model
{
    use HasFactory;

    protected $table = 'kf_notifikasi';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'url',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Kirim satu notifikasi ke seorang pengguna. */
    public static function kirim(int $userId, string $title, ?string $message = null, ?string $url = null, string $type = 'info'): self
    {
        return static::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'url' => $url,
        ]);
    }

    /**
     * Kirim notifikasi yang sama ke banyak pengguna (mis. saat publish episode).
     *
     * @param  iterable<int>  $userIds
     */
    public static function kirimBanyak(iterable $userIds, string $title, ?string $message = null, ?string $url = null, string $type = 'info'): void
    {
        foreach (collect($userIds)->unique()->filter() as $userId) {
            static::kirim((int) $userId, $title, $message, $url, $type);
        }
    }
}
