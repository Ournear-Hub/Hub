<?php

namespace App\Support;

use App\Models\User;

/**
 * Public-header portal switcher destinations (candidate vs recruiter/employer workspace).
 */
class AccountPortalNavigation
{
    public const CANDIDATE_HREF = '/account';

    /** Company / hiring workspace (activation, ATS, jobs, staffing enquiries). */
    public const CANDIDATE_RECRUITER_HREF = '/account/hiring';

    public static function companyWorkspaceHref(User $user): string
    {
        return self::CANDIDATE_RECRUITER_HREF;
    }

    /** Company workspace control in header/account chrome — hiring-enabled users only. */
    public static function showsCompanyWorkspaceAvatar(User $user): bool
    {
        return $user->isEmployer() || $user->canAccessHiringWorkspace();
    }

    /**
     * @return array{
     *     name: string,
     *     logo_url: string|null,
     *     href: string,
     *     industry: string|null,
     *     location: string|null,
     *     company_website: string|null,
     *     ournear_verified: bool,
     *     profile_completion: int|null
     * }|null
     */
    public static function companyWorkspaceIdentity(User $user): ?array
    {
        if (! self::showsCompanyWorkspaceAvatar($user)) {
            return null;
        }

        $user->loadMissing('employerProfile');
        $profile = $user->employerProfile;
        $companyName = $profile !== null ? $profile->accountWorkspaceCompanyName() : '';
        $industry = $profile !== null ? $profile->displayIndustry() : '';

        $website = trim((string) ($profile?->company_website ?? ''));
        if ($website !== '' && ! preg_match('#^https?://#i', $website)) {
            $website = 'https://'.$website;
        }

        $location = $profile !== null ? trim($profile->displayLocations()) : '';

        return [
            'name' => $companyName !== '' ? $companyName : 'Company workspace',
            'logo_url' => $profile?->logoUrl(),
            'href' => self::companyWorkspaceHref($user),
            'industry' => $industry !== '' ? $industry : null,
            'location' => $location !== '' ? $location : null,
            'company_website' => $website !== '' ? $website : null,
            'ournear_verified' => $profile !== null && $profile->ournearVerified(),
            'profile_completion' => $user->isEmployer() || $user->canAccessHiringWorkspace()
                ? (int) $user->calculateProfileCompletion()
                : null,
        ];
    }

    /**
     * @return list<array{href: string, label: string, icon?: string, group?: string}>
     */
    public static function employerNavItemsFor(User $user): array
    {
        $overviewHref = self::companyWorkspaceHref($user);

        return array_map(
            static function (array $item) use ($overviewHref): array {
                if (
                    in_array($item['label'] ?? '', ['Overview', 'My Workspace'], true)
                    && ($item['href'] ?? '') === '/account/hiring'
                ) {
                    $item['href'] = $overviewHref;
                }

                return $item;
            },
            EmployerAccountNav::items(),
        );
    }

    /**
     * @return array{
     *     candidate: array{href: string, label: string, icon: string},
     *     company: array{href: string, label: string, icon: string, has_company_profile: bool, logo_url: string|null}
     * }|null
     */
    public static function for(?User $user): ?array
    {
        if (! $user instanceof User) {
            return null;
        }

        if (! $user->canAccessHiringWorkspace()) {
            return null;
        }

        $user->loadMissing('employerProfile');
        $profile = $user->employerProfile;
        $companyName = $profile !== null ? $profile->accountWorkspaceCompanyName() : '';
        $hasProfile = $companyName !== '';

        return [
            'candidate' => [
                'href' => self::CANDIDATE_HREF,
                'label' => 'Personal Dashboard',
                'icon' => 'user',
            ],
            'company' => [
                'href' => self::companyWorkspaceHref($user),
                'label' => $hasProfile ? $companyName : 'Company workspace',
                'icon' => 'building',
                'has_company_profile' => $hasProfile,
                'logo_url' => $profile?->logoUrl(),
            ],
        ];
    }
}
