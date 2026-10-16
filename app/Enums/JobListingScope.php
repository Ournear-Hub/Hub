<?php

namespace App\Enums;

enum JobListingScope: string
{
    case EmployerPublic = 'employer_public';
    case TalentPoolInternal = 'talent_pool_internal';
    case TalentPoolAndPublic = 'talent_pool_and_public';

    public function label(): string
    {
        return match ($this) {
            self::EmployerPublic => 'Public Jobs',
            self::TalentPoolInternal => 'OurNear Talent Pool',
            self::TalentPoolAndPublic => 'Talent Pool and Public Jobs',
        };
    }

    public function isPublicWhenPublished(): bool
    {
        return in_array($this, [self::EmployerPublic, self::TalentPoolAndPublic], true);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function talentPoolCreateOptions(): array
    {
        return [
            ['value' => self::TalentPoolInternal->value, 'label' => self::TalentPoolInternal->label()],
            ['value' => self::TalentPoolAndPublic->value, 'label' => self::TalentPoolAndPublic->label()],
        ];
    }
}
