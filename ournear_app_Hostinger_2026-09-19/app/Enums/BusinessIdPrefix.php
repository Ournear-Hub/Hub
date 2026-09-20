<?php

namespace App\Enums;

enum BusinessIdPrefix: string
{
    case Candidate = 'ONT-C';
    case Job = 'ONT-J';
    case Application = 'ONT-A';
    case Employer = 'ONT-E';
}
