# Hiring Command Center

**Status:** IMPLEMENTED (Stage 26). Orchestration only. Not a second ATS, match engine, or shortlist engine.

## Purpose

Staff dashboard at `/admin/hiring` (`admin.hiring.index`) that **aggregates** existing recruitment workspaces for one signed-in admin user.

Product lines (query `line`): `all` | `ats` | `partnership` | `staffing` | `jobs`. Optional `job_id`.

## Source files

| Piece | Location |
| --- | --- |
| Controller | `app/Http/Controllers/Admin/HiringCommandCenterController.php` |
| Service | `app/Services/RecruiterHiringCommandCenterService.php` |
| Request | `app/Http/Requests/Admin/HiringCommandCenterRequest.php` |
| Page | `resources/js/Pages/Admin/Hiring/CommandCenter.tsx` |
| Gate | `User::canViewHiringCommandCenter()` + `AccessCatalog::hiringCommandCenterPermissions()` |
| Shared prop | `auth.user.can_view_hiring` (`HandleInertiaRequests`) |
| Nav | Admin sidebar item “Hiring Command Center” |

## Authorization

Opening the page requires **any** of: `jobs.view/manage`, `applications.view/manage`, `submissions.view/manage`, `placements.view/manage`, `employees.view/manage`, `replacements.view/manage`.

Cards inside the payload still follow per-module permissions (finance invoices only with `finance.view`/`finance.manage`; automation history with `automation.view`/`automation.manage`). Direct URL access is server-gated; hiding the sidebar link is convenience.

## What it reuses

- `ApplicationQuery`, `EmployerSubmissionQuery`
- Existing statuses: applications, submissions, placements, replacements, invoices, tasks, automation executions
- Links into existing Admin routes (jobs, applications, screening, submissions, etc.)

**Does not** call `ScreeningMatchService` to rescore. **Does not** auto shortlist/select/offer/reject.

## Semantics in this UI

- **Partnership** = `EmployerSubmission` client review (not Channel Partner portal).
- **OurNear Jobs origin** = applications with `user_id` null (guest snapshot) vs account apply.
- **Workload** = tasks `assigned_to` the current user. There is **no** recruiter-owner column on applications/jobs (`ownership_note` in the payload).
- **Staffing assignment end date** = [NOT IMPLEMENTED] (no such field).

## Schema fallback

Upcoming-interview counts prefer `application_interviews.lifecycle_status`. If the column is missing (local DB with Stage 6 migration still pending), the service falls back to `outcome` / `scheduled_at`.

## Tests

`tests/Feature/RecruiterHiringCommandCenterStage26Test.php`

## Do not

- Add a fifth matching/pipeline engine here.
- Merge this with My Work (`/admin/work`) — My Work remains personal assigned tasks.
- Treat Channel Partner commissions as “partnership” in this page.
