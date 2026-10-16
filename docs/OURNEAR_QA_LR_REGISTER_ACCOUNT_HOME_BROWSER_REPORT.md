# LR Register + Account/Home — browser QA report

**Date:** 2026-10-10  
**Journey ID:** `JRN-LR-REG-ACCOUNT-HOME-01`  
**Backlog:** [OURNEAR_HIRE_FRONTEND_ALIGNMENT_BACKLOG.md](./OURNEAR_HIRE_FRONTEND_ALIGNMENT_BACKLOG.md) (LR-HOME follow-on)

## Environment

| Item | Value |
|------|--------|
| Base URL | `http://127.0.0.1:8000` |
| Database | `ournear_hire_local` (via `scripts/verify-local-env.php`) |
| Vite manifest | **OK** (`VITE_MANIFEST` check) |
| Laravel | `php artisan serve` (127.0.0.1:8000) |
| Browser | Playwright Chromium |
| QA config | `qa-agent/config/local.env` (`OURNEAR_ALLOW_RUN=local`) |
| Account session | QA candidate fixture (`OURNEAR_QA_CANDIDATE_*`; password per fixtures doc — not repeated here) |
| Governance | **FE-201 / FE-202 / PR-5M** — not modified |

## Commands

```bash
cd "D:\Ournear Hub\Ournear Hire"
php scripts/verify-local-env.php

cd qa-agent
python -m pytest tests/journey/test_lr_register_account_home_browser.py -v
```

## Routes and viewports

| Spot-check | Route | Viewports |
|------------|-------|-----------|
| **A — Register** | `GET /register` (`route('register')`, Inertia `Account/Register`) | 1440×900, 390×844 |
| **B — Account/Home** | `GET /account` (`route('account.home')`, Inertia `Account/Home`) after `login_account_user` | 1440×900, 390×844 |

## Results

| Check | Outcome | Notes |
|-------|---------|--------|
| Register page load | **PASS** | HTTP 200; h1 “Create Account”; account type radios; Name/Username/Email fields; Create account disabled until terms; Log in link in main |
| Register diagnostics | **PASS** | No console/page errors, no HTTP 5xx, no actionable request failures |
| Register layout | **PASS** | Horizontal overflow delta ≤ 8px (desktop + mobile) |
| Register submission | **Not performed** | By design — no new users created |
| Account/Home load | **PASS** | Authenticated candidate; h1 “Dashboard”; “Welcome back,” copy; navigation visible |
| Account/Home diagnostics | **PASS** | Same BrowserSession rules as FE-103 |
| Account/Home layout | **PASS** | Desktop + mobile overflow check |
| Login redirect | **PASS** | POST `/login` → `/account` (existing `login_account_user` helper) |

**Pytest (final run):** `tests/journey/test_lr_register_account_home_browser.py` **2/2 pass** (~26s)  
**Run record:** `qa-agent/artifacts/20261010T105855Z-e1a75d7d/run-record.json` (if present for session)  
**Screenshots:** `qa-agent/artifacts/lr-register-account/20261010T105855Z-e1a75d7d/` — `register-desktop.png`, `register-mobile.png`, `account-home-desktop.png`, `account-home-mobile.png`

## Limitations

- Register **employer** role and full validation-error matrix not exercised.
- Account/Home checked for **QA candidate** only (not employer dashboard variant).
- No new PHPUnit added for these browser-only checks; reproducible via journey module above.

## Confirmed defects

None in this pass.
