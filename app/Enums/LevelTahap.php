<?php

namespace App\Enums;

/**
 * Level penerapan sebuah tahap:
 * - EPISODE: satu tugas per episode (mis. Script, Editing).
 * - SHOT: satu kolom per shot di matriks Produksi (mis. Animate, Simulate).
 *
 * Lihat PIPELINE.md.
 */
enum LevelTahap: string
{
    case EPISODE = 'EPISODE';
    case SHOT = 'SHOT';

    public function label(): string
    {
        return match ($this) {
            self::EPISODE => 'Per Episode',
            self::SHOT => 'Per Shot',
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
