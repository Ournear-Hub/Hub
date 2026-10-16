<?php

namespace App\Enums;

enum RecruiterOperationalFollowUpType: string
{
    case PhoneCall = 'phone_call';
    case OfficeVisit = 'office_visit';
    case Message = 'message';
    case Email = 'email';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::PhoneCall => 'Phone call',
            self::OfficeVisit => 'Office visit',
            self::Message => 'Message / chat',
            self::Email => 'Email',
            self::Other => 'Other contact',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
