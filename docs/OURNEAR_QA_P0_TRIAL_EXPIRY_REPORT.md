# OurNear QA — Trial job posting expiry

**Date:** 2026-10-09  
**Scope:** Release evidence for **one-hour trial** public visibility — no Laravel `app/` changes in this pass.

---

## 1. Implemented product behavior

| Rule | Implementation |
| --- | --- |
| First published job per employer profile | `posting_tier=trial`, `trial_expires_at = now + 1 hour` (`EmployerJobsController::store`) |
| Subsequent published jobs | `basic`, `trial_expires_at = null` |
| Public visibility | `Job::isPubliclyVisible()` requires `publishing_status=Published`, `active`, and for trial tier: `trial_expires_at >= now()` |
| Public routes | `PublicSiteController` / apply / API job show call `abort_unless($job->isPubliclyVisible(), 404)` |
| After expiry | Job remains in DB with published status but **is not publicly visible** (404 on `/jobs/{slug}`) |

Employer UI copy (create job): first post may use a one-hour trial tier. No separate “trial expired” admin banner was required for this release slice.

---

## 2. PHPUnit evidence (authoritative for expiry)

| Test | What it proves |
| --- | --- |
| `EmployerAccountWorkspaceTest::test_employer_jobs_are_owned_validated_trial_then_basic_and_never_unpaid_featured` | Trial job public while fresh; after `travel(61)->minutes()` trial job **404** on `jobs.show`; basic job still **200** |
| `JobsAtsFoundationStage5Test::test_lifecycle_pause_hides_public_job_syncs_active_and_is_audited` | Pause removes public visibility (related lifecycle, not trial clock) |
| `Job::scopePubliclyVisible()` / `isPubliclyVisible()` | Trial window enforced in query layer |

### Verification run (2026-10-09)

```text
php artisan test --filter=test_employer_jobs_are_owned_validated_trial_then_basic_and_never_unpaid_featured
Result: 1 passed (15 assertions), ~11s
```

---

## 3. Browser QA (2026-10-13 update)

| Item | Result |
| --- | --- |
| Full employer trial browser pass | **Done** — see `docs/OURNEAR_EMPLOYER_TRIAL_BROWSER_QA_2026-10-13.md` |
| Clock for expiry | **Harness only** — `php scripts/qa-trial-browser-provision.php expire <slug>` adjusts `trial_expires_at` (not used to simulate publish/activation) |
| Public hide before scheduler | **Verified** — `Job::isPubliclyVisible()` → 404 on `/jobs/{slug}` after harness expiry |
| `jobs:expire-trials` | **Verified** — transitions eligible jobs to draft; second run idempotent (0 expired) |
| Real-time 61-minute wait | **Not required** — PHPUnit + harness + browser evidence sufficient |

Screenshots: `docs/qa-evidence/2026-10-13-*.png`.

---

## 4. Unresolved / out of scope

- Live staging trial clock: **not verified** (staging smoke not run).
- Payment/upgraded featured tier: unchanged; featured remains gated in product.
- Mobile: paused per web completion charter.

---

## 5. Completion criterion

**Met:** Trial expiry uncertainty closed via **existing implementation + focused PHPUnit**. Remaining gap is **optional browser/UX proof**, not missing backend behavior.

See also: `docs/OURNEAR_WEB_RELEASE_READINESS.md`.
