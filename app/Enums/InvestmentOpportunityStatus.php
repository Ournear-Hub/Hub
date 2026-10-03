<?php

namespace App\Enums;

enum InvestmentOpportunityStatus: string
{
    case Draft = 'draft';
    case OpenForInterest = 'open_for_interest';
    case InterestReceived = 'interest_received';
    case AdminReview = 'admin_review';
    case Discussion = 'discussion';
    case DueDiligence = 'due_diligence';
    case Agreement = 'agreement';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::OpenForInterest => 'Open for interest',
            self::InterestReceived => 'Interest received',
            self::AdminReview => 'Admin review',
            self::Discussion => 'Discussion',
            self::DueDiligence => 'Due diligence',
            self::Agreement => 'Agreement',
            self::Closed => 'Closed',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $status) => ['value' => $status->value, 'label' => $status->label()],
            self::cases(),
        );
    }
}
