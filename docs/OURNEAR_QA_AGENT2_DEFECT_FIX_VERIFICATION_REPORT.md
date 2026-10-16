# Agent 2 — Defect fixes and verification (implementation + self-QA)

**Run ID:** `20261011T073000Z-defect-fix-verify`  
**Date:** 2026-10-11  
**Role:** Agent 2 (authorized implementation + verification — **not independent QA**)  
**Git HEAD:** `88ac8b0` (detached; worktree preserved)

Prior QA evidence preserved: `docs/OURNEAR_QA_AGENT2_PHASE_E_REPORT.md`, `docs/OURNEAR_QA_AGENT2_TARGETED_EXEC_REPORT.md`, `docs/OURNEAR_QA_AGENT2_PHASE_C3_PHOTO_REPORT.md`.

---

## 1. Root causes and fixes

### E-REG-MAN-PUB-02 / REG-PHP-002 (keyword + company on `/jobs`)

| | |
| --- | --- |
| **Root cause** | Inertia `router.get` used `preserveState: true` on Search, so the jobs list could stay stale after applying company + keyword filters (direct URL queries worked). |
| **Fix** | `Jobs/Index.tsx`: Search uses `preserveScroll` + `replace` without `preserveState`. `JobListingQuery`: company filter also matches employer-linked `project` label; keyword search includes employer company/name. |
| **Regression** | `QaDefectRegressionTest::test_public_jobs_keyword_and_company_filters_combine` |

### REG-BRW-004 / C2 (screening compare panel names)

| | |
| --- | --- |
| **Root cause** | Compare UI used raw `full_name` only; guest/profile-backed applications could have empty `full_name` while checkbox labels used the same field. Compare panel sat inside an `inert` wrapper when a drawer was open, hiding names from accessibility checks; horizontal scroll could hide column headers on narrow viewports. |
| **Fix** | `Application::candidateDisplayName()`; list payload uses resolved name. `ApplicationWorkspacePage`: `compareCandidateLabel()`, visible name line in panel header, compare panel outside drawer `inert` (inert only on table). |
| **Regression** | `ScreeningComparePanelTest` |

### C3-DEF-001 / C3-DEF-002 (employer logo)

| | |
| --- | --- |
| **Root cause** | Multipart profile update via Inertia `form.put` is unreliable for file uploads; validation errors did not always return the employer edit UI to a usable state. |
| **Fix** | `routes/account.php`: `Route::match(['put','post'], 'profile', …)`. `Profile.tsx`: `form.post('/account/profile', { forceFormData: true, onError: … })`. |
| **Regression** | `EmployerProfilePhotoTest` (POST upload/replace; POST invalid PDF → session errors) |

### Phase E gaps (automated coverage)

| Gap | Coverage |
| --- | --- |
| Pagination + company filter | Existing `test_public_jobs_company_filter_persists_across_pagination` |
| Clear filters | Existing company-filter test (clear restores 3 jobs) |
| Legacy vs FK parity | `PhaseEJobCardsClassificationTest` |
| Keyword + company + filters | New keyword+company test; industry/category in Phase E + `JobListingQuery` |

Browser matrix from `20261011T062000Z-phase-e-qa` **reused** (no rerun).

---

## 2. Files changed

| File | Change |
| --- | --- |
| `resources/js/Pages/Jobs/Index.tsx` | Search without `preserveState` |
| `app/Support/JobListingQuery.php` | Keyword + company filter parity |
| `app/Models/Application.php` | `candidateDisplayName()` |
| `resources/js/Components/Admin/ApplicationWorkspacePage.tsx` | Compare labels + layout |
| `resources/js/Pages/Account/Profile.tsx` | POST profile save + `onError` |
| `routes/account.php` | POST allowed for profile update |
| `tests/Feature/QaDefectRegressionTest.php` | Keyword + company test |
| `tests/Feature/ScreeningComparePanelTest.php` | **New** |
| `tests/Feature/EmployerProfilePhotoTest.php` | POST + invalid PDF on update |

---

## 3. Verification (this run)

**Label:** Same agent as implementation — verification recorded, not independent QA.

| Command | Result |
| --- | --- |
| `php artisan test` (4 focused files, 13 tests) | **PASS** — 333 assertions, ~113s |
| `npm run typecheck` | **PASS** — exit 0 |
| `npm run build` | **PASS** — ~77s |
| `pytest test_man_pub_02_jobs_search` | **PASS** (~120s total with C2, post `playwright install chromium`) |
| `pytest test_jrn_screening_comparison` | **PASS** |
| C3 browser re-run | **NOT TESTED** this run (PHPUnit covers HTTP path) |

**Development-time (earlier same batch):** `test_man_pub_02_jobs_search` **PASS** (~64s) before Playwright cache regression in this shell.

---

## 4. Browser evidence

| Item | Path / note |
| --- | --- |
| Phase E (prior) | `qa-agent/artifacts/agent2/20261011T062000Z-phase-e-qa/` |
| MAN-PUB-02 (dev verify) | pytest default evidence dir under `qa-agent/artifacts/` when run succeeds |
| C2 | Re-run **BLOCKED** until `python -m playwright install chromium` completes in the active environment and `:8000` is up |

---

## 5. Remaining risks

- **C2 mobile viewport:** Journey runs desktop + mobile; re-verify after Playwright restore.
- **C3 browser:** Confirm logo preview after reload on Employer A fixture once browser available.
- **Release gates:** Staging smoke, full manual matrix, user acceptance still open.

**Release verdict:** **`insufficient_evidence`** (unchanged).

---

*No commit, push, or deploy.*
