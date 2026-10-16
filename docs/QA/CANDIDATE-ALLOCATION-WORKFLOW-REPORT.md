# Candidate Allocation Workflow Report

**Date:** 2026-10-15 (allocation rectification — dual-role recruiter + general follow-up)  

## Root cause (2026-10-15)

| Failure | Cause |
|--------|--------|
| Assign to **OurNear (MUKUNDA)** | `RecruiterTalentPoolAssignmentService::assertRecruiter()` required `User::isEmployer()` while OurNear is **`candidate` role** with employer profile. Dropdown used `RecruitingEmployerAccounts`; persist path did not. |
| **General Candidate Follow-up** (intermittent) | Modal kept `job_id` / `employer_enquiry_id` after switching assignment type; service rejected general/talent_pool when those fields were present. Fixed in controller (strip extras) + UI clears fields on type change. |

**Fix:** `RecruitingEmployerAccounts::canActAsRecruiter()` in assert paths; controller nulls job/enquiry/application for `talent_pool` + `general`; assign modal shows validation errors.

**Date:** 2026-10-14  
**Scope:** Admin candidate allocation, recruiter progress, employer submission (where supported), notifications reuse.

## Summary

Implemented a unified **Candidate Allocation** admin experience on the existing `RecruiterTalentPoolAssignment` stack (no parallel pipeline). Recruiters see **Assigned Candidates** with granular progress stages, follow-up dates, and optional employer submission when a real linked application exists.

## Navigation and routes (unchanged route names)

| Audience | Label | Route |
|----------|-------|-------|
| Admin | Candidate Allocation | `GET /admin/talent-pool-assignments` (`admin.talent-pool-assignments.index`) |
| Admin | Assign (bulk/single) | `POST /admin/talent-pool-assignments` (`admin.talent-pool-assignments.store`) |
| Recruiter | Assigned Candidates | `GET /account/talent-pool` (`account.talent-pool.index`) |
| Recruiter | Update progress | `PUT /account/talent-pool/{assignment}/status` |
| Recruiter | Submit to employer | `POST /account/talent-pool/{assignment}/submit-to-employer` |

## Admin workflow

1. Open **Admin → Candidates → Candidate Allocation**.
2. Search/filter candidates in the paginated table; select rows (or **Select all on page**).
3. Click **Assign to Recruiter**; choose recruiter, assignment type (**Talent Pool** / Specific Job / **Staffing Requirement** / General follow-up), optional instructions and follow-up date.
4. Confirm — assignments persist via `RecruiterTalentPoolAssignmentService::assignMany`.
5. Review **Assignment tracking** below; use filters (recruiter, type, status, overdue follow-ups) and **Details** for status history.

## Recruiter workflow

1. Open **Assigned Candidates** (`/account/talent-pool`).
2. Open an assignment; view instructions and profile summary.
3. **Update progress** — progress stage dropdown (Assigned → … → Joined/Placed / Rejected / etc.), notes, follow-up and interview fields.
4. **Submit candidate to employer** — only when a linked real application exists; uses `EmployerSubmissionService` (no duplicate application).

## Employer submission

Supported when `application_id` is set on the assignment and the application passes existing submit rules. General follow-up assignments without an application cannot submit until admin links a valid shortlisted application (e.g. via job assignment with application).

## Data model

Migration `2026_10_14_200000_extend_recruiter_talent_pool_assignments.php` adds:

- `assignment_type`, `progress_stage`, `follow_up_at`, `interview_at`, `interview_details`, `employer_submission_id`
- `progress_stage` on status events

**Local non-test DB:** `php artisan migrate` — if `recruiter_talent_pool_assignments` already existed without a migration row, migration `2026_10_14_120000` reconciles partial schema (idempotent; short MySQL FK/index names) and creates missing `recruiter_talent_pool_assignment_status_events`. Extension `2026_10_14_200000` uses `hasColumn` guards.

## Coarse vs fine status

- **Coarse** (`RecruiterTalentPoolAssignmentStatus`): pending_review, in_progress, completed, declined, returned — preserved for notifications and filters.
- **Fine** (`RecruiterTalentPoolAssignmentProgressStage`): user-facing recruiter/admin progress labels; syncs coarse status on update.

## Tests (actual run)

```
php artisan test tests/Feature/EmployerModulesStaffingTest.php tests/Feature/RecruiterTalentPoolAssignmentTest.php
php artisan test   # full suite — gap closure 2026-10-14 night
```

**Result (2026-10-14 night):** module bundle **14 passed** (131 assertions) including `test_talent_pool_assignment_type_labels_are_consistent_in_admin_and_recruiter_views`; full suite **1035 passed / 3 skipped / 0 failed**.

**Result (2026-10-15):** `RecruiterTalentPoolAssignmentTest::test_dual_role_account_with_employer_profile_appears_in_admin_recruiter_list` + **`LocalDevAccountEmailVerificationTest` 4/4**; full suite **1040 passed / 3 skipped / 0 failed** (`storage/logs/phpunit-full-20261015-urgent.txt`).

## Browser (2026-10-15)

- Admin **Candidate Allocation** recruiter filter lists **MUKUNDA** (OurNear dual-role employer profile) — `docs/QA/evidence/2026-10-15-admin-allocation-recruiter-ournear.png`.
- Recruiter **Talent Pool Job Listings** shows internal draft **Local QA Talent Pool Role Oct 2026** — `docs/QA/evidence/2026-10-15-talent-pool-jobs-ournear.png`.

## Browser (2026-10-14 evening)

- Admin **Candidate Allocation** → Talent Pool assign to QA Employer A → success toast; assignment `#2` in tracking.
- Recruiter **Assigned Candidates** lists new row (`docs/QA/evidence/2026-10-14-recruiter-assigned-candidates-talent-pool.png`).
- Modal default type Talent Pool (`docs/QA/evidence/2026-10-14-candidate-allocation-talent-pool-modal.png`).

Additional coverage in `RecruiterTalentPoolAssignmentTest`: bulk assign, general follow-up without shortlisted application, progress stage persistence.

## Browser verification

| Check | Result |
|-------|--------|
| Recruiter **Assigned Candidates** page title and nav label | **PASS** — logged-in `qa-employer-a@example.test` session |
| Admin Candidate Allocation full assign flow | **PASS** (prior session) — Talent Pool assign + tracking; type labels show **Talent Pool** in admin Details and recruiter list |
| All four assignment types in one browser pass | **NOT RUN** — covered by PHPUnit per type |
| Screenshot | `docs/QA/evidence/2026-10-14-assigned-candidates-recruiter.png` |

## Build

- `npx tsc --noEmit` — PASS  
- `npm run build` — PASS  

## Limitations

- Resume download on assignment still requires linked `application_id` (existing privacy path).
- Job dropdown in assign modal lists jobs for selected recruiter’s company only; cross-company job selection is rejected server-side.
- Admin browser E2E requires admin login (`admin@ournear.test` + local password from `.env`).

## Key files

- `app/Http/Controllers/Admin/RecruiterTalentPoolAssignmentsController.php`
- `app/Services/RecruiterTalentPoolAssignmentService.php`
- `resources/js/Pages/Admin/TalentPoolAssignments/Index.tsx`
- `resources/js/Pages/Account/TalentPool/Index.tsx`, `Show.tsx`
- `app/Enums/RecruiterTalentPoolAssignmentProgressStage.php`, `RecruiterTalentPoolAssignmentType.php`
