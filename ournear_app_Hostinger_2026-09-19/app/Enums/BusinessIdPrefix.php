<?php

namespace App\Enums;

enum BusinessIdPrefix: string
{
    case User = 'ONT-U';
    case Candidate = 'ONT-C';
    case Job = 'ONT-J';
    case Application = 'ONT-A';
    case Employer = 'ONT-E';
    case Submission = 'ONT-S';
    case Placement = 'ONT-P';
    case Employee = 'ONT-W';
    case Replacement = 'ONT-R';
    case Invoice = 'ONT-I';
    case Payment = 'ONT-M';
    case Resume = 'ONT-V';
    case Document = 'ONT-D';
    case Freelancer = 'ONT-F';
}
