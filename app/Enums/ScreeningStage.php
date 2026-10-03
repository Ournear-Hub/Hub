<?php

namespace App\Enums;

enum ScreeningStage: string
{
    case Pending = 'pending';
    case Started = 'started';
    case Matched = 'matched';
    case Contacted = 'contacted';
    case Called = 'called';
    case Interviewed = 'interviewed';
    case Held = 'held';
    case Shortlisted = 'shortlisted';
    case Submitted = 'submitted';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Started => 'Screening started',
            self::Matched => 'Matched',
            self::Contacted => 'Contacted',
            self::Called => 'Called',
            self::Interviewed => 'Interviewed',
            self::Held => 'On hold',
            self::Shortlisted => 'Shortlisted',
            self::Submitted => 'Employer submission',
            self::Rejected => 'Rejected',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $stage) => [
            'value' => $stage->value,
            'label' => $stage->label(),
        ], self::cases());
    }
}
