<?php

namespace App\Enums;

enum CommunicationChannel: string
{
    case MarketingEmail = 'marketing_email';
    case MarketingSms = 'marketing_sms';
    case Whatsapp = 'whatsapp';
    case Push = 'push';
    case System = 'system';

    public function label(): string
    {
        return match ($this) {
            self::MarketingEmail => 'Marketing email',
            self::MarketingSms => 'Marketing SMS',
            self::Whatsapp => 'WhatsApp',
            self::Push => 'Push notifications',
            self::System => 'Product / system notifications',
        };
    }

    public function defaultOptIn(): bool
    {
        return $this === self::System;
    }
}
