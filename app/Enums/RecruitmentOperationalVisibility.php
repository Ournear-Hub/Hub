<?php

namespace App\Enums;

enum RecruitmentOperationalVisibility: string
{
    case Internal = 'internal';
    case EmployerShared = 'employer_shared';

    public function label(): string
    {
        return match ($this) {
            self::Internal => 'Internal (OurNear staff only)',
            self::EmployerShared => 'Shared with employer contact',
        };
    }
}
