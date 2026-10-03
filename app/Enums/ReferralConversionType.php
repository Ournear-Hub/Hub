<?php

namespace App\Enums;

enum ReferralConversionType: string
{
    case Registration = 'registration';
    case Application = 'application';

    public function label(): string
    {
        return match ($this) {
            self::Registration => 'Registration',
            self::Application => 'Application',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $type) => ['value' => $type->value, 'label' => $type->label()],
            self::cases(),
        );
    }
}
