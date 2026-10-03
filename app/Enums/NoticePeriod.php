<?php

namespace App\Enums;

enum NoticePeriod: string
{
    case Immediate = 'immediate';
    case Days15 = '15_days';
    case Days30 = '30_days';
    case Days60 = '60_days';
    case Days90 = '90_days';
    case MoreThan90 = 'more_than_90';

    public function label(): string
    {
        return match ($this) {
            self::Immediate => 'Immediate',
            self::Days15 => '15 days',
            self::Days30 => '30 days',
            self::Days60 => '60 days',
            self::Days90 => '90 days',
            self::MoreThan90 => 'More than 90 days',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $period) => [
            'value' => $period->value,
            'label' => $period->label(),
        ], self::cases());
    }
}
