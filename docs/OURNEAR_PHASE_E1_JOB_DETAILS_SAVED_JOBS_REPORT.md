# Phase E1 — Job skills, qualifications, saved jobs, verification label

**Date:** 2026-10-11  
**Agent:** 1 (development)  
**Status:** **PHASE E1 IMPLEMENTED — AWAITING AGENT 2 QA**  
**Commit / push / deploy:** **None**

---

## 1. Inspection — already working

| Area | Finding |
| --- | --- |
| Master data | `qualifications` table + admin Master Data; `skills` library for candidates |
| Job forms | `JobFormLookups.qualifications` populated; skills list was not wired |
| Job detail | Free-text `requirements` only; no structured skills/qualifications |
| Saved Jobs | Custom list rows; backend unsave via `DELETE /account/saved-jobs/{slug}` |
| Verification | `EmployerProfile::ournearVerified()` (`job_posting_approved_at`); **no public label** |
| Publication policy | Unverified one-job/one-hour — **unchanged** (no lifecycle edits in this batch) |

---

## 2. Genuine gaps addressed

### Part A — Skills and qualifications on jobs

- Added pivot tables `job_listing_qualification`, `job_listing_skill` (FK to existing master tables).
- Admin/employer `JobForm`: optional multi-select checkboxes.
- Validation on `StoreJobRequest` / `UpdateJobRequest`; sync via `JobListingRequirementService`.
- Public **detail** shows tag lists; cards stay compact (no skill lists on `JobCard`).

### Part B — Saved Jobs card consistency

- `SavedJobService::paginate()` returns `PublicJobStateService`-decorated payloads (same shape as public `JobCard`).
- `Account/SavedJobs.tsx` reuses canonical `JobCard` (`list` variant) + unsave control.

### Part C — Unverified employer label

- `Job::toPublicCard()` sets `employer_not_verified` from `EmployerProfile::ournearVerified()`.
- `JobCard` and `JobDetail` show **Employer Not Verified** when true (muted badge style).

---

## 3. Files changed

| File | Change |
| --- | --- |
| `database/migrations/2026_10_11_040000_add_job_listing_skills_and_qualifications.php` | Pivot tables |
| `app/Models/Job.php` | Relations, public/admin payloads, verification flag |
| `app/Services/JobListingRequirementService.php` | **New** — sync pivots |
| `app/Services/MasterData/MasterDataService.php` | `skills` in `formOptions()` |
| `app/Services/SavedJobService.php` | Decorated saved-job cards |
| `app/Http/Requests/Admin/*` + `ValidatesJobMasterData.php` | Requirement validation |
| `app/Http/Controllers/Admin/JobsController.php` | Sync on create/update |
| `app/Http/Controllers/Account/EmployerJobsController.php` | Sync on create/update |
| `resources/js/Components/Admin/JobForm.tsx` | Qualification/skill pickers |
| `resources/js/Pages/Admin/Jobs/Index.tsx` | Drawer shows qualification/skill arrays |
| `resources/js/Components/Public/JobDetail.tsx` | Tags + verification label |
| `resources/js/Components/Public/JobCard.tsx` | Verification label |
| `resources/js/Pages/Account/SavedJobs.tsx` | Uses `JobCard` |
| `resources/js/types/jobs.ts`, `masterData.ts` | Types |
| `tests/Feature/PhaseE1JobDetailsSavedJobsTest.php` | **New** regression |
| `docs/OURNEAR_PHASE_E1_JOB_DETAILS_SAVED_JOBS_REPORT.md` | This report |
| `docs/OURNEAR_RELEASE_ISSUE_REGISTER.md` | REG-PROD-009, REG-PHP-003 note |

---

## 4. Reused

`JobCard`, `JobDetail`, `PublicJobStateService`, `SavedJobService`, master `Qualification` / `Skill` models, existing job policies and publication services.

---

## 5. Validation

| Command | Result |
| --- | --- |
| `php artisan test --filter=PhaseE1JobDetailsSavedJobsTest` | **PASS** — 2 tests, 42 assertions |
| `php artisan test --filter=PhaseEJobCardsClassificationTest` | **PASS** — 1 test (Phase E regression) |
| `npm run typecheck` | **PASS** (after Admin Jobs drawer fix) |
| `npm run build` | **PASS** |

**Environment:** REG-ENV-001 may block live MySQL/browser QA; PHPUnit uses test harness. No Agent 1 browser verification.

---

## 6. Remaining / out of scope

- Creating **new** skill names from job form (select existing skills only).
- REG-PHP-003 publication policy enforcement — not modified; label only.
- Phase E/G1/C3/F — still awaiting independent QA; verdict **`insufficient_evidence`**.

---

## 7. Agent 2 — browser scenarios

1. **Admin/employer job form** — select qualifications + skills, save, reopen edit — selections persist.
2. **Public job detail** — tags visible; card does not list all skills.
3. **Unverified employer job** — label on card + detail; verified employer — no label.
4. **Saved Jobs** — cards match public list styling; unsave removes row; open job navigates correctly.
5. **Closed/unpublished saved job** — unavailable message + no broken navigation.
6. **Regression** — Phase E industry/category filters and Featured badge still work.

---

## 8. REG-PROD-009 — strict master-data ID validation (2026-10-12)

### Root cause

- Job create/update already used `ValidatesJobMasterData`, but **skill/qualification array elements with invalid or zero IDs were skipped** in the `after` validator instead of rejected.
- `JobListingRequirementService::sync()` could **silently omit** unknown IDs via `whereIn` even if validation were bypassed.
- Partial job updates could **clear master FKs** when fields were omitted from the request because payload builders always wrote `null` for missing lookup keys.

### Fields covered

Industry, job category, job title (hierarchy + active rules), location, employment type, experience range, employer (admin), qualification IDs, skill IDs.

Skills have no active/inactive flag in master data — existence and integer shape only.

### Files changed

| File | Change |
| --- | --- |
| `app/Http/Requests/Admin/Concerns/ValidatesJobMasterData.php` | Stricter rules (`min:1`, `distinct`); reject invalid array elements; hierarchy/selectable checks only for present fields on update |
| `app/Http/Controllers/Admin/JobsController.php` | Preserve omitted master FKs on update |
| `app/Http/Controllers/Account/EmployerJobsController.php` | Same preserve-on-omit for employer updates |
| `resources/js/Components/Admin/JobForm.tsx` | Show nested `qualification_ids.*` / `skill_ids.*` errors |
| `tests/Feature/JobMasterDataValidationTest.php` | **Added** — REG-PROD-009 matrix (not run) |

### Tests (authored, not run)

| Suite | Status |
| --- | --- |
| `JobMasterDataValidationTest` | **NOT RUN — deferred by product owner** |
| PHPUnit (full / regression) | **NOT RUN** |
| Playwright / browser | **NOT RUN** |
| `npx tsc --noEmit` / `npm run build` | **NOT RUN** |
| Migrations | **NOT RUN** |

### Remaining limitations

- Browser proof of tampered IDs on employer/admin forms still outstanding.
- Verified-negative employer label QA unchanged (local data blocker).
- Release readiness **not** claimed from implementation alone.

---

*End of Phase E1 report.*
