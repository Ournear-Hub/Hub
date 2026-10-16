# FE-103 — DetailHeader browser QA report

**Date:** 2026-10-10  
**Journey ID:** `JRN-FE103-DETAIL-HEADER-01`  
**Backlog:** [OURNEAR_HIRE_FRONTEND_ALIGNMENT_BACKLOG.md](./OURNEAR_HIRE_FRONTEND_ALIGNMENT_BACKLOG.md) (FE-103)

## Environment

| Item | Value |
|------|--------|
| Base URL | `http://127.0.0.1:8000` |
| Auth | `login_account_user` (POST `/login`, wait for `/account`) |
| Fixtures | `php artisan qa:hiring-fixtures` |
| Target discovery | `scripts/inspect-qa-fe103-detail-targets.php` (read-only) |
| Browser | Playwright Chromium (`playwright install chromium` in `qa-agent/.venv`) |
| Credentials | `qa-agent/config/local.env` (`OURNEAR_QA_*`, password `Password1!` from fixtures output) |
| Laravel | `php artisan serve --host=127.0.0.1 --port=8000` |
| Frontend assets | Prior `npm run build` (FE-101/102 batch) |

## Commands (focused)

```bash
cd qa-agent
playwright install chromium
PYTHONPATH=. pytest tests/journey/test_fe103_detail_header_browser.py -v --browser chromium
```

## Routes verified (discovered IDs — not hard-coded)

Employer A session:

| Surface | Path (2026-10-10 run) |
|---------|------------------------|
| Employer application detail | `/account/jobs/qa-fixture-journey-apply-40/applications/40` |
| Recruitment Partnership submission detail | `/account/submissions/1` |

Candidate session (focused add-on, same fixture application):

| Surface | Path |
|---------|------|
| Candidate application detail | `/account/applications/40` |

**Note:** Backlog examples used `qa-fixture-published-a` and `/applications/1`; local QA data uses journey-apply jobs and monotonic application IDs. Discovery script selects the latest QA candidate application on Employer A.

## Viewports

| Label | Size | Pages |
|-------|------|--------|
| desktop | 1440×900 | Employer application + submission |
| tablet | 768×1024 | Employer application + submission |
| mobile | 390×844 | Employer application + submission |
| mobile | 390×844 | Candidate application (second test) |

## Assertions (browser)

- Authenticated navigation reaches each target (no 403/404).
- **DetailHeader:** employer application — h1 job title, h2 candidate name, application ID in main; submission — h1 requirement title, “Candidate submission” in main, h2 candidate name, submission business ID.
- **Resume:** employer “Resume” region with View/Download on submission; candidate “Resume used” + download link.
- **Timeline:** employer application timeline region when events exist; submission timeline list with “Submitted to your company”; candidate `Application timeline` list with submit event.
- **Diagnostics:** no console errors, page errors, actionable request failures, or HTTP 5xx (`BrowserSession`).
- **Layout:** scroll width − client width ≤ 8px (no material horizontal overflow).
- **A11y (sample):** first Tab on desktop employer application detail yields a visible focus indicator.

## Results

| Test | Outcome |
|------|---------|
| `test_jrn_fe103_employer_detail_header_browser` | **PASS** |
| `test_jrn_fe103_candidate_application_detail_mobile` | **PASS** |

**Pytest run record:** `qa-agent/artifacts/20261010T080244Z-0a02b203/run-record.json`  
**Screenshots:** `qa-agent/artifacts/fe103/20261010T080244Z-0a02b203/` (`employer-application-*.png`, `employer-submission-*.png`, `candidate-application-mobile.png`)

## Limitations

- Keyboard/focus checks are a single Tab sample on employer application desktop only.
- `hideWhenEmpty` empty-state timeline not exercised (fixtures include timeline events).
- Candidate check is mobile-only; employer routes are the FE-103 primary scope.
- Re-run requires local server + fixtures; application ID may change while submission `1` may refer to an older partnership record on long-lived DBs — always use `inspect-qa-fe103-detail-targets.php`.
