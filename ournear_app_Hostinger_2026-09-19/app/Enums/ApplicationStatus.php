<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case New = 'New';
    case Reviewing = 'Reviewing';
    case Shortlisted = 'Shortlisted';
    case Interview = 'Interview';
    case Selected = 'Selected';
    case Placed = 'Placed';
    case Rejected = 'Rejected';
    case Withdrawn = 'Withdrawn';

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::Selected,
            self::Placed,
            self::Rejected,
            self::Withdrawn,
        ], true);
    }

    public function toCandidateLabel(): string
    {
        return match ($this) {
            self::New, self::Reviewing => 'Received',
            self::Shortlisted, self::Interview => 'In progress',
            self::Selected, self::Placed => 'Selected',
            self::Rejected => 'Unsuccessful',
            self::Withdrawn => 'Withdrawn',
        };
    }
}
