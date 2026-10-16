<?php

namespace App\Enums;

enum MessageTemplateAudience: string
{
    case Candidate = 'candidate';
    case Employer = 'employer';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Candidate => 'Candidate',
            self::Employer => 'Employer',
            self::Admin => 'Admin',
        };
    }
}
