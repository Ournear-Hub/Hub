<?php

namespace App\Enums;

enum MessageTemplateChannel: string
{
    case Email = 'email';
    case InApp = 'in_app';

    public function label(): string
    {
        return match ($this) {
            self::Email => 'Email',
            self::InApp => 'In-app notification',
        };
    }

    public function requiresSubject(): bool
    {
        return $this === self::Email;
    }
}
