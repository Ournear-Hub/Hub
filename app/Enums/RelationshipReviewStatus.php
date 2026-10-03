<?php

namespace App\Enums;

enum RelationshipReviewStatus: string
{
    case Submitted = 'submitted';
    case Hidden = 'hidden';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::Hidden => 'Hidden',
        };
    }
}
