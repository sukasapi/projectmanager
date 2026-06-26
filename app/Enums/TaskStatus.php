<?php

namespace App\Enums;

/**
 * Status umum tugas/aset/shot-task.
 * Alur: NOT_STARTED -> IN_PROGRESS -> REVIEW -> APPROVED
 * Warna mengikuti panduan UI: Approved=hijau, Review/Revisi=kuning, In Progress=biru.
 */
enum TaskStatus: string
{
    case NOT_STARTED = 'NOT_STARTED';
    case IN_PROGRESS = 'IN_PROGRESS';
    case REVIEW = 'REVIEW';
    case APPROVED = 'APPROVED';

    public function label(): string
    {
        return match ($this) {
            self::NOT_STARTED => 'Belum Mulai',
            self::IN_PROGRESS => 'Dikerjakan',
            self::REVIEW => 'Review',
            self::APPROVED => 'Disetujui',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NOT_STARTED => 'bg-gray-100 text-gray-600',
            self::IN_PROGRESS => 'bg-blue-100 text-blue-700',
            self::REVIEW => 'bg-amber-100 text-amber-700',
            self::APPROVED => 'bg-green-100 text-green-700',
        };
    }

    /**
     * Transisi status yang sah dari status saat ini (acuan state machine — QA Agent).
     *
     * @return array<int,self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::NOT_STARTED => [self::IN_PROGRESS],
            self::IN_PROGRESS => [self::REVIEW],
            self::REVIEW => [self::IN_PROGRESS, self::APPROVED],
            self::APPROVED => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    public function isApproved(): bool
    {
        return $this === self::APPROVED;
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
