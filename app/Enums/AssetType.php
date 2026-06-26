<?php

namespace App\Enums;

/**
 * Kategori aset pada Asset Library (kolom kf_aset.type).
 */
enum AssetType: string
{
    case CHARACTER = 'CHARACTER';
    case ENVIRONMENT = 'ENVIRONMENT';
    case PROPERTY = 'PROPERTY';

    public function label(): string
    {
        return match ($this) {
            self::CHARACTER => 'Character',
            self::ENVIRONMENT => 'Environment',
            self::PROPERTY => 'Property',
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
