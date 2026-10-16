# OurNear Hire — Frontend Architecture, Design System & UI Alignment Audit

**Date:** 2026-10-10  
**Repository:** `D:\Ournear Hub\Ournear Hire`  
**Auditor mode:** Inspection, static analysis, targeted validation — **no production code changes** in this batch.  
**Authoritative registers:** [OURNEAR_ORGANISM_TRACK_REGISTER.md](./OURNEAR_ORGANISM_TRACK_REGISTER.md), [OURNEAR_CORPORATE_LIBRARY_SPECIFICATION_v1.0.md](./OURNEAR_CORPORATE_LIBRARY_SPECIFICATION_v1.0.md), [OURNEAR_UNIFIED_PLATFORM_ARCHITECTURE_v1.1.md](./OURNEAR_UNIFIED_PLATFORM_ARCHITECTURE_v1.1.md), [OURNEAR_PHASE_5_CLOSURE_GATE.md](./OURNEAR_PHASE_5_CLOSURE_GATE.md).

---

## 1. Executive summary

OurNear Hire implements a **frozen, Inertia-first corporate frontend** with a **canonical tier** (`resources/js/Components/atoms|molecules|organisms/`), a **UI Lab** at `/admin/design-system`, and **parallel legacy surface paths** (`Components/Public/*`, `Components/UI/*`, `Components/Admin/ui/*`, feature folders). Foundations (Tailwind CSS 4 `@theme`, `foundation/tokens.ts`, `design-system/classNames.ts`) are **WORKING** and match brand teal `#214449` / orange `#F15A24`.

**Strengths:** Eight canonical organisms are implemented, tested (focused Vitest), and registered; admin list indexes largely compose **FilterBar** and **DataTable** molecules; recent employer submission and application detail pages adopt **DetailHeader**, **ApplicationTimeline**, and **ApplicantResumeOverview**; Organism Track decisions D-ORG-1–8 are closed with explicit deferrals/rejections.

**Material gaps:** The website is **not fully aligned** on a single component import graph. Account/public pages predominantly use **legacy `Components/Public/Button`** and **`Components/UI/Card`** rather than canonical atoms. Admin **`Components/Admin/ui`** re-exports **duplicate** FilterBar/Timeline implementations that diverge from `Components/molecules/*`. Only **5** of **99** Inertia pages import canonical organisms; **1** page uses **DetailHeader** in production. **Browser/visual verification** was not performed in this audit (no local app session); page assessments below are **static** unless noted.

**Verdict:** Architecture is **PARTIALLY ALIGNED** — correct direction and frozen governance, with adoption and dual-stack consistency as the main remaining work. Completion is **not** claimed by this document.

---

## 2. Scope and evidence methodology

| Evidence type | What was done |
|---------------|----------------|
| **Documentation reconciliation** | Organism register §4.1–§4.2, §5, §23, §27; Phase 5 shell gate; corporate library spec references. |
| **File inventory** | `resources/js/Pages/**/*.tsx` (99 files, excl. tests); atoms (28 TSX), molecules (31 TSX), organisms (8 TSX); layouts; UI Lab `metadata.ts` + `catalog.ts`. |
| **Import / adoption mapping** | ripgrep counts: `@/Components/molecules/FilterBar` (29), `DataTable` (30), `@/Components/Admin/ui` on Pages (35), `organisms/` on Pages (5), `DetailHeader` on Pages (1), `Components/Public/Button` on Pages (26). |
| **TypeScript** | `npm run typecheck` — **exit 0** (2026-10-10). |
| **Vitest** | 8 `*.test.tsx` under `resources/js/Components/` (organisms + select atoms/molecules); full suite **not** rerun. |
| **Browser / visual** | **Not executed** — no running tab at `127.0.0.1:8000`; UI Lab and routes **UNVERIFIED** visually. |
| **Backend** | Route → page mapping via controllers (~96 `Inertia::render` calls); no business-logic audit. |

**Out of scope (explicit):** Recruitment workflow QA, PHPUnit full suite, Flutter/mobile, FilterWorkspace implementation, shell organism builds, PR-5M/O bulk migration authorization, Storybook/Phase 9.

---

## 3. Architecture alignment (frozen platform)

| Principle | Assessment | Evidence |
|-----------|------------|----------|
| Laravel 12 + Inertia page data | **WORKING** | Controllers render named `Pages/*` components. |
| React 19 + TS + Vite 7 | **WORKING** | `package.json`, successful typecheck. |
| Tailwind 4 token layer | **WORKING** | `resources/css/app.css` `@theme`; `foundation/tokens.ts`. |
| Corporate library tiers (atom → molecule → organism) | **PARTIALLY ALIGNED** | Tiers exist; production imports split across tiers and legacy paths. |
| UI Lab as design-system host | **WORKING** | `Admin/DesignSystem/Show.tsx`, 60+ active section IDs in `ui-lab/metadata.ts`. |
| Organism Track closed (D-ORG-1–8) | **WORKING** | Register §10, §25–§27; no new §11 organism queue. |
| Phase 5 shells (Header, Sidebar, etc.) | **DEFERRED** | D-ORG-6 Outcome S; layouts own chrome — **not defects**. |
| FilterWorkspace / list-stack organisms | **REJECTED** | D-ORG-8; List Pattern = FilterBar + page — **not defects**. |

---

## 4. Foundations and theme (Layer A)

**Sources:** `resources/css/app.css` (`@theme`), `resources/js/foundation/tokens.ts`, `resources/js/design-system/classNames.ts` (684+ lines of recipes).

| Area | Status | Notes |
|------|--------|-------|
| Brand colors | **WORKING** | `--color-brand: #214449`, `--color-accent: #f15a24`, semantic success/warning/danger. |
| Typography | **WORKING** | Instrument Sans; Heading/Text atoms + UI Lab foundation sections. |
| Spacing / radius / shadow | **WORKING** | Tokenized in `@theme`; card/control recipes in `classNames.ts`. |
| Surface rules (accent vs brand) | **WORKING** | Documented in `classNames.ts` (e.g. `controlBase` vs `adminControlBase`, Button `surface` prop). |
| Dark theme | **NOT APPLICABLE** | Light canvas default; no product requirement for dark mode in frozen spec. |
| Breakpoints | **WORKING** | Tailwind defaults + responsive classes on layouts/pages. |
| Focus rings | **WORKING** | Shared `focusRing` recipe. |
| Hard-coded hex outside tokens | **PARTIALLY ALIGNED** | Some legacy components/pages may still use ad hoc classes; not exhaustively enumerated. |

---

## 5. Atoms (Layer B)

**Canonical barrel:** `resources/js/Components/atoms/index.ts` — 28+ exported primitives (Button, Input, Badge, StatusBadge, Heading, Text, Stack, Cluster, Card, Link, etc.).

| Atom / family | Canonical path | UI Lab | Tests | Production adoption | Status |
|---------------|----------------|--------|-------|---------------------|--------|
| Button | `atoms/Button.tsx` | `atoms-button` | Via design-system tests | **Low on Pages** — 26 pages use `Components/Public/Button`; ~1 page uses atoms barrel | **PARTIALLY ALIGNED** |
| IconButton | `atoms/IconButton.tsx` | active | — | Admin/feature selective | **PARTIALLY ALIGNED** |
| Input / Textarea / Select | `atoms/*` | active | — | Admin via `Admin/ui` wrappers; account mixed | **PARTIALLY ALIGNED** |
| Badge / StatusBadge | `atoms/*` | active | — | Account `Components/Account/StatusBadge` + admin `Admin/ui/StatusBadge` parallel | **PARTIALLY ALIGNED** |
| Heading / Text / Label | `atoms/*` | active | — | Used inside organisms; pages often raw `<h1>`/`<p>` | **PARTIALLY ALIGNED** |
| Layout atoms (Stack, Cluster, Container, Card) | `atoms/*` | active | — | Pages often `Components/UI/Card` instead of `atoms/Card` | **PARTIALLY ALIGNED** |
| Icons | Tabler + registry | `icons` | — | Mixed direct imports | **WORKING** |

**Finding A-1:** Account and public surfaces maintain a **second component lane** (`Components/Public/*`, `Components/UI/*`) that predates the canonical atom tier. Behavior is broadly consistent with brand colors but **contracts and token recipes differ** from canonical Button/Card.

**Finding A-2:** Admin **`Admin/ui/*`** atoms duplicate molecules/atoms with **admin-specific class strings** (e.g. Input) — intentional corporate surface split, but increases governance burden.

---

## 6. Molecules (Layer C)

**Count:** 31 TSX files under `resources/js/Components/molecules/` (excl. tests).

| Molecule | Canonical | UI Lab | Adoption | Status |
|----------|-----------|--------|----------|--------|
| FilterBar | `molecules/FilterBar.tsx` (`filterBarRoot`) | active | **29** direct imports | **PARTIALLY ALIGNED** — see duplicate below |
| DataTable | `molecules/DataTable.tsx` | active | **30** direct imports | **WORKING** (admin list pattern) |
| Timeline | `molecules/Timeline.tsx` | active | Organisms + Admin drawer screening | **WORKING** |
| FormField family, Alert, EmptyState, Pagination, Tabs, Dialog, Drawer, etc. | `molecules/*` | Most active in Lab | Mixed via Admin/ui vs direct | **PARTIALLY ALIGNED** |

**Finding M-1 (material):** `Components/Admin/ui/FilterBar.tsx` is a **separate implementation** (inline `rounded-xl border … md:grid-cols-4`) and is still exported from `Admin/ui/index.ts`. Consumers importing FilterBar from `@/Components/Admin/ui` get the **legacy shell**, not `molecules/FilterBar`. PR-5M readiness cited molecule adoption counts; **barrel re-export drift** remains.

**Finding M-2:** `Admin/ui/Timeline` vs `molecules/Timeline` — ApplicationDrawer uses **both** (organism for tab; direct molecule for screening timeline). **Not a defect** — different meta shape; document as **composition choice**.

**Deferrals respected:** No recommendation to implement FilterPanel, list-stack organism, or PR-5M/O **bulk** migration in this audit.

---

## 7. Organism reconciliation (Layer D)

### 7.1 Eight canonical implementations (`Components/organisms/`)

| Organism | File | Tests | UI Lab | Production consumers (Pages + key features) | Implementation | Adoption |
|----------|------|-------|--------|---------------------------------------------|----------------|----------|
| DetailHeader | `DetailHeader.tsx` | 2 | yes | **Pages:** `Account/Employer/Submissions/Show.tsx` only | **COMPLETE** | **PARTIALLY ALIGNED** |
| DocumentGroupList | `DocumentGroupList.tsx` | 3 | yes | Account + Employer Documents Index | **COMPLETE** | **WORKING** |
| MatchComparisonTable | `MatchComparisonTable.tsx` | yes | yes | `Admin/ApplicationDrawer.tsx` | **COMPLETE** | **WORKING** |
| ScreeningStagesTable | `ScreeningStagesTable.tsx` | yes | yes | `Admin/ApplicationDrawer.tsx` | **COMPLETE** | **WORKING** |
| DetailSection | `DetailSection.tsx` | yes | yes | Submissions Show, CandidateProfileOverview, Design System | **COMPLETE** | **PARTIALLY ALIGNED** |
| ApplicantSummaryCard | `ApplicantSummaryCard.tsx` | yes | yes | RecruiterCandidateSummary feature | **COMPLETE** | **WORKING** |
| ApplicantResumeOverview | `ApplicantResumeOverview.tsx` | yes | yes | Employer Applications/Show, Submissions/Show | **COMPLETE** | **WORKING** |
| ApplicationTimeline | `ApplicationTimeline.tsx` | 5 | yes | Account Applications/Show, Employer Applications/Show, Submissions/Show, Admin ApplicationDrawer | **COMPLETE** | **WORKING** |

### 7.2 Spec §11 register rows (15) — disposition summary

| Disposition | Count | Names (register §4.1) |
|-------------|------:|------------------------|
| **COMPLETE** (canonical) | 3 taxonomy rows | DetailHeader, DocumentGroupList, ApplicationTimeline |
| **COMPLETE** (implemented, supplementary §5) | +5 files | MatchComparisonTable, ScreeningStagesTable, DetailSection, ApplicantSummaryCard, ApplicantResumeOverview |
| **RESOLVED — PHASE 5 SHELL** | 3 rows | Header/Footer, Sidebar/TopBar, AccountNav |
| **PRESERVE IN FEATURE** | 5 | ProfileCompletionCenter, RecruitmentChain, JobForm, CandidatePipeline, ChatPanel |
| **RESOLVED — PR-5AA / Timeline** | 2 | DashboardHeader/Grid, ApplicationTimeline reconciliation |
| **REJECTED** | 3 | NotificationCenter, FilterWorkspace, NotificationItem |
| **DEFERRED** | 1 | OrganizationSwitcher (P2) |

**Do not conflate:** 8 organism **files** ≠ 15 §11 **rows** ≠ 8 **pages** using organisms.

### 7.3 Adoption opportunities (not implementation defects)

- **DetailHeader:** Candidate **Applications/Show** and **SectionHeader** pages remain optional (employer **Applications/Show** + **Submissions/Show** adopted B1 / FE-101, 2026-10-10).
- **ApplicationTimeline:** Already on main application surfaces; admin screening inline Timeline **intentionally** kept for rich meta.
- **ApplicantResumeOverview:** Employer Applications/Show **done**; admin drawer resume tab still inline links.

---

## 8. Templates and layouts (Layer E)

| Layout | Path | Consumers | Status |
|--------|------|-----------|--------|
| PublicLayout | `Layouts/PublicLayout.tsx` | Public + account auth | **WORKING** — Header/Footer feature components |
| AccountLayout | `Layouts/AccountLayout.tsx` | Account (28 pages) | **WORKING** — AccountNav embedded |
| AdminLayout | `Layouts/AdminLayout.tsx` | Admin (41+ pages) | **WORKING** — Sidebar/TopBar embedded |
| AdminAuthLayout | `Layouts/AdminAuthLayout.tsx` | Admin auth | **WORKING** |
| UiLabLayout | UI Lab shell | DesignSystem/Show | **WORKING** |

**Finding E-1:** Breadcrumbs and page titles are **layout/page-owned** — consistent with DetailHeader scope (not global shell).

**Deferred (not gaps):** P5-14 nav config extraction, P5-15 unified nav prop, P5-16 PublicShell module exports.

---

## 9. Frontend page inventory (Layer F)

**Denominator:** **99** Inertia page components under `resources/js/Pages/**/*.tsx` (excluding `*.test.tsx`).

| Surface | Count | Dominant pattern | Static assessment |
|---------|------:|-------------------|---------------------|
| Public | 18 | PublicLayout + Public/* UI | **PARTIALLY ALIGNED** |
| Account / Candidate | 16 | AccountLayout + Public/Button + UI/Card | **PARTIALLY ALIGNED** |
| Account / Employer | 12 | AccountLayout; detail pages adopting organisms | **PARTIALLY ALIGNED** (detail **WORKING** trend) |
| Admin | 44 | AdminLayout + Admin/ui list stack | **PARTIALLY ALIGNED** |
| Auth | 8 | PublicLayout or AdminAuthLayout | **WORKING** |
| Design System | 1 | UiLabLayout | **WORKING** (Lab host) |
| Error | 0 | — | **NOT APPLICABLE** (no Inertia error pages found) |

**Visual assessment:** **UNVERIFIED** for all routes (no browser run).

### 9.1 Pattern-level page notes

- **Admin index pages (~35):** FilterBar + DataTable + Drawer/Modal; RecruitmentChain on domain indexes — **PARTIALLY ALIGNED** (molecule vs Admin/ui barrel).
- **Application workspace:** Thin pages → `ApplicationWorkspacePage` + `ApplicationDrawer` — **WORKING** for screening organisms.
- **Account profile/home:** ProfileCompletionCenter feature — **PRESERVE IN FEATURE** (correct).
- **Orphans / embeds:** ~~`Account/Switch.tsx`~~ **FE-501 (2026-10-10):** unreachable Inertia page **removed**; `POST account.switch` backend retained. `Admin/WebsiteContent/CmsEditor.tsx` embedded in Index only.

### Appendix A — Page inventory (99 components)

**Legend — static assessment:** `PA` = PARTIALLY ALIGNED (legacy Public/UI or Admin/ui lane); `OK` = WORKING static composition; `ORG` = uses canonical organism(s); `UV` = visual UNVERIFIED.

| Page component | Surface | Layout | Static |
|----------------|---------|--------|--------|
| `About.tsx` | Public | PublicLayout | PA |
| `Contact.tsx` | Public | PublicLayout | PA |
| `ForEmployers.tsx` | Public | PublicLayout | PA |
| `Home.tsx` | Public | PublicLayout | PA |
| `Services.tsx` | Public | PublicLayout | PA |
| `Companies/Index.tsx`, `Companies/Show.tsx` | Public | PublicLayout | PA |
| `Freelancers/Index.tsx`, `Freelancers/Received.tsx` | Public | PublicLayout | PA |
| `Jobs/Index.tsx`, `Jobs/Show.tsx`, `Jobs/Apply.tsx` | Public | PublicLayout | PA |
| `Legal/PrivacyPolicy.tsx`, `Legal/Terms.tsx` | Public | PublicLayout | PA |
| `Platform/Hire.tsx`, `Staffing.tsx`, `TalentPool.tsx` | Public | PublicLayout | PA |
| `Profiles/Show.tsx` | Public | PublicLayout | PA |
| `Account/Home.tsx`, `Applications.tsx`, `Applications/Show.tsx` | Candidate | AccountLayout | PA / ORG (Show) |
| `Account/Documents/Index.tsx` | Candidate | AccountLayout | ORG |
| `Account/Enquiries.tsx`, `Enquiries/Show.tsx` | Candidate | AccountLayout | PA |
| `Account/Notifications.tsx`, `Profile.tsx`, `SavedJobs.tsx` | Candidate | AccountLayout | PA |
| `Account/Resumes/Index.tsx`, `Builder.tsx` | Candidate | AccountLayout | PA |
| `Account/Reviews/Index.tsx` | Candidate | AccountLayout | PA |
| `Account/Settings/*` (4 pages) | Candidate | AccountLayout | PA |
| `Account/Employer/Jobs/*`, `Applicants/Index.tsx` | Employer | AccountLayout | PA |
| `Account/Employer/Applications/Show.tsx` | Employer | AccountLayout | ORG |
| `Account/Employer/Documents/Index.tsx` | Employer | AccountLayout | ORG |
| `Account/Employer/Submissions/*` (3 pages) | Employer | AccountLayout | ORG (Show) / PA |
| `Account/Employer/Reviews/Index.tsx` | Employer | AccountLayout | PA |
| `Account/Hiring/Dashboard.tsx`, `Applications/Index.tsx`, `TalentPool/Index.tsx` | Employer | AccountLayout | PA |
| `Account/Login.tsx`, `Register.tsx`, `ForgotPassword.tsx`, `ResetPassword.tsx` | Auth | PublicLayout | OK |
| ~~`Account/Switch.tsx`~~ | — | — | **Removed FE-501** (orphan page; POST `account.switch` remains) |
| `Admin/Dashboard.tsx`, `Analytics/Index.tsx` | Admin | AdminLayout | PA |
| `Admin/Jobs/Index.tsx`, `Candidates/*`, `Employers/Index.tsx` | Admin | AdminLayout | PA |
| `Admin/Applications/Index.tsx`, `Screening/Index.tsx`, `Shortlisted/Index.tsx` | Admin | AdminLayout / workspace | ORG (drawer) |
| `Admin/Placements/Index.tsx`, `Submissions/Index.tsx`, `Employees/Index.tsx`, `Finance/Index.tsx`, `Replacements/Index.tsx` | Admin | AdminLayout | PA |
| `Admin/Messages/Index.tsx`, `Notifications/Index.tsx`, `Tasks/Index.tsx`, `Interviews/Index.tsx` | Admin | AdminLayout | PA |
| `Admin/*` remaining index pages (CRM, Partners, Campaigns, Automations, Commission, MasterData, MediaSocial, WebsiteContent, Work, Staff, Roles, Users/*, ActivityLogs, LoginLogs, Settings, Profile/*, Freelancers/*, EmployerEnquiries, Hiring/CommandCenter, ScorecardTemplates, Automations) | Admin | AdminLayout | PA |
| `Admin/Auth/*` (3) | Auth | AdminAuthLayout | OK |
| `Admin/DesignSystem/Show.tsx` | DesignSystem | UiLabLayout | OK |
| `Admin/WebsiteContent/CmsEditor.tsx` | Admin | (embedded) | OK (embed) |

Route → component mapping: see §9 and `routes/web.php`, `routes/account.php`, `routes/admin.php` + controller `Inertia::render` (~96 renders).

---

## 10. UI Lab and governance (Layer G)

| Area | Status | Evidence |
|------|--------|----------|
| Route host | **WORKING** | `admin.design-system.*` → `Admin/DesignSystem/Show.tsx` |
| Section metadata | **WORKING** | `ui-lab/metadata.ts` — atoms, molecules, 8 organisms active |
| Catalog entries | **WORKING** | ~66 `ComponentCatalogEntry` exports in `ui-lab/catalog.ts` |
| Shell tier in Lab | **DEFERRED** | `comingSoon` shells — matches D-ORG-6 |
| Drift vs production | **PARTIALLY ALIGNED** | Catalog updated for recent Submissions adopters; Public Button not canonical in Lab vs production |

**Finding G-1:** UI Lab documents **canonical** paths; production **still imports legacy paths** on account/public — documentation readers may assume adoption is complete.

**FE-401 (2026-10-10):** UI Lab **Overview** now includes an explicit **dual-lane** callout and **Production import reality** table (canonical vs Public/UI/Account vs Admin/ui). FilterBar lab copy and catalog compatibility strings updated post FE-301–303; see `resources/js/ui-lab/metadata.ts` (`productionImportLanes`).

---

## 11. Component architecture and code quality (Layer H)

| Topic | Finding | Severity |
|-------|---------|----------|
| Import paths | Three lanes: `@/Components/atoms|molecules|organisms`, `@/Components/Admin/ui`, `@/Components/Public|UI|Account` | Governance |
| Barrel exports | `organisms/index.ts`, `atoms/index.ts` — **WORKING** | — |
| Duplicate FilterBar | Admin/ui vs molecules — **evidence: two source files** | P1 alignment |
| Duplicate Button/Card/StatusBadge | Public/UI/Account vs atoms — **evidence: import counts** | P2 alignment |
| TypeScript | `tsc --noEmit` **pass** | — |
| Component tests | **8** Vitest files under `Components/` — **low coverage** vs catalog size | P2 |
| Feature-owned UI | RecruitmentChain, ProfileCompletionCenter, JobForm, CmsEditor — **correct ownership** | Preserve |

---

## 12. Visual consistency and responsive (static)

**Method:** Class and component usage review only — **not** pixel verification.

| Topic | Assessment |
|-------|------------|
| Brand teal/orange | Token-level **WORKING**; legacy components generally use brand/accent utilities |
| Admin vs account density | Admin lists use compact tables; account uses Card sections — **intentional surface split** |
| Detail pages | Submissions/Show uses DetailHeader + organisms — **best current alignment reference** |
| Responsive | Widespread `sm:`/`md:`/`lg:` usage on account detail and layouts — **UNVERIFIED** at runtime |

---

## 13. Accessibility (static)

| Area | Assessment |
|------|------------|
| Organism tests | DetailHeader asserts `banner` + heading levels; ApplicationTimeline uses Timeline `aria-label` |
| Form controls | FormField molecule + Label atoms available; admin forms use Admin/ui Label |
| Focus | Shared `focusRing` on canonical controls |
| Full page axe/keyboard pass | **UNVERIFIED** — not run in this audit |

---

## 14. Canonical adoption opportunities

Prioritized in [OURNEAR_HIRE_FRONTEND_ALIGNMENT_BACKLOG.md](./OURNEAR_HIRE_FRONTEND_ALIGNMENT_BACKLOG.md). Summary:

1. DetailHeader on employer/candidate application detail pages (optional, authorized).
2. Align account CTAs with canonical Button surface prop (large surface area — batch carefully).
3. Admin/ui barrel re-export canonical FilterBar or deprecate duplicate file (governance — requires explicit PR-5M follow-up authorization).
4. Expand Vitest coverage for molecules with zero tests.

---

## 15. Duplicated or inconsistent implementations

| Responsibility | Implementations | Verdict |
|----------------|-----------------|---------|
| Primary button | `atoms/Button`, `Public/Button`, `Admin/ui/Button` | **Duplicate** — surface-aware intentional history |
| Filter bar shell | `molecules/FilterBar`, `Admin/ui/FilterBar` | **Duplicate** — class recipes differ |
| Status badge | `atoms/StatusBadge`, `Account/StatusBadge`, `Admin/ui/StatusBadge` | **Duplicate** |
| Card container | `atoms/Card`, `UI/Card`, `Admin/ui/Card` | **Duplicate** |
| Document list | `organisms/DocumentGroupList`, `Documents/DocumentGroupList` (legacy retained) | **Accepted** per D-ORG-5 |

---

## 16. Existing work that must be preserved

- All **8 canonical organisms** and recent **Submissions/Show** integration (DetailHeader, ApplicationTimeline, ApplicantResumeOverview).
- **DocumentGroupList** 2/2 account consumers on canonical path.
- **ApplicationDrawer** screening tables and timeline tab adoption.
- **Feature-layer** components: RecruitmentChain, ProfileCompletionCenter, JobForm, Messages composition, StatCard domain usage.
- **D-ORG-6** shell resolution — layouts remain source of truth for chrome.
- **D-ORG-8** FilterWorkspace rejection — list pattern unchanged.

---

## 17. Deferred work (do not treat as defects)

| Item | Record |
|------|--------|
| PublicShell / AdminShell / AccountShell module exports | P5-16, Phase 5 |
| Sidebar nav config split | P5-14 |
| Unified Account nav prop | P5-15 |
| OrganizationSwitcher organism | P2 deferred |
| FilterWorkspace / list-stack / Filter organism | D-ORG-8 rejected |
| Storybook / Phase 9 | Deferred |
| PR-5M/O **programmatic** bulk migration | Separate gate — not auto-authorized by this audit |
| Flutter / mobile | Paused |

---

## 18. Confirmed defects (reproducible)

| ID | Defect | Evidence | Severity |
|----|--------|----------|----------|
| — | **None confirmed with runtime reproduction** in this static audit | — | — |

**Candidate issues (need visual/runtime confirmation):**

- **FE-CAND-1:** Admin/ui FilterBar visual/layout divergence from molecule — static file diff (`FilterBar.tsx` both paths).
- **FE-CAND-2:** ~~`Account/Switch.tsx` possibly unreachable~~ **Resolved FE-501** — page removed; legacy POST route documented in backlog.

---

## 19. Unverified pages and limitations

- **99/99 pages:** Static composition only; **0** pages visually inspected in browser this session.
- **UI Lab sections:** Not opened in browser.
- **Accessibility:** No automated axe run.
- **Historical test reports:** Not reused as pass/fail proof.

---

## 20. Prioritized frontend alignment roadmap

| Phase | Focus | Authorization |
|-------|--------|---------------|
| **R1** | DetailHeader parity on application detail pages + browser QA on Submissions/Show | Optional adoption (register §27) |
| **R2** | Admin/ui FilterBar duplicate — document owner + single re-export strategy | Engineering governance; PR-5M follow-up if approved |
| **R3** | Account/public Button/Card alignment batches (surface-by-surface) | Product + engineering batch PRs |
| **R4** | Vitest expansion for high-traffic molecules | Standard quality work |
| **R5** | Phase 5 shell exports (P5-16) | **Deferred** — Phase 5 track only |

---

## 21. Acceptance criteria for “Hire frontend architecture complete”

1. **Single import graph per surface** documented and enforced: account/public → canonical atoms; admin → either documented Admin/ui facade **or** direct molecules with no duplicate implementations.
2. **All §11 COMPLETE organisms** adopted on **all eligible detail pages** identified in register, or explicitly waived in register with product sign-off.
3. **UI Lab catalog** matches production import paths for every **active** catalog entry.
4. **Typecheck + Vitest** green; critical paths have focused tests.
5. **Browser sign-off** on representative pages per surface (public, account, employer, admin list, admin detail, UI Lab).
6. **Deferred/rejected** items unchanged unless new architecture decision recorded.

---

## Validation executed (this audit)

| Command / check | Result |
|-----------------|--------|
| `npm run typecheck` | **Passed** (exit 0, 2026-10-10) |
| Page file count | **99** TSX pages |
| Browser review | **Not run** |

---

*End of audit report.*
