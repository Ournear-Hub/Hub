# Agent 2 — Stage 4 employer final QA gap closure

**Run ID:** `20261011T064500Z-stage4-gap-closure`  
**Date:** 2026-10-11  
**Role:** Agent 2 (QA only)  
**Refs:** `docs/OURNEAR_STAGE_4_EMPLOYER_IMPLEMENTATION_REPORT.md` · `docs/OURNEAR_QA_AGENT2_STAGE4_EMPLOYER_VERIFICATION_REPORT.md` · `docs/OURNEAR_STAGE_4_EMPLOYER_QA_REPORT.md`  
**Artifacts:** `qa-agent/artifacts/agent2/20261011T064500Z-stage4-gap-closure/`

---

## 1. Evidence reused (not rerun)

| Area | Run ID | Status | Rationale |
| --- | --- | --- | --- |
| Full job lifecycle STG4-JOB-001 … 009 | `20261010T083818Z-74610d3c` | **PASS** | **20/20** canonical; fixed `journey_job_title("stg4")` collides with accumulated drafts → strict locator failure on `20261011T053938Z-e4f3aeae` (harness/data, not product) |
| STG4-ISO-001 | `20261010T083818Z-74610d3c` | **PASS** | Valid lifecycle slug from canonical run |
| STG4-AUTH-001 … 005 | `20261010T083818Z-74610d3c` | **PASS** | No concrete reason to repeat |
| STG4-AUTH-006 | `20261010T083818Z-74610d3c` | **PASS** | Partial run **429** throttle; canonical registration evidence retained |
| STG4-PROF-001, STG4-DASH-001/002 | `20261011T053938Z-e4f3aeae` | **PASS** | Refreshed in partial run; not duplicated |
| STG4-ISO-002 | `20261011T053938Z-e4f3aeae` | **PASS** | Latest evidence preserved |
| 4D save-draft no HTTP 500 | `20261011T053938Z-e4f3aeae` | **PASS** | P0-04 completed before strict-mode failure |

---

## 2. Stage 4D — employer job requirements (browser)

**Workflow:** Existing employer journey modules (`login_employer_a`, `discover_job_form_defaults`, `fill_new_job_form`) + manual qual/skill checkbox steps (artifact harness only — not added to pytest suite).

| Step | Result |
| --- | --- |
| Open `/account/jobs/create` — qual/skill fieldsets visible | **PASS** |
| Select GCSEs + Customer service; Save draft | **PASS** |
| DB pivots on `qa-stage4-req-67b7540a` | **PASS** — `qualifications=GCSEs`, `skills=Customer service` |
| Reopen `/account/jobs/{slug}/edit` — checkboxes checked | **PASS** (employer login; `get_by_label`) |
| Public detail for new draft | **N/A** — job remained **draft** (`publishing_status=draft`, public **404**) |
| Public tags on employer published fixture | **PASS** — `/jobs/qa-fixture-published-a` shows **GCSEs** (prior E1 save + this run spot-check) |

**Note:** Initial harness JSON flagged reopen/public **FAIL** due to stale Playwright fieldset locators and draft visibility rules; corrected with DB + employer edit URL verification.

**Confirmed application defects:** **None** — persistence matches `JobListingRequirementService` / 4D closure fix.

---

## 3. Dashboard counts vs database (§F)

**Definitions:** `AccountDashboardService::employerDashboard()` — jobs on employer profile; `applications()` on profile; `submissions()`; `employerEnquiries()` on user. UI labels on `Account/Home.tsx` (Own jobs, Applicants…, Submitted candidates, Own enquiries).

**Employer A:** `qa-employer-a@example.test`

| Metric | Service | DB | Browser `/account` | Match |
| --- | ---: | ---: | ---: | --- |
| jobs_count | 84 | 84 | 84 | **PASS** |
| active_jobs_count | 72 | 72 | (subline on card) | **PASS** |
| applicants_count | 58 | 58 | 58 | **PASS** |
| submissions_count | 1 | 1 | 1 | **PASS** |
| enquiries_count | 0 | 0 | 0 | **PASS** |

**Evidence:** `dashboard_db_compare.txt`, `dashboard_db_compare.php`, `dashboard_counts_browser.png`

---

## 4. Extended profile/job field matrix (§F)

| Item | Status |
| --- | --- |
| Per-field job form (salary, vacancy, dates, flags, …) browser matrix | **NOT TESTED** — no stage4 registry cases |
| Logo / documents / benefits / locations browser | **NOT TESTED** — §F deferred |
| PHPUnit coverage | **PASS** — `EmployerCompanyFoundationStage4Test` **8/8**, `EmployerAccountWorkspaceTest` **11/11** (this run) |

---

## 5. Lifecycle rerun

**NOT RUN.** Harness cannot disambiguate duplicate `QA Journey Publish Role stg4` cards without changing the suite (out of scope). **Canonical lifecycle evidence retained:** `20261010T083818Z-74610d3c`.

---

## 6. Authentication & isolation

| Case | Evidence | Status |
| --- | --- | --- |
| STG4-AUTH-006 | Reused `20261010T083818Z-74610d3c` | **PASS** |
| STG4-ISO-001 | Reused canonical lifecycle run | **PASS** |
| STG4-ISO-002 | `20261011T053938Z-e4f3aeae` | **PASS** |

---

## 7. Code changes

**None.**

---

## 8. Register

No dedicated REG row for Stage 4 browser matrix. Stage 4 exit remains **`PASS_WITH_KNOWN_ISSUES`** per `OURNEAR_STAGE_4_EMPLOYER_QA_REPORT.md` with §F browser matrix still partially open.

**Release verdict:** **`insufficient_evidence`**

---

## 9. Commands observed

```text
php scripts/probe_local_mysql.php
php artisan test --filter=EmployerCompanyFoundationStage4Test
php artisan test --filter=EmployerAccountWorkspaceTest
php qa-agent/artifacts/agent2/20261011T064500Z-stage4-gap-closure/dashboard_db_compare.php
python qa-agent/artifacts/agent2/20261011T064500Z-stage4-gap-closure/employer_job_requirements_manual.py
```

---

*Agent 2 — no commits, DB resets, or application changes.*
