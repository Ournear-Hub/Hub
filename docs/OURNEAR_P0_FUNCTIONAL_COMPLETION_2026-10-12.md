# P0 functional completion — unified account & profile persistence

**Date:** 2026-10-12  
**Status:** **PARTIALLY VERIFIED (local)** — see §Verification (2026-10-12 continuation)

## Root causes fixed

### 1. Dual-role / hiring-enabled candidate basic profile save wiped company data

When a user with `employerProfile` saved **candidate** personal fields (`PUT /account/profile` without company keys), `UpdateProfileRequest::profileAttributes()` still injected every employer scalar as empty → **null**, and `AccountProfileService` used `??` which **does not preserve** explicit nulls. Company `about_company`, benefits, etc. were cleared on unrelated personal saves.

**Fix:** Employer scalars in `profileAttributes()` only when `$this->exists($field)`. `AccountProfileService` updates employer profile only for keys present in attributes (aligned with `EmployerCompanyService::updateCompany()`).

### 2. Full profile view 404 for owner when public sharing disabled

`UnifiedProfilePresenter::publicCandidate()` required `shareAllows('enabled')` for all viewers. Candidates with sharing off could not open **View profile** / `/profiles/{username}` even as owner — looked like “profile broken” or unsaved data.

**Fix:** `publicCandidate(User $user, bool $forOwner = false)` — owners see canonical persisted data with privacy flags bypassed for preview; strangers still 404 when sharing off. `ownerIdentity()` always exposes `public_profile_url` when username exists; `public_profile_share_enabled` flag added for account UI.

### 3. Profile page robustness

`Profiles/Show.tsx` — optional `skills` / `employment_history` default to `[]`; owner preview banner when sharing off.

## Unified authentication (reconciled)

Existing architecture **retained** (single `User`, dual guards, `canAccessHiringWorkspace()`, `POST account.hiring.activate`). No second identity table. Tests extended in `UnifiedAccountFunctionalCompletionTest` for login → candidate home → hiring activation → hiring dashboard → profile preview URL.

Prior coverage: `AccountSwitchingTest`, `EmployerCompanyWorkspaceTest`, `AccountCandidateProfilePersistenceHardeningTest`.

## Files changed

| Area | Files |
| --- | --- |
| Profile persistence | `UpdateProfileRequest.php`, `AccountProfileService.php` |
| Full profile view | `UnifiedProfilePresenter.php`, `PublicProfileController.php`, `Profiles/Show.tsx` |
| Tests | `UnifiedAccountFunctionalCompletionTest.php` (new) |

## Tests authored (not run)

```text
NOT RUN — deferred by product owner
php artisan test --filter=UnifiedAccountFunctionalCompletionTest
php artisan test --filter=AccountCandidateProfilePersistenceHardeningTest
php artisan test --filter=EmployerCompanyWorkspaceTest
php artisan test --filter=AccountSwitchingTest
```

## Continuation (2026-10-12) — unified profile nav + compact auth + internal resumes admin

| Area | Change |
| --- | --- |
| **Compact auth** | `Account/AuthEntry.tsx` email-first flow; `POST /auth/continue`; compact `POST /register` (candidate-only, auto username, auto login); legacy full form at `/register/full` |
| **Unified profile** | `CandidateUnifiedProfileNav` on `/account/profile` — Overview / Personal / Professional / Resumes / Preview / Privacy; sharing-off banner |
| **Admin** | `/admin/candidates/internal-resumes` list, upload, authorized download; nav catalog item under Candidates |

## Verification (local, 2026-10-12)

| Check | Result |
| --- | --- |
| `CompactAuthEntryTest` | **PASS** (5) |
| `UnifiedAccountFunctionalCompletionTest` | **PASS** (3) |
| `AccountCandidateProfilePersistenceHardeningTest` | **PASS** (10) |
| `EmployerCompanyWorkspaceTest` | **PASS** (6) |
| `AccountSwitchingTest` | **PASS** (6) |
| `EmployerHiringApplicationsPipelineTest` | **PASS** (3) |
| `AdminInternalCandidateResumeUiTest` | **PASS** (3) |
| `npm run build` | **PASS** |
| `npx tsc --noEmit` | See latest run (account.ts corruption fixed) |
| Playwright / staging / full suite | **NOT RUN** |

## Remaining (not in this batch)

- Full 58-field profile browser matrix (profile field audit).
- Live SMTP / staging verification.
- FE-504 heading adoption (secondary).
- REG-COM-002–004, REG-SEC-003, REG-BRW-004 employer browser — broader PHPUnit/browser batches not re-run this session.
