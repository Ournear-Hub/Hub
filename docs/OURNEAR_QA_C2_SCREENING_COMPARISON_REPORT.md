# C2 — Admin screening multi-candidate comparison

**Date:** 2026-10-10  
**Journey ID:** `JRN-SCREENING-COMPARE-01`  
**Agent 2 status:** **BLOCKED** (local MySQL unavailable in agent session) / **FAIL** when DB had zero jobs with ≥2 applications  
**Release impact:** Admin compare UX fix shipped; browser proof pending environment

---

## Scope

- Select **two or more** applicants on `/admin/screening` for the same job.
- Open **Compare selected** panel with persisted names and application fields.
- Hide comparison; job filter context preserved.
- Desktop **1440×900** and mobile **390×844** (journey test loops both).

---

## Product fixes

| File | Fix |
| --- | --- |
| `resources/js/Components/Admin/ApplicationWorkspacePage.tsx` | `compareRows` map survives pagination; `scrollIntoView` on `#screening-compare-panel`; job id string coercion; compare button count hint |
| `scripts/qa-provision-screening-compare.php` | Idempotent local fixture job `qa-fixture-screening-compare` with ≥2 applications |
| `scripts/inspect-qa-screening-compare-job.php` | Prefers screening-compare fixture slug |
| `qa-agent/ournear_qa/journey/screening_comparison.py` | Provision + inspect PHP; navigate `/admin/screening?job_id=` (Inertia reload) instead of select-only |

---

## Root causes (failures observed)

| Step | Cause |
| --- | --- |
| C2-03 (historical) | Playwright `select_option` on `#screening-job` did not reliably trigger Inertia visit → **0** compare checkboxes |
| C2-03 (later) | `inspect-qa-screening-compare-job.php` returned `found=0` — no job with ≥2 applications until provision script runs |
| Environment | MySQL connection refused / `probe_local_mysql.php` **NO_MATCH**; Playwright chromium not installed in one agent environment |

---

## Commands

```text
cd "D:\Ournear Hub\Ournear Hire"
php artisan qa:hiring-fixtures
php scripts/qa-provision-screening-compare.php
php scripts/inspect-qa-screening-compare-job.php
cd qa-agent
playwright install chromium
python -m pytest tests/journey/test_screening_comparison_browser.py -v --ournear-suite=journey
```

**Artifacts (on success):** `qa-agent/artifacts/c2-screening/<run_id>/screening-compare-desktop.png`

---

## Automated checks (Agent 1)

| Check | Result |
| --- | --- |
| `npm run typecheck` | **PASS** (session) |
| `npm run build` | **PASS** (prior Phase D / FE batches) |

---

## Verdict

| | |
| --- | --- |
| **C2 product fix** | **COMPLETE** |
| **Independent browser QA** | **NOT READY** — run pytest after MySQL + Playwright + fixtures |

Do **not** treat Agent 1 typecheck/build as browser PASS.

---

## Employer job applicant compare (2026-10-12, REG-BRW-004 extension)

### Confirmed gap

Employer self-service had **no** comparison workflow on `/account/jobs/{slug}/applicants` — only admin `/admin/screening` supported multi-select compare (C2).

### Root cause

Comparison UI and enriched screening rows existed only in the admin `ApplicationWorkspacePage` + `ScreeningWorkspaceService` path; employer applicants list rendered summary cards without selection or server-validated compare payload.

### Implemented behavior

| Route | Behavior |
| --- | --- |
| `GET account/employer/jobs/{job}/applicants?compare={id},{id}` | Employer must own job; every ID must belong to that job; 2–5 applicants; returns `compare.applications` built from `EmployerVisibleCandidate` + advisory `ScreeningMatchService` (no admin-only screening notes, no storage paths) |

UI: checkboxes on applicant cards, **Compare selected** loads authorized rows; `#employer-applicant-compare-panel` with links back to each application; filters preserved in query string.

### Files

| File | Change |
| --- | --- |
| `app/Services/EmployerApplicantCompareService.php` | **New** — parse/validate IDs, build compare rows |
| `app/Http/Controllers/Account/EmployerJobsController.php` | `compare` query handling |
| `resources/js/Components/Account/Recruiter/EmployerApplicantComparePanel.tsx` | **New** compare table |
| `resources/js/Pages/Account/Employer/Applicants/Index.tsx` | Selection + Inertia reload |
| `tests/Feature/EmployerApplicantCompareTest.php` | **Added** — not run |

### Verification (this batch)

| Check | Result |
| --- | --- |
| PHPUnit `EmployerApplicantCompareTest` | **NOT RUN — deferred by product owner** |
| PHPUnit `ScreeningComparePanelTest` | **NOT RUN** |
| Playwright C2 / employer browser | **NOT RUN** |
| TypeScript / build | **NOT RUN** |

### Remaining

- Employer compare browser journey not authored in `qa-agent` yet.
- Admin C2 journey unchanged; re-run after fixture provision when needed.
