# OurNear Hire — Stage 6: Cross-Role Workflow QA Report

**Date:** 2026-10-10  
**Environment:** Local `http://127.0.0.1:8000` (`OURNEAR_ALLOW_RUN=local`)  
**Execution:** Playwright Stage 6 suite (`JRN-CROSS-ROLE-01` fresh apply + admin + isolation); PHPUnit `ApplicationsWorkspaceTest`.

**Prior stages:** Stage 5 `20261010T085949Z-ae1d2010` (employer read-only pipeline actions **MISSING**); Stages 1–4 canonical runs preserved — not re-run.

---

## A. Executive summary

| Metric | Value |
| --- | --- |
| **Registry cases** | **16** |
| **Browser cases recorded** | **16** |
| **PASS** | **15** |
| **FAIL** | **0** |
| **BLOCKED** | **0** |
| **NOT_TESTED** | **0** |
| **MISSING** | **1** (employer pipeline actions — Stage 5 dependency) |
| **Product defects fixed** | **0** (harness label expectation only; see §I) |
| **Canonical run ID** | **`20261010T091906Z-56bd4047`** |
| **Stage 6 exit** | **`PASS_WITH_KNOWN_ISSUES`** |

**Release gate:** unchanged — **`insufficient_evidence`**.

**Correlation application (canonical run):** `ONT-A-000051` (id **51**) on job **`qa-fixture-journey-apply-54`** — created via fresh candidate apply in the same browser session, then traced through Employer A and admin `/admin/applications`.

---

## B. Candidate submission and history

| Case | Result | Notes |
| --- | --- | --- |
| STG6-CAND-001 | **PASS** | Apply confirmation; `ONT-A-000051` captured (`D1-06-business-id`) |
| STG6-CAND-002 | **PASS** | Application on `/account/applications` (`D1-07-applications-list`) |
| STG6-CAND-003 | **PASS** | Detail persists after reload (`D1-08-persist-after-reload`) |

Mode: **`fresh_apply`** (preflight `cross_role_apply_feasible=yes`, 4 apply slots remaining at preflight).

---

## C. Employer receipt and review

| Case | Result | Notes |
| --- | --- | --- |
| STG6-EMP-001 | **PASS** | Cross-job hiring list (`D2-09-hiring-applications-list`) |
| STG6-EMP-002 | **PASS** | Detail matches submission snapshot (`D2-04-application-detail`) |
| STG6-EMP-003 | **PASS** | Resume download readable PDF (`D2-07-download-resume`) |
| STG6-ACT-001 | **MISSING** | Read-only **Application actions**; no Shortlist/Reject/Interview (same as Stage 5 **STG5-ACT-001**) |

Employer B isolation exercised in journey (**STG6-AUTH-001**).

---

## D. Admin/staff ATS receipt and review

| Case | Result | Notes |
| --- | --- | --- |
| STG6-ADM-001 | **PASS** | Search + drawer for `ONT-A-000051` (`D3-03-open-application`) |
| STG6-ADM-002 | **PASS** | Resume tab view/download readable (`D3-06-07-resume-readable`) |
| STG6-ADM-003 | **PASS** | New application → admin PATCH **Reviewing** (`D3-08-status-verified`) |
| STG6-ACT-002 | **PASS** | Status persists after drawer reload (`D3-09-persist-after-reload`) |

Interface: **`/admin/applications`** (Operations applications workspace). Not conflated with `/admin/hiring` command center.

---

## E. Cross-role consistency

| Case | Result | Notes |
| --- | --- | --- |
| STG6-X-001 | **PASS** | Same `ONT-A-000051`, job slug, and application id across roles |
| STG6-X-002 | **PASS** | After admin **Reviewing**, employer shows **Under review** + timeline “Application under review”; candidate detail did not surface the same status string on the checked view (`candidate status text present=False`) — role-specific visibility, not a handoff break |

---

## F. Resume identity and authorization

| Area | Result |
| --- | --- |
| Employer authorized resume | **PASS** — `/account/employer-applications/51/resume` |
| Admin authorized resume | **PASS** — drawer + `/admin/applications/51/resume` |
| Employer B | **PASS** — cannot list/detail/resume Employer A application |
| Candidate / Employer A → admin workspace | **PASS** — no admin application leak (`STG6-AUTH-002`, **STG6-AUTH-003**) |

Direct URL negative cases align with `ApplicationsWorkspaceTest::test_candidate_and_employer_cannot_access_application_workspaces`.

---

## G. Authorization and negative cases

| Case | Result |
| --- | --- |
| STG6-AUTH-001 | **PASS** — Employer B isolation |
| STG6-AUTH-002 | **PASS** — Candidate denied admin applications |
| STG6-AUTH-003 | **PASS** — Employer A denied admin applications |

Explicit “candidate cannot open another candidate’s application” was **not** added as a separate registry row; existing account scoping + Stage 3 history tests remain the dependency. No unauthorized status mutation attempted beyond documented employer 405/404 behavior (Stage 5 PHPUnit).

---

## H. Known dependencies and scope boundaries

1. **Stage 5 — employer pipeline actions (`STG6-ACT-001` / `STG5-ACT-001`):** **MISSING** by product design; admin/staff may change status; employer UI remains read-only.  
2. **Staging / release gate:** not executed (local only).  
3. **Stage 7 cycle** (admin → employer → admin → candidate notification/deep links): **out of scope** for Stage 6.  
4. **Fallback path `run_cross_role_reusing_fixture`:** implemented for apply-budget blocks; **not used** in canonical run (fresh apply succeeded).

---

## I. Defects, retests, and harness changes

| ID | Severity | Summary | Root cause | Fix | Retest |
| --- | --- | --- | --- | --- | --- |
| — | — | First run `20261010T091409Z-8d608db0` failed on **STG6-X-002** expecting literal “Reviewing” on employer UI | Employer displays **Under review** for admin **Reviewing** | Updated Stage 6 assertion to match product labels (`tests/stage6/test_stage6_cross_role.py`) | **`20261010T091906Z-56bd4047`** — 2/2 pytest **PASS** |

**Files touched (QA harness only):**

- `qa-agent/registry/stage_6_cross_role.yaml`
- `qa-agent/tests/stage6/conftest.py`, `test_stage6_cross_role.py`
- `qa-agent/ournear_qa/journey/cross_role.py` (`run_cross_role_reusing_fixture`, candidate history steps)
- `qa-agent/tests/journey/test_cross_role_e2e.py` (inline login throttle helper)
- `qa-agent/pyproject.toml` (`stage6` marker)

No Laravel/React product code changes.

---

## J. Manual browser evidence

| Artifact | Path (under `qa-agent/artifacts/`) |
| --- | --- |
| Cross-role journey complete | `stage6/evidence/20261010T091906Z-56bd4047/STG6-JRN/cross-role-complete.png` |
| Employer after admin status | `stage6/evidence/20261010T091906Z-56bd4047/STG6-JRN/employer-after-admin-status.png` |
| Candidate after admin status | `stage6/evidence/20261010T091906Z-56bd4047/STG6-JRN/candidate-after-admin-status.png` |
| Employer read-only actions | `stage6/evidence/20261010T091906Z-56bd4047/STG6-ACT-001/employer-read-only-actions.png` |

Human-operated browser steps are recorded through the Stage 6 Playwright checklist (same accounts and flows as prior Human QA stages).

---

## K. Automation

| Command | Exit | Result |
| --- | ---: | --- |
| `ournear-qa preflight-journey` | **0** | `cross_role_apply_feasible=true`, fixtures ready |
| `python -m pytest tests/stage6/test_stage6_cross_role.py -v --ournear-suite stage6` | **0** | **2 passed** (~236s); results **`stage6/20261010T091906Z-56bd4047/stage6_results.json`** |
| `php artisan test --filter=ApplicationsWorkspaceTest` | **0** | **7 passed** (100 assertions) |

**Not re-run:** full PHPUnit suite; Stage 1–5 browser suites; standalone `test_cross_role_e2e.py` (Stage 6 canonical run executed the same `run_cross_role_e2e` path).

**Preflight (before run):** `php artisan qa:hiring-fixtures` — fixture application on `qa-fixture-published-a` remains **Shortlisted** for PHPUnit; canonical browser handoff used new journey-apply job **54**.

---

## L. Registry and artifacts

| Item | Location |
| --- | --- |
| Registry | `qa-agent/registry/stage_6_cross_role.yaml` |
| Tests | `qa-agent/tests/stage6/test_stage6_cross_role.py` |
| Results JSON | `qa-agent/artifacts/stage6/20261010T091906Z-56bd4047/stage6_results.json` |
| Journeys | `JRN-CROSS-ROLE-01`, `JRN-EMPLOYER-RESUME-01`, `JRN-ADMIN-APPLICATION-01` |

---

## M. Stage exit and Stage 7 gate

**`PASS_WITH_KNOWN_ISSUES`** — end-to-end handoff **Candidate → Employer → Admin** proven for a single correlated application (`ONT-A-000051`); authorization and resume rules hold; admin status transition works and reflects on employer timeline with label mapping; employer self-service pipeline actions remain **MISSING**.

**Stage 7 may begin** when authorized, focused on the **admin → employer → admin → candidate** cycle (notifications, deep links, and post-decision visibility rules) without re-opening Stages 1–6 closure evidence.

**Remaining risks:** staging unverified; apply/login rate limits can block fresh cross-role runs; candidate status copy after admin changes may differ by surface; release gate stays **`insufficient_evidence`**.
