<?php

namespace App\Enums;

enum InvestorStatus: string
{
    case PendingVerification = 'pending_verification';
    case Active = 'active';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::PendingVerification => 'Pending verification',
            self::Active => 'Active',
            self::Suspended => 'Suspended',
        };
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
