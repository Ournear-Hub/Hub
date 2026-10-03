<?php

namespace App\Enums;

enum CommissionEntryStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Paid = 'paid';
    case Void = 'void';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Approved => 'Approved',
            self::Paid => 'Paid',
            self::Void => 'Void',
            self::Failed => 'Failed',
        };
    }

    public function isPending(): bool
    {
        return $this === self::Pending;
    }

    public function canApprove(): bool
    {
        return $this === self::Pending;
    }

    public function canVoid(): bool
    {
        return in_array($this, [self::Pending, self::Approved], true);
    }

    public function canPay(): bool
    {
        return $this === self::Approved;
    }

    public function canRetry(): bool
    {
        return $this === self::Failed;
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
