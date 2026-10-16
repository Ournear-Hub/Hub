<?php

namespace App\Support;

use App\Models\User;

/**
 * Curated public site header account dropdown — personal vs company workspace menus.
 */
class PublicHeaderAccountNav
{
    /**
     * Personal account menu (never includes recruiter/company destinations).
     *
     * @return list<array{href: string, label: string, icon?: string, group?: string}>
     */
    public static function personalHeaderItems(User $user): array
    {
        if ($user->isCandidate()) {
            return self::candidateHeaderItems($user);
        }

        if ($user->isEmployer()) {
            return [
                ['href' => '/account/profile#personal', 'label' => 'Personal profile', 'icon' => 'user'],
            ];
        }

        if ($user->isAdmin()) {
            return self::adminHeaderItems();
        }

        return [];
    }

    /**
     * @deprecated Use {@see personalHeaderItems()} — kept for legacy tests referencing itemsFor().
     *
     * @return list<array{href: string, label: string, icon?: string, group?: string}>
     */
    public static function itemsFor(User $user): array
    {
        return array_merge(
            self::personalHeaderItems($user),
            self::showsCompanyHeader($user) ? self::companyWorkspaceHeaderItems($user) : [],
        );
    }

    /**
     * @return list<array{href: string, label: string, icon?: string, group?: string}>
     */
    public static function candidateHeaderItems(User $user): array
    {
        return [
            ['href' => '/account', 'label' => 'Personal Dashboard', 'icon' => 'home'],
            ['href' => '/account/profile#personal', 'label' => 'Personal Profile', 'icon' => 'user'],
            ['href' => '/account/profile#professional', 'label' => 'Professional Profile', 'icon' => 'edit'],
            ['href' => '/account/applications', 'label' => 'My Applications', 'icon' => 'clipboard'],
            ['href' => '/account/saved-jobs', 'label' => 'Saved Jobs', 'icon' => 'search'],
            ['href' => '/account/settings/account', 'label' => 'Settings', 'icon' => 'settings'],
        ];
    }

    /**
     * Company / recruiter workspace menu for the company header control.
     *
     * @return list<array{href: string, label: string, icon?: string}>
     */
    public static function companyWorkspaceHeaderItems(User $user): array
    {
        $employerByHref = self::indexEmployerNavByHref();
        $dashboardHref = AccountPortalNavigation::companyWorkspaceHref($user);
        $dashboardLabel = $user->isEmployer() ? 'Company dashboard' : 'Recruiter workspace';

        return [
            [
                'href' => $dashboardHref,
                'label' => $dashboardLabel,
                'icon' => 'home',
            ],
            [
                'href' => '/account/jobs',
                'label' => 'My Jobs',
                'icon' => $employerByHref['/account/jobs']['icon'] ?? 'briefcase',
            ],
            [
                'href' => '/account/company',
                'label' => 'Company profile',
                'icon' => 'building',
            ],
            [
                'href' => '/account/enquiries',
                'label' => 'Staffing enquiries',
                'icon' => $employerByHref['/account/enquiries']['icon'] ?? 'message',
            ],
            [
                'href' => '/account/settings/verification',
                'label' => 'Recruiter Verification',
                'icon' => 'check',
            ],
        ];
    }

    /**
     * @return list<array{href: string, label: string, icon?: string}>
     */
    public static function adminHeaderItems(): array
    {
        return [
            ['href' => '/admin', 'label' => 'Admin Panel', 'icon' => 'home'],
            ['href' => '/admin/profile/password', 'label' => 'Change password', 'icon' => 'user'],
        ];
    }

    private static function showsCompanyHeader(User $user): bool
    {
        return AccountPortalNavigation::showsCompanyWorkspaceAvatar($user);
    }

    /**
     * @return array<string, array{href: string, label: string, icon?: string, group?: string}>
     */
    private static function indexEmployerNavByHref(): array
    {
        $index = [];
        foreach (EmployerAccountNav::items() as $item) {
            $path = explode('?', $item['href'], 2)[0];
            $index[$path] = $item;
        }

        return $index;
    }
}
