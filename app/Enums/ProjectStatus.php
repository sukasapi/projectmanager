<?php

namespace App\Enums;

/**
 * Status sebuah Project/Episode (kolom kf_proyek.status).
 */
enum ProjectStatus: string
{
    case PLANNING = 'PLANNING';
    case IN_PROGRESS = 'IN_PROGRESS';
    case ON_HOLD = 'ON_HOLD';
    case COMPLETED = 'COMPLETED';

    public function label(): string
    {
        return match ($this) {
            self::PLANNING => 'Perencanaan',
            self::IN_PROGRESS => 'Dikerjakan',
            self::ON_HOLD => 'Ditunda',
            self::COMPLETED => 'Selesai',
        };
    }

    /** Kelas badge Tailwind untuk indikator status di UI. */
    public function color(): string
    {
        return match ($this) {
            self::PLANNING => 'bg-gray-100 text-gray-700',
            self::IN_PROGRESS => 'bg-blue-100 text-blue-700',
            self::ON_HOLD => 'bg-amber-100 text-amber-700',
            self::COMPLETED => 'bg-green-100 text-green-700',
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
