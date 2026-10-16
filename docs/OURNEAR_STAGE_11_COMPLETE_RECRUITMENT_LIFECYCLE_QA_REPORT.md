# OurNear Hire — Stage 11: Complete Recruitment Lifecycle QA Report

**Date:** 2026-10-10  
**Environment:** Local `http://127.0.0.1:8000` (`OURNEAR_ALLOW_RUN=local`)  
**Registry:** `qa-agent/registry/stage_11_complete_recruitment_lifecycle.yaml`  
**Browser harness:** `qa-agent/ournear_qa/journey/recruitment_lifecycle.py`, `qa-agent/tests/stage11/test_stage11_lifecycle.py`

**Correlation fixture:** **`ONT-A-000051`** (application id **51**) — **Interview** status preserved; no new apply, no admin status mutation in Stage 11.

**Prior canonical runs reused (not repeated):** Stage 3 apply `20261010T073839Z-d2b6b491`; Stage 10 security `20261010T111658Z-29e6e2f6`; Stages 6–9 cross-role/interview evidence on same application.

---

## A. Executive summary

| Metric | Value |
| --- | --- |
| **Registry cases** | **18** |
| **PASS** | **10** |
| **FAIL** | **0** |
| **BLOCKED** | **0** |
| **NOT_TESTED** | **4** (prior-stage or email scope) |
| **MISSING** | **5** (known product gaps) |
| **Unique product defects** | **0** |
| **Product fixes** | **0** (harness: candidate login for MISSING checks) |
| **Canonical browser run ID** | **`20261010T113310Z-6782260a`** (initial); **final green re-run 3/3** same session |
| **Stage 11 exit** | **`PASS_WITH_KNOWN_ISSUES`** |

**Release gate:** unchanged — **`insufficient_evidence`**.

**Lifecycle completeness:** Supported **read/consistency** path through **Interview + admin interview row** is verified cross-role; **employer-driven transitions, messaging, candidate interview response, and full browser apply** remain incomplete by product design or prior-stage coverage.

---

## B. Public job discovery → application (§2A)

| Case | Status | Evidence |
| --- | --- | --- |
| STG11-APPLY-001 | **NOT_TESTED** | Stage 3 browser matrix — `docs/OURNEAR_STAGE_3_APPLICATION_WORKFLOW_QA_REPORT.md` |
| STG11-APPLY-002 | **PASS** | PHPUnit `ApplicationExperienceTest::test_duplicate_post_does_not_create_a_second_application` |
| STG11-APPLY-003 | **PASS** | PHPUnit guest apply → login, no application row |
| STG11-NT-002 | **NOT_TESTED** | Apply reload/re-login — Stage 3 + Stage 8 STG8-CAND-005 |

---

## C. Candidate application → employer review (§2B)

| Case | Status | Notes |
| --- | --- | --- |
| STG11-LCY-003 | **PASS** | Employer hiring list + detail show **Interview** for `ONT-A-000051` |
| STG11-LCY-004 | **PASS** | Resume snapshot PDF download readable |
| STG11-AUTH-001 | **NOT_TESTED** | Employer B isolation — Stage 10 **PASS** (not re-run) |
| (supporting) | **PASS** | `EmployerHiringStage25Test` — 8 tests, employer B empty list, snapshot auth |

---

## D. Admin recruitment operations (§2C)

| Case | Status | Notes |
| --- | --- | --- |
| STG11-LCY-005 | **PASS** | Admin drawer **Interview**; Interview tab **Interview #1** (or #n) visible |
| STG11-ADM-001 | **PASS** | `RecruitmentOperationsStage6Test` — 7 tests (schedule/reschedule/cancel/scorecard/tasks) on factory apps |

No invalid admin transition attempted on **`ONT-A-000051`** (state preservation).

---

## E. Interview lifecycle (§2D)

| Verification | Status |
| --- | --- |
| Admin interview row persists (read-only check) | **PASS** (STG11-LCY-005) |
| Candidate **Interview** application status | **PASS** (STG11-LCY-002) |
| Employer **Interview** on detail | **PASS** (STG11-LCY-003) |
| Candidate in-app notifications reachable | **PASS** (STG11-LCY-006) |
| STG11-MISS-002 Employer scheduling UI | **MISSING** |
| STG11-MISS-004 Candidate accept/decline | **MISSING** |
| STG11-NT-001 Interview/shortlist **email** delivery | **NOT_TESTED** (no local mail proof) |

Interview **acceptance/attendance/completion** not inferred beyond status + scheduled row.

---

## F. Decision and communication (§2E)

| Item | Status |
| --- | --- |
| In-app notification center | **PASS** (journey) |
| Notification deep link to application | **PASS** when link href matches application id (when notifications listed) |
| Email queue/delivery | **NOT_TESTED** |

Stage 7 shortlist notification on same fixture not re-run.

---

## G. Final outcomes (§2F)

| Case | Status | Notes |
| --- | --- | --- |
| STG11-OUT-001 | **PASS** | `ScreeningShortlistTest` — rejection/shortlist rules on isolated factory data |
| Browser reject/offer on `ONT-A-000051` | **NOT_TESTED** | Would mutate canonical QA fixture; factory PHPUnit used instead |

---

## H. Cross-role consistency (§2G)

| Case | Status |
| --- | --- |
| STG11-LCY-001 | **PASS** — same `ONT-A-000051`, slug, id across candidate → employer → admin → notifications |
| STG11-LCY-002 | **PASS** — timeline mentions submitted/review/shortlist/interview copy |

---

## I. Known gaps preserved (§3)

| Case | Status |
| --- | --- |
| STG11-MISS-001 | **MISSING** — employer pipeline buttons |
| STG11-MISS-002 | **MISSING** — employer interview scheduling |
| STG11-MISS-003 | **MISSING** — employer → candidate messaging |
| STG11-MISS-004 | **MISSING** — candidate interview response |
| STG11-MISS-005 | **MISSING** — correction-request workflow |

Profile audit coverage gaps unchanged (`docs/OURNEAR_PROFILE_FIELD_AUDIT_AND_REPAIR_REPORT.md`).

---

## J. Defects and harness

| Issue | Type | Resolution |
| --- | --- | --- |
| Employer session hit **403** on `/account/applications/51` in MISSING test | Harness | Re-login as **candidate** before candidate-only UI checks |

No Laravel/React product changes.

---

## K. Commands and results

| Command | Exit | Result |
| --- | ---: | --- |
| `cd qa-agent && python -m pytest tests/stage11/ -v --ournear-suite stage11` | **0** | **3 passed** (~44–133s) |
| `php artisan test tests/Feature/ApplicationExperienceTest.php tests/Feature/RecruitmentOperationsStage6Test.php tests/Feature/ScreeningShortlistTest.php tests/Feature/EmployerHiringStage25Test.php` | **0** | **35 passed**, **421 assertions** |

No frontend code changes → **`npx tsc` / `npm run build` not run**.

**Browser evidence:** `qa-agent/artifacts/stage11/evidence/{run_id}/`  
**Results JSON:** `qa-agent/artifacts/stage11/{run_id}/stage11_results.json`

---

## L. Launch-critical missing capabilities

| Capability | Impact | Owner |
| --- | --- | --- |
| Employer pipeline actions | Employers cannot advance candidates without admin/staff | Product + hiring workspace |
| Employer interview scheduling | Scheduling only via admin drawer | Product |
| Employer ↔ candidate messaging | No in-app recruitment chat to candidate | Product |
| Candidate interview response | No accept/decline UX | Product |
| Email verification for decisions | Compliance/trust gap for external comms | Product + infra |

---

## M. Stage 12 recommendation

Proceed to **Stage 12** (final roadmap gate — release packaging / remaining matrix per plan) with lifecycle **consistency through Interview** evidenced on **`ONT-A-000051`**, while treating **employer self-service outcomes** and **email proof** as **open launch risks**.

**Verdict:** **`PASS_WITH_KNOWN_ISSUES`** — not “complete recruitment lifecycle” in the product sense; **supported paths verified**, **missing transitions documented**.
