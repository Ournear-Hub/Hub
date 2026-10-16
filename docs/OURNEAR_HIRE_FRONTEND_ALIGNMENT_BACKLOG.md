# OurNear Hire — Frontend Alignment Backlog

**Date:** 2026-10-10  
**Companion:** [OURNEAR_HIRE_FRONTEND_ARCHITECTURE_AUDIT.md](./OURNEAR_HIRE_FRONTEND_ARCHITECTURE_AUDIT.md)  
**Register:** [OURNEAR_ORGANISM_TRACK_REGISTER.md](./OURNEAR_ORGANISM_TRACK_REGISTER.md)

Status key: **Authorized** = explicitly allowed by register/gates; **Optional** = product-scoped adoption; **Deferred** = do not implement without new decision; **Governance** = docs/barrel only until PR authorized.

---

## Batch overview

| Batch | Theme | Items | Priority mix |
|-------|--------|-------|--------------|
| **B1** | Detail pattern adoption + QA | FE-101, FE-102, FE-103 | P2 |
| **B2** | Admin FilterBar duplicate resolution | FE-201, FE-202 | P1 (governance) |
| **B3** | Account/public canonical atom lane | FE-301, FE-302, FE-303 | P2–P3 |
| **B4** | UI Lab & test hygiene | FE-401, FE-402 | P2–P3 |

**Recommended first implementation batch:** **B1** — FE-101, FE-102, FE-103 **COMPLETE** (2026-10-10).

---

## B1 — Detail pattern and QA

### FE-101 — **COMPLETE** (2026-10-10)

| Field | Value |
|-------|--------|
| **Layer** | organism / page |
| **Priority** | P2 |
| **Authorization** | Optional (register §27, D-ORG-5) |
| **Affected** | `resources/js/Pages/Account/Employer/Applications/Show.tsx` |
| **Outcome** | **Done** — two **DetailHeader** blocks aligned with Submissions/Show: (1) eyebrow “Applicant for”, job title, job context line; (2) candidate h2, description, **StatusBadge** meta, profile action in `actions` slot; contact snapshot + **dl** preserved below header. |
| **Evidence** | `DetailHeader.test.tsx` 2/2; `npm run typecheck`; `npm run build`; `EmployerHiringStage25Test::test_employer_application_detail_uses_contact_snapshot_and_read_only_actions_copy` **passed**. |
| **Visual browser** | **Not completed** in this batch (see FE-103). |

### FE-102 — **COMPLETE** (2026-10-10)

| Field | Value |
|-------|--------|
| **Layer** | organism / page |
| **Priority** | P2 |
| **Authorization** | Optional (register §27, D-ORG-5) |
| **Affected** | `resources/js/Pages/Account/Applications/Show.tsx` (`account.applications.show` → `CandidateApplicationsController`) |
| **Outcome** | **Done** — two **DetailHeader** blocks: (1) application context — eyebrow (`business_id` or `#id`), title “Application”, status in `meta`, withdraw in `actions`; applied/last-updated **dl** below; (2) job — `headingLevel={2}`, eyebrow “Job”, title, company/location/type description, View job / closed copy in `actions`; job **dl** below. **AccountLayout** breadcrumbs unchanged. Inline **Resume used** section and **ApplicationTimeline** (no `hideWhenEmpty`) unchanged. |
| **Evidence** | `DetailHeader.test.tsx` 2/2; `npm run typecheck`; `npm run build`; `ApplicationExperienceTest` **12/12** (includes candidate list/detail, timeline, auth boundaries). |
| **Visual browser** | **Not completed** in this batch (see FE-103). |

### FE-103 — **COMPLETE** (2026-10-10)

| Field | Value |
|-------|--------|
| **Layer** | page / governance |
| **Priority** | P2 |
| **Authorization** | Authorized (QA) |
| **Browser routes verified** | Employer: `/account/jobs/{qa-journey-slug}/applications/{id}` (discovered) + `/account/submissions/1`; Candidate (focused): `/account/applications/{id}` mobile — see [OURNEAR_QA_FE103_DETAIL_HEADER_BROWSER_REPORT.md](./OURNEAR_QA_FE103_DETAIL_HEADER_BROWSER_REPORT.md) |
| **Viewports** | 1440×900, 768×1024, 390×844 (employer); 390×844 (candidate add-on) |
| **Prerequisites run** | `playwright install chromium`; `php artisan qa:hiring-fixtures`; `php artisan serve`; prior `npm run build` |
| **Authentication** | **PASS** — `ournear_qa.journey.account_auth.login_account_user` (POST login + `/account` dashboard) |
| **Journey / assertions** | **PASS** — `JRN-FE103-DETAIL-HEADER-01` via `pytest tests/journey/test_fe103_detail_header_browser.py` (2/2); DetailHeader headings, resume blocks, timelines, diagnostics (console/network), overflow checks |
| **HTTP / authenticated evidence (historical)** | **PASS** — `ProductionUxStage15Test`; `EmployerHiringStage25Test` application detail (unchanged) |
| **Evidence** | Screenshots under `qa-agent/artifacts/fe103/`; run record `20261010T080244Z-0a02b203` |
| **Remaining limitations** | Empty timeline (`hideWhenEmpty`) not reproduced; keyboard focus sampled once (desktop Tab); candidate browser check mobile-only |

---

## B2 — Admin FilterBar duplicate (governance)

### FE-201 — **BLOCKED** (2026-10-10 governance check)

| Field | Value |
|-------|--------|
| **Layer** | molecule / governance |
| **Priority** | P1 |
| **Authorization** | **Not established for implementation.** Backlog requires **PR-5M follow-up** before mass import swap. [OURNEAR_PR5M_PR5O_ADOPTION_READINESS_REVIEW.md](./OURNEAR_PR5M_PR5O_ADOPTION_READINESS_REVIEW.md) §1 states mode is **“planning and review only — not implementation authorization”** and readiness **does not** permit implementation or consumer migration without **explicit adoption authorization**. No separate PR-5M follow-up / adoption authorization record was found for FE-201 barrel unification. **This execution prompt does not substitute for that gate.** |
| **Inspection (no code change)** | **Canonical molecule:** `Components/molecules/FilterBar.tsx` (`filterBarRoot`). **Duplicate:** `Components/Admin/ui/FilterBar.tsx` (inline `mb-6 grid … rounded-xl border … md:grid-cols-4`). **Consumers:** Admin index pages already **`import FilterBar from '@/Components/molecules/FilterBar'`** (~28 sites per prior PR-5O evidence); **zero** TSX imports of `FilterBar` from `@/Components/Admin/ui` barrel (rg 2026-10-10). Barrel still **`export { default as FilterBar } from '@/Components/Admin/ui/FilterBar'`** — drift risk for future barrel-only imports. |
| **Approved scope when authorized** | **Option A (preferred):** Admin/ui re-export/wrap `molecules/FilterBar`. **Option B:** Barrel re-export molecule; remove divergent TSX body. **Not authorized now:** consumer migration (already on molecule), visual diff, or deleting Admin file without recorded approval. |
| **Dependencies (unchanged)** | Visual diff on 2–3 admin index pages; confirm no regression in grid layout. |
| **Acceptance (unchanged)** | One source file for FilterBar logic; ripgrep shows no divergent TSX body. |
| **Validation (pending authorization)** | `FilterBar.test.tsx` (FE-402) exists; browser Jobs/Applications index filters per original FE-201 entry. |
| **Action required to unblock** | Product/engineering **explicit adoption authorization** (PR-5M follow-up) naming Option A or B; then re-run FE-201 implementation + visual diff. |

### FE-202

| Field | Value |
|-------|--------|
| **Layer** | governance |
| **Priority** | P2 |
| **Authorization** | Governance |
| **Affected** | `docs/OURNEAR_PR5M_PR5O_ADOPTION_READINESS_REVIEW.md` (or addendum), UI Lab FilterBar section |
| **Current state** | Lab references molecule; Admin/ui path undocumented for new contributors. |
| **Change** | Document **which import path is canonical** post FE-201; update FilterBarLabSection copy if still citing Admin/ui as production default incorrectly. |
| **Rationale** | Prevent new duplicate imports. |
| **Dependencies** | FE-201 decision |
| **Acceptance** | Lab text matches single canonical path. |
| **Validation** | Doc review |

---

## B3 — Account/public atom lane (large surface)

### FE-301 — **COMPLETE** (2026-10-10)

| Field | Value |
|-------|--------|
| **Layer** | atom / page |
| **Priority** | P2 |
| **Authorization** | Optional product batch — **satisfied** (not organism track) |
| **Batch 1 (account)** | **26** files — `Pages/Account/**`, `Components/Account/**`, account Profile modules, `ResumeReadinessPanel` → `atoms/Button` + `surface="public"` |
| **Batch 2 (public/admin/lab)** | **18** files — `Components/Public/{JobsSearchPanel,CtaSection,ApplicationConfirmation}`, marketing/platform pages (`Home`, `Jobs/*`, `Platform/*`, `Companies/*`, `Contact`, `ForEmployers`, `Freelancers`, `Profiles/Show`), `Pages/Admin/{Dashboard,Hiring/CommandCenter}` → `atoms/Button` with `surface="public"` or **`surface="admin"`** on admin pages (corrects prior alias forcing public teal/orange on admin chrome); `ui-lab/sections/ApplicantSummaryCardLabSection.tsx`; `ui-lab/catalog.ts` button compatibility copy updated |
| **Import reconciliation** | Pre–Batch 2: **18** `Public/Button` import sites in `resources/js` (17 TSX + catalog string); post-migration: **0** TSX imports — only legacy `Components/Public/Button.tsx` alias file + catalog documentation reference |
| **Register** | `Pages/Register` already used canonical atoms (no Batch change) |
| **Evidence** | `npm run typecheck` **pass**; `npm run build` **pass**; `ProductionUxStage15Test` + `ApplicationExperienceTest` **17/17 pass** (Batch 2 rerun) |
| **Visual browser** | Homepage **`test_public_home.py` resolved** — **LR-HOME**; Register + Account/Home — **LR-REG-ACCOUNT** (2026-10-10) |
| **Residual** | `Components/Public/Button.tsx` retained as V2 re-export for external/rollback; admin pages otherwise use `Admin/ui/Button` — out of FE-301 scope |

### FE-302 — **COMPLETE** (2026-10-10)

| Field | Value |
|-------|--------|
| **Layer** | atom / page |
| **Priority** | P3 |
| **Authorization** | Optional — **satisfied** (FE-301 complete) |
| **Canonical** | `Components/atoms/Card.tsx` — `padding` maps to `cardDensity` recipes; additive **`as`** prop (`div` \| `section` \| `article`) for parity with legacy UI/Card semantics |
| **Scope migrated** | **8** account consumers (audit ~6 pages + hiring components): `Pages/Account/{Home,Enquiries,Enquiries/Show,Employer/Submissions/Index,Employer/Submissions/Show,Employer/Applications/Show}`; `Components/Account/Hiring/{EmployerHiringCommandCenter,HiringEntryOnboarding}` |
| **Change** | `import` → `@/Components/atoms/Card`; `density=` → `padding=`; preserved `as`, `className`, children |
| **Acceptance** | **Zero** `UI/Card` imports under `Pages/Account/**` and `Components/Account/**` (verified ripgrep) |
| **Evidence** | `npm run typecheck` **pass**; `npm run build` **pass**; `EmployerSubmissionTest` + `ProductionUxStage15Test` **11/11 pass** (submissions list/detail + ownership) |
| **Visual browser** | **Not run** — Submissions list/detail browser spot-check deferred (homepage `/` unblocked per LR-HOME) |
| **Out of scope (2026-10-10)** | Public/marketing pages still on `UI/Card` (e.g. `Pages/Home.tsx`); `Components/UI/Card.tsx` retained as V2 adapter |
| **FE-302 residual (2026-10-12)** | **COMPLETE (code)** — public marketing bespoke shells → `atoms/Card`: components `ServiceCard`, `IndustryCard`, `ContactCard`, `PublicModuleCard`, `PublicCompanyCard`, `PublicProductProofGrid`; pages `About`, `ForEmployers`, `Contact`; prior `Jobs/Apply`, `Platform/TalentPool`. **Retained:** `JobCard` (interactive card); `Home` hero pipeline panel; `Admin/Hiring/CommandCenter` (`UiCard`); `Components/UI/Card.tsx` adapter. tsc/build/vitest **NOT RUN — deferred by product owner**. |

### FE-303 — **COMPLETE** (2026-10-10)

| Field | Value |
|-------|--------|
| **Layer** | atom |
| **Priority** | P3 |
| **Authorization** | Optional — **satisfied** |
| **Outcome** | Account surface uses **one stack**: `@/Components/Account/StatusBadge` (domain `{ status }` API) → `@/Components/atoms/StatusBadge` → `atoms/Badge`. Domain map extracted to `Components/Account/accountStatusPresentation.ts` (unchanged presentation classes + `Verified`, `Unread`, `Action required`). |
| **Atoms change** | `atoms/StatusBadge` accepts optional **`toneClassName`** for adapter maps (admin/account remain separate maps per PR3A). |
| **Consumers** | **No import changes** — 10 account paths already imported `Account/StatusBadge` (submissions list/detail, applications detail, enquiries, notifications, settings, staffing/recruiter cards, `ApplicantSummaryCard`, `Profiles/Show` hiring context). |
| **Acceptance** | **Met** — single account implementation path; no account page imports `atoms/StatusBadge` or `UI/StatusBadge` directly. |
| **Evidence** | `npm run typecheck` **pass**; `ProductionUxStage15Test` + `EmployerHiringStage25Test` **13/13 pass** (applicants, submissions, application detail). |
| **Visual browser** | **Not run** (applicant/submission badge spot-check deferred). |
| **Out of scope** | `Admin/ui/StatusBadge` unchanged (admin lane); `UI/StatusBadge` re-export unchanged. |

---

## B4 — UI Lab and tests

### FE-401 — **COMPLETE** (2026-10-10)

| Field | Value |
|-------|--------|
| **Layer** | UI Lab |
| **Priority** | P2 |
| **Authorization** | Authorized (documentation) — **satisfied** |
| **Outcome** | **Production import reality** table + explicit **dual-lane** callout on Lab Overview; `productionImportLanes` in `metadata.ts`. FilterBar lab copy corrected (canonical **molecules/FilterBar** vs **Admin/ui** duplicate until FE-201). Catalog compatibility refreshed for Button (prior FE-301), Card, Input, Textarea, Select, IconButton, FilterBar. Audit §10 addendum pointer. |
| **Files changed** | `resources/js/ui-lab/metadata.ts`, `sections/OverviewSection.tsx`, `sections/FilterBarLabSection.tsx`, `catalog.ts`; `docs/OURNEAR_HIRE_FRONTEND_ARCHITECTURE_AUDIT.md`; this backlog |
| **Import guidance corrected** | Canonical: `@/Components/atoms|molecules|organisms` + `surface`/`padding`. Legacy: Public/UI/Account adapters (Button alias, UI/Card on marketing, Account/StatusBadge stack). Admin/ui barrel adapters + FilterBar duplicate called out — not canonical for new FilterBar work. |
| **Evidence** | `npm run typecheck` **pass**; `npm run build` **pass** (see run in FE-401 completion). |
| **Visual browser** | **Not run** — `/admin/design-system` overview table not spot-checked in browser this batch (local server not verified). |
| **Remaining gaps** | No dedicated Lab sections/catalog entries for Badge/StatusBadge atoms; public **UI/Card** lane still undocumented per-component beyond Card compatibility; FE-202 post–FE-201 FilterBar canonical path doc. |

### FE-402 — **COMPLETE** (2026-10-10)

| Field | Value |
|-------|--------|
| **Layer** | governance |
| **Priority** | P3 |
| **Authorization** | Standard engineering — **satisfied** |
| **Outcome** | Added **3** co-located Vitest suites (12 tests total): `FilterBar.test.tsx` (3), `DataTable.test.tsx` (4), `FormField.test.tsx` (5). No prior tests existed for these molecules. |
| **Behaviors covered** | **FilterBar:** child controls + labels, `onSubmit`, `className` merge. **DataTable:** table headers/cells, empty EmptyState copy, `onRowClick`, `selectedKey` smoke. **FormField:** label/`id`, hint `aria-describedby`, required label, error `role="alert"` + `aria-invalid`, error-over-hint. |
| **Evidence** | Focused: 3 files **12/12 pass**; regression: `resources/js/Components` **11 files, 34/34 pass**; `npm run typecheck` **pass**; `npm run build` **pass** (FE-402 completion). |
| **Out of scope** | No sorting/pagination/loading on DataTable (not in API); FilterBar has no built-in reset callback (native child controls only). |
| **Remaining gaps** | ~25 other molecules still without Vitest; Admin FilterBar duplicate untested (FE-201). |

---

## B5 — Hygiene / investigation

### FE-501 — **COMPLETE** (2026-10-10)

| Field | Value |
|-------|--------|
| **Layer** | page / governance |
| **Priority** | P3 |
| **Authorization** | Optional cleanup — **satisfied** |
| **Finding** | **Inertia page orphaned; POST switch route still live.** No `Inertia::render('Account/Switch')` in `app/` (rg exit 1). No GET route to the page. Only frontend consumer of the page was the deleted file itself (`post('/account/switch')`). |
| **Live backend contract (retained)** | `POST /account/switch` → `AuthenticatedSessionController::switch` (`routes/account.php`, `account.switch`) — validates `destination` ∈ {login, register}, requires `auth:account` + `User::isAdmin()`, redirects to named login/register routes (no Inertia). Covered by `AccountSwitchingTest::test_only_an_authenticated_account_admin_can_hit_the_legacy_switch_route`. |
| **Current product flow (documented)** | Admin ↔ account separation uses **dual guards**: admin can `GET` `login` / `register` while `admin` session persists (`AccountSwitchingTest` — no Switch page). QA registry `ACC-AUTH-05` tracks POST `/account/switch` only. |
| **Disposition** | **Removed** unreachable page `resources/js/Pages/Account/Switch.tsx`. Did **not** remove POST route, controller, or tests — not authorized as dead code. Did **not** wire a new GET route (would be new product surface). |
| **References checked** | `routes/account.php`, `AuthenticatedSessionController.php`, `tests/Feature/AccountSwitchingTest.php`, `qa-agent/registry/features.yaml`, `resources/js` (no other `Account/Switch` imports), docs audit §9 / FE-CAND-2. Distinct from `Components/atoms/Switch.tsx` (PR-3H toggle). |
| **Evidence** | `rg "Inertia::render.*Switch" --glob "*.php"` **no matches**; `php artisan test tests/Feature/AccountSwitchingTest.php` **6/6 pass**; `npm run typecheck` **pass**. |
| **Uncertainty** | Whether product ever wants a confirmation UI before legacy POST — no controller render existed; reintroducing would need explicit product decision + new backlog item. |

---

## B5 — Launch readiness

### LR-HOME — **COMPLETE** (2026-10-10)

| Field | Value |
|-------|--------|
| **Issue** | Public `/` returned **HTTP 500** during FE-301 Batch 2 browser QA (`qa-agent/tests/smoke/test_public_home.py`). |
| **Root cause** | **Environment / build artifact** — `Illuminate\Foundation\ViteManifestNotFoundException`: missing `public/build/manifest.json` when rendering `resources/views/app.blade.php` (Laravel log entries 2026-10-10 06:20, 08:37, 09:51). Not traced to `Home.tsx`, Button migration, or `PublicSiteController` data. PHPUnit uses `withoutVite()` and did not catch this. |
| **Repair** | **No homepage code change.** Restored/retained production Vite build (`npm run build`). Added **`PublicHomePageTest`** (Inertia `Home` + props smoke). Extended **`scripts/verify-local-env.php`** with **`VITE_MANIFEST`** check for pre-browser QA. |
| **Before** | `GET /` → **500** when manifest absent; QA artifact `20261010T083659Z-a8b50faa` failure. |
| **After** | `curl.exe /` → **200**; `python -m pytest qa-agent/tests/smoke/test_public_home.py` → **1/1 pass** (22.8s); `PublicHomePageTest` → **1/1 pass**; `verify-local-env.php` all **OK** including manifest. DB confirmed **`ournear_hire_local`**. |
| **Prerequisite** | Run **`npm run build`** (or keep `npm run dev` + Vite dev server) before Playwright smoke against `php artisan serve` without hot reload. |
| **Remaining** | ~~Register/account-home browser spot-checks~~ **done** — see **LR-REG-ACCOUNT** below. |

### PHASE-D-BRANDING — **COMPLETE** (2026-10-10)

| Field | Value |
|-------|--------|
| **Scope** | Admin-managed website logo mark + companion wordmark PNG; shared `BrandMark` across public, account, admin. |
| **Root cause** | `wordmark_path` existed in schema/model but was missing from admin upload UI and `updateBranding()`. |
| **Evidence** | [OURNEAR_QA_PHASE_D_WEBSITE_BRANDING_REPORT.md](./OURNEAR_QA_PHASE_D_WEBSITE_BRANDING_REPORT.md) |
| **Tests** | `WebsiteBrandingSettingsTest` **4/4**; `npm run typecheck`; `npm run build` |
| **Owner assets** | Production logo/wordmark PNGs must be uploaded via **Admin → Settings → Branding** (suggested wordmark ~320×80). |

### LR-REG-ACCOUNT — **COMPLETE** (2026-10-10)

| Field | Value |
|-------|--------|
| **Scope** | Browser spot-check **Register** (`/register`) and **Account/Home** (`/account`) — LR-HOME follow-on. |
| **Journey** | `JRN-LR-REG-ACCOUNT-HOME-01` — `qa-agent/ournear_qa/journey/lr_register_account_home.py`, `tests/journey/test_lr_register_account_home_browser.py` |
| **Evidence** | [OURNEAR_QA_LR_REGISTER_ACCOUNT_HOME_BROWSER_REPORT.md](./OURNEAR_QA_LR_REGISTER_ACCOUNT_HOME_BROWSER_REPORT.md); screenshots under `qa-agent/artifacts/lr-register-account/20261010T105855Z-e1a75d7d/` |
| **Results** | Register **PASS** (desktop + mobile); Account/Home **PASS** (QA candidate login, desktop + mobile); `verify-local-env.php` **OK**; pytest **2/2 pass** |
| **Not tested** | Register form submit; employer account home variant |
| **Governance** | FE-201 **BLOCKED**; FE-202 blocked — unchanged |

---

## Explicitly excluded (deferred / rejected)

| ID | Item | Reason |
|----|------|--------|
| **X-01** | FilterWorkspace organism | D-ORG-8 rejected |
| **X-02** | Header/Footer/Sidebar/TopBar/AccountNav DS exports | D-ORG-6 Phase 5 shell |
| **X-03** | P5-14, P5-15, P5-16 | Phase 5 deferred |
| **X-04** | OrganizationSwitcher | P2 deferred |
| **X-05** | Lift RecruitmentChain / ProfileCompletionCenter | PRESERVE IN FEATURE |
| **X-06** | PR-5M/O full-repo migration | Separate gate — not auto-opened |
| **X-07** | Storybook / Phase 9 | Deferred |
| **X-08** | Flutter / mobile | Paused |

---

## First implementation batch (status)

**B1 closed:** FE-101, FE-102, FE-103 **COMPLETE** (2026-10-10).

### FE-504 — **PARTIAL** (2026-10-12)

| Field | Value |
|-------|--------|
| **Layer** | molecule / cross-surface navigation |
| **Priority** | P2 |
| **Outcome** | `BackNavLink` + `BackNavButton`; adopted on account, employer, public jobs, auth, profile modules, hiring profile context, admin detail/auth. Report: `docs/OURNEAR_UI_ARCHITECTURE_ADOPTION_WAVE2_REPORT.md`. |
| **Tests** | `BackNavLink.test.tsx` — **NOT RUN — deferred by product owner** |
| **Remaining** | AccountNav icons, public UI/Card lane, auth back links, full-page density sweep |

---

**Next authorized batch:** **FE-201 blocked** until PR-5M follow-up authorization is recorded; optional public **UI/Card** follow-on (not itemized). **FE-202** remains blocked on FE-201. Launch QA: employer Account/Home variant or Register submit path — optional, not backlog-itemized.

---

*End of backlog.*
