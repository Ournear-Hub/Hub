# Agent 1 — Release defect fix batch

**Date:** 2026-10-11  
**Workspace:** `D:\Ournear Hub\Ournear Hire`  
**Role:** Agent 1 (implementation)  
**Release verdict:** unchanged — **`insufficient_evidence`** (Agent 2 browser verification required)

---

## 1. Defects open at start

| ID | Summary | Prior QA |
| --- | --- | --- |
| E-REG-MAN-PUB-02 / REG-PROD-008 | Public `/jobs` keyword + company UI search → “No matching roles” | Agent 2 Phase E `20261011T062000Z-phase-e-qa` **FAIL** |
| REG-BRW-004 (C2) | Screening compare panel missing selected candidate name | Agent 2 `20261011T011700Z-targeted-exec` **FAIL** |
| C3-DEF-001 | Employer company logo not visible after Save/reload | Agent 2 C3 `20261011T014500Z-c3-photo-qa` **FAIL** |
| C3-DEF-002 | Invalid PDF upload leaves **Saving…** stuck | Same C3 run **FAIL** |
| REG-PROD-009 (E1) | No new E1 defect reported | **NOT TESTED** — implementation preserved |

---

## 2. Root cause and fix

### MAN-PUB-02 — public company + keyword filter (UI)

**Reproduced (server):** With `qa:hiring-fixtures`, `JobListingQuery` returns ≥10 jobs for `keyword=QA Fixture Published Role` + `company=QA Hiring Co A` (backend already correct).

**Root cause (UI):** Inertia `router.get` on **Search** used `preserveState: true` (default) and omitted `featured` when the checkbox was unchecked. A prior `featured=1` query string could stay active and zero out results after a second Search. Passing `job: selected?.slug` on filter searches could also desync list vs detail state.

**Fix:** `resources/js/Pages/Jobs/Index.tsx` — on Search, send `featured: 0` when unchecked, drop `job` from filter navigation, set `preserveState: false` so listing props match the new query.

**Regression:** `QaDefectRegressionTest::test_qa_fixture_keyword_and_company_filters_combine_on_public_jobs` (fixture-backed, includes `featured=0`).

### REG-BRW-004 — screening compare candidate names

**Root cause:** Compare selection stored full row objects in React state; after Inertia partial updates, stale row references could lack the same `full_name` / `candidate.name` fields shown on table checkboxes (`data-compare-candidate-name`).

**Fix:** `ApplicationWorkspacePage.tsx` — resolve compared rows from current `applications.data` by id, falling back to stored row for off-page selections.

**Data alignment:** `scripts/qa-provision-screening-compare.php` applicant labels → `QA Screening Applicant {n}` (matches C2 journey expectations).

**Regression:** Existing `ScreeningComparePanelTest` (Inertia payload includes resolved names).

### C3-DEF-001 / C3-DEF-002 — employer logo upload form

**Root cause:** `useForm` `transform()` spread the entire form (including `benefits_text`, null `photo`) into multipart payloads; combined with a persistent transform, browser submits could omit the `File` or leave `processing` true after validation errors.

**Fix:** `resources/js/Pages/Account/Profile.tsx` — build transform payload explicitly (strip `benefits_text`, attach `photo` only when `instanceof File`), reset transform on `onSuccess` / `onError` / `onFinish`, clear local `photo` after success.

**Regression:** Existing `EmployerProfilePhotoTest` (upload, replace, invalid PDF session errors, remove).

---

## 3. Files changed

| File | Change |
| --- | --- |
| `resources/js/Pages/Jobs/Index.tsx` | Search query / Inertia options |
| `resources/js/Components/Admin/ApplicationWorkspacePage.tsx` | Compare row resolution |
| `resources/js/Pages/Account/Profile.tsx` | Employer/candidate profile submit + transform |
| `scripts/qa-provision-screening-compare.php` | Applicant display names |
| `tests/Feature/QaDefectRegressionTest.php` | QA fixture keyword+company test |
| `docs/OURNEAR_RELEASE_ISSUE_REGISTER.md` | Builder/QA notes |
| `docs/OURNEAR_AGENT1_RELEASE_DEFECT_FIX_REPORT.md` | This report |

Phase E1 code untouched.

---

## 4. Verification (this batch)

| Command | Result |
| --- | ---: |
| `php artisan test --filter=QaDefectRegressionTest` | **PASS** (10 tests) |
| `php artisan test --filter=ScreeningComparePanelTest` | **PASS** (1 test) |
| `php artisan test --filter=EmployerProfilePhotoTest` | **PASS** (2 tests) |
| `php artisan test --filter=PhaseE1JobDetailsSavedJobsTest` | **PASS** (2 tests) |
| `npm run typecheck` | **PASS** |
| `npm run build` | **PASS** |

**NOT TESTED (Agent 2):** Playwright MAN-PUB-02, C2 journey, C3 browser scenarios, full PHPUnit suite, staging smoke.

---

## 5. Remaining gaps

- Browser confirmation for MAN-PUB-02 UI path, C2 compare panel, C3 logo save/reload and invalid PDF UX.
- REG-PROD-009 Phase E1 manual/browser QA still outstanding.
- Re-run `php scripts/qa-provision-screening-compare.php` before C2 journey if compare applicants were created with old names.

---

## 6. Issue register status

| ID | Builder status |
| --- | --- |
| REG-PROD-008 / MAN-PUB-02 | **IMPLEMENTED — AWAITING VERIFICATION** |
| REG-BRW-004 | **IMPLEMENTED — AWAITING VERIFICATION** |
| REG-PROD-006 (C3) | **IMPLEMENTED — AWAITING VERIFICATION** |
| REG-PHP-002 | **IMPLEMENTED — AWAITING VERIFICATION** (browser leg) |
| REG-PROD-009 | **FIXED — AWAITING QA** (unchanged) |

**Next step:** Agent 2 verification per register “Agent 2 next commands”.
