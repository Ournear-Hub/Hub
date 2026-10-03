<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Candidate = 'candidate';
    case Employer = 'employer';
    case Partner = 'partner';
    case Investor = 'investor';
}
