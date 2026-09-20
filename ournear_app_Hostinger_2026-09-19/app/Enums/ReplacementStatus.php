<?php

namespace App\Enums;

enum ReplacementStatus: string
{
    case Requested = 'Requested';
    case InProgress = 'In Progress';
    case Placed = 'Placed';
    case Completed = 'Completed';
    case Cancelled = 'Cancelled';

    public function isOpen(): bool
    {
        return in_array($this, [self::Requested, self::InProgress, self::Placed], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled], true);
    }
}
