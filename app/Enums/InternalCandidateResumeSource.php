<?php

namespace App\Enums;

enum InternalCandidateResumeSource: string
{
    case Email = 'email';
    case Whatsapp = 'whatsapp';
    case Referral = 'referral';
    case Offline = 'offline';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Email => 'Email',
            self::Whatsapp => 'WhatsApp',
            self::Referral => 'Referral',
            self::Offline => 'Offline',
            self::Other => 'Other',
        };
    }
}
