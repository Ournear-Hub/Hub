<?php

namespace App\Enums;

enum PartnerAgreementStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Active = 'active';
    case Expired = 'expired';
    case Terminated = 'terminated';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Pending => 'Pending',
            self::Active => 'Active',
            self::Expired => 'Expired',
            self::Terminated => 'Terminated',
        };
    }

    public function isActive(): bool
    {
        return $this === self::Active;
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
