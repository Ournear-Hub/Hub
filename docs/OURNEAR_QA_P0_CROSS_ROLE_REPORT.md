# OurNear QA — P0 Cross-Role Recruitment Lifecycle

**Date:** 2026-10-09  
**Journey ID:** `JRN-CROSS-ROLE-01` (canonical; reuses D1/D2/D3 helpers)  
**Laravel `app/`:** unchanged.

---

## 1. Summary

Cross-role P0 is **closed** by stabilizing and re-running the existing orchestrator in `cross_role.py` — no second workflow implementation. One new application per run correlates on **`ONT-A-*`** across candidate, employer A, and admin surfaces, with authorization checks for employer B and account roles on admin routes.

Registry journey status: **`implemented`**.

---

## 2. Lifecycle verified (single run)

| Phase | Step IDs (representative) | Evidence |
| --- | --- | --- |
| **Candidate** | D1-01 … D1-09 | Apply on provisioned slug; **ONT-A-***; `/account/applications` + reload; duplicate guard |
| **Employer A** | D2-01 … D2-09 | Applicants → detail → inline/download **readable PDF** → hiring applications list |
| **Admin** | D3-01 … D3-09 | Drawer by business id; resume; **New → Reviewing** when applicable; reload persistence |
| **Authorization** | D4-* | Employer B forbidden on A’s application/resume; **candidate + employer A** denied admin index/resume |

---

## 3. Reused vs changed

| Asset | Action |
| --- | --- |
| `ournear_qa/journey/cross_role.py` | **Extended** — employer A admin denial; unchanged orchestration |
| `ournear_qa/journey/candidate_apply.py` | Reused (`run_new_application_steps`, `provision_journey_apply_target`) |
| `ournear_qa/journey/employer_resume.py` | **Stabilized** — 403 heading check in `assert_http_forbidden` |
| `ournear_qa/journey/admin_application.py` | **Stabilized** — 403 heading in admin isolation |
| `tests/journey/test_cross_role_e2e.py` | **Extended** — preflight gate; stricter step + auth assertions |
| `tests/test_cross_role_helpers.py` | Registry status unit test |
| `registry/features.yaml` | `JRN-CROSS-ROLE-01` → `implemented`, expanded steps |
| Scripts | Unchanged (`qa-provision-journey-apply-job.php`, `inspect-qa-application-by-business-id.php`, `inspect-qa-journey-capacity.php`) |

---

## 4. Preflight (before apply-creating run)

```text
php scripts/inspect-qa-journey-capacity.php
apply_submit_budget_remaining=8
cross_role_apply_feasible=yes
next_eligible_slug=qa-fixture-journey-apply-27
```

Cross-role pytest calls `inspect_journey_capacity()` and **skips** when budget/slots block apply (no throttle bypass).

---

## 5. Verification commands and results (2026-10-09, local)

| Command | Result |
| --- | --- |
| `pytest tests/test_cross_role_helpers.py tests/test_registry_validation.py` | **17 passed** |
| `ournear-qa validate-registry` | OK — 53 features, 7 journeys |
| `pytest tests/journey/test_employer_resume_journey.py` (2 tests) | **2 passed** |
| `pytest tests/journey/test_admin_application_journey.py::test_jrn_admin_application_review_workflow` | **1 passed** |
| `pytest tests/journey/test_cross_role_e2e.py` | **1 passed** (~372s combined sequence) |
| `pytest … admin isolation (candidate + employer B)` | **2 passed** |

**Not rerun:** Full journey matrix; staging/production.

---

## 6. Defects

| Type | Finding |
| --- | --- |
| **Product** | None identified in this pass |
| **QA** | Registry does not allow `verified` journey status — use **`implemented`**; 403 pages needed `heading` role checks to avoid strict-mode flakes |

---

## 7. Coverage gaps

- **Employer ATS status changes** remain staff/admin (by design; employer UI explains on detail).
- **Cross-role** does not re-run if preflight reports exhausted apply budget — honest skip.
- **`JRN-EMPLOYER-RESUME-01` / `JRN-ADMIN-APPLICATION-01`** registry rows remain **`partial`** (standalone tests exist; promote when policy requires repeated green matrix).

---

## 8. Regression planner

`registry/regression_rules.yaml` maps `qa-agent/ournear_qa/journey/cross_role.py` → `tests/journey/test_cross_role_e2e.py`. Planner unchanged; validated via `ournear-qa plan-regression --json` (local git diff).

---

## 9. Follow-on (2026-10-09)

- **`JRN-EMPLOYER-RESUME-01` / `JRN-ADMIN-APPLICATION-01`:** registry promoted to **`implemented`** after focused green runs.
- **Employer pause/republish:** added to `JRN-EMPLOYER-JOB-PUBLISH-01` journey — see `docs/OURNEAR_QA_P0_EMPLOYER_JOB_PUBLISH_REPORT.md`.
- **Next product gap:** trial posting expiry in browser (if in release scope) or non-P0 employer services workflows.
