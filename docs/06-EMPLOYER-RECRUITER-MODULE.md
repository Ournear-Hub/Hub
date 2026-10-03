# Employer and recruiter module

Keep **employer (company account)** separate from **staff recruiter (admin)**.

## Employer account — IMPLEMENTED

- Middleware: `account.employer`.
- Jobs CRUD + lifecycle: `EmployerJobsController`, `JobLifecycleService`, `EmployerPostingEligibilityService`.
- Applicants: restricted employer-visible candidate data (`EmployerVisibleCandidate` support).
- Submissions list: `EmployerSubmissionsController` (account).
- Documents: `EmployerDocumentController` + `EmployerDocumentLibraryService`.
- Reviews: `EmployerRelationshipReviewController`.
- Enquiries: `EmployerEnquiriesController` (own enquiries).

Isolation: jobs `employer_id` → `employer_profiles.id`. One employer user ↔ one company profile in current architecture.

## Staff recruiter — IMPLEMENTED

Not a `UserRole`. Staff with Recruiter/Manager permissions use `/admin/*`.

Workspaces: jobs, candidates (talent pool, tags, interest), applications, screening, shortlisted, interviews, work/tasks, submissions, placements, employees, replacements, **Hiring Command Center** (`/admin/hiring`).

## Recruiter Command Center

**IMPLEMENTED (Stage 26).** See [24-HIRING-COMMAND-CENTER.md](24-HIRING-COMMAND-CENTER.md). `HiringCommandCenterController` + `RecruiterHiringCommandCenterService` + `Admin/Hiring/CommandCenter`. Permission: `AccessCatalog::hiringCommandCenterPermissions()` / `User::canViewHiringCommandCenter()`. Product-line filter (`all|ats|partnership|staffing|jobs`) and optional `job_id`. Aggregates existing queries; does not call `ScreeningMatchService` to rescore. Finance invoice cards only with `finance.view`/`finance.manage`. Partnership = `EmployerSubmission` client review, not Channel Partner commissions. No assignment-end staffing logic (field does not exist). Application owner assignment: **not implemented** (documented on the page). Local MySQL without `application_interviews.lifecycle_status` uses `outcome` fallback for upcoming-interview counts. Tests: `RecruiterHiringCommandCenterStage26Test`.

## Permissions

Employer: account middleware, not `jobs.manage`.
Staff: `employers.*`, `jobs.*`, `applications.*`, etc.

## Tests

`EmployerCompanyFoundationStage4Test`, `EmployerAccountWorkspaceTest`, `EmployerAccountTest`, `RecruiterHiringCommandCenterStage26Test`.

## Risks

Leaking other employers’ applicants. Admin posting-approval / restrict-posting flags on `employer_profiles`.
