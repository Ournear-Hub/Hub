# Agent 1 — Stage 3: Candidate job application & application history

**Date:** 2026-10-11  
**Workspace:** `D:\Ournear Hub\Ournear Hire`  
**Role:** Agent 1 (implementation verification + incremental hardening)  
**Canonical QA:** `docs/OURNEAR_STAGE_3_APPLICATION_WORKFLOW_QA_REPORT.md` (run `20261010T073839Z-d2b6b491`, gap `20261010T081721Z-77729f53`)

---

## 1. Acceptance criteria audit (A–F)

| Area | Status | Evidence |
| --- | --- | --- |
| **A. Job application submission** | **IMPLEMENTED** | `PublicSiteController` apply routes; `ApplicationSubmissionService`; guest → login; duplicate guard; expired/closed job rules; `JobApplicationTest` |
| **B. Application history** | **IMPLEMENTED** | `CandidateApplicationsController` + `CandidateApplicationViewService`; `Account/Applications.tsx` filters, pagination, empty state, job links |
| **C. Status & timeline** | **IMPLEMENTED** | `CandidateApplicationViewService::timeline()`; `ApplicationTimeline` on `Account/Applications/Show.tsx`; real pipeline events only (`ApplicationExperienceTest::test_timeline_uses_real_recruitment_events_only`) |
| **D. Resume snapshot** | **IMPLEMENTED** | Snapshot at submit; library replace does not alter application path (`test_candidate_can_apply_with_a_library_resume_without_merging_the_snapshot`) |
| **E. Notifications** | **IMPLEMENTED** | `RecruitmentNotificationService::notifyApplicationSubmitted`; no duplicate on repeat submit (`test_existing_notifications_continue_without_duplicates`) |
| **F. Candidate UX** | **IMPLEMENTED** | Design-system account layout; mobile cases in Stage 3 QA registry (`STG3-MOB-001`) |

**Conclusion:** Stage 3 product scope was already complete before this batch. This run **did not rebuild** UI, routes, or history views.

---

## 2. Incremental change (this batch)

**Gap addressed:** Stage 3 plan / §A requires concurrent submits not to create duplicate rows. Prior QA marked parallel POST **NOT_TESTED**; DB had `unique(job_id, email)` but duplicate check was outside the transaction.

**Change:** `ApplicationSubmissionService::submit()` — job row `lockForUpdate`, duplicate re-check inside transaction, map `UniqueConstraintViolationException` to the existing validation message (no duplicate notifications on race).

**Files:** `app/Services/ApplicationSubmissionService.php`, `tests/Feature/ApplicationExperienceTest.php` (duplicate notification assertion on rejected second post).

---

## 3. Files touched (this batch)

| File | Change |
| --- | --- |
| `app/Services/ApplicationSubmissionService.php` | Transactional duplicate guard + unique-index fallback |
| `tests/Feature/ApplicationExperienceTest.php` | Assert single lifecycle notification after duplicate POST |
| `docs/OURNEAR_STAGE_3_CANDIDATE_APPLICATION_IMPLEMENTATION_REPORT.md` | This report |
| `docs/OURNEAR_RELEASE_ISSUE_REGISTER.md` | Stage 3 verification note |

No changes to Agent 2 defect-fix areas (MAN-PUB-02, C2, C3, E1).

---

## 4. Automated verification (this batch)

| Command | Result |
| --- | --- |
| `php artisan test --filter=ApplicationExperienceTest` | **PASS** — 12 tests, 179 assertions |
| `php artisan test --filter=JobApplicationTest` | **PASS** — 5 tests, 38 assertions |
| `npm run typecheck` | **NOT RUN** — no frontend changes |
| `npm run build` | **NOT RUN** — no frontend changes |

---

## 5. Limitations

- **Browser:** Stage 3 checklist not re-run this batch; prior Agent 2 evidence remains authoritative until optional refresh.
- **Concurrent POST harness:** Still no parallel browser/HTTP load test; mitigation is server-side lock + unique index + validation mapping.
- **Release verdict:** **`insufficient_evidence`** (unchanged).

---

## 6. Handoff to Agent 2 (optional refresh)

Re-run only if staging a release gate refresh (not required for this small PHP hardening):

```text
cd "D:\Ournear Hub\Ournear Hire"
php artisan qa:hiring-fixtures
php artisan serve
cd qa-agent
python -m pytest tests/stage3 -v --ournear-suite=stage3
python -m pytest tests/journey/test_candidate_apply_journey.py -v --ournear-suite=journey
php artisan test --filter=ApplicationExperienceTest
php artisan test --filter=JobApplicationTest
```

Registry cases: `qa-agent/registry/stage_3_application_workflow.yaml` (STG3-AUTH-001 … STG3-MOB-001, STG3-INT-001).

---

## 8. Lifecycle status parity (2026-10-12)

### Confirmed gap

Employer applicant cards used `EmployerApplicationPipeline::stageLabelForApplication()`, which mapped grouped pipeline stages (e.g. **Placed** rolled into the “Selected” bucket) to the **group label**, while candidate application list/detail used `ApplicationStatus::toCandidateStatusLabel()` (**Placed**). Same application could read “Selected” to the employer and “Placed” to the candidate.

Withdraw failures (e.g. placed application) returned session validation errors but candidate **Applications** list/detail pages did not render `errors.application`.

### Fix

| File | Change |
| --- | --- |
| `app/Support/EmployerApplicationPipeline.php` | Per-application label = `toCandidateStatusLabel()` (pipeline **filters** unchanged) |
| `app/Models/Application.php` | `toAccountArray()` status uses `toCandidateStatusLabel()` |
| `resources/js/Pages/Account/Applications.tsx` | Show withdraw validation errors |
| `resources/js/Pages/Account/Applications/Show.tsx` | Same |
| `tests/Feature/CandidateApplicationLifecycleConsistencyTest.php` | **Added** — not run |

### Preserved

`ApplicationSubmissionService` transaction/duplicate rules, `ApplicationPipelineService` transitions, withdrawal rules, notifications, resume snapshot semantics, REG-BRW-004 compare work untouched.

### Verification (this batch)

| Check | Result |
| --- | --- |
| PHPUnit (`CandidateApplicationLifecycleConsistencyTest`, regressions) | **NOT RUN — deferred by product owner** |
| Browser / Playwright / TS / build | **NOT RUN** |

---

## 7. Git

No commit, push, or deployment performed.
