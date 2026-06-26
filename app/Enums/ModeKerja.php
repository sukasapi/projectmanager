<?php

namespace App\Enums;

/**
 * Mode kerja per hari kehadiran. Ditentukan saat clock-in, BUKAN dikunci oleh
 * employment_type — karyawan kontrak onsite boleh memilih OFFSITE pada hari tertentu
 * (lihat ABSENSI.md §1).
 */
enum ModeKerja: string
{
    case ONSITE = 'ONSITE';
    case OFFSITE = 'OFFSITE';

    public function label(): string
    {
        return match ($this) {
            self::ONSITE => 'Onsite (di studio)',
            self::OFFSITE => 'Offsite (remote)',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ONSITE => 'bg-green-100 text-green-700',
            self::OFFSITE => 'bg-blue-100 text-blue-700',
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
