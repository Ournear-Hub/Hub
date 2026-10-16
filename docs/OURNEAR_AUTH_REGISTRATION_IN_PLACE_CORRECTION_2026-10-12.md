# Auth / registration in-place correction (2026-10-12)

## Summary

Reverted the parallel **`Account/AuthEntry`** flow and redeveloped **`Account/Register`** and **`Account/Login`** in place with a shared **`AccountAuthShell`**. Public website registration is **candidate-only**, auto-username when omitted, **no auto-login** (redirect to `/login` + verification message per existing contract).

## Removed / consolidated

| Item | Action |
| --- | --- |
| `Account/AuthEntry.tsx` | Removed |
| `POST /auth/continue`, `AuthEmailContinueController` | Removed |
| `GET /register/full`, `createLegacy()` | Removed |
| `compact=1` registration branch (auto-login) | Removed |
| `CompactAuthEntryTest` | Replaced by `AccountCompactRegistrationTest` |

## Current routes (unchanged names)

- `GET/POST /register` → `Account/Register` + `RegisteredUserController`
- `GET/POST /login` → `Account/Login` + `AuthenticatedSessionController`
- Password reset, verification, guards unchanged

## Registration UX ( `/register` )

Fields: **name**, **email**, **password**, **confirm password**, **terms**. No employer type, company, mobile, or username on the form (username generated server-side when not posted).

## Backend

- `RegisterRequest`: `role` **prohibited**; optional `username` for tests/API payloads
- `RegisteredUserController::store`: always `UserRole::Candidate` + `CandidateProfile`; redirect **`login`** with `registered_email` + status flash (guest)

## Tests executed (this correction)

```text
php artisan test tests/Feature/HireIdentityResumeImportTest.php
php artisan test tests/Feature/AccountCompactRegistrationTest.php
php artisan test tests/Feature/AccountVerificationEmailTest.php
php artisan test tests/Feature/AuthenticationTest.php
php artisan test tests/Feature/DualGuardAuthenticationTest.php
php artisan test tests/Feature/AccountSwitchingTest.php
→ 52 passed, 0 failed

php artisan test tests/Unit/PhpunitMemoryLimitTest.php
→ 1 passed (process memory_limit >= 512M via phpunit.xml)

npx tsc --noEmit → **PASS**
npm run build → **PASS**

php artisan test tests/Feature/AccountAuthExperienceTest.php → **10/10 PASS** (after auto-username test update)

php artisan test (full suite) → first run started before AccountAuthExperience fix: `storage/logs/phpunit-full-20261012-post-auth-correction.txt` (may show 1 failure); **re-run recommended** for final gate
```

## HireIdentityResumeImportTest (REG separate from OOM)

**Root cause:** Test expected user creation on **stage-only** upload; product flow is **stage + confirm**. PDF stubs were not structurally valid for email extraction.

**Fix:** Use `ReadablePdfFixture::onePageWithContact()`, `Storage::fake('local')`, call **`imports.confirm`** before assertions.

**Result:** **4/4 PASS**

## PHPUnit memory

- Bare `php -r "ini_get('memory_limit')"` → **128M** (Windows CLI default)
- `php artisan test` with `phpunit.xml` `<ini name="memory_limit" value="512M"/>` → **`PhpunitMemoryLimitTest` PASS** (512M effective in test process)
- Prior full-suite OOM at 128M was **before** phpunit.xml ini; re-run logged separately (see above)

## Full suite evidence (2026-10-12)

| Log | Result |
|-----|--------|
| `storage/logs/phpunit-full-20261012-post-auth-correction.txt` (v1) | **911 passed**, 3 failed, 3 skipped — included pre-fix `AccountAuthExperienceTest` username failure |
| `storage/logs/phpunit-full-20261012-post-auth-correction-v2.txt` (v2) | **912 passed**, 2 failed, 3 skipped, exit **2** — auth experience **PASS**; `CandidateAccountStage19Test`, `PublicHeaderAccountNavTest` failed |
| `storage/logs/phpunit-full-20261012-post-auth-correction-v3.txt` (v3) | **914 passed**, 0 failed, 3 skipped, **14479** assertions, ~484s, exit **0** — after scoped test fixes (Inertia assert order; registration `registered_email` session assert) |

PHPUnit full suite is **green** on v3. Browser/Playwright and profile matrix remain **not run**.

## Not run in this pass

- Playwright / qa-agent auth journeys
- 58-field profile browser matrix
