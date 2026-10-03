<?php

namespace App\Enums;

enum InvestorInterestStatus: string
{
    case Interested = 'interested';
    case Reviewing = 'reviewing';
    case Discussing = 'discussing';
    case Diligence = 'diligence';
    case Committed = 'committed';
    case Declined = 'declined';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Interested => 'Interested',
            self::Reviewing => 'Reviewing',
            self::Discussing => 'Discussing',
            self::Diligence => 'Diligence',
            self::Committed => 'Committed (non-binding)',
            self::Declined => 'Declined',
            self::Withdrawn => 'Withdrawn',
        };
    }

    public function isRecordedIntent(): bool
    {
        return $this === self::Committed;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $status) => ['value' => $status->value, 'label' => $status->label()],
            self::cases(),
        );
    }
}
