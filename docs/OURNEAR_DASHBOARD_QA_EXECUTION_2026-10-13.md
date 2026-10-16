# OurNear Hire — Dashboard QA execution (2026-10-13)

**Scope:** Candidate dashboard, profile, resumes, applications, saved jobs, settings, contact, navigation, authorization (local).  
**Environment:** `C:\Ournear Hub\Ournear Hire` · `http://127.0.0.1:8000` · `php scripts/verify-local-env.php` **OK** (Vite manifest present).  
**Git:** Not available in workspace (no `.git` at reported root).  
**Test account:** `qa-candidate@example.test` / fixtures via `php artisan qa:hiring-fixtures` (per `docs/CURRENT-STATE.md`).  
**Commit / push / deploy:** None.

---

## Executive summary

| Layer | Result |
| --- | --- |
| Focused PHPUnit (165 tests) | **165 passed**, 0 failed |
| `npx tsc --noEmit` | **PASS** (exit 0) |
| `npm run build` | **PASS** (exit 0) |
| Playwright smoke `test_account_login.py` | **1 passed** (after harness fix + `playwright install chromium`) |
| Cursor browser (manual evidence) | **Partial** — dashboard, save-job sync, profile deep links (prior batches); see §Browser |
| Full PHPUnit suite (~914) | **NOT RUN** — focused dashboard bundle only (product-owner pattern: avoid long concurrent DB runs) |
| Staging / email delivery | **NOT RUN** — not authorized |
| Print/PDF visual | **NOT RUN** — manual print preview not executed this session |
| Employer portal switching matrix | **NOT RUN** — PHPUnit employer workspace tests not in this bundle; browser not exercised for all four employer personas |

**Release verdict:** **NOT READY** — local focused evidence is strong; staging smoke, full regression suite re-run, exhaustive manual matrix, and employer-switching browser matrix remain **insufficient_evidence** for production release (aligned with `docs/OURNEAR_RELEASE_ISSUE_REGISTER.md`).

---

## Defects found and fixed (this session)

| ID | Sev | Root cause | Fix | Regression |
| --- | --- | --- | --- | --- |
| DEF-QA-HARNESS-001 | P2 | Playwright login journey used label `Username or Email` and submit `Log in`; Account Auth uses **Username or email address** and button **Login** | `qa-agent/ournear_qa/browser/forms.py`: `fill_account_login` + `click_login_submit()`; smoke test + `account_auth.py` / `candidate_apply.py` / profile audit updated | `pytest tests/smoke/test_account_login.py` **PASS** |
| DEF-QA-HARNESS-002 | P3 | Playwright Chromium missing in agent sandbox cache | `playwright install chromium` in `qa-agent/.venv` | Smoke test **PASS** |

No new **application** defects confirmed in candidate dashboard code during this pass (prior rectification batches 1–4 cover saved jobs, preferences, resumes).

---

## PHPUnit commands and results

### Bundle A — dashboard / profile / saved jobs / contact (102 tests)

```text
php artisan test tests/Feature/AccountCandidateDashboardTest.php
  tests/Feature/AccountCandidateProfessionalTest.php
  tests/Feature/AccountCandidateResumeDocumentTest.php
  tests/Feature/AccountSavedJobTest.php
  tests/Feature/CandidateTalentPoolStage3Test.php
  tests/Feature/AccountCandidateSidebarIdentityTest.php
  tests/Feature/PublicWebsiteTest.php
  tests/Feature/CandidateAccountStage19Test.php
  tests/Feature/CorrectionPassTest.php
```

**Result:** 102 passed, 1697 assertions, ~35s.

### Bundle B — applications / settings / public profile / comms / resumes (63 tests)

```text
php artisan test tests/Feature/ApplicationExperienceTest.php
  tests/Feature/ApplicationsWorkspaceTest.php
  tests/Feature/AccountUserFoundationTest.php
  tests/Feature/UnifiedProfileStage23FTest.php
  tests/Feature/CandidateCommunicationPreferenceDeliveryTest.php
  tests/Feature/AccountEmployerCommunicationSelfServiceTest.php
  tests/Feature/ResumeModulePart4Test.php
```

**Result:** 63 passed, 716 assertions, ~33s.

### Full suite

**NOT RUN** — deferred to avoid unverified parallel load on shared local DB; last recorded full run: **914 passed / 3 skipped** (`storage/logs/phpunit-qa-execution-20261012T100000Z.txt`).

---

## Test inventory (summary)

Legend: **A** = automated PHPUnit · **B** = Playwright/browser · **M** = manual Cursor browser this session.

| ID | Module | Route | Expected | Actual | Status | Evidence |
| --- | --- | --- | --- | --- | --- | --- |
| DASH-01 | Dashboard layout | `/account` | Single H1 “Candidate dashboard” | One H1 observed | **PASS (M)** | Browser snapshot 2026-10-13 |
| DASH-02 | Metrics | `/account` | App/saved counts match data | Applications 1, Saved 0→1 after save | **PASS (M)** | Save Employer B job, reload |
| DASH-03 | Saved jobs section | `/account` | Separate card + empty state | “Saved jobs” section with preview row | **PASS (M)** | After save |
| DASH-04 | Cross-candidate data | `/account` | No other user’s data | — | **PASS (A)** | `AccountCandidateDashboardTest` |
| PHOTO-01 | Photo auth | `/account/profile/photo` | Owner only | — | **PASS (A)** | `CorrectionPassTest`, `AccountUserFoundationTest` |
| PHOTO-02 | Upload JPG/PNG/WEBP | UI | Validation 5MB | — | **NOT RUN (M)** | PHPUnit covers upload/remove |
| PERS-01 | Personal profile | `#personal` | View/edit persist | — | **PASS (A)** partial | Prior batch 4 browser; `AccountUserFoundationTest` |
| PROF-01 | Professional scoped update | API | Preferences don’t wipe role | — | **PASS (A)** | `AccountCandidateProfessionalTest` |
| PREF-01 | Job Preferences UI | `#preferences` | Dedicated component | Dedicated page observed | **PASS (M)** | Prior session + build |
| LANG-01 | Languages CRUD | `#languages` | Add/edit/remove persist | — | **PASS (A)** | `CandidateTalentPoolStage3Test` |
| RES-01 | Hire resume preview | `/account/resumes/builder` | Profile-backed preview | — | **PASS (A)** | `AccountCandidateResumeDocumentTest`, Stage19 |
| RES-02 | My resume replace fail | POST replace | Keep prior file on invalid | — | **PASS (A)** | `failed_resume_replacement_preserves_*` |
| RES-03 | Supporting docs | `/account/resumes` | Private CRUD | — | **PASS (A)** | `AccountCandidateResumeDocumentTest` |
| RES-PRINT | Print/PDF | builder | Hide nav in print | CSS present | **NOT RUN (M)** | `app.css` @media print — not visually opened |
| APP-01 | Applications list/filters | `/account/applications` | Filters + auth | — | **PASS (A)** | `ApplicationExperienceTest` |
| SAV-01 | Save/unsave/persist | saved-jobs | DB + UI | — | **PASS (A)** | `AccountSavedJobTest` |
| SAV-02 | Dashboard sync | `/account` | Count updates | 0→1 on reload | **PASS (M)** | 2026-10-13 |
| SET-01 | Settings pages | `/account/settings/*` | Render + auth | — | **PASS (A)** | `AccountUserFoundationTest` |
| COM-01 | Comm preferences | settings communication | Persist + delivery rules | — | **PASS (A)** | `CandidateCommunicationPreferenceDeliveryTest` |
| PUB-01 | Public profile privacy | `/profiles/{user}` | No private leak | — | **PASS (A)** | `UnifiedProfileStage23FTest` |
| CNT-01 | Contact enquiry | `/contact` | Persist + validation | — | **PASS (A)** | `PublicWebsiteTest` |
| AUTH-01 | Login smoke | `/login` | Reach `/account` | Redirect OK | **PASS (B)** | Playwright smoke |
| NAV-01 | Sidebar OurNear ID | account pages | Hidden except settings | — | **PASS (A)** | `AccountCandidateSidebarIdentityTest`, batch 4 |
| EMP-01 | Employer switching | account menu | Activation gates | — | **NOT RUN (M/B)** | Requires employer fixture browser matrix |
| NOTIF-01 | Bell | account layout | Dropdown (no `/account/notifications` navigation) | Expanded menu on click | **PASS (B)** | Batch 8: `AccountNotificationsBell`; Settings hub notifications card removed |
| NAV-B8 | Sidebar scope | `/account` | No Job Prefs / Languages / Privacy | Sidebar list verified | **PASS (B)** | Batch 8 browser 2026-10-13 |
| A11Y-390 | Mobile overflow | 390×844 | No horizontal scroll | — | **NOT RUN (M)** | Batch 4 partial; not re-run all routes |
| SEC-01 | Cross-user isolation | APIs | 403/404 | — | **PASS (A)** | Multiple tests in bundles A/B |

Detailed row-level inventory for all 58-field profile audit remains in `docs/OURNEAR_PROFILE_FIELD_AUDIT_EXECUTION_20261012T110000Z.md` (**PASS_WITH_COVERAGE_GAPS**).

---

## Browser evidence (this session)

| Viewport | URL | Action | Outcome |
| --- | --- | --- | --- |
| Desktop (default) | `/account` | Load dashboard as QA candidate | Single heading, metrics, recent apps, saved jobs empty state, recommended jobs |
| Desktop | `/account` | Save recommended job (Employer B) | Saved jobs metric **1**, preview row, “Saved job” on card |
| Desktop | `/account` | Hard reload | Saved count **1** persisted |
| Desktop | `/account/profile#preferences` | Direct hash | Dedicated Job Preferences UI (prior verified post-build) |
| Desktop | `/account/profile#languages` | Direct hash | Dedicated Languages UI |
| Desktop | `/account` | Click Notifications bell | Dropdown with recent items; URL stays `/account` |

Screenshots: Playwright artifacts under `qa-agent/artifacts/` for failed runs earlier today; successful smoke has no retained screenshot unless trace enabled.

---

## Automation follow-up (not executed)

Recommended Playwright journeys (§Phase 11B) — **NOT RUN** except login smoke:

1. Dashboard → profile → return  
2. Photo upload → reload  
3. … (full list in product plan)

Update remaining stage tests to use `forms.click_login_submit()` instead of `click_submit("Log in")` (partial: smoke, account_auth, candidate_apply, profile audit).

---

## Files changed (QA harness only)

- `qa-agent/ournear_qa/browser/forms.py`
- `qa-agent/tests/smoke/test_account_login.py`
- `qa-agent/ournear_qa/journey/account_auth.py`
- `qa-agent/ournear_qa/journey/candidate_apply.py`
- `qa-agent/tests/profile_audit/test_profile_field_audit.py`

Application PHP/React source unchanged in this QA session (rectification batches 1–4 already landed).

---

*Append-only; do not replace historical QA logs in `storage/logs/` or prior `docs/OURNEAR_QA_*` reports.*
