<?php

namespace App\Support;

use App\Enums\PermissionName;
use App\Enums\StaffRoleSlug;

class AccessCatalog
{
    /**
     * @return list<array{name: string, group: string, label: string}>
     */
    public static function permissions(): array
    {
        return array_map(fn (PermissionName $permission) => [
            'name' => $permission->value,
            'group' => $permission->group(),
            'label' => $permission->label(),
        ], PermissionName::cases());
    }

    /**
     * @return list<string>
     */
    public static function permissionNames(): array
    {
        return array_map(fn (PermissionName $permission) => $permission->value, PermissionName::cases());
    }

    /**
     * @return array<string, list<string>>
     */
    public static function rolePermissions(): array
    {
        $all = self::permissionNames();
        $admin = array_values(array_filter(
            $all,
            fn (string $name) => $name !== PermissionName::RolesManage->value,
        ));

        return [
            StaffRoleSlug::SuperAdmin->value => $all,
            StaffRoleSlug::Admin->value => $admin,
            StaffRoleSlug::Recruiter->value => [
                PermissionName::JobsView->value,
                PermissionName::JobsManage->value,
                PermissionName::CandidatesView->value,
                PermissionName::CandidatesManage->value,
                PermissionName::ApplicationsView->value,
                PermissionName::ApplicationsManage->value,
                PermissionName::SubmissionsView->value,
                PermissionName::SubmissionsManage->value,
                PermissionName::PlacementsView->value,
                PermissionName::PlacementsManage->value,
                PermissionName::EmployeesView->value,
                PermissionName::EmployeesManage->value,
                PermissionName::ReplacementsView->value,
                PermissionName::ReplacementsManage->value,
                PermissionName::EmployersView->value,
                PermissionName::EmployersManage->value,
                PermissionName::FreelancersView->value,
                PermissionName::FreelancersManage->value,
                PermissionName::NotificationsView->value,
                PermissionName::MessagesManage->value,
                PermissionName::AutomationView->value,
                PermissionName::AutomationManage->value,
            ],
            StaffRoleSlug::RecruitmentManager->value => [
                PermissionName::JobsView->value,
                PermissionName::JobsManage->value,
                PermissionName::CandidatesView->value,
                PermissionName::CandidatesManage->value,
                PermissionName::ApplicationsView->value,
                PermissionName::ApplicationsManage->value,
                PermissionName::SubmissionsView->value,
                PermissionName::SubmissionsManage->value,
                PermissionName::PlacementsView->value,
                PermissionName::PlacementsManage->value,
                PermissionName::EmployeesView->value,
                PermissionName::EmployeesManage->value,
                PermissionName::ReplacementsView->value,
                PermissionName::ReplacementsManage->value,
                PermissionName::EmployersView->value,
                PermissionName::EmployersManage->value,
                PermissionName::FreelancersView->value,
                PermissionName::FreelancersManage->value,
                PermissionName::UsersView->value,
                PermissionName::NotificationsView->value,
                PermissionName::MessagesManage->value,
                PermissionName::AutomationView->value,
                PermissionName::AutomationManage->value,
            ],
            StaffRoleSlug::HrOperations->value => [
                PermissionName::JobsView->value,
                PermissionName::CandidatesView->value,
                PermissionName::CandidatesManage->value,
                PermissionName::ApplicationsView->value,
                PermissionName::ApplicationsManage->value,
                PermissionName::PlacementsView->value,
                PermissionName::PlacementsManage->value,
                PermissionName::EmployeesView->value,
                PermissionName::EmployeesManage->value,
                PermissionName::ReplacementsView->value,
                PermissionName::ReplacementsManage->value,
                PermissionName::EmployersView->value,
                PermissionName::FreelancersView->value,
                PermissionName::FreelancersManage->value,
                PermissionName::NotificationsView->value,
            ],
            StaffRoleSlug::Finance->value => [
                PermissionName::EmployeesView->value,
                PermissionName::FinanceView->value,
                PermissionName::FinanceManage->value,
                PermissionName::NotificationsView->value,
            ],
            StaffRoleSlug::ContentManager->value => [
                PermissionName::WebsiteManage->value,
                PermissionName::NotificationsView->value,
            ],
            StaffRoleSlug::Viewer->value => [
                PermissionName::JobsView->value,
                PermissionName::CandidatesView->value,
                PermissionName::ApplicationsView->value,
                PermissionName::SubmissionsView->value,
                PermissionName::PlacementsView->value,
                PermissionName::EmployeesView->value,
                PermissionName::ReplacementsView->value,
                PermissionName::EmployersView->value,
                PermissionName::FreelancersView->value,
                PermissionName::FinanceView->value,
                PermissionName::NotificationsView->value,
                PermissionName::UsersView->value,
                PermissionName::SecurityView->value,
                PermissionName::InvestorsView->value,
                PermissionName::CampaignsView->value,
                PermissionName::AutomationView->value,
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function recruitmentManagePermissions(): array
    {
        return [
            PermissionName::JobsManage->value,
            PermissionName::CandidatesManage->value,
            PermissionName::ApplicationsManage->value,
            PermissionName::SubmissionsManage->value,
            PermissionName::PlacementsManage->value,
            PermissionName::EmployeesManage->value,
            PermissionName::ReplacementsManage->value,
            PermissionName::EmployersManage->value,
            PermissionName::FreelancersManage->value,
        ];
    }

    public static function isCatalogRole(string $slug): bool
    {
        return StaffRoleSlug::tryFrom($slug) !== null;
    }

    /**
     * Permissions that justify opening operational analytics.
     * Website/content-only staff (plus notifications/messages) are excluded.
     *
     * @return list<string>
     */
    public static function operationalAnalyticsPermissions(): array
    {
        return array_values(array_filter(
            self::permissionNames(),
            fn (string $name) => ! in_array($name, [
                PermissionName::WebsiteManage->value,
                PermissionName::NotificationsView->value,
                PermissionName::MessagesManage->value,
            ], true),
        ));
    }

    /**
     * Permissions that unlock the Recruiter Hiring Command Center.
     *
     * @return list<string>
     */
    public static function hiringCommandCenterPermissions(): array
    {
        return [
            PermissionName::JobsView->value,
            PermissionName::JobsManage->value,
            PermissionName::ApplicationsView->value,
            PermissionName::ApplicationsManage->value,
            PermissionName::SubmissionsView->value,
            PermissionName::SubmissionsManage->value,
            PermissionName::PlacementsView->value,
            PermissionName::PlacementsManage->value,
            PermissionName::EmployeesView->value,
            PermissionName::EmployeesManage->value,
            PermissionName::ReplacementsView->value,
            PermissionName::ReplacementsManage->value,
        ];
    }
}
