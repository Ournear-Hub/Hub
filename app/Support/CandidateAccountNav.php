<?php

namespace App\Support;

use App\Models\User;

class CandidateAccountNav
{
    /**
     * @return list<array{href: string, label: string, group?: string}>
     */
    public static function items(?User $user = null): array
    {
        $items = [
            ['href' => '/account', 'label' => 'Dashboard', 'group' => 'OVERVIEW'],
        ];

        if ($user instanceof User && $user->canAccessHiringWorkspace()) {
            $items[] = ['href' => '/account/hiring', 'label' => 'Hiring', 'group' => 'RECRUITING'];
        }

        $items = array_merge($items, [
            ['href' => '/account/profile', 'label' => 'Profile', 'group' => 'PROFILE'],
            ['href' => '/account/profile/professional', 'label' => 'Professional Profile', 'group' => 'PROFILE'],
            ['href' => '/account/resumes/builder', 'label' => 'Hire Resume', 'group' => 'RESUMES'],
            ['href' => '/account/resumes', 'label' => 'My Resume', 'group' => 'RESUMES'],
            ['href' => '/account/applications', 'label' => 'Applications', 'group' => 'ACTIVITY'],
            ['href' => '/account/saved-jobs', 'label' => 'Saved Jobs', 'group' => 'ACTIVITY'],
            ['href' => '/account/notifications', 'label' => 'Notifications', 'group' => 'ACTIVITY'],
            ['href' => '/account/settings/security', 'label' => 'Verification', 'group' => 'ACCOUNT'],
            ['href' => '/account/documents', 'label' => 'Documents', 'group' => 'ACCOUNT'],
            ['href' => '/account/settings', 'label' => 'Settings', 'group' => 'ACCOUNT'],
            ['href' => '/account/profile#privacy', 'label' => 'Privacy & Visibility', 'group' => 'ACCOUNT'],
            ['href' => '/contact', 'label' => 'Help & Support', 'group' => 'ACCOUNT'],
        ]);

        return $items;
    }
}
