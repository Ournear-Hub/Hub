# Admin restricted-role access control hardening

**Date:** 2026-10-12  
**Workspace:** `D:\Ournear Hub\Ournear Hire`  
**Status:** **COMPLETE (code)** — PHPUnit / browser / build **NOT RUN** (deferred by product owner)

## 1. Confirmed gaps and root causes

| Gap | Root cause | Permission model |
| --- | --- | --- |
| Mail **validate**, **test SMTP connection**, and **prerequisites** JSON endpoints | `EmailSettingsController` methods had no `canManageWebsite()` gate; only `update`, `sendTest`, and `previewVerification` were protected | `website.manage` (`PermissionName::WebsiteManage`) — ContentManager catalog role; same as Admin → Settings / Email delivery |
| Admin **design-system** catalog routes | `DesignSystemController::show()` validated section slug only; any authenticated staff could open `/admin/design-system/*` by URL | `website.manage` — aligned with website/CMS/settings surfaces |

**Audit note:** Recruitment mutations (jobs, applications, master data, users, automation, candidate files) already enforce policies / FormRequests per `AccessCatalog`. Finance `/admin/hiring` staffing slice and Viewer read-only paths were verified unchanged; no new permissions introduced.

## 2. Implementation

- Added `MailDiagnosticRequest` (`authorize` → `canManageWebsite()`) and wired it to mail diagnostic actions.
- Gated `DesignSystemController::show()` with `canManageWebsite()`.
- Preserved existing 403 conventions, audit logging, and transaction behavior on mutating routes (unchanged in this batch).

## 3. Files and routes

| File | Change |
| --- | --- |
| `app/Http/Requests/Admin/MailDiagnosticRequest.php` | New FormRequest |
| `app/Http/Controllers/Admin/EmailSettingsController.php` | Diagnostic endpoints require `website.manage` |
| `app/Http/Controllers/Admin/DesignSystemController.php` | Catalog requires `website.manage` |
| `tests/Feature/AdminMailSettingsTest.php` | Recruiter forbidden on diagnostic routes |
| `tests/Feature/AdminRestrictedRoleAccessTest.php` | **5 tests — NOT RUN** |
| `docs/OURNEAR_RELEASE_ISSUE_REGISTER.md` | REG-SEC-003 row |
| `docs/OURNEAR_REG_PROD_002_EMAIL_MANAGEMENT_REPORT.md` | Security note |
| `docs/OURNEAR_STAGE_5_ADMIN_IMPLEMENTATION_REPORT.md` | 5E hardening note |

**Routes affected:** `admin.settings.email.validate`, `admin.settings.email.test-connection`, `admin.settings.email.prerequisites`, all `admin.design-system.*`.

## 4. Authorization protections preserved

- Super-admin / Admin catalog roles unchanged (`AccessCatalog`, `AuthorizationBootstrapper`).
- Employer and candidate guards and policies untouched.
- Company isolation, application privacy, resume delivery, job publish limits, and REG-BRW-004 screening compare code paths not modified.

## 5. Tests authored (not run)

```text
NOT RUN — deferred by product owner
php artisan test --filter=AdminRestrictedRoleAccessTest
php artisan test --filter=AdminMailSettingsTest
php artisan test --filter=AdminRbacStage25Test
php artisan test --filter=UsersRolesSecurityTest
```

## 6. Remaining limitations

- Partial-role **browser** matrix for admin nav (REG-PROD-003) still **NOT TESTED**.
- No independent QA sign-off or release readiness claimed.
- Design-system routes remain absent from sidebar; URL knowledge still required for ContentManager access (intentional dev catalog).

## 7. Product owner decisions (optional)

- Whether **SuperAdmin-only** (vs `website.manage`) should gate design-system in production deployments.
- Whether to add explicit sidebar entry for design-system under Website & Content for ContentManager only.
