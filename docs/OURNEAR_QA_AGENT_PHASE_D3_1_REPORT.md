# OurNear Human QA Agent — Phase D3.1 report

**Date:** 2026-10-09  
**Scope:** Combined journey suite stability (employer login timeout)  
**Laravel `app/` code:** unchanged.

---

## Summary

| Factor | Verdict |
| --- | --- |
| **Primary failure mode** | Account **`POST /login`** returned **HTTP 429** (`throttle:account-login`, **5/min per email\|IP**) during back-to-back combined runs — not a slow page load. |
| **Test defect (symptom)** | `NavigationHelper.wait_for_path("**/account**")` after submit **timed out** while URL stayed `/login`, which looked like a “login timeout” rather than throttling or failed auth. |
| **Session isolation** | **Contributing factor (D3):** shared browser context without clearing cookies let admin/account guards collide; **`clear_cookies()`** before each login remains required. |
| **Parallel workers** | **Not involved** — default single-worker pytest; no `xdist` in `pyproject.toml`. |
| **Fixture contention** | **Not observed** — module-scoped journey targets are read-only; failures correlated with login count, not discover script races. |
| **Arbitrary sleeps / timeout inflation** | **Not applied** — fix uses POST + URL predicates (same pattern as admin login). |

---

## Evidence

### Inspection

| Area | Finding |
| --- | --- |
| `page` fixture | Function-scoped (pytest-playwright); **same browser context** across tests in a session. |
| `clear_cookies()` | Present on admin/account login entry; prevents admin guard from blocking account login. |
| Old employer login | Click **Log in** → `wait_for_url("**/account**")` with **no POST assertion**; on 429 or validation failure, URL remains `/login` until 30s timeout. |
| Rate limit | `AppServiceProvider`: `account-login` → `Limit::perMinute(5)->by(email\|ip)`. |
| Full journey logins (typical) | Multiple account logins within one minute (admin isolation ×2, candidate ×2, employer ×2) can exceed **5/min** for the same email when runs are chained. |

### Reproduction (after shared `login_account_user`, before skip handling)

Three **`pytest tests/journey -q`** runs in quick succession:

- Run 1: **13 passed**
- Run 2: **12 passed**, 1 skipped (provision)
- Run 3: **7 passed**, **3 failed**, **3 skipped** — failures were **`JourneyStepError` with HTTP 429** on account login (candidate duplicate, admin isolation ×2); employer tests **skipped** with explicit 429 message.

This confirms the intermittent employer failure class under load is **login throttling**, surfaced as navigation timeout before 429 was detected.

### Controlled verification (70s pause between runs)

After **`login_account_user`** + **`skip_if_account_login_throttled`**:

| Run | Result |
| --- | --- |
| 1 | **12 passed**, 1 skipped (no eligible new-apply job) |
| 2 | **12 passed**, 1 skipped |
| 3 | **12 passed**, 1 skipped |

**Registry + smoke:** `pytest tests/test_registry_validation.py tests/smoke -q` → **16 passed**.

---

## Fix (qa-agent only)

| File | Change |
| --- | --- |
| `ournear_qa/journey/account_auth.py` | **New** — `login_account_user()`: clear cookies, `expect_response` on account `POST /login`, classify **429**, `wait_for_url` with `/account` predicate (excludes `/login`). |
| `ournear_qa/journey/errors.py` | **New** — shared `JourneyStepError` (avoids circular imports). |
| `candidate_apply.py` / `employer_resume.py` | Delegate account login to `login_account_user`. |
| `admin_application.py` | Isolation helper uses shared account login. |
| `tests/journey/conftest.py` | `skip_if_account_login_throttled()` — explicit skip, not false fail. |
| Journey test modules | Catch login **429** on candidate, employer, admin isolation tests. |
| `tests/journey/test_account_auth_url.py` | URL predicate unit tests. |

No login retries, no global timeout increases, no cache clears, no Laravel changes.

---

## Remaining risks

- **Heavy local looping:** More than ~**5 account logins/minute** for the same QA email (e.g. three full suites back-to-back with no pause) may **skip** isolation or duplicate tests — correct outcome, not a pass.
- **New-apply test** may still **skip** when provision finds no job or **job-applications** hourly limit applies (D2.1).
- **Admin login** remains separate (`/admin/login`); admin throttle is independent of account login.

---

## Commands

```powershell
cd "D:\Ournear Hub\Ournear Hire\qa-agent"
.venv\Scripts\python.exe -m pytest tests/journey -q
# For three stable local runs: allow ~70s between runs if prior run logged in many times.
.venv\Scripts\python.exe -m pytest tests/test_registry_validation.py tests/smoke -q
```

---

## References

- D3: `docs/OURNEAR_QA_AGENT_PHASE_D3_REPORT.md`
- D2.1: `docs/OURNEAR_QA_AGENT_PHASE_D2_1_REPORT.md`
