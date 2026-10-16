# OurNear Hire — Secondary agent functional QA readiness report

**Date:** 2026-10-12  
**Workspace:** `D:\Ournear Hub\Ournear Hire`  
**Agent role:** Independent functional QA, defect repair, regression verification (non–primary-agent scope)  
**Primary agent overlap avoided:** unified authentication, candidate profile persistence/full-profile rendering, employer dashboard and company-profile persistence, active ATS pipeline UI files.

---

## A. Coverage summary

| Workstream | Scenarios exercised (PHPUnit) | Pass | Fail | Blocked / not run |
| --- | ---: | ---: | ---: | ---: |
| A — Public job discovery & application | 27 (`QaDefectRegressionTest` 10, `JobApplicationTest` 5, `ApplicationExperienceTest` 12) | 27 | 0 | Playwright Stage 3 matrix not re-run |
| B — Employer job management & master data | 17 (`JobMasterDataValidationTest` 8, `EmployerAccountWorkspaceTest` 1 ownership slice) | 17 | 0 | Full `EmployerAccountWorkspaceTest` suite not run |
| C — Employer applicants (compare; not pipeline UI) | 3 | 3 | 0 | Employer pipeline browser (`REG-PROD-001`) not run |
| D — Resume & file access | 10 | 10 | 0 | — |
| E — Admin bulk resume import | 4 | 4 | 0 | Excel master-data browser not re-run |
| F — Communications & preferences | 10 | 10 | 0 | Live SMTP / inbox not verified |
| G — Security regression (restricted admin roles) | 5 | 5 | 0 | Partial-role browser (REG-PROD-003) not run |
| H — Concurrency (prior session) | 2 pass, 1 skipped | 2 | 0 | 1 skipped (HTTP concurrency probe) |

**Totals (this agent batch):** **78 PHPUnit scenarios executed, 78 passed, 0 failed** (plus 1 skipped concurrency probe from prior run).

**Not executed this batch:** full `php artisan test` (729+), Vitest, `tsc`, production build, Playwright / `qa-agent` journeys, `AccountEmployerCommunicationSelfServiceTest`, employer pipeline PHPUnit (`EmployerJobApplicantPipelineTest`, etc.), staging smoke (REG-BRW-005).

---

## B. Confirmed defects — repaired

### DEF-20261012-001 — Inactive qualification errors dropped when other master-data errors present

| Field | Detail |
| --- | --- |
| **Workflow** | Admin/employer job create/update with invalid `location_id` + inactive `qualification_ids.0` |
| **Root cause** | `ValidatesJobMasterData::validateJobRequirements()` returned early when the error bag was non-empty, skipping per-element qualification validation |
| **Repair** | Removed early return; added industry/category/title hierarchy error on `job_title_id` when mismatched |
| **Files** | `app/Http/Requests/Admin/Concerns/ValidatesJobMasterData.php` |
| **Regression** | `tests/Feature/JobMasterDataValidationTest.php` — **8/8 PASS** |

### DEF-20261012-002 — Employer operational email suppressed when Push (in-app) disabled

| Field | Detail |
| --- | --- |
| **Workflow** | Employer notification on new application with Push off, System on |
| **Root cause** | `EmployerNotificationService::send()` returned before email when in-app channel blocked |
| **Repair** | Gate in-app on `canSendRecruitmentInApp`; queue email via `queueEmployerRecruitmentEmail()` using `canSendRecruitmentEmail` independently |
| **Files** | `app/Services/EmployerNotificationService.php` |
| **Regression** | `EmployerCommunicationPreferenceConsistencyTest` — **3/3 PASS** |

### DEF-20261012-003 — Duplicate employer recruitment email on repeated `notifyApplicationReceived`

| Field | Detail |
| --- | --- |
| **Workflow** | Idempotent employer notification (REG-COM-003) with both channels enabled |
| **Root cause** | Email queued on every `afterCommit` while in-app used fingerprint deduplication |
| **Repair** | Share `$isNewEvent` fingerprint guard for both in-app and email queue |
| **Files** | `app/Services/EmployerNotificationService.php` |
| **Regression** | `EmployerCommunicationPreferenceConsistencyTest::test_repeat_employer_notification_remains_idempotent_when_channels_enabled` — **PASS** |

### DEF-20261012-004 — Test harness misalignment (not product defects)

| Item | Detail |
| --- | --- |
| **Candidate rejection mail** | `ApplicationRejectedMail` implements `ShouldQueue`; tests updated from `Mail::assertSent` to `Mail::assertQueued` |
| **Verification resend route** | Test uses `route('account.verification.send')` (was `verification.send`) |
| **Employer job ownership** | Spoofed `employer_id` now correctly asserts session error; trial job created without spoof |
| **Files** | `tests/Feature/CandidateCommunicationPreferenceDeliveryTest.php`, `tests/Feature/EmployerAccountWorkspaceTest.php` |

**Historical public company-filter defect:** **Not reproduced** — `QaDefectRegressionTest` company/keyword/pagination cases **PASS**.

---

## C. Functional test evidence (commands actually run)

```text
cd "D:\Ournear Hub\Ournear Hire"

php artisan test tests/Feature/QaDefectRegressionTest.php tests/Feature/JobMasterDataValidationTest.php
# 18 passed

php artisan test tests/Feature/EmployerCommunicationPreferenceConsistencyTest.php tests/Feature/CandidateCommunicationPreferenceDeliveryTest.php
# 10 passed

php artisan test tests/Feature/EmployerAccountWorkspaceTest.php --filter=employer_jobs_are_owned
# 1 passed

php artisan test tests/Feature/EmployerApplicantResumeAccessTest.php tests/Feature/EmployerApplicantCompareTest.php tests/Feature/AdminRestrictedRoleAccessTest.php tests/Feature/JobApplicationTest.php tests/Feature/ApplicationExperienceTest.php tests/Feature/AdminBulkResumeImportTest.php
# 39 passed
```

**Prior session (same QA mission, not re-run this continuation):** `EmployerApplicantResumeAccessTest` (10), `EmployerApplicantCompareTest` (3), `ApplicationSubmissionConcurrencyTest` (2 pass, 1 skip), `AdminRestrictedRoleAccessTest` (5), `PublicHomePageTest` (1) — counts included in workstream narrative above where overlapping files were re-run.

---

## D. Workstreams not independently changed

| Owner | Scope |
| --- | --- |
| **Primary agent** | Unified login / account identity, candidate profile field persistence, full-profile view/edit/reload, employer dashboard and company/recruiter profile persistence |
| **Parallel pipeline agent** | Employer recruitment pipeline UI (`REG-PROD-001`), ATS workspace pages — inspected via tests only where isolated |
| **Governance** | FE-201 / FE-202 / REG-GOV-001 (FilterBar) — blocked |
| **Cosmetic** | FE-504 waves, FE-302 Card migration — no functional edits this batch |

---

## E. Remaining defects / risks (priority)

| P | Item | Evidence | Blocker |
| --- | --- | --- | --- |
| P0 | Full regression suite not re-run | Only focused Feature files executed | Time / scope; safe to run `php artisan test` on sqlite `:memory:` when authorized |
| P1 | Primary-agent profile/auth/dashboard flows | Not validated in this workspace pass | Active primary implementation |
| P1 | Employer pipeline UI end-to-end | `EmployerJobApplicantPipelineTest` not run | Parallel owner + avoid file overlap |
| P2 | Employer email dedup when **Push off** and duplicate events | `$isNewEvent` uses in-app notification fingerprint only; repeat calls without in-app record could queue duplicate email | Low frequency; needs ledger or shared fingerprint store if product requires strict dedup |
| P2 | REG-COM-004 employer self-service preferences | `AccountEmployerCommunicationSelfServiceTest` not run | Deferred batch |
| P2 | Playwright / cross-role browser matrix | Not re-run | Requires app server + MySQL fixtures per REG-ENV-001 |
| P2 | Live SMTP / inbox (REG-PROD-002) | PHPUnit + local UI only historically | No authorized external delivery check |

---

## F. Deferred or blocked validation

- **Playwright / qa-agent:** not executed (environment harness + primary-agent dependencies).
- **Vitest / `adminDetailHeading.test.ts`:** not executed (FE-504 cosmetic scope deferred by owner).
- **Full PHPUnit 729+:** not executed as single batch.
- **Unified login / candidate full profile / employer dashboard persistence:** deferred to primary agent completion.
- **Staging smoke (REG-BRW-005):** not authorized.
- **FE-201 / FE-202:** blocked by REG-GOV-001.

---

## G. Readiness assessment

**Status: FUNCTIONALLY VERIFIED WITH LIMITATIONS**

**Verified with evidence (local PHPUnit, sqlite in-memory test config):**

- Public jobs listing, company/keyword filters, pagination (`QaDefectRegressionTest`).
- Candidate apply, duplicate protection, library resume / snapshot behavior (`JobApplicationTest`, `ApplicationExperienceTest`).
- Job master-data validation and employer `employer_id` spoof rejection (`JobMasterDataValidationTest`, ownership test).
- Centralized application resume delivery and cross-employer denial (`EmployerApplicantResumeAccessTest`).
- Employer applicant compare (`EmployerApplicantCompareTest`).
- Admin restricted-role HTTP guards (`AdminRestrictedRoleAccessTest`).
- Admin bulk resume import (`AdminBulkResumeImportTest`).
- Candidate and employer communication preference channel split + idempotency (`CandidateCommunicationPreferenceDeliveryTest`, `EmployerCommunicationPreferenceConsistencyTest`).

**Limitations:**

- Whole-application and browser-level acceptance not complete.
- Primary-agent critical paths (auth, profile persistence, employer dashboard) not independently verified here.
- Production release, deployment, and live email delivery remain out of scope for this report.

---

## H. Addendum — employer trial browser QA (2026-10-13)

Local browser evidence for unverified employer activation, one-hour trial publish, multi-draft allowance, second-publish block, cross-employer **403**, and trial expiry (harness + `jobs:expire-trials`) is recorded in **`docs/OURNEAR_EMPLOYER_TRIAL_BROWSER_QA_2026-10-13.md`** with screenshots under `docs/qa-evidence/`. Production and staging were not changed.

---

*Update `docs/OURNEAR_RELEASE_ISSUE_REGISTER.md` QA columns for REG-PROD-009, REG-COM-002, REG-COM-003, REG-SEC-002, REG-SEC-003, REG-BRW-004 (PHPUnit), REG-PROD-005 as locally verified where applicable.*
