<?php

namespace App\Enums;

/**
 * Fase produksi animasi. Tiap fase memuat sejumlah tahap (kf_tahap) yang dapat
 * dikonfigurasi admin. Lihat PIPELINE.md.
 */
enum FaseProduksi: string
{
    case PRA = 'PRA';
    case PRODUKSI = 'PRODUKSI';
    case PASCA = 'PASCA';

    public function label(): string
    {
        return match ($this) {
            self::PRA => 'Pra-Produksi',
            self::PRODUKSI => 'Produksi',
            self::PASCA => 'Pasca-Produksi',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PRA => 'bg-indigo-100 text-indigo-700',
            self::PRODUKSI => 'bg-brand-100 text-brand-700',
            self::PASCA => 'bg-purple-100 text-purple-700',
        };
    }

    /** @return array<string,string> nilai => label */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $case) => $carry + [$case->value => $case->label()],
            []
        );
    }
}
