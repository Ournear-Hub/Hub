# OurNear Hire — Independent QA continuation (2026-10-12)

**Agent:** Independent QA / defect repair (post P0 implementation report)  
**Workspace:** `D:\Ournear Hub\Ournear Hire`  
**Scope:** Re-test, fix reproducible defects, extend evidence; no feature building.

---

## 1. Readiness verdict (evidence-based)

**`PARTIALLY VERIFIED — NOT RELEASE-READY`**

| Gate | Result |
| --- | --- |
| Scoped P0 + REG PHPUnit (this session) | **PASS** (see §3) |
| REG-COM-002 / COM-003 / COM-004 | **PASS** after route + mail queue fixes |
| Full PHPUnit suite | **BLOCKED (OOM)** — aborted at **128MB** in `zipstream-php` after `LocalDevelopmentSeederTest`; `php -d memory_limit=512M` did **not** raise CLI limit on this host; **`phpunit.xml` now sets `memory_limit=512M`** for re-run |
| 58-field profile browser matrix | **NOT EXECUTED** this session (prior audit: PASS_WITH_COVERAGE_GAPS) |
| Playwright / qa-agent journeys | **NOT RUN** (no local app server started for browser batch) |
| `npx tsc --noEmit` | **PASS** (app sources; test files excluded — see §6) |
| `npm run build` | **PASS** |

Staging, live SMTP/inbox, and full browser regression remain **unverified**.

---

## 2. Defects found and repaired (this session)

### DEF-QA-20261012-001 — Employer communication settings 403 (REG-COM-004)

- **Symptom:** `GET/PUT account.settings.communication` returned **403** for employers.
- **Root cause:** Routes lived inside `account.candidate` middleware (`EnsureCandidate`), not controller policy.
- **Fix:** Moved `settings/communication` routes to the shared `auth:account` + `account.member` group in `routes/account.php`.
- **Regression:** `AccountEmployerCommunicationSelfServiceTest` **9/9 PASS**.

### DEF-QA-20261012-002 — Operational reject email not asserted / not queued (REG-COM-002)

- **Symptom:** `CandidateCommunicationPreferenceDeliveryTest` — `Mail::assertQueued(ApplicationRejectedMail::class, 1)` failed (0 queued).
- **Root cause:** `RecruitmentNotificationService::queueToApplicationEmail()` used `Mail::send()` while tests and employer path use `Mail::queue()`.
- **Fix:** Use `Mail::to($email)->queue($mailable)` in `RecruitmentNotificationService.php`.
- **Regression:** `CandidateCommunicationPreferenceDeliveryTest` **7/7 PASS**.

### DEF-QA-20261012-003 — Employer verification COM test fixture (REG-COM-004)

- **Symptom:** `verification email still sent when system opted out` — notification not sent.
- **Root cause:** `UserFactory` sets `email_verified_at` by default; `sendEmailVerificationNotification()` no-ops when already verified.
- **Fix:** Test uses `email_verified_at => null` (test harness only).

### DEF-QA-20261012-004 — Missing preference rows test vs User::created defaults (REG-COM-004)

- **Symptom:** Expected 0 preference rows before first settings load; factory user already had 5 from `User::created` → `ensureDefaults`.
- **Fix:** Test deletes preference rows before asserting lazy materialization on settings GET.

---

## 3. Commands executed and results (this session)

Historical P0 run (implementation report) is **not** repeated here as a new proof of browser behavior.

### Communications (REG-COM-002–004)

```text
php artisan test tests/Feature/CandidateCommunicationPreferenceDeliveryTest.php tests/Feature/AccountEmployerCommunicationSelfServiceTest.php tests/Feature/EmployerCommunicationPreferenceConsistencyTest.php
→ 19 passed, 0 failed (after fixes)
```

### P0 + registered REG batch

```text
php artisan test tests/Feature/CompactAuthEntryTest.php … JobMasterDataValidationTest.php (10 files)
→ 52 passed, 0 failed
```

### Resume, profile modules, ATS regressions

```text
php artisan test tests/Feature/EmployerApplicantResumeAccessTest.php … QaDefectRegressionTest.php (5 files)
→ 41 passed, 0 failed
```

### Full suite

```text
php artisan test  → ABORTED (PHP memory_limit 128MB exhausted), log: storage/logs/phpunit-independent-qa-20261012.txt
```

### Test harness alignment (P0 auth / header / profile owner preview)

Regression tests updated to match shipped behavior (not product defects):

- `Account/AuthEntry` on `/login` (`DualGuardAuthenticationTest`, `CorrectionPassTest`)
- Public header nav via `headerAccountNav` + `PublicHeaderAccountNav.php`
- Saved jobs list uses `status_label` (`AccountSavedJobTest`)
- Closed job apply/show → **404** when not publicly visible (`CandidateApplicationLifecycleConsistencyTest`)
- Owner full profile when sharing disabled; guest **404** (`CandidateTalentPoolStage3Test`)
- `VerifyAccountEmailNotification` (`AccountUserFoundationTest`)
- Log out after `giveCandidateResume()` before guest profile assertions

### Frontend

```text
npx tsc --noEmit  → exit 0
npm run build     → exit 0
```

---

## 4. Test coverage by role / workflow (PHPUnit, this session)

| Role / workflow | Test class(es) | Result |
| --- | --- | --- |
| Compact auth entry | `CompactAuthEntryTest` | 5/5 PASS |
| Account switching / dual guard | `AccountSwitchingTest` | 6/6 PASS |
| Candidate profile persistence | `AccountCandidateProfilePersistenceHardeningTest`, `UnifiedAccountFunctionalCompletionTest` | 13/13 PASS |
| Public profile owner preview | `UnifiedAccountFunctionalCompletionTest`, `UnifiedProfileStage23FTest` | PASS (scoped) |
| Employer company workspace | `EmployerCompanyWorkspaceTest` | 6/6 PASS |
| Hiring-wide ATS | `EmployerHiringApplicationsPipelineTest` | 3/3 PASS |
| Employer compare | `EmployerApplicantCompareTest` (REG-BRW-004) | 3/3 PASS |
| Master data on jobs | `JobMasterDataValidationTest` (REG-PROD-009) | 8/8 PASS |
| Admin restricted roles | `AdminRestrictedRoleAccessTest` (REG-SEC-003) | 5/5 PASS |
| Admin internal CVs | `AdminInternalCandidateResumeUiTest` | 3/3 PASS |
| Application + resume access | `ApplicationExperienceTest`, `EmployerApplicantResumeAccessTest` | 22/22 PASS |
| COM preferences | COM-002/003/004 test files | 19/19 PASS |

---

## 5. Profile 58-field matrix (Workstream 2)

**Browser matrix not re-run** in this session (requires `127.0.0.1:8000` + Playwright profile audit suite).

**PHPUnit proxy coverage:** `AccountCandidateProfilePersistenceHardeningTest` (10), professional module spot checks in prior audit, `UserProfileModulePart3Test` (3), `QaDefectRegressionTest` library resume on profile (1).

**Canonical inventory:** `docs/OURNEAR_PROFILE_FIELD_AUDIT_AND_REPAIR_REPORT.md` — **58** candidate fields; **23 PASS**, **1 BLOCKED** (429 login), remainder **NOT_TESTED** in browser as of 2026-10-10 run `20261010T105154Z-f5679a64`.

**REG-CAN-PROF-001:** Personal save not clearing company fields — **PASS** via `UnifiedAccountFunctionalCompletionTest` + hardening suite (not a per-field browser matrix).

---

## 6. TypeScript (Workstream 6)

| File | Issue | Resolution |
| --- | --- | --- |
| `BackNavLink.tsx` | `onClick` type incompatible with Inertia `Link` | Use `MouseEventHandler` |
| `BackNavLink.test.tsx` | `toHaveAttribute` without jest-dom in app tsconfig | Use `getAttribute` + `toBe` |
| `adminDetailHeading.test.ts` | `node:fs` / `node:path` outside app `lib` | Exclude `**/*.test.ts(x)` from root `tsconfig.json` (Vitest still runs these) |

**Note:** Excluding tests from `tsc` matches “app typecheck” vs “vitest typecheck”; run `npm test` for vitest coverage of test files.

---

## 7. Workstream 1 — Auth gaps (remaining)

`CompactAuthEntryTest` covers happy paths only. **Not PHPUnit-covered this session:** invalid email on `/auth/continue`, duplicate registration, weak password, terms rejection, throttle limits, password reset flow, explicit hiring activation redirect chain, logout session invalidation.

**No product defects filed** for these gaps — **coverage gap**, not confirmed failures.

---

## 8. Workstream 4 — Pipeline overlap

No edits to `EmployerApplicationPipeline.php` or hiring-wide ATS controllers (avoid primary-agent overlap). Existing `EmployerHiringApplicationsPipelineTest` **PASS** re-run only.

---

## 9. Security negative tests (this session)

| Test | Result |
| --- | --- |
| `AdminRestrictedRoleAccessTest` | 5/5 PASS |
| `EmployerApplicantResumeAccessTest` (cross-employer, path traversal) | 10/10 PASS |
| `EmployerApplicantCompareTest` (foreign IDs) | 3/3 PASS |
| `AccountEmployerCommunicationSelfServiceTest` (guest/admin denied) | PASS |

---

## 10. Remaining unverified work (priority)

1. **P0:** Execute `qa-agent/tests/profile_audit/test_profile_field_audit.py` (or manual matrix) for NOT_TESTED fields.  
2. **P0:** Playwright cross-role / employer job publish / auth edge journeys.  
3. **P1:** Auth validation negative cases (PHPUnit or browser).  
4. **P1:** Full suite log completion + compare count to REG-PHP-001 baseline (~729+).  
5. **P2:** FE-504 / FE-302 visual regression (deferred by product owner).

---

## 11. Files changed (defect repair)

- `routes/account.php` — communication routes for all account members  
- `app/Services/RecruitmentNotificationService.php` — queue operational mail  
- `tests/Feature/AccountEmployerCommunicationSelfServiceTest.php` — fixtures  
- `resources/js/Components/molecules/BackNavLink.tsx` — typing  
- `resources/js/Components/molecules/BackNavLink.test.tsx` — assertion  
- `tsconfig.json` — exclude test files from app `tsc`
- Test files listed in §3 “Test harness alignment”
