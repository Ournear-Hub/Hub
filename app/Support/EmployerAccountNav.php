<?php

namespace App\Support;

class EmployerAccountNav
{
    /**
     * Grouped hiring workspace navigation for account employers (and hiring-enabled users).
     *
     * @return list<array{href: string, label: string, group?: string, icon?: string}>
     */
    public static function items(): array
    {
        return [
            ['href' => '/account/hiring', 'label' => 'My Workspace', 'group' => 'OVERVIEW', 'icon' => 'home'],

            ['href' => '/account/jobs', 'label' => 'Jobs', 'group' => 'HIRING ATS', 'icon' => 'briefcase'],
            ['href' => '/account/hiring/applications?stage='.AtsStage::Screening, 'label' => 'Screening', 'group' => 'HIRING ATS', 'icon' => 'eye'],
            ['href' => '/account/hiring/automation', 'label' => 'Automation', 'group' => 'HIRING ATS', 'icon' => 'settings'],
            ['href' => '/account/hiring/applications?stage='.AtsStage::Shortlisted, 'label' => 'Shortlisted', 'group' => 'HIRING ATS', 'icon' => 'check'],
            ['href' => '/account/hiring/applications', 'label' => 'Applications', 'group' => 'HIRING ATS', 'icon' => 'clipboard'],
            ['href' => '/account/hiring/applications?stage='.AtsStage::Interview, 'label' => 'Interviewed', 'group' => 'HIRING ATS', 'icon' => 'message'],
            ['href' => '/account/hiring/applications?stage='.AtsStage::Joined, 'label' => 'Joined', 'group' => 'HIRING ATS', 'icon' => 'check'],

            ['href' => '/account/coordinated-talent-pool', 'label' => 'OurNear submissions', 'group' => 'TALENT POOL', 'icon' => 'clipboard'],
            ['href' => '/account/hiring/candidates', 'label' => 'All Candidates', 'group' => 'HIRING ATS', 'icon' => 'user'],
            ['href' => '/account/hiring/candidates?stage=shortlisted', 'label' => 'Shortlisted', 'group' => 'HIRING ATS', 'icon' => 'check'],
            ['href' => '/account/hiring/candidates?stage=under_review', 'label' => 'In Review', 'group' => 'HIRING ATS', 'icon' => 'eye'],
            ['href' => '/account/hiring/candidates?stage=interview', 'label' => 'Interviewed', 'group' => 'HIRING ATS', 'icon' => 'message'],
            ['href' => '/account/hiring/candidates?stage=offered', 'label' => 'Offered', 'group' => 'HIRING ATS', 'icon' => 'download'],
            ['href' => '/account/hiring/candidates?stage=joined', 'label' => 'Joined', 'group' => 'HIRING ATS', 'icon' => 'check'],
            ['href' => '/account/recruitment-support', 'label' => 'Recruitment Support', 'group' => 'HIRING ATS', 'icon' => 'clipboard'],
            ['href' => '/account/talent-pool', 'label' => 'Assigned Candidates', 'group' => 'TALENT POOL', 'icon' => 'user'],

            ['href' => '/account/enquiries', 'label' => 'Staffing', 'group' => 'STAFFING', 'icon' => 'message'],

            ['href' => '/account/company', 'label' => 'Company Profile', 'group' => 'SETTINGS', 'icon' => 'building'],
            ['href' => '/account/settings/verification', 'label' => 'Recruiter Verification', 'group' => 'SETTINGS', 'icon' => 'check'],

            ['href' => '/account/employee-reviews', 'label' => 'Employee reviews', 'group' => 'ACCOUNT', 'icon' => 'info'],
            ['href' => '/account/contact', 'label' => 'Contact Us', 'group' => 'ACCOUNT', 'icon' => 'message'],
        ];
    }
}
