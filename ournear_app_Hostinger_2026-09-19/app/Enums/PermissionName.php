<?php

namespace App\Enums;

enum PermissionName: string
{
    case JobsView = 'jobs.view';
    case JobsManage = 'jobs.manage';
    case CandidatesView = 'candidates.view';
    case CandidatesManage = 'candidates.manage';
    case ApplicationsView = 'applications.view';
    case ApplicationsManage = 'applications.manage';
    case SubmissionsView = 'submissions.view';
    case SubmissionsManage = 'submissions.manage';
    case PlacementsView = 'placements.view';
    case PlacementsManage = 'placements.manage';
    case EmployeesView = 'employees.view';
    case EmployeesManage = 'employees.manage';
    case ReplacementsView = 'replacements.view';
    case ReplacementsManage = 'replacements.manage';
    case EmployersView = 'employers.view';
    case EmployersManage = 'employers.manage';
    case FreelancersView = 'freelancers.view';
    case FreelancersManage = 'freelancers.manage';
    case FinanceView = 'finance.view';
    case FinanceManage = 'finance.manage';
    case NotificationsView = 'notifications.view';
    case MessagesManage = 'messages.manage';
    case WebsiteManage = 'website.manage';
    case MasterDataManage = 'master_data.manage';
    case UsersView = 'users.view';
    case UsersManage = 'users.manage';
    case RolesManage = 'roles.manage';
    case SecurityView = 'security.view';

    public function group(): string
    {
        return match ($this) {
            self::JobsView, self::JobsManage,
            self::CandidatesView, self::CandidatesManage,
            self::ApplicationsView, self::ApplicationsManage,
            self::SubmissionsView, self::SubmissionsManage,
            self::PlacementsView, self::PlacementsManage,
            self::EmployeesView, self::EmployeesManage,
            self::ReplacementsView, self::ReplacementsManage,
            self::FreelancersView, self::FreelancersManage => 'Recruitment',
            self::EmployersView, self::EmployersManage => 'Employers',
            self::FinanceView, self::FinanceManage => 'Finance',
            self::NotificationsView, self::MessagesManage => 'Communication',
            self::WebsiteManage => 'Website',
            self::MasterDataManage, self::UsersView, self::UsersManage, self::RolesManage => 'Administration',
            self::SecurityView => 'Security',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::JobsView => 'View jobs',
            self::JobsManage => 'Manage jobs',
            self::CandidatesView => 'View candidates',
            self::CandidatesManage => 'Manage candidates',
            self::ApplicationsView => 'View applications',
            self::ApplicationsManage => 'Manage applications',
            self::SubmissionsView => 'View employer submissions',
            self::SubmissionsManage => 'Manage employer submissions',
            self::PlacementsView => 'View placements',
            self::PlacementsManage => 'Manage placements',
            self::EmployeesView => 'View employees',
            self::EmployeesManage => 'Manage employees',
            self::ReplacementsView => 'View replacements',
            self::ReplacementsManage => 'Manage replacements',
            self::EmployersView => 'View employers',
            self::EmployersManage => 'Manage employers',
            self::FreelancersView => 'View freelancers',
            self::FreelancersManage => 'Manage freelancers',
            self::FinanceView => 'View finance',
            self::FinanceManage => 'Manage finance',
            self::NotificationsView => 'View notifications',
            self::MessagesManage => 'Manage messages',
            self::WebsiteManage => 'Manage website',
            self::MasterDataManage => 'Manage master data',
            self::UsersView => 'View users',
            self::UsersManage => 'Manage users',
            self::RolesManage => 'Manage roles and permissions',
            self::SecurityView => 'View security logs',
        };
    }
}
