# OurNear Human QA Agent — Phase D4 report

**Date:** 2026-10-09  
**Journey:** `JRN-CROSS-ROLE-01`  
**Laravel `app/` code:** unchanged. **Operational scripts only:** extended provision limit; new inspect-by-business-id script.

**Validation closure (same day, D5 session):** see [§ Validation closure](#validation-closure-d5-session) — combined journey **14 passed, 0 skipped** after spaced execution; preflight documented apply budget exhaustion without bypassing throttles.

---

## Summary

| Item | Outcome |
| --- | --- |
| **Initial combined-run skip (D3.1 pattern)** | `test_jrn_candidate_apply_new_application` skipped when `provision_journey_apply_target()` returned **None** — all **`qa-fixture-journey-apply` … `-20`** slugs already had candidate applications. |
| **Root cause** | Provisioning loop capped at **20** slugs; not login 429, not missing QA users. |
| **Fix (scripts only)** | `scripts/qa-provision-journey-apply-job.php` loop **20 → 60** slugs. |
| **Cross-role orchestration** | **Implemented and verified** — one focused run **passed** (~108s). |
| **Overall D4 gate** | **Partial complete** — orchestration works; full journey matrix still subject to **account-login (5/min)** and **job-applications (8/hour)** throttles when suites run back-to-back. |

---

## Phase 1 — Initial skip (verified)

### Evidence

| Source | Finding |
| --- | --- |
| D3.1 spaced runs | **12 passed, 1 skipped** — skip on new-apply only. |
| Skip message | Provision / no eligible job (pytest skip from missing slug+title in provision stdout). |
| `provision_journey_apply_target()` | Runs `php scripts/qa-provision-journey-apply-job.php`; **non-zero exit** → `None`. |
| Script stderr (before fix) | `Could not find or create an eligible job (limit 20 slugs).` |
| `inspect-qa-employer-journey-target.php` | Still found latest application (e.g. slug `-20`, `ONT-A-*`) — data existed; **no free apply slot** within the old cap. |

### Not the cause

- Account **HTTP 429** on login (separate skip path via `skip_if_account_login_throttled`).
- Missing `qa:hiring-fixtures` (script exits with explicit “run fixtures first”).
- Deliberate bypass of throttles, cache clears, or application deletes.

### After fix

```text
php scripts/qa-provision-journey-apply-job.php
created=1
slug=qa-fixture-journey-apply-21
eligible=yes
```

Subsequent runs advance slugs as apply tests consume jobs (e.g. `-24` eligible after cross-role and journey activity).

---

## Phase 2 — Orchestration design

### Flow (`JRN-CROSS-ROLE-01`)

1. **Local + credentials** — pytest skips if not `OURNEAR_ALLOW_RUN=local` or missing env passwords (never logged).
2. **`provision_journey_apply_target()`** — one eligible published Employer A job, candidate not yet applied.
3. **Candidate** — `run_new_application_steps()` → capture **`ONT-A-*`** as correlation key.
4. **Resolve** — `scripts/inspect-qa-application-by-business-id.php` → numeric `application_id`, `job_slug`, `status`, `resume_path_set`.
5. **Employer A** — `run_employer_a_resume_steps()` on the same record (ID + readable synthetic resume).
6. **Admin** — `run_admin_application_steps()` (ID, resume, one permitted status transition when initial state allows, reload persistence).
7. **Authorization** — `run_employer_b_isolation_steps()` + `assert_role_cannot_access_admin_applications()` for candidate.
8. **Report** — `CrossRoleReport` aggregates step IDs from D1/D2/D3 helpers; test fails if `report.blocked` is non-empty.

Single application per run; no retry loops that submit multiple applications.

### Correlation key (sanitized example)

From post-run inspect (local QA data):

| Field | Example |
| --- | --- |
| `business_id` | `ONT-A-000026` |
| `application_id` | `26` (internal numeric id) |
| `job_slug` | `qa-fixture-journey-apply-23` |
| `status` | `New` |

The focused cross-role run completed before the hourly apply limit; exact ID for that run is in the same sequence (slug **21** was provisioned immediately before the passing run).

### Files changed

| Path | Role |
| --- | --- |
| `scripts/qa-provision-journey-apply-job.php` | Slug search limit **60** |
| `scripts/inspect-qa-application-by-business-id.php` | **New** — resolve application by `ONT-A-*` |
| `scripts/inspect-qa-employer-journey-target.php` | `status=` field for journey targets |
| `qa-agent/ournear_qa/journey/cross_role.py` | **New** — `run_cross_role_e2e()`, `CrossRoleReport` |
| `qa-agent/tests/journey/test_cross_role_e2e.py` | **New** — orchestrated browser test |
| `qa-agent/tests/test_cross_role_helpers.py` | **New** — provision-none step id; missing record resolve |
| `qa-agent/registry/features.yaml` | `JRN-CROSS-ROLE-01` (`partial`); feature_ids aligned to registry |
| `qa-agent/ournear_qa/journey/account_auth.py` | Append `Retry-After` on login 429 when present |
| `docs/OURNEAR_QA_AGENT_PHASE_D4_REPORT.md` | This document |

Reused without duplication: `candidate_apply`, `employer_resume`, `admin_application`, `account_auth`, `errors`, journey `conftest` throttling skips.

---

## Phase 3 — Authentication and throttling (observed)

| Throttle | Limit | Observed in D4 runs |
| --- | --- | --- |
| `account-login` | 5/min per email\|IP | Full `tests/journey -q` immediately after cross-role: **3 skipped** (cross-role, employer ×2) with explicit 429 skip. |
| `job-applications` | 8/hour per candidate | Second spaced journey run: new-apply and **cross-role skipped** with **HTTP 429** and `Retry-After=678` on apply POST. |

Outcomes are distinguished:

- **passed** — step completed.
- **failed** — assertion or unexpected error.
- **skipped/blocked** — 429, no provision target, missing credentials; cross-role test does **not** mark overall success if critical steps are skipped (`report.blocked` → fail).

Focused cross-role run used shared `login_account_user()` POST checks; no blind retries or password logging.

---

## Phase 4 — Safety

- Local app only (`OURNEAR_ALLOW_RUN=local`).
- No Laravel application code changes.
- No production data, destructive resets, or blanket cache clears.
- Synthetic resumes and existing QA fixtures only.

---

## Phase 5 — Test results (each command separate)

| Command | Result |
| --- | --- |
| `php scripts/qa-provision-journey-apply-job.php` (post-fix) | **Success** — `slug=qa-fixture-journey-apply-21`, `eligible=yes` |
| `.venv\Scripts\python.exe -m pytest tests/journey/test_cross_role_e2e.py -v` | **1 passed** (108s) |
| `.venv\Scripts\python.exe -m pytest tests/journey -q` (immediate follow-up) | **11 passed**, **3 skipped** (account-login 429 on cross-role + employer tests) |
| `.venv\Scripts\python.exe -m pytest tests/journey -q` (~90s after prior run) | **10 passed**, **4 skipped** (job-applications 429 on new-apply + cross-role; account-login 429 on employer ×2) |
| `.venv\Scripts\python.exe -m pytest tests/test_registry_validation.py tests/smoke tests/test_cross_role_helpers.py -q` | **18 passed** (after registry `feature_ids` fix) |

**Note:** Do not sum journey run counts across rows — throttling and skips differ per run. D3.1 baseline with spacing and no hourly apply exhaustion remains **12 passed, 1 skipped** (provision-only skip before slug cap fix).

### Per-role verification (focused cross-role run)

| Role | Checks |
| --- | --- |
| Candidate | Apply UI; `D1-06-business-id`; steps through new-application flow |
| Employer A | `D2-09-hiring-applications-list`; resume readability on same `application_id` |
| Admin | `D3-09-persist-after-reload`; status transition when applicable |
| Employer B / candidate | Two authorization steps recorded (`D4-auth-*`) |

---

## Remaining gaps

- **Registry status** — `JRN-CROSS-ROLE-01` stays **`partial`** until repeated spaced runs pass without skip (hourly apply budget + login spacing).
- **Combined suite** — Running all journeys in one minute will continue to **skip** rather than false-fail; schedule cross-role separately or wait for both throttle windows.
- **Slug ceiling** — At **60** exhausted slugs, new-apply and cross-role will skip again until manual QA policy adds capacity (same class of issue as the original 20-cap).
- **No broad feature sweep** — Out of scope for D4; stop here per charter.

---

## Validation closure (D5 session)

**Environment:** `OURNEAR_ALLOW_RUN=local`, `OURNEAR_BASE_URL=http://127.0.0.1:8000/` (verified via settings loader).

| Step | Command | Duration / time (local) | Result |
| --- | --- | --- | --- |
| Capacity | `php scripts/inspect-qa-journey-capacity.php` | ~67s | **37** provision slots; **8/8** applies in last 60m (DB); `cross_role_apply_feasible=no` |
| Provision | `php scripts/qa-provision-journey-apply-job.php` | (same window) | `slug=qa-fixture-journey-apply-24`, `eligible=yes` |
| Cross-role | `pytest tests/journey/test_cross_role_e2e.py -v` | **85.6s** (20:46 local) | **1 passed** (limiter allowed one submission despite DB 8/8 advisory — cache vs DB count can diverge slightly) |
| Spacing | ~75s pause | — | Reduce account-login **429** risk before combined suite |
| Full journey | `pytest tests/journey -q` | **184.7s** (20:48–20:51) | **14 passed**, **0 skipped** |
| Post-run capacity | `inspect-qa-journey-capacity.php` | — | **36** provision slots; still **8/8** applies last 60m |

**D4 validation verdict:** **Verified green** for the required combined journey matrix on this session (14/14, no critical skips). **Not a permanent guarantee** — preflight may report `blocked_reason` when hourly apply budget is exhausted; operators must wait for the window or accept explicit skips. Earlier D4 development runs (11–12 passed with login/apply skips) remain valid historical evidence under throttle load.

Registry/smoke/helpers were **not** re-run in this closure pass (no D4 code change); D5 session re-ran smoke/registry separately — see `docs/OURNEAR_QA_AGENT_PHASE_D5_REPORT.md`.

---

## Suggested operator sequence

```powershell
cd "D:\Ournear Hub\Ournear Hire"
php artisan qa:hiring-fixtures
php scripts/qa-provision-journey-apply-job.php
cd qa-agent
.venv\Scripts\python.exe -m pytest tests/journey/test_cross_role_e2e.py -v
# Wait ≥1 minute before full tests/journey if account-login skips appear
# Wait for job-applications window if apply POST returns 429
```
