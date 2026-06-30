<?php

namespace App\Models\Concerns;

use App\Models\VersiKiriman;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Memberi model kemampuan menyimpan riwayat versi deliverable (tautan file/preview).
 */
trait PunyaVersi
{
    /** @return MorphMany<VersiKiriman, $this> */
    public function versi(): MorphMany
    {
        return $this->morphMany(VersiKiriman::class, 'subjek')->orderByDesc('version');
    }

    /** Catat versi baru bila URL berbeda dari versi terakhir. True bila tercatat. */
    public function catatVersi(string $url, ?string $catatan = null, ?int $authorId = null): bool
    {
        $url = trim($url);
        if ($url === '') {
            return false;
        }

        $last = $this->versi()->first(); // sudah orderByDesc('version')
        if ($last && $last->url === $url) {
            return false;
        }

        $this->versi()->create([
            'version' => ($last?->version ?? 0) + 1,
            'url' => $url,
            'catatan' => $catatan ?: null,
            'author_id' => $authorId,
        ]);

        return true;
    }
}
