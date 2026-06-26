<?php

namespace App\Enums;

/**
 * Sub-pipeline per Shot. Berurutan: Layout -> Animate -> Simulate -> LRC.
 * Urutan dipakai QA Agent untuk mencegah tahap diloncati
 * (mis. SIMULATE tak boleh mulai sebelum ANIMATE disetujui).
 */
enum ShotTaskType: string
{
    case LAYOUT = 'LAYOUT';
    case ANIMATE = 'ANIMATE';
    case SIMULATE = 'SIMULATE';
    case LRC = 'LRC';

    public function label(): string
    {
        return match ($this) {
            self::LAYOUT => 'Layout',
            self::ANIMATE => 'Animate',
            self::SIMULATE => 'Simulate',
            self::LRC => 'LRC (Lighting, Rendering, Compositing)',
        };
    }

    /** Posisi tahap dalam pipeline (1 = paling awal). */
    public function sequence(): int
    {
        return match ($this) {
            self::LAYOUT => 1,
            self::ANIMATE => 2,
            self::SIMULATE => 3,
            self::LRC => 4,
        };
    }

    /** Tahap sebelumnya yang harus disetujui lebih dulu (null jika tahap pertama). */
    public function previous(): ?self
    {
        return match ($this) {
            self::LAYOUT => null,
            self::ANIMATE => self::LAYOUT,
            self::SIMULATE => self::ANIMATE,
            self::LRC => self::SIMULATE,
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
