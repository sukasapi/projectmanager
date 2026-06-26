<?php

namespace App\Enums;

/**
 * Status kehadiran harian (kf_kehadiran.status).
 * HADIR/TERLAMBAT diturunkan dari clock_in vs jam masuk; ALPHA di-set otomatis
 * akhir hari untuk yang tak absen (lihat ABSENSI.md §2, §4).
 */
enum StatusKehadiran: string
{
    case HADIR = 'HADIR';
    case TERLAMBAT = 'TERLAMBAT';
    case IZIN = 'IZIN';
    case SAKIT = 'SAKIT';
    case CUTI = 'CUTI';
    case ALPHA = 'ALPHA';

    public function label(): string
    {
        return match ($this) {
            self::HADIR => 'Hadir',
            self::TERLAMBAT => 'Terlambat',
            self::IZIN => 'Izin',
            self::SAKIT => 'Sakit',
            self::CUTI => 'Cuti',
            self::ALPHA => 'Alpha (tanpa kabar)',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::HADIR => 'bg-green-100 text-green-700',
            self::TERLAMBAT => 'bg-amber-100 text-amber-700',
            self::IZIN, self::CUTI => 'bg-blue-100 text-blue-700',
            self::SAKIT => 'bg-slate-100 text-slate-600',
            self::ALPHA => 'bg-red-100 text-red-700',
        };
    }

    /** Status yang menandakan karyawan benar-benar bekerja hari itu. */
    public function isMasuk(): bool
    {
        return in_array($this, [self::HADIR, self::TERLAMBAT], true);
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
