<?php

namespace App\Enums;

enum InterviewType: string
{
    case Technical = 'technical';
    case Hr = 'hr';
    case Final = 'final';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Technical => 'Technical',
            self::Hr => 'HR',
            self::Final => 'Final',
            self::Other => 'Other',
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
