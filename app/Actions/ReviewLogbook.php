<?php

namespace App\Actions;

use App\Enums\StatusLogbook;
use App\Models\Logbook;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Menyetujui / menolak entri logbook oleh mentor/supervisor.
 *
 * Guard:
 * - Peninjau harus lolos Gate 'review-logbook' (bukan magang).
 * - Tidak boleh me-review logbook milik sendiri.
 * - Hanya entri berstatus DIKIRIM yang bisa di-review.
 *
 * Lihat ABSENSI.md §8.
 */
class ReviewLogbook
{
    public function handle(User $peninjau, Logbook $logbook, bool $setujui, ?string $catatan = null): Logbook
    {
        if (Gate::forUser($peninjau)->denies('review-logbook')) {
            throw ValidationException::withMessages([
                'review' => 'Anda tidak berwenang me-review logbook.',
            ]);
        }

        if ($logbook->user_id === $peninjau->id) {
            throw ValidationException::withMessages([
                'review' => 'Tidak dapat me-review logbook milik sendiri.',
            ]);
        }

        if ($logbook->status !== StatusLogbook::DIKIRIM) {
            throw ValidationException::withMessages([
                'review' => 'Hanya logbook berstatus "Menunggu Review" yang dapat ditinjau.',
            ]);
        }

        $logbook->fill([
            'status' => ($setujui ? StatusLogbook::DISETUJUI : StatusLogbook::DITOLAK)->value,
            'reviewed_by' => $peninjau->id,
            'reviewed_at' => now(),
            'review_notes' => $catatan ?: null,
        ])->save();

        return $logbook;
    }
}
