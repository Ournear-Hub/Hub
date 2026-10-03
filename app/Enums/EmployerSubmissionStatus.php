<?php

namespace App\Enums;

enum EmployerSubmissionStatus: string
{
    case Submitted = 'Submitted';
    case UnderReview = 'Under Review';
    case Selected = 'Selected';
    case Rejected = 'Rejected';
    case OnHold = 'On Hold';

    public function isTerminal(): bool
    {
        return in_array($this, [self::Selected, self::Rejected], true);
    }
}
