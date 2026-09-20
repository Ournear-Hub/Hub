<?php

namespace App\Enums;

enum InterviewOutcome: string
{
    case Pending = 'pending';
    case Attended = 'attended';
    case NoShow = 'no_show';
    case Passed = 'passed';
    case Failed = 'failed';
}
