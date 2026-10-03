<?php

namespace App\Enums;

enum ScreeningDecision: string
{
    case Pending = 'pending';
    case Suitable = 'suitable';
    case NotSuitable = 'not_suitable';
    case Shortlisted = 'shortlisted';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Suitable => 'Suitable',
            self::NotSuitable => 'Not Suitable',
            self::Shortlisted => 'Shortlisted',
        };
    }
}
