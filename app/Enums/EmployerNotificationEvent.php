<?php

namespace App\Enums;

enum EmployerNotificationEvent: string
{
    case ApplicationReceived = 'employer_application_received';

    public function label(): string
    {
        return match ($this) {
            self::ApplicationReceived => 'New application',
        };
    }
}
