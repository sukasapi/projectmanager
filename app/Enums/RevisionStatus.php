<?php

namespace App\Enums;

/**
 * Status iterasi revisi pada sebuah ShotTask (kolom kf_tugas_shot.revision_status).
 * Riwayat revisi tidak ditimpa — kolom ini hanya menandai keadaan terkini.
 */
enum RevisionStatus: string
{
    case NONE = 'NONE';
    case NEEDS_REVISION = 'NEEDS_REVISION';
    case REVISED = 'REVISED';
    case OK = 'OK';

    public function label(): string
    {
        return match ($this) {
            self::NONE => 'Tanpa Revisi',
            self::NEEDS_REVISION => 'Perlu Revisi',
            self::REVISED => 'Sudah Direvisi',
            self::OK => 'Oke',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NONE => 'bg-gray-100 text-gray-600',
            self::NEEDS_REVISION => 'bg-red-100 text-red-700',
            self::REVISED => 'bg-amber-100 text-amber-700',
            self::OK => 'bg-green-100 text-green-700',
        };
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
