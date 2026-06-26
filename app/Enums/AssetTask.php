<?php

namespace App\Enums;

/**
 * Tahap pipeline pengerjaan aset (kolom kf_aset.task): Modeling -> Texturing -> Rigging.
 */
enum AssetTask: string
{
    case MODELING = 'MODELING';
    case TEXTURING = 'TEXTURING';
    case RIGGING = 'RIGGING';

    public function label(): string
    {
        return match ($this) {
            self::MODELING => 'Modeling',
            self::TEXTURING => 'Texturing',
            self::RIGGING => 'Rigging',
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
