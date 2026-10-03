<?php

namespace App\Enums;

enum FreelancerStatus: string
{
    case New = 'new';
    case UnderReview = 'under_review';
    case Contacted = 'contacted';
    case Shortlisted = 'shortlisted';
    case Rejected = 'rejected';
    case Converted = 'converted';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::UnderReview => 'Under review',
            self::Contacted => 'Contacted',
            self::Shortlisted => 'Shortlisted',
            self::Rejected => 'Rejected',
            self::Converted => 'Converted',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $status) => [
            'value' => $status->value,
            'label' => $status->label(),
        ], self::cases());
    }
}
