# OurNear QA — P0 Candidate profile and persistence

**Date:** 2026-10-10 (updated)  
**Scope:** Evidence for `CAN-01` / `CAN-02` / `CAN-03` profile and dashboard workflows.

---

## 1. Investigation summary

### CAN-01 (`/account`)

| Concern | Finding |
| --- | --- |
| Completion % vs checklist | **Fixed:** home `profile` enriches `professional` + `library_resume`; header `%` and `profile.profile_completion` both use `calculateProfileCompletion()`. |
| Next-step guidance | **Fixed:** generic “Complete your profile” replaced by server `dashboard.next_action` from `App\Support\CandidateProfileGuidance` (aligned with `profileCompletionItems()` order). |
| Stale guidance after save | **Verified:** fresh GET after profile update advances `next_action` (PHPUnit). |

| Command | Result |
| --- | --- |
| `php artisan test tests/Feature/AccountCandidateDashboardTest.php` | **16 passed** (362 assertions), ~52s |

Browser: not re-run for dashboard completion loop (existing smoke `test_candidate_login_reaches_account` only).

### CAN-02 / CAN-03 profile

Candidate profile persistence was investigated via existing **PHPUnit** coverage (not assumed broken from prior audit lists).

| Concern | Finding |
| --- | --- |
| Professional fields not saving | **Not reproduced** — `AccountCandidateProfessionalTest` updates designation, company, salary, notice period, skills, employment history |
| Personal vs professional sections | Data stored on `CandidateProfile`; tests assert `users` table does not absorb professional columns |
| Cross-user modification | Blocked — employer/candidate B cannot update another profile |
| Reload / redirect persistence | Inertia PUT flows assert DB refresh after redirect |
| Public profile privacy | `UnifiedProfileStage23FTest` — private education/projects hidden from public |
| Resume + profile | `QaDefectRegressionTest::test_candidate_profile_page_reflects_uploaded_library_resume` |

**Browser QA gap:** Registry `human: partial` for `CAN-02` — no dedicated Playwright journey yet; PHPUnit + smoke login provide backend contract evidence.

---

## 2. Verification (2026-10-09)

| Command | Result |
| --- | --- |
| `php artisan test --filter=AccountCandidateProfessionalTest` | **16 passed** (168 assertions), ~21s |
| `php artisan test --filter=UnifiedProfileStage23FTest` | **6 passed** (104 assertions), ~8s |
| `php artisan test --filter=QaDefectRegressionTest` | **7 passed** (201 assertions) — includes profile/resume regression |

---

## 3. Release impact

| Item | Status |
| --- | --- |
| Product defect | **CAN-01 (2026-10-10):** dashboard checklist/next-action consistency (see above). **CAN-02 fixes (2026-10-10):** profile `%` on account profile/dashboard now derived via `calculateProfileCompletion()` (matches `completion_items`); professional module reloads `profile` after save; `library_resume` on profile edit for completion module |
| P0 backend contract | **Supported** by PHPUnit |
| Human browser proof for profile edit UX | **Optional** follow-on (`JRN-CANDIDATE-PROFILE-01` not created — would duplicate PHPUnit unless UX-specific) |

---

## 4. Employer staffing / partnership (related session check)

| Feature | PHPUnit |
| --- | --- |
| `EMP-06` enquiries | `EmployerAccountTest` — **3 passed** (isolation + candidate denied) |
| `EMP-05` submissions | `EmployerSubmissionTest` — **6 passed** |

Staffing/partnership are **implemented** with authorization tests; limited browser QA remains acceptable for P2 risk tier.

---

## 5. Next step

Optional **browser** journey for profile edit (single field save + reload) only if product requires human sign-off beyond PHPUnit; otherwise prioritize **full PHPUnit suite once** before deploy.

---

## REG-CAN-PROF-001 — Partial basic profile PUT (2026-10-12)

### Confirmed defect

After CAN-02 module split, **`PUT /account/profile`** (basic/personal module) no longer sends `experience` / `qualification`, but `UpdateProfileRequest::profileAttributes()` still read missing keys as empty strings → **null**, and `AccountProfileService` applied them to `candidate_profiles`, **wiping** values saved via the professional module or legacy PUT.

Same pattern could clear optional personal fields (`bio`, `professional_headline`, `mobile`, `location`) when omitted from non-standard clients.

### Fix

- `UpdateProfileRequest`: include optional keys only when `$this->exists($field)` (same intent as `UpdateProfessionalProfileRequest::fieldWasSubmitted()`).
- `AccountProfileService`: use `array_key_exists` for personal fields; only pass `experience` / `qualification` to `updateOrCreate` when present in attributes.

### Tests (not run)

`tests/Feature/AccountCandidateProfilePersistenceHardeningTest.php` — **10 cases** — **NOT RUN — deferred by product owner**

PHPUnit / Playwright / TypeScript / build: **NOT RUN — deferred by product owner**

### Follow-up (2026-10-12 P0 functional completion)

**Dual-role wipe:** Hiring-enabled candidates saving **personal** profile only no longer null employer company fields — see `docs/OURNEAR_P0_FUNCTIONAL_COMPLETION_2026-10-12.md`.

**Owner full profile:** `/profiles/{username}` works for owner when public sharing is disabled (preview); strangers still 404.

### Remaining limitations

- Full 58-field browser matrix still **NOT_TESTED** per profile field audit.
- Retiring duplicate legacy `experience`/`qualification` on main PUT remains a **product** cleanup (not required for this fix).
- Photo upload and master-data select controls unchanged.
