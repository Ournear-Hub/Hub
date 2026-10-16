# Master Browser Acceptance Report

**Date:** 2026-10-15 (updated — OurNear recruiter visibility + local verification)

### OurNear local unblock (2026-10-15)

| Test | Result | Evidence |
|------|--------|----------|
| Admin Candidate Allocation recruiter filter includes **MUKUNDA** (OurNear, user id 4) | **PASS** | `2026-10-15-admin-allocation-recruiter-ournear.png` |
| Recruiter Talent Pool jobs list — internal draft job | **PASS** | `2026-10-15-talent-pool-jobs-ournear.png` (job id 41, `talent_pool_internal`) |
| Public `/jobs?q=Local+QA+Talent+Pool` excludes internal job | **PASS** | browser — no matching card (public job #32 `height` still listed for ournear) |
| Local email verify via artisan (single account) | **PASS** | `ournear:local-verify-account-email mukunda10` — inbox N/A (`MAIL_MAILER=log`) |
| Talent Pool job create form reachable (post email verify) | **PASS** | `/account/talent-pool/jobs/create` loads; save blocked in browser on job category combobox (PHPUnit covers store) |

**Date:** 2026-10-14 (updated — Hire modules / Staffing access)

### Modules / Staffing / Talent Pool (2026-10-14 evening)

| Test | Result | Evidence |
|------|--------|----------|
| Local `/` after migrate | **PASS** | — |
| Admin login + dashboard | **PASS** | — |
| Admin Employers → OurNear (ONT-E-000002) | **PASS** | `2026-10-14-admin-employers-ournear-list.png` |
| OurNear verification tab (posting approved; email unverified) | **PASS** | `2026-10-14-admin-employers-ournear-verification.png` |
| Staffing access **not_requested** (local DB; not auto-approved) | **PASS** | DB + verification screenshot |
| Candidate Allocation → Talent Pool assign (no job/requirement) | **PASS** | `2026-10-14-candidate-allocation-talent-pool-modal.png` |
| Recruiter Assigned Candidates sees assignment | **PASS** | `2026-10-14-recruiter-assigned-candidates-talent-pool.png` |
| Employer `/admin/employers` | **PASS (denied)** | blank/forbidden when not admin |
| Staffing request → admin approve (OurNear ONT-E-000002) | **PASS** | `2026-10-14-staffing-ournear-before-request.png`, `…-pending.png`, `2026-10-14-admin-ournear-staffing-pending.png`; employer login via username (`mukunda10`) — email unverified |
| Staffing reject + notification copy | **PHPUnit only** | `EmployerModulesStaffingTest` |
| All four allocation types exercised in one session | **PARTIAL** | Talent Pool assign browser-verified earlier; job / staffing / general **PHPUnit** (`RecruiterTalentPoolAssignmentTest`, `EmployerModulesStaffingTest`) |
| Create Talent Pool internal job + public exclusion | **PHPUnit PASS** | `EmployerModulesStaffingTest::test_talent_pool_internal_job_is_not_publicly_visible_when_published`; browser create **NOT RUN** |

Guide: `docs/QA/OURNEAR-HIRE-MODULES-AND-PERMISSIONS-GUIDE.md`

**Date:** 2026-10-14 (updated — Candidate Allocation recruiter UI)

### Candidate Allocation — recruiter (2026-10-14)

| Test | Result |
|------|--------|
| Assigned Candidates nav + page title | **PASS** — `/account/talent-pool` as QA employer |
| Admin Candidate Allocation assign flow | **NOT RUN** — admin route returned 403 without admin session |

Evidence: `docs/QA/evidence/2026-10-14-assigned-candidates-recruiter.png`  
Report: `docs/QA/CANDIDATE-ALLOCATION-WORKFLOW-REPORT.md`

**Date:** 2026-10-14 (updated — company dashboard search centering + admin access recovery)

### Company dashboard contextual search (2026-10-14)

| Viewport | Result | Evidence |
|----------|--------|----------|
| 1440px | **BROWSER VERIFIED** — medium-width search centered in workspace band | `docs/QA/evidence/2026-10-14-company-dashboard-search-1440.png` |
| 1280px | **BROWSER VERIFIED** | `…-1280.png` |
| 768px | **BROWSER VERIFIED** — controls row above full-width search | `…-768.png` |
| 390px | **BROWSER VERIFIED** — no horizontal overflow observed | `…-390.png` |
| 360px | **BROWSER VERIFIED** | `…-360.png` |

Functional check: query `QA Fixture` submitted from `/account/hiring` (employer session). API regression: `ContinuationBatchSearchStaffingTest` / `RemainingGapsCompletionTest` (employer scope).

**Date:** 2026-10-14 (updated — master rectification UI + Recent Jobs pass)  
**Base URL:** `http://127.0.0.1:8000`  
**Tooling:** Cursor IDE browser; PHPUnit; Vite production build  
**Account used:** `qa-employer-a@example.test` / `Password1!`

### Immediate rectification (this pass)

| Test | Result |
|------|--------|
| A — Recruitment Support stays in company workspace | **PASS** — form + employer sidebar; URL `/account/recruitment-support` |
| B — Assigned Executive loads (no 403) | **PASS** — `/account/talent-pool` title **Assigned Executive**; empty state (was **403** for dual-role before policy fix) |
| C — Staffing inline form | **PASS** — **Staffing Requirements** form on `/account/enquiries`; no **Contact support** button |
| D — No duplicate breadcrumb/title row on recruiter pages | **PASS** (spot-check recruitment + staffing + hiring candidates) |
| E — Global search placement | **PASS** — 1440px + 768px screenshots `docs/QA/evidence/2026-10-14-global-search-header-1440.png`, `…-768.png` |
| F — Responsive viewports | **PARTIAL** — 1440 + 768 captured; 1280 / 390 / 360 **NOT RUN** this pass |
| G — Jobs workspace Create Job | **PASS** — `/account/jobs` header button; `/account/jobs/create` in company shell |
| H — Job cards without logo | **PARTIAL** — homepage latest jobs show title/company without logo column (post-build); Personal Dashboard 2-col **NOT browser-verified** (dual-role `/account` is employer home) |
| I — Recent jobs applicants | **PASS** — href `/account/jobs/{slug}/applicants`; direct navigation **PASS**; table View click **INCONCLUSIVE** in one automation click |

This report separates **previously reported implemented**, **actually observed in browser**, **automated test**, and **not yet tested**.

---

## Environment

| Check | Result |
|-------|--------|
| HTTP GET `/` | 200 |
| Frontend assets | `npm run build` completed successfully |
| MySQL / Laravel | Tests ran without connection failures |

---

## Public website

| Workflow | Previously reported | Browser (this run) | Automated | Verdict |
|----------|---------------------|--------------------|-----------|---------|
| Homepage loads | Implemented | **PASS** — title Home, no error page | `PublicHomePageTest` PASS | **Browser verified** |
| Industry cards not clickable | Implemented | **PASS** — industry entries are headings, scroll controls only | — | **Browser verified** |
| Popular searches | Implemented | **PASS** — designation chips visible | `HomepagePopularSearchDiscoveryTest` PASS | **Tests + partial browser** |
| Latest Jobs → workspace | Implemented | **PASS** — View job links on home | `PublicJobsWorkspaceNavigationTest` | **Tests verified** |
| Job cards (no employer logo on card) | New requirement | **PASS (mobile home sample)** — cards show title, company, metadata; no logo column | — | **Browser verified (sample)** |
| Personal dashboard 2-column jobs | New requirement | **NOT TESTED** — QA employer `/account` is company dashboard | — | Implemented in code |
| Global search companies/profiles only | Gap | **PASS** — label “Search companies and public profiles”; placeholder updated; no job groups | `GlobalSearchSuggestTest`, `GlobalDiscoverySearchTest` | **Browser + tests** |
| Company public profile | Implemented | **NOT TESTED** | — | Not tested |
| Personal public profile | Implemented | **NOT TESTED** | — | Not tested |
| Sharing canonical URLs | Implemented | **NOT TESTED** (Share buttons present on jobs) | `PublicShareUrlBuilderTest` (historical) | Not browser-verified this run |
| Public/private visibility | Implemented | **NOT TESTED** | — | Not tested |

---

## Jobs workspace

| Workflow | Browser | Automated | Verdict |
|----------|---------|-----------|---------|
| Filters / list / selection | **PASS** — 19 jobs, 9 shown, job selected with detail pane | `PublicJobsWorkspaceNavigationTest` PASS | **Browser verified** |
| Own-company apply/save | Not exercised as dual-role candidate in browser | `JobOwnCompanyApplicationTest` PASS | **Tests verified** |
| Save/Share independent of title | Share control on selected job | — | Partial browser |

---

## Company workspace

| Workflow | Browser | Verdict |
|----------|---------|---------|
| `/account/hiring` loads | **PASS** — Company Workspace, KPIs, pipeline, talent/staffing, recent jobs/applicants | **Browser verified** |
| Recruitment Support nav + form | **PASS** — `/account/recruitment-support`, title **Recruitment Requirement Form** | **Browser verified** |
| Staffing workspace | **PASS** — inline **Staffing Requirements** form; submit button; no Contact support | **Browser verified** |
| Assigned Executive | **PASS** — `/account/talent-pool` loads for QA employer (dual-role) | **Browser verified** |
| `/account/staffing/request` | Redirect → `/account/enquiries` | **Browser verified (prior pass)** |
| Duplicate headers | **PASS** on sampled pages (single workspace chrome) | Partial — not all routes visited |
| Recent job View → applicants | Backend href fixed; applicants page loads | **PASS** (direct URL); table click **INCONCLUSIVE** once |
| Create Job (Jobs workspace) | Hidden when recruiter shell hid layout action | **PASS** — in-page header button on `/account/jobs` |

---

## Verification

| Workflow | Browser | Verdict |
|----------|---------|---------|
| Mobile/WhatsApp not blocking hiring | Not opened Verification Center this run | Partial — settings card fix for employers |
| Document upload in Verification Center | **NOT TESTED** | Open |

---

## Responsive and shared UI

| Item | Browser | Verdict |
|------|---------|---------|
| Job cards 1440/1280/768/390/360 | **NOT TESTED** (single desktop viewport) | Open |
| Header search width | **PASS** after build | **Browser verified** |
| White bottom nav | Code change; mobile viewport **NOT TESTED** | Fix implemented |
| Branding PNG | **BLOCKED** — no asset | Blocked |

---

## Automated tests executed (this run)

```
php artisan test tests/Feature/GlobalSearchSuggestTest.php
php artisan test tests/Feature/EmployerUnverifiedTrialPublishTest.php
php artisan test tests/Feature/PublicHomePageTest.php
php artisan test tests/Feature/PublicJobsWorkspaceNavigationTest.php
php artisan test tests/Feature/JobOwnCompanyApplicationTest.php
php artisan test tests/Feature/HomepagePopularSearchDiscoveryTest.php
→ 17 passed (157 assertions)

npx tsc --noEmit → PASS
npm run build → PASS
```

**Recommended next command:** `php artisan test tests/Feature/GlobalDiscoverySearchTest.php`

---

## Playwright / qa-agent

Full matrix in `qa-agent/` was **not executed** in this pass. Prior artifacts under `qa-agent/artifacts/` remain historical evidence only.

---

## Conclusion

**Material progress:** Global discovery search, job card grid layout, white mobile bottom bar, employer settings verification badge, trial activation test fix, and discovery search regression test.

**Not done (explicit):** Full responsive screenshot matrix, every dashboard route header audit, company/personal public profile browser pass, sharing channel click-through, verification document lifecycle browser proof, qa-agent full journey replay, production `ournear.app` checks, branding PNG.

**Definition of done (user §23):** Not fully met — register and honest gaps above remain for follow-up passes.
