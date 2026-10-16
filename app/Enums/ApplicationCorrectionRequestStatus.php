<?php

namespace App\Enums;

enum ApplicationCorrectionRequestStatus: string
{
    case Pending = 'pending';
    case Acknowledged = 'acknowledged';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Acknowledged => 'Acknowledged',
            self::Resolved => 'Resolved',
        };
    }
}
