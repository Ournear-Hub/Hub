<?php

namespace App\Support;

use App\Models\User;
use App\Support\AccessCatalog;

/**
 * Batch 2 / REG-PROD-003 — grouped admin sidebar (Core Operations, Recruitment Operations, Platform Management).
 */
class AdminNavigationCatalog
{
    /**
     * @return list<array{id: string, label: string, icon: string, items: list<array<string, mixed>>}>
     */
    public static function sections(): array
    {
        return [
            [
                'id' => 'core-operations',
                'label' => 'Core Operations',
                'icon' => 'home',
                'items' => [
                    ['label' => 'Dashboard', 'href' => '/admin'],
                    ['label' => 'Companies', 'href' => '/admin/employers', 'permission' => 'employers.view'],
                    ['label' => 'Candidates', 'href' => '/admin/users?segment=candidates', 'permission' => 'users.view'],
                    ['label' => 'Recruiters', 'href' => '/admin/staff?staff_role=recruiter', 'permission' => 'users.view'],
                ],
            ],
            [
                'id' => 'recruitment-operations',
                'label' => 'Recruitment Operations',
                'icon' => 'briefcase',
                'items' => [
                    [
                        'label' => 'Hiring / ATS',
                        'href' => '/admin/hiring',
                        'permission' => 'applications.view',
                        'children' => [
                            ['label' => 'Command Center', 'href' => '/admin/hiring', 'permission' => 'applications.view'],
                            ['label' => 'My Work', 'href' => '/admin/work', 'permission' => 'applications.view'],
                            ['label' => 'Jobs', 'href' => '/admin/jobs', 'permission' => 'jobs.view'],
                            ['label' => 'Applications', 'href' => '/admin/applications', 'permission' => 'applications.view'],
                            ['label' => 'Interviews', 'href' => '/admin/interviews', 'permission' => 'applications.view'],
                            ['label' => 'Scorecard templates', 'href' => '/admin/scorecard-templates', 'permission' => 'applications.view'],
                            ['label' => 'Tasks', 'href' => '/admin/tasks', 'permission' => 'applications.view'],
                            ['label' => 'Automation', 'href' => '/admin/automations', 'permission' => 'automation.view'],
                            ['label' => 'Screening', 'href' => '/admin/screening', 'permission' => 'applications.view'],
                            ['label' => 'Shortlisted', 'href' => '/admin/shortlisted', 'permission' => 'applications.view'],
                            ['label' => 'Placements', 'href' => '/admin/placements', 'permission' => 'placements.view'],
                            ['label' => 'Employees', 'href' => '/admin/employees', 'permission' => 'employees.view'],
                            ['label' => 'Replacements', 'href' => '/admin/replacements', 'permission' => 'replacements.view'],
                        ],
                    ],
                    [
                        'label' => 'Talent Pool',
                        'href' => '/admin/talent-pool-assignments',
                        'permission' => 'candidates.view',
                        'children' => [
                            [
                                'label' => 'Recruiter workspace',
                                'href' => '/admin/talent-pool-workspace',
                                'permission' => 'candidates.view',
                            ],
                            [
                                'label' => 'Assignments & coordination',
                                'href' => '/admin/talent-pool-assignments',
                                'permission' => 'candidates.view',
                            ],
                            [
                                'label' => 'Talent Pool jobs',
                                'href' => '/admin/talent-pool-jobs',
                                'permission' => 'jobs.view',
                            ],
                            ['label' => 'Browse talent pool', 'href' => '/admin/candidates', 'permission' => 'candidates.view'],
                            ['label' => 'Resume Import', 'href' => '/admin/candidates/imports', 'permission' => 'candidates.manage'],
                            ['label' => 'Internal resumes', 'href' => '/admin/candidates/internal-resumes', 'permission' => 'candidates.view'],
                        ],
                    ],
                    [
                        'label' => 'Staffing',
                        'href' => '/admin/employer-enquiries',
                        'permission' => 'employers.view',
                        'children' => [
                            ['label' => 'Staffing workspace', 'href' => '/admin/staffing-workspace', 'permission' => 'employers.view'],
                            ['label' => 'Staffing enquiries', 'href' => '/admin/employer-enquiries', 'permission' => 'employers.view'],
                            ['label' => 'Recruitment partnership', 'href' => '/admin/submissions', 'permission' => 'submissions.view'],
                        ],
                    ],
                ],
            ],
            [
                'id' => 'platform-management',
                'label' => 'Platform Management',
                'icon' => 'settings',
                'items' => [
                    ['label' => 'Website Content', 'href' => '/admin/website-content', 'permission' => 'website.manage'],
                    ['label' => 'Media & Social', 'href' => '/admin/media-social', 'permission' => 'website.manage'],
                    [
                        'label' => 'Notifications & templates',
                        'href' => '/admin/notifications',
                        'permission' => 'notifications.view',
                        'children' => [
                            ['label' => 'Notifications', 'href' => '/admin/notifications', 'permission' => 'notifications.view'],
                            ['label' => 'Message templates', 'href' => '/admin/message-templates', 'permission' => 'notifications.view'],
                            ['label' => 'Messages', 'href' => '/admin/messages', 'permission' => 'messages.manage'],
                            ['label' => 'Campaigns', 'href' => '/admin/campaigns', 'permission' => 'campaigns.view'],
                            ['label' => 'Email delivery', 'href' => '/admin/settings?tab=email', 'permission' => 'website.manage'],
                        ],
                    ],
                    [
                        'label' => 'Master Data',
                        'href' => '/admin/master-data',
                        'permission' => 'master_data.manage',
                        'children' => [
                            ['label' => 'Industries', 'href' => '/admin/master-data?tab=industries', 'permission' => 'master_data.manage'],
                            ['label' => 'Categories', 'href' => '/admin/master-data?tab=categories', 'permission' => 'master_data.manage'],
                            ['label' => 'Job Titles', 'href' => '/admin/master-data?tab=job-titles', 'permission' => 'master_data.manage'],
                            ['label' => 'Locations', 'href' => '/admin/master-data?tab=locations', 'permission' => 'master_data.manage'],
                            ['label' => 'Other Master Data', 'href' => '/admin/master-data', 'permission' => 'master_data.manage'],
                        ],
                    ],
                    [
                        'label' => 'Settings & permissions',
                        'href' => '/admin/settings',
                        'permission' => 'website.manage',
                        'children' => [
                            ['label' => 'Settings', 'href' => '/admin/settings', 'permission' => 'website.manage'],
                            ['label' => 'Admin Users', 'href' => '/admin/staff', 'permission' => 'users.view'],
                            ['label' => 'All platform users', 'href' => '/admin/users', 'permission' => 'users.view'],
                            ['label' => 'Roles & Permissions', 'href' => '/admin/roles', 'permission' => 'roles.manage'],
                            ['label' => 'Activity Logs', 'href' => '/admin/activity-logs', 'permission' => 'security.view'],
                            ['label' => 'Login Logs', 'href' => '/admin/login-logs', 'permission' => 'security.view'],
                            ['label' => 'Design system (UI Lab)', 'href' => '/admin/design-system', 'permission' => 'website.manage'],
                            ['label' => 'Freelancer intake', 'href' => '/admin/freelancers', 'permission' => 'freelancers.view'],
                            ['label' => 'Investors', 'href' => '/admin/investors', 'permission' => 'investors.view'],
                            ['label' => 'Invoices', 'href' => '/admin/finance?view=invoices', 'permission' => 'finance.view'],
                            ['label' => 'Payments', 'href' => '/admin/finance?view=payments', 'permission' => 'finance.view'],
                            ['label' => 'Commission', 'href' => '/admin/commission', 'permission' => 'finance.view'],
                            ['label' => 'Partners', 'href' => '/admin/partners', 'permission' => 'employers.view'],
                            ['label' => 'CRM', 'href' => '/admin/crm', 'permission' => 'employers.view'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return list<array{id: string, label: string, icon: string, items: list<array<string, mixed>>}>
     */
    public static function visibleSectionsFor(User $user): array
    {
        $permissions = $user->permissionNames();

        return collect(self::sections())
            ->map(function (array $section) use ($user, $permissions) {
                if ($section['id'] === 'core-operations') {
                    $items = [['label' => 'Dashboard', 'href' => '/admin']];
                    if ($user->canViewOperationalAnalytics()) {
                        $items[] = ['label' => 'Analytics', 'href' => '/admin/analytics'];
                    }
                    $items = array_merge($items, array_slice($section['items'], 1));
                    $section['items'] = self::visibleItems($items, $permissions);
                } else {
                    $section['items'] = self::visibleItems($section['items'], $permissions);
                }

                return $section;
            })
            ->filter(fn (array $section) => $section['items'] !== [])
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @param  list<string>  $permissions
     * @return list<array<string, mixed>>
     */
    public static function visibleItems(array $items, array $permissions): array
    {
        $filtered = [];

        foreach ($items as $item) {
            $children = isset($item['children']) && is_array($item['children'])
                ? self::visibleItems($item['children'], $permissions)
                : null;

            $allowed = self::hasPermission($permissions, $item['permission'] ?? null)
                || ($children !== null && $children !== []);

            if ($allowed) {
                $entry = $item;
                if ($children !== null) {
                    $entry['children'] = $children;
                }
                $filtered[] = $entry;
            }
        }

        return $filtered;
    }

    /**
     * @param  list<string>  $permissions
     */
    public static function hasPermission(array $permissions, ?string $required): bool
    {
        if ($required === null || $required === '') {
            return true;
        }

        if (in_array($required, $permissions, true)) {
            return true;
        }

        $manage = str_replace('.view', '.manage', $required);

        return in_array($manage, $permissions, true);
    }

    /**
     * @return list<string>
     */
    public static function collectHrefs(): array
    {
        $hrefs = [];

        $walk = function (array $items) use (&$hrefs, &$walk): void {
            foreach ($items as $item) {
                if (isset($item['href']) && is_string($item['href'])) {
                    $hrefs[] = $item['href'];
                }
                if (isset($item['children']) && is_array($item['children'])) {
                    $walk($item['children']);
                }
            }
        };

        foreach (self::sections() as $section) {
            $walk($section['items']);
        }

        return array_values(array_unique($hrefs));
    }

    /**
     * @return list<string>
     */
    public static function permissionKeysUsed(): array
    {
        $keys = [];

        $walk = function (array $items) use (&$keys, &$walk): void {
            foreach ($items as $item) {
                if (isset($item['permission']) && is_string($item['permission'])) {
                    $keys[] = $item['permission'];
                }
                if (isset($item['children']) && is_array($item['children'])) {
                    $walk($item['children']);
                }
            }
        };

        foreach (self::sections() as $section) {
            $walk($section['items']);
        }

        return array_values(array_unique($keys));
    }

    public static function assertPermissionsExistInCatalog(): void
    {
        $catalog = AccessCatalog::permissionNames();
        foreach (self::permissionKeysUsed() as $permission) {
            if (! in_array($permission, $catalog, true)) {
                throw new \InvalidArgumentException("Unknown admin nav permission: {$permission}");
            }
        }
    }
}
