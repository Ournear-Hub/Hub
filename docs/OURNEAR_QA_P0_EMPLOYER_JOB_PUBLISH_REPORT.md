# OurNear QA — P0 Employer Job Publish Lifecycle

**Date:** 2026-10-09  
**Journey ID:** `JRN-EMPLOYER-JOB-PUBLISH-01`  
**Scope:** QA agent browser journey + unit/registry checks. No Laravel `app/` changes.

---

## 1. Product behavior exercised (existing UI)

| Step | Transition | Surface |
| --- | --- | --- |
| P0-01 | Employer A session | `/account/hiring` (Hiring Command Center) |
| P0-02 | Job management | `/account/jobs` |
| P0-03 | Invalid create submit | `/account/jobs/create` → validation (422 or field errors) |
| P0-04 | Save draft | POST `/account/jobs` → draft index flash |
| P0-05 | Reopen draft | `?listing=draft` → edit form persisted `#title` |
| P0-06 | Publish | PATCH `/account/jobs/{slug}/lifecycle` `action=publish` |
| P0-07–08 | Public visibility | `/jobs/{slug}`, `/jobs` listing link |
| P0-09 | Edit published | PUT `/account/jobs/{slug}` → public title updated |
| P0-10–11 | Close | lifecycle `action=close` → detail **404**, absent from listing |
| P0-12 | Employer B isolation | edit fixture job `qa-fixture-published-a` → **403** |
| P0-13 | Candidate isolation | `/account/jobs/create` and edit → **403** |

Lifecycle actions match **Account/Employer/Jobs/Index** (`publish`, `pause`, `close`). Employer create form only supports **Save draft**; publish is from job management (not admin-style lifecycle select).

---

## 2. Implementation artifacts

| Path | Role |
| --- | --- |
| `qa-agent/ournear_qa/journey/employer_job_publish.py` | Journey steps, lifecycle constants |
| `scripts/inspect-qa-employer-job-form-defaults.php` | Read-only master-data IDs for form selects |
| `qa-agent/tests/journey/test_employer_job_publish_journey.py` | Browser P0 tests |
| `qa-agent/tests/test_employer_job_publish_unit.py` | Registry + discovery unit tests |
| `qa-agent/registry/features.yaml` | Journey + `EMP-02` phpunit mapping |
| `qa-agent/registry/regression_rules.yaml` | `Employer/Jobs` → `EMP-02` |

PHPUnit reference (not replaced): `EmployerAccountWorkspaceTest` (draft/publish/eligibility/ownership).

---

## 3. Prerequisites

- Local app: `OURNEAR_ALLOW_RUN=local`, `OURNEAR_BASE_URL=http://127.0.0.1:8000`
- `php artisan qa:hiring-fixtures`
- `config/local.env`: `OURNEAR_QA_EMPLOYER_A_*`, `OURNEAR_QA_EMPLOYER_B_*`, `OURNEAR_QA_CANDIDATE_*`
- PHP on PATH for inspect script

Rate limits: account login **429** → honest skip (same as other journeys). Journey creates **one new draft/publish/close job** per lifecycle test run (no fixture applications deleted).

---

## 4. Verification evidence (2026-10-09)

| Command | Result |
| --- | --- |
| `ournear-qa validate-registry` | OK — 53 features, **7** journeys |
| `pytest tests/test_employer_job_publish_unit.py tests/test_registry_validation.py` | **19 passed** |
| `pytest tests/journey/test_employer_job_publish_journey.py::test_jrn_employer_job_publish_lifecycle` | **1 passed** (~51s) |
| `pytest tests/journey/test_employer_job_publish_journey.py::test_jrn_employer_job_publish_authorization` | **1 passed** |
| Combined employer publish + registry unit (21 tests) | **21 passed** |

**Not run:** Full journey matrix; `pause` lifecycle UI (supported in product, not in this P0 path); staging/production.

---

## 5. Defects and gaps

| Item | Status |
| --- | --- |
| Application/product defects found | **None** in this pass |
| QA locator ambiguity (“Job title” vs “Classified job title”) | Fixed via `#title` / exact labels |
| Public job detail duplicate `<h1>` | Assert `.first` heading |
| **Pause / republish** | **Covered (2026-10-09):** Pause → public 404 + absent from listing → **Publish** reactivation → public again (steps P0-08c–08e). Separate unpublish button not in employer UI. |
| Trial expiry public visibility | **Closed in PHPUnit** — `docs/OURNEAR_QA_P0_TRIAL_EXPIRY_REPORT.md` (browser journey intentionally omitted) |

---

## 6. Next web / QA gaps

- **`JRN-CROSS-ROLE-01`:** closed — see `docs/OURNEAR_QA_P0_CROSS_ROLE_REPORT.md`.
- **`JRN-EMPLOYER-RESUME-01` / `JRN-ADMIN-APPLICATION-01`:** registry **implemented** after green focused runs (2026-10-09).
- **Trial job expiry:** PHPUnit only; add browser journey only if release gate requires it.
- **Web release gate:** aggregate `ournear-qa report --ci-exit` + remaining non-P0 employer/admin workflows (staffing, submissions) as product priorities dictate.
