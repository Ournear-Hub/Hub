# OurNear Human QA Agent — Phase D2.1 report

**Date:** 2026-10-09  
**Scope:** Diagnose and stabilize `test_jrn_candidate_apply_new_application`  
**Laravel `app/` code:** unchanged.

---

## Summary

| Classification | Finding |
| --- | --- |
| **Primary** | **Test defect** — post-submit `wait_for_url("**/jobs/{slug}**")` matched the apply URL (`/jobs/{slug}/apply`), so the test did not wait for redirect to job show before asserting success. |
| **Secondary** | **Legitimate rate limiting** — `POST /jobs/{job}/apply` returned **HTTP 429** after repeated local journey runs (`throttle:job-applications`: 8/hour per email + IP cap). Not CAPTCHA; apply UI has no Turnstile widget. |
| **Not root cause** | CAPTCHA iframe — apply page (`Jobs/Apply.tsx`) does not render Turnstile. Aria **dialog + iframe** appeared on one throttled run (empty `src`); with a successful **302** POST, dialog count stayed **0**. |

---

## Evidence

### Code inspection

- Submit flow: Inertia `router.post` → `jobs.apply.store` (no Turnstile in controller or `Apply.tsx`).
- Rate limit: `routes/web.php` → `throttle:job-applications`; `AppServiceProvider` → `Limit::perHour(8)` by email|IP and `Limit::perHour(20)` by IP.

### Controlled diagnosis (one browser run, stdout captured)

| Field | Run A (throttled) | Run B (success POST) |
| --- | --- | --- |
| URL after click | `/jobs/.../apply` | `/jobs/.../apply` (before Inertia navigation completes) |
| POST status | **429** | **302** |
| Dialog / iframe | 1 dialog, empty iframe `src` | 0 dialogs, 0 iframes |
| Console / network (BrowserSession) | No actionable errors on either run |

Artifact: `qa-agent/artifacts/session/d2_1_apply_diag.txt` (Run B excerpt).

### Test defect proof

Unit test `test_legacy_glob_would_false_positive_on_apply_path` documents that `/jobs/{slug}` is a substring of `/jobs/{slug}/apply`, so a trailing `**` glob on the slug allows a false “navigation complete” while still on the apply page.

---

## Changes made (qa-agent only)

| File | Change |
| --- | --- |
| `ournear_qa/journey/candidate_apply.py` | `job_show_url_pattern()`; wait for POST response; classify **429** / **4xx**; wait for job show URL regex; then assert **Application submitted**. |
| `tests/journey/test_candidate_apply_journey.py` | **Skip** (not pass) when `D1-05-submit-blocked` and HTTP 429 — documents rate-limit blocker. |
| `tests/journey/test_candidate_apply_url_wait.py` | Regression tests for URL matching. |
| Removed | `test_apply_submit_diagnose.py` (one-shot probe; avoided keeping an extra submit in CI). |

No CAPTCHA bypass, no retries on POST, no sleeps, no Laravel changes.

---

## Supported local strategy

1. **Normal run:** `php scripts/qa-provision-journey-apply-job.php` then pytest new-apply (creates at most one new application per passing run).
2. **If skipped for 429:** Wait for the hourly window or clear local throttle cache (e.g. `php artisan cache:clear` on local) — operational only, not automated in tests.
3. **Do not** interpret a skip as a green journey when rate limited.

---

## Verification commands and results

```powershell
cd "D:\Ournear Hub\Ournear Hire\qa-agent"

# Focused (after fix)
.venv\Scripts\python.exe -m pytest tests/journey/test_candidate_apply_journey.py::test_jrn_candidate_apply_new_application -v
# 1 passed (first run post-fix)

# Stability (3 consecutive new-apply attempts)
# Run 1: skipped (provision found no eligible job — prior run consumed target)
# Run 2: 1 passed
# Run 3: (included in full journey below)

.venv\Scripts\python.exe -m pytest tests/journey -q
# 8 passed

.venv\Scripts\python.exe -m pytest tests/journey/test_employer_resume_journey.py -q
# 2 passed

.venv\Scripts\python.exe -m pytest tests/test_registry_validation.py tests/smoke -q
# 16 passed
```

| Suite | Count |
| --- | --- |
| `tests/journey` | **8 passed**, 0 failed, 0 skipped (full suite after fix) |
| `tests/journey/test_employer_resume_journey.py` | **2 passed** |
| Registry + smoke | **16 passed** |

---

## Remaining blockers

- **Rate limit:** Heavy repeated D1 new-apply runs in one hour will **skip** with an explicit message — correct behavior, not a false pass.
- **Provision skip:** If every journey-apply slug is already applied and provision returns nothing, new-apply **skips** (unchanged D1 prerequisite).
- **Pagination:** Duplicate/persistence test still scans application list pages 1–3 for fixture job A (unchanged from D2).

---

## References

- D1: `docs/OURNEAR_QA_AGENT_PHASE_D1_REPORT.md`
- D2: `docs/OURNEAR_QA_AGENT_PHASE_D2_REPORT.md`
