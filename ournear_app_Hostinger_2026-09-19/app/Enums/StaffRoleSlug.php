<?php

namespace App\Enums;

enum StaffRoleSlug: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Recruiter = 'recruiter';
    case Finance = 'finance';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin',
            self::Recruiter => 'Recruiter',
            self::Finance => 'Finance',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Full operational access plus users, roles, and security configuration.',
            self::Admin => 'Full operational access except role and permission changes.',
            self::Recruiter => 'Recruitment, employers, notifications, and internal messages.',
            self::Finance => 'Invoices, payments, employee billing context, and notifications.',
        };
    }

    public function isLocked(): bool
    {
        return $this === self::SuperAdmin;
    }
}
