<?php

namespace App\Support;

final class PublicPageImageRegistry
{
    /**
     * Slots that are listed even before an image is saved.
     *
     * @var list<string>
     */
    public const REQUIRED_PAGES = [
        'development-programme',
    ];

    /**
     * @return list<array{page: string, page_label: string, key: string, label: string}>
     */
    public static function slots(): array
    {
        return [
            self::slot('development-programme', 'Development Programme', 'hero', 'Hero workplace image'),
            self::slot('development-programme', 'Development Programme', 'hr-recruitment', 'HR & Recruitment image'),
            self::slot('development-programme', 'Development Programme', 'business-development', 'Business Development image'),
            self::slot('development-programme', 'Development Programme', 'digital-marketing', 'Digital Marketing image'),
            self::slot('development-programme', 'Development Programme', 'business-operations', 'Business Operations & MIS image'),
            self::slot('development-programme', 'Development Programme', 'team', 'Real-work / team collaboration image'),
            self::slot('home', 'Home', 'hero', 'Hero image'),
            self::slot('hire', 'Hiring', 'hero', 'Hero image'),
            self::slot('talent-pool', 'Talent Pool', 'hero', 'Hero image'),
            self::slot('staffing', 'Staffing', 'hero', 'Hero image'),
        ];
    }

    public static function has(string $page, string $key): bool
    {
        foreach (self::slots() as $slot) {
            if ($slot['page'] === $page && $slot['key'] === $key) {
                return true;
            }
        }

        return false;
    }

    public static function label(string $page, string $key): string
    {
        foreach (self::slots() as $slot) {
            if ($slot['page'] === $page && $slot['key'] === $key) {
                return $slot['label'];
            }
        }

        return $key;
    }

    /**
     * @return array{page: string, page_label: string, key: string, label: string}
     */
    private static function slot(string $page, string $pageLabel, string $key, string $label): array
    {
        return [
            'page' => $page,
            'page_label' => $pageLabel,
            'key' => $key,
            'label' => $label,
        ];
    }
}
