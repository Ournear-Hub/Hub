# OurNear Hire — Stage 4: Employer Workspace QA Report

**Date:** 2026-10-10  
**Environment:** Local `http://127.0.0.1:8000` (`OURNEAR_ALLOW_RUN=local`)  
**Execution:** Playwright Stage 4 suite (`qa-agent/tests/stage4/`) reusing `JRN-EMPLOYER-JOB-PUBLISH-01` lifecycle steps for job transitions.

**Prior stages:** Stage 3 checklist `20261010T073839Z-d2b6b491` + gap `20261010T081721Z-77729f53` — not re-run.

---

## A. Executive summary

| Metric | Value |
| --- | --- |
| **Registry cases** | **20** (`qa-agent/registry/stage_4_employer_workspace.yaml`) |
| **Browser cases recorded** | **20** |
| **PASS** | **20** |
| **FAIL** | **0** |
| **BLOCKED** | **0** |
| **NOT_TESTED (extended field matrix)** | See §F |
| **MISSING** | **0** in Stage 4 scope |
| **Product defects fixed** | **0** |
| **Canonical run ID** | **`20261010T083818Z-74610d3c`** |
| **Stage 4 exit** | **`PASS_WITH_KNOWN_ISSUES`** |

**Release gate:** unchanged — **`insufficient_evidence`**.

---

## B. Registration and authentication

| Case | Result | Evidence |
| --- | --- | --- |
| STG4-AUTH-001 | Employer A login → dashboard (Own jobs) | `.../STG4-AUTH-001/dashboard.png` |
| STG4-AUTH-002 | Invalid login rejected | `.../STG4-AUTH-002/invalid-login.png` |
| STG4-AUTH-003 | Logout clears session | `.../STG4-AUTH-003/after-logout.png` |
| STG4-AUTH-004 | Guest `/account` → login | `.../STG4-AUTH-004/guest-account.png` |
| STG4-AUTH-005 | Duplicate employer email blocked | `.../STG4-AUTH-005/duplicate-email.png` |
| STG4-AUTH-006 | Unique employer register → login → employer shell | `.../STG4-AUTH-006/employer-registered.png` |

**Product behavior (same as Stage 2):** registration redirects to **`/login`**; new accounts sign in with **username**.

---

## C. Company profile

| Case | Result | Notes |
| --- | --- | --- |
| STG4-PROF-001 | **PASS** — **Edit company profile** → company name save → reload → value persisted | Harness must enter edit mode (view mode hides `#company_name`) |

**Extended profile fields** (contact, website, benefits, locations, logo, documents): PHPUnit `EmployerCompanyFoundationStage4Test`; not every field exercised in browser — see §F.

---

## D. Employer dashboard

| Case | Result | Evidence |
| --- | --- | --- |
| STG4-DASH-001 | Metric cards (jobs, applicants, submissions, enquiries); Own jobs → `/account/jobs` | `.../STG4-DASH-001/jobs-from-dashboard.png` |
| STG4-DASH-002 | Hiring Command Center heading | `.../STG4-DASH-002/hiring-center.png` |

Numeric card totals were not reconciled row-by-row against DB in this run (visual + navigation proof only).

---

## E. Job creation, validation, and lifecycle

| Case | Transition | Result |
| --- | --- | --- |
| STG4-JOB-001 | Empty create → validation | **PASS** |
| STG4-JOB-002 | Save draft | **PASS** |
| STG4-JOB-003 | Reopen draft, title persisted | **PASS** |
| STG4-JOB-004 | Publish from management | **PASS** |
| STG4-JOB-005 | Public detail + listing | **PASS** |
| STG4-JOB-006 | Pause → hidden from public | **PASS** |
| STG4-JOB-007 | Republish after pause | **PASS** |
| STG4-JOB-008 | Edit published job → public title update | **PASS** |
| STG4-JOB-009 | Close → absent from public listing | **PASS** |

Shared evidence: `.../STG4-JOB-LIFE/lifecycle-complete.png` (journey job `qa-journey-publish-role-stg4-2`).

---

## F. Extended inventory — browser vs automated

| Area | Browser | PHPUnit / journey |
| --- | --- | --- |
| Per-field job form matrix (salary, vacancy, dates, flags, …) | **NOT_TESTED** individually | `EmployerAccountWorkspaceTest`, job form defaults script |
| Logo / company document upload | **NOT_TESTED** | `EmployerCompanyFoundationStage4Test` |
| Benefits, locations, socials on profile | **NOT_TESTED** | `EmployerCompanyFoundationStage4Test` |
| Dashboard count accuracy vs DB | **NOT_TESTED** | `EmployerAccountWorkspaceTest` (partial) |
| Applicant pipeline / resume download (Stage 5) | Out of scope | `EmployerHiringStage25Test` |

---

## G. Authorization and isolation

| Case | Result | Evidence |
| --- | --- | --- |
| STG4-ISO-001 | Employer B → **403** on Employer A job edit URL | `.../STG4-ISO-001/employer-b-403.png` |
| STG4-ISO-002 | Candidate → **403** on `/account/jobs/create` and edit | `.../STG4-ISO-002/candidate-403.png` |

---

## H. Defect register

No confirmed Stage 4 **product** defects.

**Harness notes:** Profile case requires clicking **Edit company profile** before fields are visible; employer journey pytest import aligned with Stage 3 journey fix.

---

## I. Automated test results

| Command | Exit | Result |
| --- | --- | --- |
| `pytest tests/stage4/test_stage4_employer.py --ournear-suite stage4` | **0** | **12 passed**, ~287s → **20** recorded cases |
| `php artisan test --filter=EmployerCompanyFoundationStage4Test` | **0** | **8 passed**, 148 assertions |
| `php artisan test --filter=EmployerAccountWorkspaceTest` | **0** | **11 passed**, 206 assertions |
| `php artisan qa:hiring-fixtures` | **0** | Fixtures restored after profile marker test |
| `scripts/inspect-qa-employer-job-form-defaults.php` | **0** | Master data for job form |
| Full `php artisan test` | **Not re-run** | No product PHP changes |
| `tsc` / `npm run build` | **Not run** | No frontend product changes |

---

## J. Artifacts

| Item | Path |
| --- | --- |
| Registry | `qa-agent/registry/stage_4_employer_workspace.yaml` |
| Tests | `qa-agent/tests/stage4/test_stage4_employer.py` |
| Results JSON | `qa-agent/artifacts/stage4/20261010T083818Z-74610d3c/stage4_results.json` |
| Screenshots | `qa-agent/artifacts/stage4/evidence/20261010T083818Z-74610d3c/STG4-*/` |

---

## K. Stage exit

**`PASS_WITH_KNOWN_ISSUES`** — employer auth, profile save (company name), dashboard navigation, full job lifecycle, and cross-role isolation verified in browser; extended per-field job/profile matrix and dashboard count reconciliation deferred.

---

## L. Stage 5 preview — applicant management and employer hiring views

1. Employer applicant list and application detail for owned jobs.  
2. Resume snapshot download / preview (candidate application resume on employer side).  
3. Pipeline stage changes within employer scope (no admin ATS redesign).  
4. Reuse `EmployerHiringStage*Test`, `JRN-EMPLOYER-RESUME-01`, fixture application on `qa-fixture-published-a`.  
5. Stage 5 may start for **local scope** after accepting Stage 4 exit above; release remains **`insufficient_evidence`** until staging QA.
