# OurNear Hire — Stage 5: Employer Application Management QA Report

**Date:** 2026-10-10  
**Environment:** Local `http://127.0.0.1:8000` (`OURNEAR_ALLOW_RUN=local`)  
**Execution:** Playwright Stage 5 suite + `JRN-EMPLOYER-RESUME-01` journey steps; PHPUnit `EmployerHiringStage25Test`.

**Prior stages:** Stage 4 `20261010T083818Z-74610d3c`; Stage 3 gap `20261010T081721Z-77729f53` — not re-run.

---

## A. Executive summary

| Metric | Value |
| --- | --- |
| **Registry cases** | **12** |
| **Browser cases recorded** | **12** |
| **PASS** | **11** |
| **FAIL** | **0** |
| **BLOCKED** | **0** |
| **NOT_TESTED** | **0** |
| **MISSING** | **1** (employer self-service status actions — by product design) |
| **Product defects fixed** | **0** |
| **Canonical run ID** | **`20261010T085949Z-ae1d2010`** |
| **Stage 5 exit** | **`PASS_WITH_KNOWN_ISSUES`** |

**Release gate:** unchanged — **`insufficient_evidence`**.

**Fixture note:** Primary browser evidence uses latest Employer A application on journey-apply job (`ONT-A-000049`, slug `qa-fixture-journey-apply-52`). Fixture A shortlisted application on `qa-fixture-published-a` remains in DB for PHPUnit.

---

## B. Application lists

| Case | Result | Evidence |
| --- | --- | --- |
| STG5-LIST-001 | Cross-job `/account/hiring/applications` shows fixture `ONT-A-*` | `.../STG5-JRN/journey-complete.png` |
| STG5-LIST-002 | Per-job `/account/jobs/{slug}/applicants` loads with job title | same |

---

## C. Applicant detail

| Case | Result | Notes |
| --- | --- | --- |
| STG5-DET-001 | **PASS** — Application ID, status, contact snapshot section | Journey step D2-04 |
| STG5-REL-001 | **PASS** — Detail survives logout + re-login | `.../STG5-REL-001/after-relogin.png` |

---

## D. Resume view and download

| Case | Result | Notes |
| --- | --- | --- |
| STG5-RES-001 | **PASS** — View + Download yield readable PDF (`pdf_bytes_are_readable`) | Authorized route `/account/employer-applications/{id}/resume` |
| STG5-RES-002 | **PASS** — Controls persist after reload | Aligns with Stage 3 snapshot model (application file, not live library) |

---

## E. Search and filters

| Case | Result | Evidence |
| --- | --- | --- |
| STG5-FILT-001 | Search by candidate name returns fixture row | `.../STG5-FILT-001/search-hit.png` |
| STG5-FILT-002 | Navigate without query restores list | `.../STG5-FILT-002/cleared-filters.png` |

Per-job applicant **stage** filter matrix: **NOT_TESTED** in this run (PHPUnit/pipeline coverage only).

---

## F. Hiring actions and status transitions

| Case | Result | Notes |
| --- | --- | --- |
| STG5-ACT-001 | **MISSING** | UI shows read-only **Application actions** copy; no Shortlist/Reject/Interview buttons. Server returns **405/404** on employer status POST (`EmployerHiringStage25Test::test_employer_cannot_update_application_status_via_account_routes`). Status changes are **staff/admin recruitment operations**, not employer self-service in current product. |

---

## G. Application counts

| Case | Result | Notes |
| --- | --- | --- |
| STG5-CNT-001 | **PASS** | UI “N applications” on hiring list matches `employer_a_application_total` from `scripts/inspect-qa-employer-journey-target.php` |

Dashboard card reconciliation vs hiring list: **NOT_TESTED** (Stage 4 §F).

---

## H. Authorization and isolation

| Case | Result | Evidence |
| --- | --- | --- |
| STG5-ISO-001 | Employer B: empty hiring list; **403** on A detail + resume URLs | `.../STG5-ISO-001/employer-b-forbidden.png` |
| STG5-ISO-002 | Candidate **403** on employer application URL | `.../STG5-ISO-002/candidate-403.png` |

---

## I. Defect register

No Stage 5 **product** defects confirmed.

**Harness:** Stage 5 suite, registry, inspect script total count; employer resume journey import fix.

---

## J. Automated test results

| Command | Exit | Result |
| --- | --- | --- |
| `pytest tests/stage5/test_stage5_employer_applications.py --ournear-suite stage5` | **0** | **8 passed**, ~110s → **12** recorded cases |
| `php artisan test --filter=EmployerHiringStage25Test` | **0** | **8 passed**, 56 assertions |
| `php artisan qa:hiring-fixtures` | **0** | Before run |
| `scripts/inspect-qa-employer-journey-target.php` | **0** | Target + `employer_a_application_total` |
| Full PHPUnit / `tsc` / `build` | **Not run** | No product code changes |

---

## K. Artifacts

| Item | Path |
| --- | --- |
| Registry | `qa-agent/registry/stage_5_employer_applications.yaml` |
| Tests | `qa-agent/tests/stage5/test_stage5_employer_applications.py` |
| Results JSON | `qa-agent/artifacts/stage5/20261010T085949Z-ae1d2010/stage5_results.json` |
| Screenshots | `qa-agent/artifacts/stage5/evidence/20261010T085949Z-ae1d2010/STG5-*/` |

---

## L. Stage exit

**`PASS_WITH_KNOWN_ISSUES`** — employer list, detail, resume authorization, filters, count reconciliation, and cross-account isolation verified in browser; employer-driven pipeline status changes documented as **MISSING** (intentional read-only employer workspace).

---

## M. Stage 6 preview

1. Staff/admin application workspace and status transitions (where employer UI is read-only).  
2. Cross-role notification deep links beyond employer A fixture.  
3. Staging smoke and release gate evidence — not local Stage 5 scope.  
4. Do not conflate `/admin/hiring` with employer `/account/hiring`.
