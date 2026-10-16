<?php

namespace App\Enums;

enum RecruiterTalentPoolAssignmentStatus: string
{
    case PendingReview = 'pending_review';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Declined = 'declined';
    case Returned = 'returned';

    public function label(): string
    {
        return match ($this) {
            self::PendingReview => 'Pending review',
            self::InProgress => 'In progress',
            self::Completed => 'Completed',
            self::Declined => 'Declined',
            self::Returned => 'Returned to Admin',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::PendingReview, self::InProgress], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Declined, self::Returned], true);
    }

    /**
     * @return list<self>
     */
    public function allowedRecruiterTransitions(): array
    {
        return match ($this) {
            self::PendingReview => [self::InProgress, self::Declined],
            self::InProgress => [self::Completed, self::Returned, self::Declined],
            default => [],
        };
    }
}
