<?php

namespace App\Enums;

/**
 * Peran kolom shotlist yang dipetakan untuk MEMBUAT shot di Produksi.
 * Kolom tanpa peran (null) = metadata biasa (disimpan di kf_shot.meta).
 */
enum PeranKolomShotlist: string
{
    case SCENE = 'scene';
    case SHOT_CODE = 'shot_code';
    case DURATION = 'duration';

    public function label(): string
    {
        return match ($this) {
            self::SCENE => 'Scene (nama adegan)',
            self::SHOT_CODE => 'Shot No# (kode shot)',
            self::DURATION => 'Durasi shot (detik)',
        };
    }

    /** @return array<string,string> nilai => label */
    public static function options(): array
    {
        return array_reduce(self::cases(), fn (array $c, self $case) => $c + [$case->value => $case->label()], []);
    }
}
