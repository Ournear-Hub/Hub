<?php

namespace App\Enums;

enum WhatsAppDeliveryStatus: string
{
    case Accepted = 'accepted';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Read = 'read';
    case Failed = 'failed';

    public function rank(): int
    {
        return match ($this) {
            self::Accepted => 1,
            self::Sent => 2,
            self::Delivered => 3,
            self::Read => 4,
            self::Failed => 5,
        };
    }

    public function canTransitionTo(self $next): bool
    {
        if ($this === $next) {
            return false;
        }

        if ($this === self::Failed) {
            return false;
        }

        if ($next === self::Failed) {
            return true;
        }

        return $next->rank() > $this->rank();
    }
}
