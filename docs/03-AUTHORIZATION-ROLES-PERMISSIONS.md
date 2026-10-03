# Authorization, roles, permissions

## Two layers

1. **Account type** — `users.role` (`UserRole`): who is logging into account vs admin.
2. **Staff RBAC** — `roles`, `permissions`, pivot; names from `PermissionName`.

Policies live in `app/Policies` and are registered in `AppServiceProvider`.

## Account types

| Role | Admin UI | Account UI |
| --- | --- | --- |
| `admin` | Yes (with staff role) | No (typically) |
| `candidate` | No | Candidate routes |
| `employer` | No | Employer routes |
| `partner` | No dedicated portal | [FOUNDATION ONLY] enum value |
| `investor` | Admin Investors module is staff-managed | [FOUNDATION ONLY] no account portal |

## Staff role slugs (`StaffRoleSlug`)

`super_admin`, `admin`, `recruitment_manager`, `recruiter`, `hr_operations`, `finance`, `content_manager`, `viewer`.

Grants: `AccessCatalog::rolePermissions()`. Super Admin is locked (`isLocked()`).

`AuthorizationBootstrapper::seedRoles()`:

- Super Admin (locked): full `sync()` of every catalog permission.
- Other catalog staff roles: `syncWithoutDetaching` so catalog grants are a **floor**; extras added in Admin role management are kept.
- `roles.manage` is detached from every role except Super Admin on each bootstrap run.

`RoleManagementService` rejects granting `roles.manage` to any non–Super Admin role, rejects catalog-permission removals on catalog roles, and rejects granting permissions the actor does not have.

`UserManagementService::syncRoles` rejects assigning a role that includes `roles.manage` or any permission the actor lacks (Super Admin assignment remains Super-Admin-only).

Hiring Command Center (`/admin/hiring`) requires `User::canViewHiringCommandCenter()` (`AccessCatalog::hiringCommandCenterPermissions()`). Module cards inside the page still follow jobs/applications/submissions/placements/employees/replacements/finance/automation permissions. Sidebar uses `auth.user.can_view_hiring`. Details: [24-HIRING-COMMAND-CENTER.md](24-HIRING-COMMAND-CENTER.md).

Bulk resume import uses `candidates.view` / `candidates.manage` only — [25-HIRE-IDENTITY-AND-RESUME-IMPORT.md](25-HIRE-IDENTITY-AND-RESUME-IMPORT.md).

Tests: `tests/Feature/AdminRbacStage25Test.php`, `RolesPermissionsStage2Test.php`, `UsersRolesSecurityTest.php`, `RecruiterHiringCommandCenterStage26Test.php`.

## Permissions (34)

Pattern `{module}.view` / `{module}.manage` except some manage-only (`website.manage`, `master_data.manage`, `roles.manage`, `messages.manage`, `security.view`).

Includes `automation.view` / `automation.manage`.

Finance role: finance + employees view + notifications — **not** automations.

Viewer: includes `automation.view`, not manage.

## Typical capabilities (admin)

| Action | Typical permission / policy |
| --- | --- |
| View jobs | `jobs.view` |
| Publish/edit jobs | `jobs.manage` |
| Shortlist / pipeline | `applications.manage` + `ApplicationPolicy` |
| Submit to employer | applications/submissions manage |
| Invoices | `finance.manage` |
| CMS | `website.manage` |
| Roles | `roles.manage` |
| Automation CRUD | `automation.manage` |

## Employer isolation

Employer account queries scoped to `employer_profile_id` / job `employer_id`. Employers must not see other companies’ applicants beyond allowed submission/applicant routes.

## Candidate privacy

Candidates cannot open other candidates’ files, applications, or notifications (policy + tests).

## Ownership

Applications belong to a job; jobs to `employer_profiles` (nullable until assigned). Files belong to candidate/employer profile records, not raw public paths.

Do not invent a second permission system for automation or tasks. Tasks reuse applications/jobs abilities (`TaskPolicy`).
