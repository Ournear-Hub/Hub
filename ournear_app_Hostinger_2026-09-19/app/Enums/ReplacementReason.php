<?php

namespace App\Enums;

enum ReplacementReason: string
{
    case Resigned = 'Employee resigned';
    case Left = 'Employee left';
    case FailedAfterJoining = 'Failed after joining';
    case Performance = 'Performance issue';
    case Absence = 'Absence';
    case EmployerRequested = 'Employer requested replacement';
    case Other = 'Other';
}
