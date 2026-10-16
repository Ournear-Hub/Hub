# OurNear Hire — Stage 10: Security, Negative Cases & Recovery QA Report

**Date:** 2026-10-10  
**Environment:** Local `http://127.0.0.1:8000` (`OURNEAR_ALLOW_RUN=local`)  
**Registry:** `qa-agent/registry/stage_10_security_negative_recovery.yaml`  
**Browser harness:** `qa-agent/tests/stage10/test_stage10_security.py`

---

## A. Executive summary

| Metric | Value |
| --- | --- |
| **Browser cases recorded** | **12** |
| **PASS** | **11** |
| **FAIL** | **0** |
| **BLOCKED** | **0** |
| **NOT_TESTED** | **0** |
| **MISSING** | **1** (preserved known gap **STG10-KNOWN-001**) |
| **Product defects fixed in Stage 10** | **0** |
| **Canonical browser run ID** | **`20261010T111658Z-29e6e2f6`** |
| **Stage 10 exit** | **`PASS_WITH_KNOWN_ISSUES`** |

**Profile batch verdict (unchanged):** **`PASS_WITH_COVERAGE_GAPS`** — see `docs/OURNEAR_PROFILE_FIELD_AUDIT_AND_REPAIR_REPORT.md`.

**Release gate (unchanged):** **`insufficient_evidence`** — staging not verified; Stage 10 does not substitute for full release matrix.

---

## B. Browser evidence (canonical run)

**Command:** `cd qa-agent && python -m pytest tests/stage10/test_stage10_security.py -v --ournear-suite=stage10`  
**Result:** **7/7** pytest passed (~95s)  
**Structured results:** `qa-agent/artifacts/stage10/20261010T111658Z-29e6e2f6/stage10_results.json`  
**Screenshots:** `qa-agent/artifacts/stage10/evidence/20261010T111658Z-29e6e2f6/`

| Case | Status | Notes |
| --- | --- | --- |
| STG10-AUTH-001 | **PASS** | Guest → `/login` from `/account` |
| STG10-AUTH-002 | **PASS** | Guest → `/admin/login` |
| STG10-AUTH-003 | **PASS** | Guest → `/login` from `/account/hiring` |
| STG10-AUTH-004 | **PASS** | Logout; `/account/profile` requires login |
| STG10-AUTH-005 | **PASS** | Candidate denied admin applications |
| STG10-AUTH-006 | **PASS** | Employer denied admin applications |
| STG10-AUTH-007–009 | **PASS** | Employer B isolation (list, detail, resume) |
| STG10-AUTH-010 | **PASS** | Guest resume URL → login (no leak) |
| STG10-AUTH-011 | **PASS** | Non-owner forbidden on `/account/applications/{id}` |
| STG10-KNOWN-001 | **MISSING** | Employer self-service pipeline controls (read-only **Application actions**) |

---

## C. PHPUnit registry cases (closure session)

**Command (representative):**  
`php artisan test tests/Feature/ApplicationExperienceTest.php tests/Feature/EmployerHiringStage25Test.php tests/Feature/DualGuardAuthenticationTest.php`

| Registry ID | Coverage | Result |
| --- | --- | --- |
| STG10-VAL-001–005 | `ApplicationExperienceTest`, `UnifiedProfileStage23FTest`, `AccountCandidateProfessionalTest` | **Not re-run in this closure slice** — covered by prior full suite (712 pass) |
| STG10-FILE-001 | `EmployerHiringStage25Test::test_application_resume_snapshot_authorization` | **PASS** (included in closure run) |
| STG10-FILE-002 | `ProductionUxStage14Test` | **Not re-run this slice** |
| STG10-REC-001 | `ApplicationExperienceTest` notification dedup | **Not re-run this slice** |
| STG10-PHP-AUTH-001–003 | Dual guard, RBAC, policies | **PASS** (`DualGuardAuthenticationTest` + related in closure) |

**Closure run totals:** **34 passed**, 341 assertions (~21s).

---

## D. Preserved findings (do not reinterpret as fixed)

- **Employer pipeline UI (EMP-03):** **MISSING** — aligns with Stages 5–9 and profile audit; Stage 10 records **STG10-KNOWN-001** only.
- **Profile field coverage:** 34 candidate + 13 employer groups remain **NOT_TESTED** per profile audit; no change to **`PASS_WITH_COVERAGE_GAPS`**.

---

## E. Next work (product implementation track)

Authorized owner repairs (C–J) proceed **after** this evidence archive — see implementation session report and backlog updates. **FE-201 / FE-202** remain **BLOCKED** until PR-5M adoption authorization.
