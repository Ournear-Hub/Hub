<?php

namespace App\Enums;

enum PlacementStatus: string
{
    case Open = 'Open';
    case Offered = 'Offered';
    case Accepted = 'Accepted';
    case Declined = 'Declined';
    case JoiningConfirmed = 'Joining Confirmed';
    case Joined = 'Joined';
    case Withdrawn = 'Withdrawn';

    public function isTerminal(): bool
    {
        return in_array($this, [self::Declined, self::Joined, self::Withdrawn], true);
    }
}
