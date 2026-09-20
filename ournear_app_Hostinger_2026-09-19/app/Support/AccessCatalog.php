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
                PermissionName::NotificationsView->value,
                PermissionName::MessagesManage->value,
            ],
            StaffRoleSlug::Finance->value => [
                PermissionName::EmployeesView->value,
                PermissionName::FinanceView->value,
                PermissionName::FinanceManage->value,
                PermissionName::NotificationsView->value,
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
        ];
    }
}
