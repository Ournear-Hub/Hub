<?php

namespace App\Enums;

enum RecruiterTalentPoolAssignmentType: string
{
    case TalentPool = 'talent_pool';
    case Job = 'job';
    case Staffing = 'staffing';
    case General = 'general';

    public function label(): string
    {
        return match ($this) {
            self::TalentPool => 'Talent Pool',
            self::Job => 'Specific Job',
            self::Staffing => 'Staffing Requirement',
            self::General => 'General Candidate Follow-up',
        };
    }
}
