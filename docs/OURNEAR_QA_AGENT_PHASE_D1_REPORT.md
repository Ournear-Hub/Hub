# OurNear Human QA Agent — Phase D1 report

**Date:** 2026-10-09 (updated: journey apply prerequisite completed)  
**Journey:** `JRN-CANDIDATE-APPLY-01`  
**Laravel `app/` code:** unchanged. **Operational script added:** `scripts/qa-provision-journey-apply-job.php` (local/testing bootstrap, not production routes).

---

## Discovery: fixture gap

`php artisan qa:hiring-fixtures` provisions exactly **three** job slugs (`qa-fixture-published-a`, `qa-fixture-draft-a`, `qa-fixture-published-b`) and **one** seeded application (candidate → published A). It does **not** add extra apply targets. After manual QA (Batch 7), the candidate had applied to all three fixture jobs, so D1 new-apply tests **skipped** until a supplemental job existed.

**Approved extension (non-destructive):** `scripts/qa-provision-journey-apply-job.php`

| Property | Behavior |
| --- | --- |
| Environments | `local`, `testing` only; refuses production |
| Prerequisite | `php artisan qa:hiring-fixtures` (QA users + resume) |
| Creates | Published Employer A jobs `qa-fixture-journey-apply`, `-2`, `-3`, … until one is **unapplied** |
| Does not | Delete applications, reset existing jobs, or touch non-QA rows |

**Safe command sequence:**

```powershell
cd "D:\Ournear Hub\Ournear Hire"
php artisan qa:hiring-fixtures
php scripts/qa-provision-journey-apply-job.php
# stdout: slug=... title=... eligible=yes
cd qa-agent
.venv\Scripts\python.exe -m pytest tests/journey -v
```

Read-only check: `php scripts/inspect-qa-apply-eligibility.php`

---

## Verified UI routes (unchanged)

| Step | Route / UI |
| --- | --- |
| Login | `/login` → `/account` (**Dashboard**) |
| Job detail | `/jobs/{slug}` (**Apply** / **Applied**) |
| Apply | `/jobs/{slug}/apply` → **Submit application** |
| Success | `/jobs/{slug}` → **Application submitted**, `ONT-A-*` |
| List | `/account/applications` (persist after reload) |

Navigation in tests uses direct `/jobs/{slug}` (deterministic; avoids unrelated search hits).

---

## Files changed

| Path | Role |
| --- | --- |
| `scripts/qa-provision-journey-apply-job.php` | **New** — eligible journey job provisioning |
| `qa-agent/ournear_qa/journey/candidate_apply.py` | Steps, `provision_journey_apply_target()` |
| `qa-agent/tests/journey/test_candidate_apply_journey.py` | Calls provision before new-apply test |
| `qa-agent/registry/features.yaml` | Journey assertions reference provision script |
| `docs/OURNEAR_QA_AGENT_PHASE_D1_REPORT.md` | This update |

---

## Journey steps and assertions (new application)

| Step ID | Assertion |
| --- | --- |
| D1-01 | Login + **Dashboard** |
| D1-02 | Job detail for provisioned slug |
| D1-03–05 | Apply page, resume ready, **Application submitted** |
| D1-06 | `ONT-A-*` on confirmation (e.g. **ONT-A-000004**, **ONT-A-000005** on local runs) |
| D1-07–08 | Listed on `/account/applications`, survives reload |
| D1-09 | Re-open apply URL → **Applied** (no second submit) |

Duplicate/persistence test still uses **`qa-fixture-published-a`** (fixture application; no new submit).

---

## Execution results (2026-10-09, after provision script)

**Provision:**

```
slug=qa-fixture-journey-apply-2
title=QA Fixture Journey Apply Role (Employer A) 2
eligible=yes
```

**Journey suite:**

```powershell
.venv\Scripts\python.exe -m pytest tests/journey -v
```

| Result | Count |
| --- | ---: |
| Passed | **4** |
| Failed | 0 |
| Skipped | 0 |

Includes **new-application** and **duplicate/persistence** browser tests + 2 discovery unit tests.

**Focused regression (journey + registry + smoke):** **20 passed**.

---

## Registry status

`JRN-CANDIDATE-APPLY-01` remains **`partial`**: candidate new-apply + duplicate guard are automated; employer/admin resume verification (`JRN-EMPLOYER-RESUME-01`) is not part of D1.

---

## Defects / blockers

None observed in the latest run. Earlier skip was **missing data**, not application logic.

---

## Coverage gaps

- Employer/admin snapshot views (Phase D / `JRN-EMPLOYER-RESUME-01`).
- Repeated new-apply runs consume `-2`, `-3`, … slugs (by design; no deletes).
- Optional: document slug series in `QaHiringFixtures` in a future Laravel change (out of scope for this task).
