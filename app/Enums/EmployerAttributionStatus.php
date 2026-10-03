<?php

namespace App\Enums;

enum EmployerAttributionStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Disputed = 'disputed';
    case Void = 'void';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
            self::Disputed => 'Disputed',
            self::Void => 'Void',
        };
    }

    public function isConfirmed(): bool
    {
        return $this === self::Confirmed;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $status) => [
            'value' => $status->value,
            'label' => $status->label(),
        ], self::cases());
    }
}
