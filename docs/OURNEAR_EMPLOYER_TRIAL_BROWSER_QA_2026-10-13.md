# OurNear Hire — Employer activation, trial publishing & security (browser QA)

**Date:** 2026-10-13  
**Environment:** Local only (`http://127.0.0.1:8000`)  
**Production / staging:** Not modified, not deployed.

## Test accounts

| Account | Login | Password | Notes |
| --- | --- | --- | --- |
| Trial browser employer | `qatrialbrowser` (username; email login blocked when unverified) | `Password1!` | `php scripts/qa-trial-browser-provision.php prepare` resets trial state |
| Cross-employer A | `qaemployerA` or `qa-employer-a@example.test` | `Password1!` | `php artisan qa:hiring-fixtures` |
| Cross-employer B | `qaemployerB` or `qa-employer-b@example.test` | `Password1!` | Published job slug `qa-fixture-published-b` |

## Scenario results

| Scenario | Status | Summary |
| --- | --- | --- |
| **A/B** — Activation + first trial publish | **PASS** | Workspace activated without OTP; first publish from Job management; trial tier + ~1h expiry; public job detail 200 |
| **D** — Multiple drafts | **PASS** | Two UI drafts saved; `trial_job_used_at` null until publish; drafts not public (404 on `/jobs/{slug}`) |
| **E** — Second publication | **PASS** | Publish on second draft rejected in UI; beta remains draft; alpha trial unchanged |
| **G** — Authorization & visibility | **PASS** | Employer A → B edit/applicants: **403**; expired trial hidden before scheduler (`expire` harness → public **404**); `jobs:expire-trials` idempotent |

## Routes exercised (browser)

| Route | Method / action | Result |
| --- | --- | --- |
| `/login` | POST (UI) | Unverified employer: use **username** |
| `/account/hiring` | GET + activation form | Workspace ready banner after POST |
| `/account/jobs/create` | POST via **Save draft** | Drafts created |
| `/account/jobs` | GET; **Publish** on draft | Trial job published; second publish blocked |
| `/jobs/qa-browser-draft-alpha` | GET (guest) | **200** while trial valid; **404** after `expire` harness |
| `/account/jobs/qa-fixture-published-b/edit` | GET as Employer A | **403** |
| `/account/jobs/qa-fixture-published-b/applicants` | GET as Employer A | **403** |
| `/account/jobs/qa-fixture-published-a/edit` | GET as Employer A | **200** (Edit Job UI) |

## Persisted state (local harness)

After full flow (inspect via `php scripts/qa-trial-browser-provision.php inspect`):

- **Pre-publish (two drafts):** `trial_job_used_at=null`; jobs 23–24 `status=draft`, `public=no`.
- **After trial publish:** `trial_job_used_at` set; job 23 `status=published`, `tier=trial`, `trial_expires_at` ≈ publish + 1 hour, `public=yes`; job 24 `draft`.
- **After `expire qa-browser-draft-alpha`:** `public=no` before artisan command.
- **After `php artisan jobs:expire-trials` (×2):** first run `Expired 1 trial job(s).` exit **0**; second run `Expired 0 trial job(s).` exit **0**; job 23 `status=draft`, `active=false`, `public=no`.

## Commands executed (exit codes)

| Command | Exit |
| --- | --- |
| `php scripts/qa-trial-browser-provision.php prepare` | 0 |
| `php scripts/qa-trial-browser-provision.php inspect` | 0 (multiple) |
| `php artisan qa:hiring-fixtures` | 0 |
| `php scripts/qa-trial-browser-provision.php expire qa-browser-draft-alpha` | 0 |
| `php artisan jobs:expire-trials` (1st) | 0 |
| `php artisan jobs:expire-trials` (2nd) | 0 |
| `php artisan test tests/Feature/EmployerUnverifiedTrialPublishTest.php` | 0 (4 passed) |
| `php artisan test --filter=test_employer_can_update_and_delete_only_owned_jobs` | 0 |
| `php artisan test --filter=test_cross_employer_resume_access_is_forbidden` | 0 |
| `npx tsc --noEmit` | 0 |
| `npm run build` | 0 |

## Evidence (screenshots)

Stored under `docs/qa-evidence/`:

- `2026-10-13-trial-activation-success.png`
- `2026-10-13-scenario-d-draft-one.png`
- `2026-10-13-scenario-d-two-drafts.png`
- `2026-10-13-scenario-ab-trial-published.png`
- `2026-10-13-scenario-ab-public-job-detail.png`
- `2026-10-13-scenario-e-second-publish-blocked.png`
- `2026-10-13-scenario-g-cross-edit-403.png`

## Scheduler (staging / production)

- **Registered:** `Schedule::command('jobs:expire-trials')->everyFiveMinutes();` in `routes/console.php`.
- **Host cron (staging/production):** run Laravel scheduler every minute:  
  `* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1`
- **Local:** manual `php artisan jobs:expire-trials` or rely on scheduler if `schedule:work` / system cron is configured.

## Notes / defects

- **Automation:** Inertia controlled fields (activation phone, some selects) require React-compatible input events or `browser_select_option`; plain `browser_fill` alone can submit empty `company_phone`. Manual UI works; use native value setter or select helpers for E2E.
- **No product defects** filed from this pass for trial policy, draft allowance, second-publish block, cross-employer 403, or expiry command.

## Confirmation

- **Production:** unchanged.  
- **Staging:** unchanged.  
- **Local DB:** QA accounts and jobs only; reset trial employer via `qa-trial-browser-provision.php prepare` when needed.
