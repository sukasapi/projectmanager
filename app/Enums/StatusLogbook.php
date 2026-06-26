<?php

namespace App\Enums;

/**
 * Status entri logbook (kf_logbook.status). Entri DRAFT bisa diedit; setelah DIKIRIM
 * terkunci (append-only) lalu di-review mentor/supervisor. Lihat ABSENSI.md §3.2.
 */
enum StatusLogbook: string
{
    case DRAFT = 'DRAFT';
    case DIKIRIM = 'DIKIRIM';
    case DISETUJUI = 'DISETUJUI';
    case DITOLAK = 'DITOLAK';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::DIKIRIM => 'Menunggu Review',
            self::DISETUJUI => 'Disetujui',
            self::DITOLAK => 'Ditolak',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'bg-slate-100 text-slate-600',
            self::DIKIRIM => 'bg-amber-100 text-amber-700',
            self::DISETUJUI => 'bg-green-100 text-green-700',
            self::DITOLAK => 'bg-red-100 text-red-700',
        };
    }

    /** Entri masih boleh diedit/hapus oleh pemiliknya (hanya saat DRAFT). */
    public function dapatDiedit(): bool
    {
        return $this === self::DRAFT;
    }

    /** Sudah final (tidak menunggu review lagi). */
    public function sudahDireview(): bool
    {
        return in_array($this, [self::DISETUJUI, self::DITOLAK], true);
    }

    /** @return array<string,string> nilai => label, untuk dropdown/select */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $case) => $carry + [$case->value => $case->label()],
            []
        );
    }
}
