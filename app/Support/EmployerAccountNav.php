<?php

namespace App\Support;

class EmployerAccountNav
{
    /**
     * Grouped hiring workspace navigation for account employers (and hiring-enabled users).
     *
     * @return list<array{href: string, label: string, group?: string}>
     */
    public static function items(): array
    {
        return [
            ['href' => '/account/hiring', 'label' => 'Overview', 'group' => 'HIRING'],
            ['href' => '/account/jobs', 'label' => 'Jobs', 'group' => 'HIRING'],
            ['href' => '/account/jobs/create', 'label' => 'Create Job', 'group' => 'HIRING'],
            ['href' => '/account/hiring/applications', 'label' => 'Applications', 'group' => 'HIRING'],
            ['href' => '/account/hiring/candidates', 'label' => 'All Candidates', 'group' => 'TALENT POOL'],
            ['href' => '/account/hiring/candidates?stage=shortlisted', 'label' => 'Shortlisted', 'group' => 'TALENT POOL'],
            ['href' => '/account/hiring/candidates?stage=under_review', 'label' => 'In review', 'group' => 'TALENT POOL'],
            ['href' => '/account/hiring/candidates?stage=interview', 'label' => 'Interviewed', 'group' => 'TALENT POOL'],
            ['href' => '/account/hiring/candidates?stage=offered', 'label' => 'Offered', 'group' => 'TALENT POOL'],
            ['href' => '/account/hiring/candidates?stage=joined', 'label' => 'Joined', 'group' => 'TALENT POOL'],
            ['href' => '/account/submissions', 'label' => 'Recruitment Partnership', 'group' => 'SERVICES'],
            ['href' => '/account/enquiries', 'label' => 'Staffing', 'group' => 'SERVICES'],
            ['href' => '/account/profile', 'label' => 'Company Profile', 'group' => 'ACCOUNT'],
            ['href' => '/account/settings/security', 'label' => 'Verification', 'group' => 'ACCOUNT'],
            ['href' => '/account/notifications', 'label' => 'Notifications', 'group' => 'ACCOUNT'],
            ['href' => '/account/settings', 'label' => 'Settings', 'group' => 'ACCOUNT'],
        ];
    }
}
