<?php

namespace App\Enums;

enum PreferredWorkType: string
{
    case Onsite = 'onsite';
    case Remote = 'remote';
    case Hybrid = 'hybrid';
    case Flexible = 'flexible';

    public function label(): string
    {
        return match ($this) {
            self::Onsite => 'On-site',
            self::Remote => 'Remote',
            self::Hybrid => 'Hybrid',
            self::Flexible => 'Flexible',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $type) => [
            'value' => $type->value,
            'label' => $type->label(),
        ], self::cases());
    }
}
