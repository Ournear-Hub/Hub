<?php

namespace App\Enums;

enum InterviewLifecycleStatus: string
{
    case Scheduled = 'scheduled';
    case Confirmed = 'confirmed';
    case Rescheduled = 'rescheduled';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Scheduled',
            self::Confirmed => 'Confirmed',
            self::Rescheduled => 'Rescheduled',
            self::Cancelled => 'Cancelled',
            self::Completed => 'Completed',
        };
    }

    public function canConfirm(): bool
    {
        return $this === self::Scheduled;
    }

    public function canReschedule(): bool
    {
        return in_array($this, [self::Scheduled, self::Confirmed], true);
    }

    public function canCancel(): bool
    {
        return in_array($this, [self::Scheduled, self::Confirmed], true);
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Scheduled, self::Confirmed], true);
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
