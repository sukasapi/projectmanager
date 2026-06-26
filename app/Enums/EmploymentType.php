<?php

namespace App\Enums;

/**
 * Jenis kepegawaian artis terdaftar (kolom kf_pengguna.employment_type).
 */
enum EmploymentType: string
{
    case CONTRACT = 'CONTRACT';
    case FREELANCE = 'FREELANCE';
    case INTERN = 'INTERN';

    public function label(): string
    {
        return match ($this) {
            self::CONTRACT => 'Karyawan Kontrak',
            self::FREELANCE => 'Freelance',
            self::INTERN => 'Magang',
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
