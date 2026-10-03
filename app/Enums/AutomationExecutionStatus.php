<?php

namespace App\Enums;

enum AutomationExecutionStatus: string
{
    case Executed = 'executed';
    case Skipped = 'skipped';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Executed => 'Executed',
            self::Skipped => 'Skipped',
            self::Failed => 'Failed',
        };
    }
}
