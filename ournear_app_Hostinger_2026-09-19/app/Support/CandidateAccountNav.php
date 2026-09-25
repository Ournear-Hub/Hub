<?php

namespace App\Support;

class CandidateAccountNav
{
    /**
     * @return list<array{href: string, label: string}>
     */
    public static function items(): array
    {
        return [
            ['href' => '/account', 'label' => 'Dashboard'],
            ['href' => '/account/profile', 'label' => 'Profile'],
            ['href' => '/account/profile#professional', 'label' => 'Professional Profile'],
            ['href' => '/account/resumes', 'label' => 'Resumes'],
            ['href' => '/account/documents', 'label' => 'Documents'],
            ['href' => '/account/saved-jobs', 'label' => 'Saved Jobs'],
            ['href' => '/account/applications', 'label' => 'Applications'],
            ['href' => '/account/settings', 'label' => 'Settings'],
        ];
    }
}
