<?php

namespace App\Enums;

enum PreferredLanguage: string
{
    case English = 'en';
    case Telugu = 'te';
    case Hindi = 'hi';

    public function label(): string
    {
        return match ($this) {
            self::English => 'English',
            self::Telugu => 'Telugu',
            self::Hindi => 'Hindi',
        };
    }
}
