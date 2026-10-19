<?php

namespace App\Support;

final class DevelopmentProgramme
{
    public const HrRecruitment = 'hr-recruitment';

    public const BusinessDevelopment = 'business-development';

    public const DigitalMarketing = 'digital-marketing';

    public const BusinessOperations = 'business-operations';

    public const StatusSubmitted = 'submitted';

    /**
     * @return list<string>
     */
    public static function slugs(): array
    {
        return [
            self::HrRecruitment,
            self::BusinessDevelopment,
            self::DigitalMarketing,
            self::BusinessOperations,
        ];
    }

    public static function isSlug(string $slug): bool
    {
        return in_array($slug, self::slugs(), true);
    }

    public static function label(string $slug): string
    {
        return match ($slug) {
            self::HrRecruitment => 'HR & Recruitment Intern',
            self::BusinessDevelopment => 'Business Development & Sales Intern',
            self::DigitalMarketing => 'Digital Marketing & Content Intern',
            self::BusinessOperations => 'Business Operations & MIS Intern',
            default => $slug,
        };
    }
}
