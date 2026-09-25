<?php

namespace App\Enums;

enum StaffRoleSlug: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case RecruitmentManager = 'recruitment_manager';
    case Recruiter = 'recruiter';
    case HrOperations = 'hr_operations';
    case Finance = 'finance';
    case ContentManager = 'content_manager';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin',
            self::RecruitmentManager => 'Recruitment Manager',
            self::Recruiter => 'Recruiter',
            self::HrOperations => 'HR / Operations',
            self::Finance => 'Finance',
            self::ContentManager => 'Content Manager',
            self::Viewer => 'Viewer',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Full operational access plus users, roles, and security configuration.',
            self::Admin => 'Full operational access except role and permission changes.',
            self::RecruitmentManager => 'Recruitment leadership, employers, platform user viewing, and internal communication.',
            self::Recruiter => 'Recruitment, employers, notifications, and internal messages.',
            self::HrOperations => 'Recruitment operations, employees, replacements, and notifications.',
            self::Finance => 'Invoices, payments, employee billing context, and notifications.',
            self::ContentManager => 'Website content, media, social links, and notifications.',
            self::Viewer => 'Read-only access to operational modules.',
        };
    }

    public function isLocked(): bool
    {
        return $this === self::SuperAdmin;
    }
}
