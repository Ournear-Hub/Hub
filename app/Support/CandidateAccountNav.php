<?php

namespace App\Support;

class CandidateAccountNav
{
    /**
     * Candidate-only sidebar (no recruiter / company workspace links).
     *
     * @return list<array{href: string, label: string, group?: string, icon?: string}>
     */
    public static function items(): array
    {
        return [
            ['href' => '/account', 'label' => 'Personal Dashboard', 'group' => 'OVERVIEW', 'icon' => 'home'],
            ['href' => '/account/profile#personal', 'label' => 'Personal Profile', 'group' => 'PROFILE', 'icon' => 'user'],
            ['href' => '/account/profile#professional', 'label' => 'Professional Profile', 'group' => 'PROFILE', 'icon' => 'edit'],
            ['href' => '/account/resumes/builder', 'label' => 'Hire Resume', 'group' => 'RESUMES', 'icon' => 'edit'],
            ['href' => '/account/resumes', 'label' => 'My Resume', 'group' => 'RESUMES', 'icon' => 'file'],
            ['href' => '/account/applications', 'label' => 'My Applications', 'group' => 'ACTIVITY', 'icon' => 'clipboard'],
            ['href' => '/account/saved-jobs', 'label' => 'Saved Jobs', 'group' => 'ACTIVITY', 'icon' => 'search'],
            ['href' => '/account/settings/account', 'label' => 'Settings', 'group' => 'ACCOUNT', 'icon' => 'settings'],
            ['href' => '/account/contact', 'label' => 'Contact Us', 'group' => 'ACCOUNT', 'icon' => 'message'],
        ];
    }
}
