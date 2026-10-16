<?php

namespace App\Enums;

enum CandidateInterviewResponse: string
{
    case Accepted = 'accepted';
    case Declined = 'declined';

    public function label(): string
    {
        return match ($this) {
            self::Accepted => 'Accepted',
            self::Declined => 'Declined',
        };
    }
}
