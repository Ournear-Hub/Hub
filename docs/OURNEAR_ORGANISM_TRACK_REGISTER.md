# OurNear Corporate Library — Organism Track Register (Stage 0)

**Date:** 2026-10-09  
**Repository:** `D:\Ournear Hub\Ournear Hire`  
**Purpose:** Single evidence-backed inventory and completion register for **Design System organisms** before further implementation.  
**Mode:** Governance / inventory only — **no implementation authorization** is implied by this register alone.

**Related records:** [OURNEAR_CORPORATE_LIBRARY_SPECIFICATION_v1.0.md](./OURNEAR_CORPORATE_LIBRARY_SPECIFICATION_v1.0.md) §11, §36, §37; [OURNEAR_CORPORATE_ARCHITECTURE_CLOSURE_PR5AE.md](./OURNEAR_CORPORATE_ARCHITECTURE_CLOSURE_PR5AE.md) §8–§9; [OURNEAR_PHASE_5_CLOSURE_GATE.md](./OURNEAR_PHASE_5_CLOSURE_GATE.md) §1.3; [OURNEAR_PHASE_6_DOCUMENTATION_CLOSURE_GATE.md](./OURNEAR_PHASE_6_DOCUMENTATION_CLOSURE_GATE.md); [OURNEAR_PR5M_PR5O_ADOPTION_READINESS_REVIEW.md](./OURNEAR_PR5M_PR5O_ADOPTION_READINESS_REVIEW.md) §13.

---

## 1. Purpose and scope

| In scope | Out of scope |
|----------|----------------|
| Organisms in **Spec §11** and **explicit PR-5 organism decisions** | **Spec §37 Phase 6** UX Systems **code** (Form/Data/Search wrappers — documentation closed) |
| Canonical tier `resources/js/Components/organisms/` | **PR-6A** V2 consumer adoption program |
| UI Lab registration model as exists today | Phase **9** Storybook / DocumentationShell |
| V2/feature references **only** for named candidates | Re-auditing atoms/molecules (PR-3–PR-5 freeze) |
| **DataTable / FilterBar molecules** + completed admin migrations (retain) | List-stack / DataTable **organism** |

**Rule (Spec §11):** Organisms accept props/slots; **no domain API calls** — pages pass data. Domain business rules → **Feature** layer.

---

## 2. Spec §37 phase numbering vs Organism Track stages

| Official Spec §37 phase | Deliverable | Relationship to Organism Track |
|-------------------------|-------------|--------------------------------|
| **Phase 3** | Components + **organisms** | **Authoritative home** for DS organism **implementation** in the specification |
| **Phase 4** | Patterns + templates | Composes organisms; does not replace organism registry |
| **Phase 5** | Product **shells** (contracts) | **Shell chrome** organisms (Header, Sidebar, TopBar, AccountNav) → **implementation deferred** — see Phase 5 gate §1.3 |
| **Phase 6** | **UX Systems** wrappers | **Not** the Organism Track — closed as **docs only** (Phase 6 documentation gate) |
| **Phase 7–8** | Feature standardization, thin pages | Consumer **adoption** of canonical organisms (separate PRs) |
| **Phase 9** | Registry / Storybook host | Deferred |

| Organism Track stage | Intent |
|----------------------|--------|
| **Stage 0** (this document) | Master inventory + completion register |
| **Stage 1+** | One bounded organism (or adoption) PR per authorization, updating this register §9 changelog |
| **Stage 2** | Post–Stage 1 reconciliation: counts, adoption vs implementation, decision queue (§19) |

**Operational name “Phase 6 Organisms”** used in product conversation **≠** Spec §37 Phase 6. Always map organism work to **§37 Phase 3** + this register.

---

## 3. Status vocabulary

| Status | Meaning |
|--------|---------|
| **COMPLETE** | Canonical DS organism meets §8 acceptance criteria |
| **PARTIAL** | Canonical + V2 both exist, or canonical missing lab/tests boundary item |
| **NOT IMPLEMENTED — AUTHORIZED** | Spec/decisions allow DS build; no blocking reject/defer |
| **BLOCKED — PRODUCT DECISION** | Needs product scope/contract |
| **BLOCKED — ARCHITECTURE DECISION** | Needs architecture charter (overlap/deferral) |
| **DEFERRED** | Explicit phase/gate deferral |
| **REJECTED BY DECISION** | PR-5 / Phase 6 / adoption record closes DS organism |
| **PRESERVE IN FEATURE LAYER** | Keep V2/feature; not a global DS export |
| **NEEDS EVIDENCE** | Spec aspirational or weak cross-surface contract |
| **RESOLVED — COMPOSE TIMELINE MOLECULE** | Spec §11 name maps to existing **Timeline** molecule; no distinct organism export |
| **RESOLVED — CONFIRM PR-5AA (D-ORG-1)** | Spec §11 dashboard names; **no** DashboardHeader/DashboardGrid DS export; page + Dashboard System composition; **StatCard** stays domain |
| **RESOLVED — PHASE 5 SHELL LAYER (D-ORG-6)** | Spec §11 shell names map to **Phase 5 Product Shells** on existing layouts; **no** Spec §37 Phase 3 organism export in `Components/organisms/` |

---

## 4. Complete candidate inventory

### 4.1 Spec §11 Organism Library (primary register)

| Canonical name | Purpose / surfaces | Spec / decision ref | Canonical DS | V2 / feature | Atoms/molecules to compose | DS vs feature | Tests | UI Lab | Status | Next action |
|----------------|-------------------|---------------------|--------------|--------------|----------------------------|---------------|-------|--------|--------|-------------|
| **DetailHeader** | Entity/section title + actions (Detail pattern) | Spec §11, §12; PR-5P ≠ PageHeader | `Components/organisms/DetailHeader.tsx`, exported | `Components/UI/SectionHeader.tsx` (different scope; 6 public/account pages) | Heading, Text, Stack, Cluster | **DS** | `DetailHeader.test.tsx` (2) | `/admin/design-system/organisms/detail-header` | **COMPLETE** | **3** account detail pages (§27); **SectionHeader** surfaces optional |
| **DocumentGroupList** | Grouped document rows (display) | Spec §11, §36; upload on page; **D-ORG-5** | `Components/organisms/DocumentGroupList.tsx`, exported | `Components/Documents/DocumentGroupList.tsx` — **legacy** (unchanged); **2/2** account consumers on canonical | Card, Stack, Text, EmptyState | **DS** (display); File System on page | `DocumentGroupList.test.tsx` (3) | `/admin/design-system/organisms/document-group-list` | **COMPLETE** | V2 path retained for rollback; no further §11 consumers |
| **Header / Footer** | Public marketing chrome | Spec §11; **D-ORG-6** §24; Phase 5 **PublicShell** | None — **not** a DS organism | `Layouts/PublicLayout.tsx` → `Components/Public/Header.tsx`, `Footer.tsx`; `content/navigation.ts` | Atoms, Link, Container (via layout) | **Phase 5 shell layer** | — | Shell tier comingSoon (UI Lab optional) | **RESOLVED — PHASE 5 SHELL LAYER (D-ORG-6)** | Optional P5-16 `PublicShell` module export — **not** Organism Track |
| **Sidebar / TopBar** | Admin app chrome | Spec §11; **D-ORG-6** §24; Phase 5 **AdminShell** | None — **not** DS organisms | `Components/Admin/Sidebar.tsx`, `TopBar.tsx`; sole layout consumer **`AdminLayout.tsx`** | Breadcrumbs molecule; permission-filtered nav in Sidebar | **Phase 5 shell layer** | — | — | **RESOLVED — PHASE 5 SHELL LAYER (D-ORG-6)** | Nav config extraction **P5-14 deferred**; library export only via Phase 5 **implementation** track |
| **AccountNav** | Account sidebar nav | Spec §11; **D-ORG-6** §24; Account logical shells | None — **not** a DS organism | `Components/Account/AccountNav.tsx`; **`AccountLayout.tsx`**; `candidateNav` / `employerNav` from middleware | Link atom; server-built nav arrays | **Phase 5 shell layer** | — | — | **RESOLVED — PHASE 5 SHELL LAYER (D-ORG-6)** | No cross-surface reuse; unified `nav` prop **P5-15 deferred** |
| **ProfileCompletionCenter** | Profile progress UI | Spec §11, §36; **D-ORG-2** | None — **preserve feature path** | `Components/Profile/ProfileCompletionCenter.tsx` — **2** account consumers (Home compact, Profile full) | Badge, Button, Progress (inline bar); rules in `profileCompletionModules.ts` | **Feature / account profile** | — | Progress lab references only | **PRESERVE IN FEATURE LAYER** | DS lift only if product reopens with **≥2 surfaces** + pre-computed props contract (§21) |
| **RecruitmentChain** | Recruitment context chain (ordered steps) | Spec §11; **PR-5E §4–§5** (domain UI); **D-ORG-3** | None — **preserve V2 path** | `Components/Admin/RecruitmentChain.tsx` — **7** admin consumers (Employees, Finance, Messages, Notifications, Placements, Replacements, Submissions) | Stack, Text, Link, Card (if lifted later) | **Feature / admin domain** | — | — | **PRESERVE IN FEATURE LAYER** | Lift only via explicit **Organism Track adoption PR** after product reopens PR-5E deferral or cross-domain reuse evidence |
| **JobForm** | Job fields block | Spec §11 “Feature if lookups+submit” | None | `Components/Admin/JobForm.tsx` | FormField family | **Feature** | — | — | **PRESERVE IN FEATURE LAYER** | No DS organism without Job domain charter |
| **CandidatePipeline** | Stage columns (ATS) | Spec §11, §39 | None | Pipeline/kanban in features (no shared DS export) | Cards, domain DnD | **Feature** | — | — | **PRESERVE IN FEATURE LAYER** | Product ATS scope |
| **ApplicationTimeline** | Application / recruitment event history (Detail **Activity** block) | Spec §11; **D-ORG-4** reconciled Stage 3 (2026-10-10) | `Components/organisms/ApplicationTimeline.tsx`, exported; composes **DetailSection** + **Timeline** molecule | Admin `ApplicationDrawer` keeps feature mappers → **Timeline** molecule directly | DetailSection, Timeline, Spinner, Alert | **DS** presentation; pages pass `{ key, label, at? }[]` | `ApplicationTimeline.test.tsx` (5) | `/admin/design-system/organisms/application-timeline` | **COMPLETE** | Optional admin drawer adoption; not required for contract |
| **ChatPanel** | Thread + composer | Spec §11, §12, §21; Arch §10; **D-ORG-7** | None — **no `ChatPanel` export** | `Pages/Admin/Messages/Index.tsx` — inline Drawer thread + compose Modal; types `communication.ts` | Drawer, Modal, DataTable, FilterBar, Textarea, Button, RecruitmentChain (context) | **Communication feature / page** | — | — | **PRESERVE IN FEATURE LAYER** | Extract **feature module** only if reuse appears; DS organism **not** authorized |
| **NotificationCenter** | Inbox layout | Spec §11, §20 | None | Admin/account notification **pages** (tables/cards differ) | Alert, EmptyState, DataTable | **REJECTED** PR-5V | — | — | **REJECTED BY DECISION** | Do not build; domain notification UI |
| **OrganizationSwitcher** | Org context | Spec §11 **P2** | None | No shared V2 component evidenced | Combobox / Select | **DEFERRED** P2 | — | — | **DEFERRED** | Product P2 multi-org |
| **DashboardHeader / DashboardGrid** | Dashboard chrome + widget grid | Spec §11; PR-5AA; **D-ORG-1 §20** | None — **no DS organism** | Admin: `StatCard` (**4** pages), page-local grids/headers; Account Home **no** StatCard | Card, Text, EmptyState; **StatCard domain** | **Dashboard System** + pages (Spec §24); not Spec §37 Phase 6 UX code | — | — | **RESOLVED — CONFIRM PR-5AA (N + M0)** | No `Components/organisms/Dashboard*`; PR-5AA unchanged |
| **FilterWorkspace** | Filters + results layout | Spec §11; **D-ORG-8** §25; PR-5M/O; Phase 6 List Pattern | None — **not** a DS organism | Admin **List Pattern**: page composes **FilterBar** + **DataTable** + **Pagination** (+ optional Drawer) | FilterBar, DataTable, Pagination molecules | **Page / feature** owns query + Inertia | — | — | **REJECTED BY DECISION (D-ORG-8)** | **Alternative A** confirmed — same boundary as rejected list-stack / Filter **organism** (§4.2); no FilterWorkspace export |
| **NotificationItem** (§20 UI) | Single notification row | Spec §9, §20; **PR-5V**; §22 | None — **no DS export** | Inline: `Account/Notifications.tsx` (`<li>` cards); `Admin/Notifications/Index.tsx` (**DataTable** columns); `Admin/TopBar.tsx` (recent links) | Badge, Text, Link, StatusBadge, DataTable | **Notification System** — domain rows | — | — | **REJECTED BY DECISION (PR-5V)** | No DS NotificationItem; compose atoms on pages; NotificationCenter rejected §4.1 |

### 4.2 Architecture-explicit organism candidates (not §11 names; governed by PR-5)

| Name | Purpose | Decision ref | Status | Notes |
|------|---------|--------------|--------|-------|
| **DataTable organism** / **admin list stack** | FilterBar + table + pagination + drawer | PR-5M §1C, §6; Phase 6 charter §2.3; PR-5M/O §13 complete **molecule** adoption | **REJECTED BY DECISION** | Use **molecules** + page composition |
| **FilterPanel / Filter organism** | Coordinated filter system | PR-5O §1C | **REJECTED BY DECISION** | **FilterBar** is molecule shell |
| **PageHeader / Toolbar organism** | Admin title + CTA row | PR-5P §1C, §5 | **REJECTED BY DECISION** | Use DetailHeader (detail) or page markup (list index) |
| **SearchPanel organism** | Unified search results UI | PR-5Y §1, §89 | **REJECTED BY DECISION** | **SearchField** + page composition |
| **Tree organism** | Hierarchical tree UI | PR-5N §1C | **REJECTED BY DECISION** | — |
| **ProgressiveList organism** | Infinite scroll / load-more system | PR-5Q §1C | **REJECTED BY DECISION** | — |
| **Sortable organism** | Reorder system | PR-5R §1C | **REJECTED BY DECISION** | — |
| **FileUploader / Dropzone / UploadList** (organism tier) | Upload queue UI | PR-5T §20 | **REJECTED BY DECISION** | **FileField** molecule + page Inertia |
| **ContentEditor / RichText organism** | CMS editor shell | PR-5U §166 | **REJECTED BY DECISION** | Domain **CmsEditor** only |
| **Stepper / Wizard organism** | Multi-step workflow chrome | PR-5E §1 | **REJECTED BY DECISION** (not created) | Use Progress, Tabs, Timeline, pages |
| **LoadingState / ErrorState organism** | Section loading/error | Loading/error review §1; Phase 6 Search contract | **REJECTED BY DECISION** (compose-only) | Spinner/Skeleton/Alert composition |
| **MetricCard / DescriptionList organism** | Metrics / key-value panels | PR-5AA | **REJECTED BY DECISION** / domain | **StatCard** stays admin domain |
| **ActivityFeed organism** | Compact feed | PR-5AE documented-only | **PRESERVE IN FEATURE LAYER** | `Admin/ui/ActivityFeed.tsx` |
| **ConfirmDialog organism** | Destructive confirm | PR-5AE domain | **PRESERVE IN FEATURE LAYER** | `Admin/ui/ConfirmDialog.tsx` |
| **Navigation organism** | Shared nav chrome | PR-5A §115; **D-ORG-6** §24 | **RESOLVED — PHASE 5 SHELL LAYER (D-ORG-6)** | Public **Header**, **AccountNav**, admin **Sidebar/TopBar** are **surface-specific shell** pieces — no unified DS nav organism |

---

## 5. Completed work and evidence (baseline verified 2026-10-09)

| Item | Evidence |
|------|----------|
| **DetailHeader** | `Components/organisms/DetailHeader.tsx`; export in `organisms/index.ts`; catalog `detailHeaderCatalog`; metadata `detail-header` active; route `organisms.detail-header`; `DetailHeader.test.tsx` (2); **consumers (§27):** `Pages/Account/Employer/Submissions/Show.tsx`, `Pages/Account/Employer/Applications/Show.tsx`, `Pages/Account/Applications/Show.tsx` |
| **DocumentGroupList** | `Components/organisms/DocumentGroupList.tsx`; export; catalog; route; `DocumentGroupList.test.tsx` (3); V2 `Components/Documents/DocumentGroupList.tsx` **unchanged**; **Stage 1C:** consumers `Pages/Account/Documents/Index.tsx`, `Pages/Account/Employer/Documents/Index.tsx` import **canonical** (`@/Components/organisms/DocumentGroupList`) — **2** migrated |
| **MatchComparisonTable**, **ScreeningStagesTable**, **DetailSection**, **ApplicantSummaryCard**, **ApplicantResumeOverview** | Canonical under `Components/organisms/` with focused Vitest + UI Lab routes (prior Organism Track stages — see git history) |
| **ApplicationTimeline** | **Stage 3 (2026-10-10):** `ApplicationTimeline.tsx`; maps `ApplicationTimelineEvent[]` → Timeline items; **consumers:** `Pages/Account/Applications/Show.tsx`, `Pages/Account/Employer/Applications/Show.tsx`, `Pages/Account/Employer/Submissions/Show.tsx` (`hideWhenEmpty` where noted), `Components/Admin/ApplicationDrawer.tsx` (Timeline tab); lab route `organisms.application-timeline`; `ApplicationTimeline.test.tsx` (5) |
| **DataTable / FilterBar molecules** | PR-5M M-B (**29** consumers), PR-5O O-B (**28** consumers) per [OURNEAR_PR5M_PR5O_ADOPTION_READINESS_REVIEW.md](./OURNEAR_PR5M_PR5O_ADOPTION_READINESS_REVIEW.md) §13 — **frozen baseline** |
| **UI Lab organism nav** | `ui-lab/metadata.ts` — Organisms group with DetailHeader + DocumentGroupList (no longer organisms-only `comingSoon`) |
| **DesignSystemController** | Allows `organisms-detail-header`, `organisms-document-group-list`, `molecules-data-table`, `molecules-filter-bar` |

---

## 6. Remaining authorized work (ordered by dependency and risk)

| Priority | Work item | Risk | Depends on | Status |
|----------|-----------|------|------------|--------|
| R0 | **Update this register** after each organism PR | Low | — | Ongoing |
| R1 | **RecruitmentChain** architecture mini-review → lift or feature-freeze | Medium | **D-ORG-3 closed** — preserve feature | **Complete (Stage 1A)** |
| R2 | **ApplicationTimeline** vs **Timeline molecule** boundary memo | Low | **D-ORG-4 closed** — Timeline sufficient | **Complete (Stage 1B)** |
| R3 | **DetailHeader** / **DocumentGroupList** **consumer adoption** (swap imports) | Medium | **D-ORG-5 closed** (DocumentGroupList **2/2**); DetailHeader optional | **DocumentGroupList complete (Stage 1C)**; DetailHeader **3** pages (§27); remaining **SectionHeader** surfaces optional |
| R4 | **DashboardHeader / DashboardGrid** | High | **D-ORG-1 closed** — N + M0 | **Complete (D-ORG-1 task)** |
| R5 | **ProfileCompletionCenter** boundary | High | **D-ORG-2 closed** — preserve feature | **Complete (D-ORG-2 task)** |
| R6 | **Shell organisms** (Header, Sidebar, TopBar, AccountNav) | High | **D-ORG-6 closed** §24 | **Complete (register disposition)** — Phase 5 **P5-16 implementation** remains separate |
| R7 | **ChatPanel** boundary | High | **D-ORG-7 closed** — preserve feature | **Complete (Stage 1D)** |
| R8 | **OrganizationSwitcher** | High | P2 multi-org product | **Deferred** |

**Not authorized:** FilterWorkspace, list-stack organism, NotificationCenter, PageHeader/Toolbar organism, SearchPanel, file-upload organisms, Phase 6 UX code, PR-6A bulk migration.

---

## 7. Decisions required (product / architecture)

| ID | Decision | Blocks |
|----|----------|--------|
| D-ORG-1 | **Dashboard metric/card contract** for DashboardHeader/Grid | **Closed 2026-10-09 — N + M0** (§20); **PR-5AA confirmed**; no dashboard DS organisms |
| D-ORG-2 | **ProfileCompletionCenter**: DS presentation organism vs single feature component | **Closed 2026-10-09 — Outcome B** (§21); preserve feature component |
| D-ORG-3 | **RecruitmentChain**: canonical DS name/API vs admin-only feature preservation | **Closed 2026-10-09 — Outcome B** (§15); see register update before any lift PR |
| D-ORG-4 | **ApplicationTimeline** vs Timeline molecule ownership | **Closed 2026-10-09 — Outcome A** (§16); compose **Timeline** molecule |
| D-ORG-5 | **Consumer adoption** scope for DetailHeader / DocumentGroupList (which pages, empty-state parity) | **Closed 2026-10-09 — Outcome A** (§17); DocumentGroupList **2** account consumers migrated; DetailHeader adoption still optional |
| D-ORG-6 | **Deferred shell candidates** (Header, Sidebar, TopBar, AccountNav) vs Spec §11 organism tier | **Closed 2026-10-10 — Outcome S** (§24): **RESOLVED — PHASE 5 SHELL LAYER**; no canonical organism exports. **Phase 5 P5-16** `*Shell` library implementation remains **deferred** (separate authorization) |
| D-ORG-7 | **ChatPanel**: DS organism vs Communication System / feature page composition | **Closed 2026-10-09 — Outcome B** (§18); no canonical ChatPanel |
| D-ORG-8 | **FilterWorkspace** vs FilterBar + page-level List Pattern | **Closed 2026-10-10 — Outcome A** (§25): **REJECTED BY DECISION**; no organism; **no** FilterWorkspace implementation authorized |

---

## 8. Explicit deferrals and rejections (frozen — do not reopen in organism PRs)

| Topic | Authority |
|-------|-----------|
| DataTable **organism**; admin **list stack** as one DS component | PR-5M; Phase 6 data list contract |
| FilterPanel / Filter **organism** | PR-5O |
| **FilterWorkspace** organism (Spec §11) | **D-ORG-8** §25 — same as list-stack / Filter organism rejection |
| PageHeader / Toolbar **organism** | PR-5P |
| SearchPanel / GlobalSearch organism | PR-5Y |
| NotificationCenter / NotificationList DS | PR-5V |
| LoadingState / ErrorState **organism** | Loading/error review; compose Spinner/Skeleton/Alert |
| Shell **implementation** (Sidebar, TopBar, layouts as `*Shell` exports) | Phase 5 gate §1.3 |
| Phase **6 UX Systems code** | Phase 6 documentation closure gate |
| Phase **9** Storybook host | Spec §37 Phase 9 |
| PR-**6A** program | Separate product gate |

---

## 9. Implementation acceptance checklist (§5 criteria)

An organism PR may mark **COMPLETE** in §4 only when all applicable items are checked and recorded in §10 changelog:

- [ ] **Boundary** documented (organism vs Feature vs File System / UX System)
- [ ] Implementation in `Components/organisms/` (or explicit exception recorded)
- [ ] Reuses frozen **atoms/molecules** where appropriate
- [ ] **Types** and `organisms/index.ts` exports match contract
- [ ] **UI Lab**: catalog entry, metadata status, route, `Show.tsx` section, controller allow-list
- [ ] **Focused tests** (`*.test.tsx`) cover contract (grouping, empty, slots, a11y landmarks where relevant)
- [ ] **Accessibility**: landmarks/labels for interactive slots documented
- [ ] **`npm run typecheck`** pass recorded
- [ ] **`npm run test`** pass recorded (organism tests)
- [ ] **V2/production consumers unchanged** unless separate adoption authorization
- [ ] **This register** updated (§4 status + §10 changelog)

---

## 10. Organism Track exit gate (Stage completion)

The Organism Track **implementation wave** (Spec §37 Phase 3 organism subset for Hire v1) may be considered for **freeze review** when:

1. Every **§11** row is **COMPLETE**, **PRESERVE IN FEATURE LAYER**, **DEFERRED**, **REJECTED**, **BLOCKED**, or **RESOLVED** (compose-only / no distinct organism) with owner — none left **NEEDS EVIDENCE** without a dated decision; and  
2. All **NOT IMPLEMENTED — AUTHORIZED** rows are either **COMPLETE** or explicitly **withdrawn**; and  
3. **Shell-tier** organisms remain **deferred** under Phase 5 (documented, not blocking organism-tier freeze for non-shell organisms); and  
4. **Consumer adoption** for canonical organisms is either complete per plan or explicitly out of scope for v1; and  
5. PR-5AE-style **inventory reconciliation** updated to include organism exports (optional governance PR).

**Current exit readiness:** **Met — Organism Track CLOSED** (2026-10-09 inventory; **2026-10-10** D-ORG-6 + D-ORG-8). §11 **15/15** rows dispositioned; **0** **BLOCKED — ARCHITECTURE**; supplementary **NotificationItem** **REJECTED (PR-5V)** §22; **3** §11 rows **COMPLETE** (DetailHeader, DocumentGroupList, ApplicationTimeline) plus **5** additional canonical organisms in **§5** (not separate §11 taxonomy rows); **0** **NOT IMPLEMENTED — AUTHORIZED** queue.

**Post-closure (separate gates — do not reopen Organism Track by default):** **Phase 5 P5-16** shell **implementation**; optional **DetailHeader** adoption; **PR-5M/O** molecule adoption (separate gates).

---

## 11. Summary counts (reconciled Stage 2 — 2026-10-09)

| Bucket | Count |
|--------|------:|
| **Spec §11 primary rows** | **15** (+ **NotificationItem** aspirational §20 row) |
| **PR-5 explicit pseudo-organism rows** | **15** (§4.2) |
| **Total tracked candidates** | **30** (§4.1 + §4.2; §11 rows counted once) |

### §4.1 status counts (**15** Spec §11 rows only — must sum to **15**)

| Status | Count | Rows |
|--------|------:|------|
| **COMPLETE** | 3 | DetailHeader, DocumentGroupList, ApplicationTimeline |
| **DEFERRED** | 1 | OrganizationSwitcher (P2) |
| **RESOLVED — PHASE 5 SHELL LAYER (D-ORG-6)** | 3 | Header/Footer, Sidebar/TopBar, AccountNav |
| **BLOCKED — PRODUCT DECISION** | **0** | — (D-ORG-1 closed §20) |
| **BLOCKED — ARCHITECTURE DECISION** | **0** | — (D-ORG-8 closed §25) |
| **REJECTED BY DECISION** | 2 | NotificationCenter, FilterWorkspace (D-ORG-8) |
| **PRESERVE IN FEATURE LAYER** | 5 | JobForm, CandidatePipeline, RecruitmentChain, ChatPanel, ProfileCompletionCenter |
| **RESOLVED — COMPOSE TIMELINE MOLECULE** | **0** | — (ApplicationTimeline promoted to **COMPLETE** Stage 3) |
| **RESOLVED — CONFIRM PR-5AA (D-ORG-1)** | 1 | DashboardHeader / DashboardGrid |
| **NEEDS EVIDENCE** | **0** | — |

**Check:** 3+1+3+0+0+2+5+0+1 = **15** ✓

**Note:** Additional canonical organisms (MatchComparisonTable, ScreeningStagesTable, DetailSection, ApplicantSummaryCard, ApplicantResumeOverview) are tracked in **§5** and UI Lab — not separate Spec §11 taxonomy rows.

### §4.1 supplementary row (Spec §20 — not in §11 total)

| Status | Count | Row |
|--------|------:|-----|
| **REJECTED BY DECISION (PR-5V)** | 1 | NotificationItem (§20 UI — §22) |
| **NEEDS EVIDENCE** | **0** | — |

### §4.2 status counts (15 rows)

| Status | Count |
|--------|------:|
| **REJECTED BY DECISION** | 11 |
| **PRESERVE IN FEATURE LAYER** | 2 |
| **RESOLVED — PHASE 5 SHELL LAYER (D-ORG-6)** | 1 (Navigation organism) |
| **NEEDS EVIDENCE** | **0** |
| **BLOCKED — ARCHITECTURE** | **0** | — (FilterWorkspace closed §25; list stack **REJECTED** §4.2) |

### Work summary (implementation vs adoption — do not conflate)

| Metric | Value | Notes |
|--------|------:|-------|
| **Canonical organisms implemented + verified** | **2** | DetailHeader, DocumentGroupList in `Components/organisms/` |
| **Production consumers on canonical organisms** | **5** pages | DocumentGroupList **2**; DetailHeader **3** (§27 employer submission/application + candidate application show) |
| **§11 rows preserved / resolved (not DS gaps)** | **7** | 5 PRESERVE + 2 RESOLVED (ApplicationTimeline; DashboardHeader/Grid D-ORG-1) |
| **§11 blocked pending product** | **0** | D-ORG-1 closed |
| **§11 deferred (P2 only)** | **1** | OrganizationSwitcher |
| **§11 resolved — Phase 5 shell layer** | **3** | D-ORG-6 §24 |
| **§11 rejected** | **2** | NotificationCenter, FilterWorkspace (D-ORG-8) |
| **§11 blocked architecture** | **0** | D-ORG-8 closed §25 |
| **Closed boundary/adoption decisions** | **8** | D-ORG-1 … D-ORG-8 |
| **Remaining §11 DS builds without new gate** | **0** | No authorized organism PR queue |
| **Supplementary §20 dispositioned** | **1** | NotificationItem **REJECTED (PR-5V)** §22 — not in §11 count |
| **Organism Track (register) status** | **CLOSED** | Exit gate **met** §10 — 2026-10-09 final reconciliation |

---

## 12. Recommended first implementation batch (post–Stage 0)

Execute **only after** explicit task authorization — register does not auto-authorize.

| Batch | Candidates | Prerequisite |
|-------|------------|--------------|
| ~~Batch A~~ | **RecruitmentChain** — **closed** preserve feature (Stage 1A) | **D-ORG-3** |
| ~~Batch B (DocumentGroupList)~~ | **DocumentGroupList** — **2/2** account consumers (Stage 1C) | **D-ORG-5** Outcome A |
| **Batch B (optional)** | **DetailHeader** import swap on named pages | Product scope for SectionHeader pages |
| ~~Batch C~~ | **ApplicationTimeline** — **closed** Outcome A (Stage 1B) | **D-ORG-4** |
| ~~Dashboard*~~ | **Closed** — D-ORG-1 N + M0 (§20); no dashboard organisms | **D-ORG-1** |
| **Not in first batch** | ~~FilterWorkspace~~ (D-ORG-8 closed), shells, NotificationCenter | ProfileCompletion §21; ChatPanel §18 |

---

## 13. Next prompt / task to execute

**Organism Track:** **CLOSED** (§10; checkpoint **§26**). **No default Organism Track PR queue.** All architecture decisions **D-ORG-1 … D-ORG-8** are **closed**. New canonical organism work requires an **explicit dated authorization** (not inferred from optional adoption notes).

**Separate workstreams (not Organism Track):**

| Gate / item | Track |
|-------------|--------|
| **Phase 5 P5-16** | Shell **implementation** (library exports, renames) — **deferred**; D-ORG-6 **closed** §24 (disposition only) — [OURNEAR_PHASE_5_CLOSURE_GATE.md](./OURNEAR_PHASE_5_CLOSURE_GATE.md) §1.3 |
| **FilterWorkspace** | **Closed — D-ORG-8** §25 (page composition; not an organism) |
| **DetailHeader** adoption | Optional product-scoped adoption PR |

**Do not implement** dashboard DS organisms or shared metric components unless a **future dated product decision** reopens D-ORG-1 with H/G/B and explicit PR-5AA override.

Optional (not Organism Track — molecule adoption):

```text
Timeline adoption: Account Applications/Show inline <ol> → molecules/Timeline
- Feature/page PR only; does not create ApplicationTimeline organism.
```

Optional (organism adoption — not authorized by default):

```text
DetailHeader consumer adoption on named public/account pages (SectionHeader overlap review per register §4.1).
```

---

## 14. Change log

| Date | Author | Change |
|------|--------|--------|
| 2026-10-09 | Organism Track Stage 0 (venkat task) | **Created** register; verified DetailHeader + DocumentGroupList complete; reconciled §11 + PR-5 organism decisions; exit gate **not met** |
| 2026-10-09 | Organism Track Stage 1A (venkat task) | **D-ORG-3 closed — Outcome B**; RecruitmentChain **PRESERVE IN FEATURE LAYER**; no canonical implementation |
| 2026-10-09 | Organism Track Stage 1B (venkat task) | **D-ORG-4 closed — Outcome A**; ApplicationTimeline **not** a distinct organism; compose **Timeline** molecule |
| 2026-10-09 | Organism Track Stage 1C (venkat task) | **D-ORG-5 closed — Outcome A**; **2** account DocumentGroupList consumers → canonical organism; V2 file retained |
| 2026-10-09 | Organism Track Stage 1D (venkat task) | **D-ORG-7 closed — Outcome B**; ChatPanel **PRESERVE IN FEATURE LAYER**; no DS organism |
| 2026-10-09 | Organism Track Stage 2 (venkat task) | Reconciled §11 counts (15 sum); split §20 NotificationItem; §19 decision queue; verified Stage 1 evidence |
| 2026-10-09 | D-ORG-1 decision preparation (venkat task) | **§20** added — options/tradeoffs; D-ORG-1 **awaiting product**; no implementation |
| 2026-10-09 | D-ORG-2 boundary (venkat task) | **Closed — Outcome B**; ProfileCompletionCenter **PRESERVE IN FEATURE LAYER** (§21) |
| 2026-10-09 | D-ORG-1 closure (Product Owner) | **Closed — N + M0**; **PR-5AA confirmed**; §20.4–§20.5; no dashboard DS organisms |
| 2026-10-09 | Supplementary NotificationItem (venkat task) | **REJECTED BY DECISION (PR-5V)** §22; no DS NotificationItem export |
| 2026-10-09 | Final exit-gate reconciliation (venkat task) | **Organism Track CLOSED** §10; **D-ORG-6** recorded as **open separate workstream** §19.6; §19.5 exit criteria **met** |
| 2026-10-10 | Organism Track Stage 3 (venkat task) | **ApplicationTimeline** canonical organism **COMPLETE** §23; **D-ORG-4** reconciled (workflow composition, not Timeline duplicate); account application detail **2** consumers |
| 2026-10-10 | D-ORG-6 shell disposition (venkat task) | **Closed — Outcome S** §24; Header, Sidebar, TopBar, AccountNav → **RESOLVED — PHASE 5 SHELL LAYER**; Navigation pseudo-organism resolved; **no** §11 organism implementation authorized |
| 2026-10-10 | D-ORG-8 FilterWorkspace (venkat task) | **Closed — Outcome A** §25; **REJECTED BY DECISION**; List Pattern = FilterBar + page composition; **no** FilterWorkspace organism or implementation |
| 2026-10-10 | Organism Track continuation (venkat task) | Verified D-ORG-8 **Case B**; **no** new organism implementation authorized; §26 checkpoint; §10/§19.5 count reconciliation |
| 2026-10-10 | ApplicationTimeline + ApplicantResumeOverview adoption (venkat task) | **Employer Submissions/Show** — canonical timeline + resume organisms; **ApplicationDrawer** Timeline tab → **ApplicationTimeline**; UI Lab catalog + §23 integration updated |
| 2026-10-10 | DetailHeader Stage 1C adoption (venkat task) | **Employer Submissions/Show** — requirement + candidate **DetailHeader** blocks (§27); first production consumer; organism contract unchanged |
| 2026-10-10 | B1 FE-101 (venkat task) | **Employer Applications/Show** — **DetailHeader** parity with Submissions/Show; §27 consumers updated; Vitest + typecheck + build + `EmployerHiringStage25Test` |
| 2026-10-10 | B1 FE-102 (venkat task) | **Candidate Applications/Show** — application + job **DetailHeader** blocks; withdraw/status/actions preserved; §27 + `detailHeaderCatalog`; Vitest + typecheck + build + `ApplicationExperienceTest` |

---

## 15. Decision record — D-ORG-3 (RecruitmentChain)

**Outcome:** **B — Preserve in feature layer** (lift **not** authorized in Stage 1A).

| Criterion | Evidence | Conclusion |
|-----------|----------|------------|
| Presentation vs business rules | Component is props-only (`steps[]`: `key`, `label`, `value`, `href`, `status`); no fetch/auth | **Presentation-only UI** |
| Domain-neutral API | Spec §11 names **RecruitmentChain**; all **7** consumers are admin recruitment/finance/placement/message drawers; server payloads are recruitment **lineage/chain** graphs | **Recruitment-domain contract**, not cross-surface |
| Consumer behavior variance | Identical usage: `<RecruitmentChain steps={selected.chain ?? []} />` (Finance: two lineage tabs) | **Stable within domain** |
| Atoms/molecules | Could compose Card, Text, Link atom, Stack | Lift technically feasible |
| Feature dependencies | Uses Inertia `Link` + `recorded()` display helper only — **no** Eloquent/services in component | **No persistence coupling** |
| Governance | **PR-5E §4:** “Domain **chain-of-records** display”; **§5:** “**RecruitmentChain — should remain domain UI until a future migration phase**, not a DS molecule clone” | **Deferral binding** for organism track unless explicitly reopened |
| Spec §11 organism list | Aspirational taxonomy; does **not** override PR-5E component-specific classification | **Preserve** until adoption charter |

**Rationale:** Although the JSX is small and data-driven, architecture already classifies **RecruitmentChain** as **admin recruitment domain UI**, not a global DS organism ready for canonical export. Lifting now would duplicate `Components/Admin/RecruitmentChain.tsx` without authorized consumer migration and would blur the Stepper/chain/timeline boundaries documented in PR-5E. A future **neutral ChainList** rename + lift requires separate product/architecture approval.

**Implementation in Stage 1A:** **None.**

**Re-open lift when:** (1) product authorizes **Organism Track adoption PR** explicitly reopening PR-5E deferral, or (2) **≥2 non-recruitment domains** share the same step-chain props contract (register evidence bar, analogous to PR-5M/O adoption rationale).

---

## 16. Decision record — D-ORG-4 (ApplicationTimeline)

**Outcome:** **A — Timeline molecule is sufficient** (distinct **ApplicationTimeline organism not authorized**).

| Criterion | Evidence | Conclusion |
|-----------|----------|------------|
| Separate V2 component named ApplicationTimeline | **None** in `resources/js` (grep: spec + register only) | Spec §11 name is **taxonomy**, not an implemented export |
| Canonical presentation | `Components/molecules/Timeline.tsx` — `{ id, title, meta? }`, empty string, `aria-label`; mirrors V2 `Admin/ui/Timeline.tsx` | **Molecule owns rail timeline UI** (PR-5F frozen) |
| Application / recruitment usage | `ApplicationDrawer`: maps `activities`, `screening_timeline` → Timeline `items`; `activityTitle()` etc. in **feature** | **Lifecycle logic and copy on feature** |
| Admin indexes | Employees, Finance, Placements, Replacements, Submissions — Timeline tabs with server `timeline` arrays | Same molecule API |
| Account application detail | `Pages/Account/Applications/Show.tsx` — **inline** `<ol>` for `application.timeline` | **Presentation could use Timeline molecule** via mapper; still **not** a new organism |
| vs RecruitmentChain | Chain = ordered **records** with links; Timeline = **event history** rail | Different patterns; ApplicationTimeline does not add a third contract |
| vs ActivityFeed | PR-5G: card-stack feed shares item shape with Timeline; ActivityFeed **preserve feature** | Application events use **Timeline**, not a third DS export |
| Distinct organism value | Would duplicate Timeline props/rendering with application-only naming | **No minimal organism contract** beyond molecule + mapper |

**Rationale:** **ApplicationTimeline** in the spec describes a **feature concern** (what events to show for an application), not a reusable organism layer. Hire already renders application/recruitment history with the **Timeline** molecule (admin) or ad hoc markup (account). Creating `Components/organisms/ApplicationTimeline.tsx` would duplicate the frozen molecule without new semantics.

**Implementation in Stage 1B:** **None.**

**Optional follow-up (not Organism Track):** Page-level adoption of `molecules/Timeline` on account application show — **Timeline molecule adoption PR**, not ApplicationTimeline organism.

---

## 17. Decision record — D-ORG-5 (DocumentGroupList consumer adoption)

**Outcome:** **A — Accept canonical EmptyState**; bounded migration **authorized and executed** for **2** account document index pages.

| Criterion | Evidence | Conclusion |
|-----------|----------|------------|
| Copy / visibility | Same `empty` strings passed by pages; description-only EmptyState (`align="start"`, `size="sm"`) — no icon/title | **No copy or workflow visibility loss** |
| Layout / typography | V2: `text-sm text-muted` inline `<p>`. Canonical: `emptyStateDescription` = `text-sm text-muted` + `emptyStateSizeSm` padding + dashed `emptyStateRoot` border | **Intentional DS zero-data surface** (classNames § EmptyState); not V2 pixel parity |
| Responsive | Both full-width inline/block; EmptyState `max-w-md` only caps line length | **No mobile regression** |
| Accessibility | Both expose message as visible text in `<p>`; canonical adds bordered region (visual, not live region) | **Equivalent SR announcement**; no new focus trap |
| Spec / architecture | Spec §9 **EmptyState** molecule; §11 **DocumentGroupList** display-only; organism implementation already chose EmptyState at build | **Accepted design-system standard**, not ad hoc organism change |
| Workflow safety | Consumers unchanged: `items`/`empty`/actions/extra mapping, upload/replace/archive routes, forms on page | **Import swap only** — safe |
| Populated list delta | Canonical Card + `aria-labelledby` on group sections; class tokens match V2 spacing/typography recipes | **Presentation refresh within DS**; props contract identical |

**Rationale:** Reverting to V2 plain `<p>` would bypass the frozen **EmptyState** molecule for a cosmetic match and contradict the organism’s documented composition. The dashed compact empty block matches other account zero-data patterns and keeps upload CTAs above the list unchanged.

**Implementation in Stage 1C:** Migrate `Pages/Account/Documents/Index.tsx`, `Pages/Account/Employer/Documents/Index.tsx` to `@/Components/organisms/DocumentGroupList`. Keep `Components/Documents/DocumentGroupList.tsx`.

**DetailHeader:** Stage 1C bounded adoption completed **2026-10-10** on employer submission/application detail and candidate `Pages/Account/Applications/Show.tsx` (§27). Further **SectionHeader** pages remain optional.

---

## 18. Decision record — D-ORG-7 (ChatPanel)

**Outcome:** **B — Preserve in the communication feature layer** (canonical **`Components/organisms/ChatPanel` not authorized**).

| Criterion | Evidence | Conclusion |
|-----------|----------|------------|
| Named `ChatPanel` implementation | **None** under `resources/js/Components/` (spec §11 taxonomy only) | No V2 lift target; aspirational organism name |
| Actual UI | **Single consumer:** `Pages/Admin/Messages/Index.tsx` — list via **FilterBar + DataTable**; thread in **Drawer** (`<ul>` message rows + **Textarea** reply); compose in **Modal**; `router.get/post` to `/admin/messages` | **Page-composed Communication pattern**, not a shared panel export |
| Data contract | `resources/js/types/communication.ts` — `ConversationListItem`, `ConversationDetail`, `ConversationMessage` (`can_reply`, `read_state`, recruitment `chain`, context URLs) | **Domain-shaped props**; not a surface-neutral organism API |
| Persistence / send | Inertia `router.post` for create/reply; no client message store | **Feature + server**; organism rule would push behavior into pages anyway |
| Conversation selection | URL query `conversation` + Drawer open state; list `inert` when drawer open | **Navigation + state on page** |
| Read / delivery UI | `read_state` string appended in message meta; unread counts in table | **Domain semantics** on feature types |
| Attachments / channels | Page copy: admin-only V1.1, attachments N/A; `attachments_available` optional on types | **Product scope unset** for cross-channel ChatPanel |
| Realtime | No websocket/poll UI evidenced — full page visits via Inertia | **Not a realtime presentation contract** |
| Loading / empty / error | DataTable `emptyTitle` / `emptyDescription`; drawer has no dedicated empty-message state | **List empty via DataTable molecule**; thread edge cases page-owned |
| Spec §12 Communication pattern | “Conversation list → Thread → Composer” owned by **Communication System**; “Patterns … **not necessarily one React export**” | **System/page composition** over DS organism |
| Spec §21 | Notification ≠ Message ≠ Conversation; V2 anchor = Admin Messages index | **Communication System** scope, not Phase 3 organism deliverable today |
| Architecture §10 | Communication System row: scope = conversations/messages; **ChatPanel UI** vs **Messages page** | UI groups with **system + domain**, not global DS export |
| Communication “charter” in repo | **No** `OURNEAR_*COMMUNICATION*` contract (unlike Phase 6 Search/Form/Data docs); §21 is overview only | **Charter not present** — insufficient to authorize organism **A** |
| Phase 6 UX code | Phase 6 gate: **implementation not authorized** | No Communication System wrapper code |

**Responsibility split (recorded):**

| Concern | Owner |
|---------|--------|
| Message list presentation (columns, filters, pagination) | **Page** composing frozen **FilterBar**, **DataTable**, **Pagination** |
| Thread message rendering (sender, body, timestamp) | **Communication feature** (inline JSX today) |
| Composer / compose forms | **Communication feature** + **Admin/ui** atoms |
| Send/receive/persistence, permissions (`can_reply`) | **Laravel + Inertia** (server authoritative) |
| Conversation pick / deep link | **Page** (URL + Drawer) |
| Recruitment context in thread | **Feature** (**RecruitmentChain** — D-ORG-3 preserve) |
| Read receipts / delivery states | **Domain** fields → feature display |
| Realtime | **Not implemented** in V2 UI |
| List empty state | **DataTable** molecule slots |

**Rationale:** Hire’s only in-app messaging surface is an **admin Messages page** that already composes canonical **molecules** for the inbox list while embedding thread/composer markup and **Inertia** mutations in the page. A DS **ChatPanel** would either duplicate Drawer + form atoms without a stable cross-surface API, or smuggle domain rules (recruitment chain, admin-only recipients, reply gating) into an organism. Spec and architecture place **ChatPanel-like UI** under the **Communication System** and **Messages page**, consistent with **Outcome B**.

**Implementation in Stage 1D:** **None.**

**If product later pursues a shared DS export (Outcome A), a Communication System charter (or equivalent gate) must answer at minimum:** (1) which surfaces share one thread/composer contract (admin vs account vs future social); (2) neutral message row props vs domain fields (`read_state`, `chain`); (3) attachment and channel matrix; (4) realtime vs Inertia refresh; (5) permission model for compose/reply; (6) relationship to **NotificationCenter** (rejected PR-5V). **Do not author that charter in the Organism Track unless explicitly requested.**

**Optional feature refactor (not Organism Track):** Extract `Admin/Messages/ConversationDrawer` (or similar) **feature component** when a second consumer exists — still **not** `Components/organisms/ChatPanel`.

---

## 19. Stage 2 — progress reconciliation and decision queue (2026-10-09)

### 19.1 Verified disposition snapshot (no re-investigation)

| Item | Verified status | Evidence pointer |
|------|-----------------|------------------|
| **DetailHeader** | **COMPLETE** (canonical); adoption optional | `Components/organisms/DetailHeader.tsx`; `organisms/index.ts`; UI Lab; tests |
| **DocumentGroupList** | **COMPLETE**; **2/2** account consumers on canonical | Organism file + `Pages/Account/Documents/Index.tsx`, `Employer/Documents/Index.tsx` import organism; V2 file retained |
| **RecruitmentChain** | **PRESERVE** — D-ORG-3 Outcome B | §15; V2 `Components/Admin/RecruitmentChain.tsx` |
| **ApplicationTimeline** | **RESOLVED** — D-ORG-4 Outcome A | §16; compose **Timeline** molecule |
| **ChatPanel** | **PRESERVE** — D-ORG-7 Outcome B | §18; `Pages/Admin/Messages/Index.tsx` page-composed |
| **DashboardHeader/Grid** | **RESOLVED — D-ORG-1 N + M0** | §20; PR-5AA confirmed |
| **ProfileCompletionCenter** | **PRESERVE — D-ORG-2 Outcome B** | §21; `Components/Profile/ProfileCompletionCenter.tsx` |
| **Shell tier (§11 names)** | **RESOLVED — D-ORG-6 §24** | Phase 5 shell layer on layouts; P5-16 implementation deferred |
| **NotificationCenter** | **REJECTED** PR-5V | §8 frozen |
| **NotificationItem (§20)** | **REJECTED (PR-5V)** | §22; page-local rows; no DS export |

### 19.2 Count corrections applied

- **§11 NEEDS EVIDENCE** was incorrectly **1** inside the 15-row table (double-count with NotificationItem). **Corrected:** **0** for §11; NotificationItem tracked under **supplementary §20** row.
- **Deferred shell/P2** corrected from **5** → **4** §11 deferred rows (OrganizationSwitcher is P2, not shell, but counted in same deferred bucket).

### 19.3 D-ORG-1 — product decision (DashboardHeader / DashboardGrid)

**Closed 2026-10-09 — N + M0; PR-5AA confirmed.** Record **§20.4–§20.5**. No dashboard DS organisms authorized.

### 19.4 D-ORG-2 — ProfileCompletionCenter

**Closed 2026-10-09 — Outcome B** (preserve feature). Full record **§21**.

### 19.5 Exit gate status (Stage 2)

| Criterion | Status |
|-----------|--------|
| §11 rows dispositioned | **Met** (15/15; no §11 NEEDS EVIDENCE) |
| Supplementary NotificationItem | **Met** — **REJECTED BY DECISION (PR-5V)** §22 |
| NOT IMPLEMENTED — AUTHORIZED | **N/A** (none queued) |
| Shell deferred documented | **Met** |
| Consumer adoption plan | **Met for v1 scope** — DocumentGroupList **2/2** per D-ORG-5; DetailHeader optional/out of scope unless product adopts (§10.4) |
| Shell deferred under Phase 5 | **Met** — §10.3; does **not** require D-ORG-6 closure for Organism Track freeze |
| FilterWorkspace | **Met** — **REJECTED BY DECISION (D-ORG-8)** §25; terminal §11 disposition |
| **Overall exit gate** | **Met — Organism Track CLOSED** — §10 criteria satisfied; **3/15 §11 COMPLETE** + reject/resolve/defer/preserve covers remaining rows; D-ORG-6/8 closed 2026-10-10 |

### 19.6 D-ORG-6 vs Organism Track closure (evidence)

| Source | Finding |
|--------|---------|
| Register **§10** criterion **3** | Shell-tier organisms remain **deferred** under Phase 5 — **documented, not blocking organism-tier freeze for non-shell organisms** |
| Register **§2** / **§8** | Phase **5** shell **implementation** explicitly **deferred**; Spec §37 **Phase 3** organisms ≠ Phase 5 shells |
| Register **§7** D-ORG-6 | Charter for Sidebar/TopBar/AccountNav as **library exports** — implementation gate, not §11 inventory completeness |
| [OURNEAR_PHASE_5_CLOSURE_GATE.md](./OURNEAR_PHASE_5_CLOSURE_GATE.md) §1.1, §1.3 | Phase 5 **doc closure** **2026-10-09**; `*Shell` re-exports and shell tier freeze = **separate authorization** / post–Phase 5 **implementation PR** |
| Register **§4.1** shell rows | **Closed 2026-10-10** — **RESOLVED — PHASE 5 SHELL LAYER** (§24); not Spec §37 Phase 3 organisms |

**Classification (updated 2026-10-10):** **D-ORG-6 organism-register gate closed.** Phase 5 **P5-16** `*Shell` **library implementation** remains a **separate deferred track** ([OURNEAR_PHASE_5_CLOSURE_GATE.md](./OURNEAR_PHASE_5_CLOSURE_GATE.md) §1.3, P5-16).

---

## 20. D-ORG-1 — Dashboard organism product decision

**Status:** **CLOSED 2026-10-09** — Product Owner approved **N + M0**; **PR-5AA confirmed**. No dashboard organism implementation authorized.

### 20.1 Evidence reviewed (2026-10-09)

| Source | Finding |
|--------|---------|
| [OURNEAR_DATA_DISPLAY_METRICS_ARCHITECTURE_REVIEW_PR5AA.md](./OURNEAR_DATA_DISPLAY_METRICS_ARCHITECTURE_REVIEW_PR5AA.md) | **No new DS metric/molecule**; Admin **`StatCard`** domain-owned; Account uses **UI `Card`** inline metrics; Analytics adds **ConversionCard** / **TimeCard** (distinct APIs); **no UI Lab** metric catalog entry |
| [OURNEAR_CORPORATE_ARCHITECTURE_CLOSURE_PR5AE.md](./OURNEAR_CORPORATE_ARCHITECTURE_CLOSURE_PR5AE.md) §2–§4 | **PR-5AA: Metrics — No DS**; **StatCard** stays domain; Spec §24 MetricCard naming ≠ DS export |
| Spec §24, §26 | Dashboard System lists DashboardHeader, WidgetGrid, **StatCard**; dashboards compose via system + **features**, not mandatory single React export |
| `Components/Admin/StatCard.tsx` | Props: `label`, `value`, `href?`, `hint?`, `emptyHint?`; presentation-only; **not** in `organisms/` or UI Lab |
| **StatCard consumers (Admin only, 4)** | `Pages/Admin/Dashboard.tsx`, `Pages/Admin/Analytics/Index.tsx`, `Pages/Admin/Hiring/CommandCenter.tsx` (`MetricGrid`), `Pages/Admin/EmployerEnquiries/Index.tsx` |
| **Dashboard-like surfaces without StatCard** | `Pages/Account/Home.tsx` — inline headers, `Card`, `ProfileCompletionCenter`; employer metrics use **Public UI Card** (PR-5AA § Evidence) |
| Register §4.2 | **MetricCard organism REJECTED** (PR-5AA) |
| DetailHeader (existing organism) | Overlaps **detail** title+actions pattern; admin dashboard headers are **list/dashboard index** copy + CTAs (PR-5P PageHeader **rejected**) |

### 20.2 Decision matrix — composition (pick one)

| Option | Description | Reuse evidence | Compatibility / risk | Maintenance |
|--------|-------------|----------------|----------------------|-------------|
| **N — Neither (confirm PR-5AA default)** | No `DashboardHeader` / `DashboardGrid` organisms; pages keep markup + `StatCard` / `Card` / grids | Matches **4** Admin StatCard screens + heterogeneous Account dashboards today | **Low risk** — no new API; Spec §11 names remain aspirational or map to Dashboard **System** docs | **Lowest** — no DS export to version; StatCard stays Admin domain |
| **H — DashboardHeader only** | DS organism: title, optional eyebrow, description, actions slot (props/slots only) | Admin Dashboard + Command Center both use **inline `<header>`** with greeting/title/CTA — **similar layout**, not identical props | **Medium** — must not duplicate **PageHeader** (rejected PR-5P) or **DetailHeader** scope; Account Home header is **role-specific** (candidate vs employer) | **Medium** — one organism + optional adoption on **≥2** pages to justify export |
| **G — DashboardGrid only** | DS organism: responsive grid wrapper + gap presets; **children** are page-supplied tiles | Repeated `grid grid-cols-* gap-4` in Dashboard, CommandCenter, Analytics — **layout only** | **Low–medium** — risk of duplicating **Stack/Container** patterns; does not unify **StatCard** vs Account **Card** tiles | **Medium** — thin wrapper; benefit only if many dashboards adopt same gap/breakpoints |
| **B — Both Header + Grid** | Two organisms as Spec §11 pair | Theoretical cross-surface dashboard template | **Higher** — doubles adoption surface; still **does not** resolve metric tile API without a third contract or StatCard decision | **Highest** — two exports + lab/tests + adoption tracking |

### 20.3 Decision matrix — metric / tile contract (pick one; required if H, G, or B chosen)

| Option | Description | vs StatCard | Risk |
|--------|-------------|-------------|------|
| **M0 — No DS metric tile** | Grid/header organisms (if any) accept **ReactNode children** only; **StatCard** unchanged | **Confirms PR-5AA** | Lowest — no migration |
| **M1 — Wrap StatCard (Admin)** | DS documents “use Admin StatCard inside grid” — **not** a new export | **Leaves StatCard domain**; documents pairing | Admin-only; Account still divergent |
| **M2 — Neutral MetricTile molecule** | New **`Components/molecules/*`** (not organism): `{ label, value, href?, hint?, emptyHint? }` + optional slots | **Requires dated PR-5AA override** + consumer migration from StatCard | **High** — PR-5AA explicitly rejected; migration cost on **4** Admin pages |
| **M3 — StatCard lift to DS** | Rename/move StatCard to library tier | **Override PR-5AA** + Account alignment program | **High** — cross-surface visual parity (Account uses UI Card, different spacing per PR-5AA) |

**If option N (neither organism):** metric row defaults to **M0** (implicit PR-5AA confirm).

### 20.4 Approved product decision (authoritative)

```text
D-ORG-1 approved by: Product Owner
Date: 2026-10-09
1. Composition (N / H / G / B): N — neither DashboardHeader nor DashboardGrid
2. Metric contract (M0 / M1 / M2 / M3): M0 — no shared metric component
3. Shared surfaces (list): N/A — no shared organism authorized
4. Minimum v1 adoption count: 0
5. PR-5AA: [x] Confirm — existing “Metrics — No DS” decision
6. Authorized follow-up (if not N): N/A — no dashboard organism implementation authorized
```

**Effect:** Spec §11 names **DashboardHeader** / **DashboardGrid** remain **taxonomy + Dashboard System (Spec §24)** composition on pages — **not** Spec §37 **Phase 3** organism deliverables for Hire v1. This does **not** authorize Spec §37 **Phase 6** UX Systems code. Admin **`StatCard`** and page-local dashboard markup unchanged.

#### Reference — pre-decision evidence questions (answered above)

| Question | Evidence today (unchanged) | Approved answer |
|----------|----------------------------|-----------------|
| **Shared surfaces** | StatCard: Admin **4** pages; Account dashboards heterogeneous | **N/A** |
| **Adoption bar** | PR-5M/O **≥2** for molecule adoption | **0** |
| **PR-5AA stance** | PR-5AA closed **No DS metric** | **Confirm** (no override) |

### 20.5 Closure record — D-ORG-1 gate

| Field | Value |
|-------|--------|
| **Approver** | Product Owner |
| **Decision date** | 2026-10-09 |
| **Composition** | **N** — neither DashboardHeader nor DashboardGrid |
| **Metric contract** | **M0** — no shared metric component |
| **Shared surfaces** | N/A |
| **Minimum v1 adoption count** | **0** |
| **PR-5AA** | **Confirmed** — Metrics — No DS; StatCard stays domain |
| **D-ORG-1 status** | **CLOSED** |
| **Implementation authorized?** | **No** — no dashboard organism PR; no StatCard / metric DS work |

**Re-open:** Requires new dated product decision in §20.4 format with H/G/B and, if applicable, scoped PR-5AA override.

---

## 21. Decision record — D-ORG-2 (ProfileCompletionCenter)

**Outcome:** **B — Preserve in feature layer** (canonical **`Components/organisms/ProfileCompletionCenter` not authorized**).

| Criterion | Evidence | Conclusion |
|-----------|----------|------------|
| Production consumers | **2** account pages only: `Pages/Account/Home.tsx` (`compact`), `Pages/Account/Profile.tsx` (full + `onOpenModule`) | **No cross-surface reuse** (no admin/employer/corporate) |
| Props contract | Accepts **`AccountProfile`**; calls **`buildProfileModules(profile)`** and **`nextIncompleteModule`** inside component | **Domain graph + rules coupled** to UI — violates §11 organism boundary as implemented |
| Rule ownership | `profileCompletionModules.ts` — module catalog, `completionKeys`, `statusFromKeys`, resume skip in `nextIncompleteModule`, education/resume exceptions | **Profile-domain**; must not move to DS |
| Navigation / copy | Hard-coded `/account/profile`, `/account/resumes`, `#${module.id}` anchors; threshold copy (70%/100%); resume **Manage** vs module **Edit/Continue/Add** | **Account candidate workflow**, not neutral API |
| Percent display | `profile.profile_completion` from server; module rows **recomputed client-side** from profile + completion_items | Split possible in theory; **not authorized** without product **Outcome A** charter |
| DS composition today | **Badge** atom, **Public Button**, inline progress bar (not **`Progress`** molecule export in component) | Presentation could use DS atoms if refactored — still **feature-owned** |
| Spec §11 vs §26 | §11 names organism; §26 lists **Profile Completion** under **Feature** examples | **Feature classification** governs current implementation |
| PR-5AA / PR-5AE | Metrics decision unrelated; PR-5AE pattern: **domain components intentional**, not missing DS | No contradiction with preserving ProfileCompletionCenter |
| UI Lab | `ProgressLabSection` / catalog may reference pattern — **not** a production consumer | Lab ≠ adoption evidence |

**Presentation vs domain (recorded split for future reference only — not implemented):**

| Layer | Belongs to |
|-------|------------|
| Progress bar, module list layout, badges, section titles | Potential **presentation** if props were `{ completionPercent, modules: { id, title, summary, status, action }[] }` |
| Module catalog, completion keys, status rules, next-step algorithm, profile field reads | **`profileCompletionModules.ts` + controller/server** |
| Persistence, eligibility, fetching | **Laravel / pages** |

**Rationale:** Hire ships one **account candidate** profile-completion UX tightly bound to **`AccountProfile`** shape and **`profileCompletionModules`**. There is no second surface sharing the same contract and no product request for a global DS export. Lifting without a pre-computed props API would duplicate rules in an organism or import forbidden domain modules. Spec §26 already treats profile completion as **feature** scope.

**Implementation in D-ORG-2 task:** **None.**

**Re-open DS organism (Outcome A) only when product explicitly records:** (1) **≥2 named surfaces** need identical UI; (2) approved **neutral props** (percent + module rows + action slots); (3) **`profileCompletionModules` stays feature/page/controller**; (4) separate bounded Organism Track PR authorization.

---

## 22. Supplementary decision — NotificationItem (Spec §20 UI)

**Disposition:** **REJECTED BY DECISION (PR-5V)** — no canonical **`NotificationItem`** molecule or organism; **not** implementation authorization.

| Criterion | Evidence | Conclusion |
|-----------|----------|------------|
| Named DS component | **No** `NotificationItem.tsx` under `Components/atoms|molecules|organisms` | Spec §9 / §20 **aspirational** name only |
| PR-5V closure | [OURNEAR_NOTIFICATION_ARCHITECTURE_REVIEW_PR5V.md](./OURNEAR_NOTIFICATION_ARCHITECTURE_REVIEW_PR5V.md) §1, §8–§9, §19 — rejects **NotificationCenter**, **NotificationList**, bell/list DS exports; persistent notifications = **domain UI** | **NotificationItem** row UI falls under same boundary as inbox/list (no generic DS row contract) |
| §11 NotificationCenter | Register §4.1 **REJECTED BY DECISION** PR-5V | Row primitive **not** separated as authorized DS work while center/list rejected |
| Admin inbox row | `Pages/Admin/Notifications/Index.tsx` — **DataTable** column `render` on `AdminAlertItem` (category, title, recipient, read badge) | **Table cell composition**, not reusable NotificationItem |
| Account inbox row | `Pages/Account/Notifications.tsx` — inline `<li>` card (event label, title, body, actions) | **Page-local** markup; `AccountNotification` shape |
| Admin recent item | `Components/Admin/TopBar.tsx` — dropdown links from `adminAlerts.recent` | **Shell chrome**, single consumer |
| Cross-surface API | `AdminAlertItem` vs `AccountNotification` — different fields, actions, layouts (PR-5V §8) | **No stable shared props** for DS export |
| DS composition | Spec table cites Badge, Text, Link — available as **atoms** | **Compose on pages**; no new export required |

**Rationale:** Hire implements notification **rows** three different ways (table cells, account cards, top-bar links). PR-5V already closed DS notification **center/list/bell** patterns for lack of a repeated generic contract. Creating **NotificationItem** would either duplicate **DataTable** cell composition (admin) or a thin wrapper around **Card** + **Badge** (account) without cross-surface adoption evidence. **Alert/Toast** remain the DS feedback surface (PR-5V); product notification records stay **Notification System / domain pages**.

**Re-open DS NotificationItem only if:** product **reopens PR-5V** NotificationCenter/list decision **and** documents a **neutral row props contract** adopted on **≥2** surfaces — separate authorization, not this register row alone.

**Implementation:** **None.**

---

## 23. Implementation record — ApplicationTimeline (Stage 3)

| Field | Value |
|-------|--------|
| **Canonical path** | `resources/js/Components/organisms/ApplicationTimeline.tsx` |
| **Export** | `organisms/index.ts` — `ApplicationTimeline`, `mapApplicationTimelineEvents`, types |
| **Purpose** | Detail-pattern **Activity** region for application/recruitment history: titled section, optional description, maps server events to the frozen **Timeline** molecule |
| **Props contract** | `events: { key, label, at? }[]`; `title?`; `description?`; `empty?`; `hideWhenEmpty?`; `loading?`; `error?`; `aria-label?` |
| **Boundary** | No fetch, auth, or event selection — pages/controllers own lifecycle data (reconciles **D-ORG-4**: not a Timeline rename wrapper) |
| **Composition** | DetailSection + Timeline (+ Spinner / Alert for async states) |
| **Integration** | `Pages/Account/Applications/Show.tsx`; `Pages/Account/Employer/Applications/Show.tsx` (`hideWhenEmpty`); `Pages/Account/Employer/Submissions/Show.tsx` (`hideWhenEmpty`, client-formatted `at`); `Components/Admin/ApplicationDrawer.tsx` (Timeline tab) |
| **UI Lab** | `applicationTimelineCatalog`; section `organisms-application-timeline`; route `organisms.application-timeline` |
| **Tests** | `ApplicationTimeline.test.tsx` — mapping, populated, empty, hideWhenEmpty, loading/error |
| **Validation (2026-10-10)** | Vitest (focused), `npm run typecheck`, `npm run build` — recorded in task report |
| **Browser verification** | **Not run** in this batch (no manual account application detail session) |
| **Status** | **COMPLETE** |

---

## 24. Decision record — D-ORG-6 (Deferred shell candidates)

**Status:** **CLOSED 2026-10-10** — **Outcome S (Shell layer resolve)**. Organism-register disposition **complete** for Header, Sidebar, TopBar, and AccountNav. **No** Spec §37 Phase 3 organism implementation authorized in this decision.

### 24.1 Evidence reviewed (targeted)

| Source | Finding |
|--------|---------|
| [OURNEAR_PHASE_5_CLOSURE_GATE.md](./OURNEAR_PHASE_5_CLOSURE_GATE.md) §1.1–§1.3 | Phase 5 **architecture closed** 2026-10-09; deliverable = **contracts on existing layouts**, not `Components/organisms/*` exports |
| [OURNEAR_PRODUCT_SHELL_ARCHITECTURE_REVIEW.md](./OURNEAR_PRODUCT_SHELL_ARCHITECTURE_REVIEW.md) §2–§3 | **PublicShell** ← `PublicLayout` + Header/Footer; **AdminShell** ← `AdminLayout` + Sidebar/TopBar; **AccountShell** ← `AccountLayout` + **AccountNav** |
| `Layouts/PublicLayout.tsx` | Composes `Components/Public/Header`, `Footer`; SEO/branding via shared props |
| `Layouts/AdminLayout.tsx` | Sole consumer of `Sidebar`, `TopBar`; flash + breadcrumbs in layout |
| `Components/Admin/Sidebar.tsx` | In-component nav tree + **client permission filter**; nav config file split **P5-14 deferred** |
| `Components/Admin/TopBar.tsx` | Menu affordance + **`adminAlerts`** recent list (permission-gated server prop) |
| `Components/Account/AccountNav.tsx` | **`candidateNav` / `employerNav`** from `HandleInertiaRequests`; role branch; **one layout consumer** |
| Spec §11 vs §37 Phase 5 | §11 shell **names** are taxonomy; Phase 5 maps them to **layouts**, not mandatory organism tier |
| PR-5P PageHeader rejected | Public/admin **chrome headers** ≠ **DetailHeader** detail pattern |

### 24.2 Per-candidate disposition (authoritative)

| Candidate | Existing implementation | Consumers / surfaces | Disposition | Rationale (narrow) |
|-----------|-------------------------|----------------------|-------------|-------------------|
| **Header** (public) | `Components/Public/Header.tsx` via **PublicLayout** | ~23 public pages (Product Shell Review §2) | **RESOLVED — PHASE 5 SHELL LAYER** | Public-only marketing chrome; admin uses **TopBar**; not a cross-surface organism contract |
| **Footer** (public) | `Components/Public/Footer.tsx` via **PublicLayout** | Same as Header | **RESOLVED — PHASE 5 SHELL LAYER** | Paired with Header in **PublicShell**; no separate §11 organism |
| **Sidebar** (admin) | `Components/Admin/Sidebar.tsx` | **AdminLayout** only | **RESOLVED — PHASE 5 SHELL LAYER** | Permission-gated admin nav embedded in component; lifting to DS organism would duplicate **AdminShell** without neutral API (**P5-14** prerequisite) |
| **TopBar** (admin) | `Components/Admin/TopBar.tsx` | **AdminLayout** only | **RESOLVED — PHASE 5 SHELL LAYER** | Admin shell utility bar + alerts; domain **`adminAlerts`** shape; not reusable outside AdminShell |
| **AccountNav** | `Components/Account/AccountNav.tsx` | **AccountLayout** (candidate / employer / hiring routes) | **RESOLVED — PHASE 5 SHELL LAYER** | Server-built nav arrays; single-surface; unified Inertia **`nav` prop** remains **P5-15 deferred** |

**§4.2 Navigation organism:** Same outcome — **no** unified DS nav organism; surface-specific shell pieces above.

### 24.3 Approved architecture decision (authoritative)

```text
D-ORG-6 approved by: Architecture / Organism Track (register-only closure)
Date: 2026-10-10
Outcome: S — Resolve Spec §11 shell names to Phase 5 shell layer (not DS organisms)
1. Create Components/organisms/Header|Sidebar|TopBar|AccountNav?  No
2. Spec §11 shell rows disposition: RESOLVED — PHASE 5 SHELL LAYER (3 §4.1 rows)
3. Phase 5 P5-16 *Shell library exports: remains DEFERRED — separate implementation authorization
4. Phase 5 P5-14 admin nav config extraction: remains DEFERRED — prerequisite for any Sidebar lift
5. Minimum adoption count for organism tier: 0 (not applicable)
6. UI Lab shell gallery: optional — not required for this gate
```

**Effect:** Spec §11 **Header/Footer**, **Sidebar/TopBar**, and **AccountNav** are **not** missing DS organisms — they are **implemented under Phase 5 Product Shells** on V2 layouts. Future work may add **`PublicShell` / `AdminShell` / `AccountShell` modules** (P5-16) without creating Spec §37 Phase 3 **`Components/organisms/`** exports for these names.

### 24.4 What remains open (explicit — not D-ORG-6 blockers)

| Item | Track | Status |
|------|--------|--------|
| **P5-16** `*Shell.tsx` re-exports / canonical shell folder | Phase 5 **implementation** | **DEFERRED** — separate PR authorization |
| **P5-14** Sidebar nav external config | Phase 5 maintenance | **DEFERRED** |
| **P5-15** unified `nav` Inertia prop | Adoption / later SDK | **DEFERRED** |
| **OrganizationSwitcher** | Spec §11 P2 | **DEFERRED** (unchanged) |
| **FilterWorkspace** | **Closed — D-ORG-8** §25 | **REJECTED BY DECISION** — not an organism |

### 24.5 D-ORG-6 gate closure

| Field | Value |
|-------|--------|
| **Organism-register question** | Should deferred §11 shell names become canonical **organisms**? |
| **Answer** | **No** — map to **Phase 5 shell layer** on existing layouts |
| **D-ORG-6 status** | **CLOSED** |
| **Implementation authorized by D-ORG-6?** | **No** — documentation and disposition only |

---

## 25. Decision record — D-ORG-8 (FilterWorkspace)

**Status:** **CLOSED 2026-10-10** — **Outcome A (Page composition)**. Spec §11 **FilterWorkspace** is **not** an authorized canonical organism. Architecture gate **closed**; **FilterWorkspace implementation not authorized**.

### 25.1 Evidence reviewed (targeted)

| Source | Finding |
|--------|---------|
| Register §4.2 | **DataTable organism / admin list stack** — **REJECTED BY DECISION** (PR-5M); **FilterPanel / Filter organism** — **REJECTED BY DECISION** (PR-5O) |
| [OURNEAR_TABLE_ARCHITECTURE_REVIEW_PR5M.md](./OURNEAR_TABLE_ARCHITECTURE_REVIEW_PR5M.md) §2 | **Option C — DataTable organism rejected**; filters, pagination, query = **page-owned**; **Option D** = product **pattern** only |
| [OURNEAR_FILTER_ARCHITECTURE_REVIEW_PR5O.md](./OURNEAR_FILTER_ARCHITECTURE_REVIEW_PR5O.md) §2 | **Option C — Filter organism rejected**; **FilterPanel not warranted**; **FilterBar** = presentation shell molecule; **Option D** = domain/page compositions |
| [OURNEAR_PHASE_6_DATA_LIST_PATTERN_CONTRACT.md](./OURNEAR_PHASE_6_DATA_LIST_PATTERN_CONTRACT.md) §2 | Authoritative **List Pattern**: FilterBar → DataTable → Pagination; **~28–29** admin consumers; **not** one DS organism |
| [OURNEAR_PR5M_PR5O_ADOPTION_READINESS_REVIEW.md](./OURNEAR_PR5M_PR5O_ADOPTION_READINESS_REVIEW.md) §2 | PR-5M/O decisions **not reopened**; canonical **molecules** adoption is a **separate** gate from any FilterWorkspace organism |
| `Components/molecules/FilterBar.tsx` | **Frozen molecule API:** `onSubmit`, `children`, optional `className` — **no** query/state coordination |
| Spec §11 **FilterWorkspace** | Aspirational “filters + results layout” — **no** `FilterWorkspace.tsx` in repo; admin indexes already implement the pattern via page composition |

### 25.2 Alternative comparison (mandated)

| Criterion | **A — FilterBar + page composition** | **B — FilterWorkspace organism** |
|-----------|----------------------------------------|----------------------------------|
| Existing evidence | **High** — documented List Pattern; **28+** FilterBar consumers | **None** — no component, no neutral cross-surface props contract |
| Cross-feature reuse | Pattern reuse via **docs + molecules** | Would **duplicate** rejected list-stack + Filter organism |
| Filter / query state | **Server + page** (Inertia `router.get`, FormData) | Would smuggle state API into organism or duplicate page logic |
| Result list | **DataTable** or cards — **page chooses** | Same children as A, with extra wrapper tier |
| vs FilterBar | **Uses** canonical molecule | **Overlaps** FilterBar shell + children |
| vs FilterPanel deferral | Mobile/advanced filters stay **`<details>` / sections** (PR-5O) | Risks reinventing FilterPanel under new name |
| vs PR-5M/O | **Aligned** | **Violates** user constraint — overlap with frozen baseline |
| Regression risk | **Low** — status quo | **High** — new abstraction over **29** table surfaces |

**Selected:** **Alternative A** — confirm existing **List Pattern** ownership. **Reject Alternative B.**

### 25.3 Authoritative disposition

| Topic | Decision |
|-------|----------|
| **Spec §11 FilterWorkspace row** | **REJECTED BY DECISION (D-ORG-8)** — taxonomy name maps to **Phase 6 List Pattern** + page composition, not `Components/organisms/FilterWorkspace` |
| **Reusable responsibility for a organism** | **None demonstrated** beyond what **FilterBar**, **DataTable**, **Pagination**, and **pages** already own |
| **Included in architecture** | Documented stack in [OURNEAR_PHASE_6_DATA_LIST_PATTERN_CONTRACT.md](./OURNEAR_PHASE_6_DATA_LIST_PATTERN_CONTRACT.md); FilterBar/DataTable **molecule** adoption per PR-5M/O when separately authorized |
| **Explicitly excluded** | FilterWorkspace organism; FilterPanel organism; list-stack organism; coordinated filter state hook in DS; PR-5O/M reopening |
| **Filter state / URL sync** | **Laravel controllers + Inertia**; pages read FormData / props — **not** a DS organism |
| **Result-list composition** | **Page / feature** — table vs cards, drawers, empty copy |
| **Implementation authorized?** | **No** — there is **no** FilterWorkspace component to build under this decision |

### 25.4 Approved architecture decision (authoritative)

```text
D-ORG-8 approved by: Architecture / Organism Track (register-only closure)
Date: 2026-10-10
Outcome: A — FilterBar + page-level List Pattern composition
1. Authorize Components/organisms/FilterWorkspace?  No
2. §11 FilterWorkspace disposition: REJECTED BY DECISION
3. Architecture gate: CLOSED
4. FilterWorkspace implementation batch: NOT AUTHORIZED
5. PR-5M / PR-5O / FilterPanel / list-stack deferrals: UNCHANGED — not reopened
6. Follow-up (separate gates only): PR-5M/O molecule adoption PRs; page-local filter UX on account/public surfaces
```

### 25.5 D-ORG-8 gate closure

| Field | Value |
|-------|--------|
| **Question** | Should FilterWorkspace exist as a canonical organism coordinating filters + results? |
| **Answer** | **No** — use **FilterBar** molecule + **page-level List Pattern** |
| **D-ORG-8 status** | **CLOSED** |
| **Implementation authorized?** | **No** |

---

## 26. Organism Track continuation checkpoint (post D-ORG-8)

**Date:** 2026-10-10  
**Trigger:** Verify FilterWorkspace outcome; execute next **explicitly authorized** organism-track action.

### 26.1 FilterWorkspace status (verified from §25)

| Question | Answer |
|----------|--------|
| Architecture decision closed? | **Yes** — **D-ORG-8 CLOSED**, Outcome **A** |
| Recorded disposition | **REJECTED BY DECISION** — List Pattern = **FilterBar** + page composition |
| Implementation authorized? | **No** |
| Exclusions in force | FilterWorkspace / list-stack / Filter **organisms**; PR-5M/O reopening (§8) |

**Case classification:** **B** — gate closed; **do not implement** FilterWorkspace.

### 26.2 Action selected (2026-10-10 implementation batch)

| Candidate | Authorization | Result |
|-----------|---------------|--------|
| FilterWorkspace code | **Not authorized** | Skipped |
| New §11 organism | **None** (§13) | Skipped |
| **ApplicationTimeline** consumer adoption | **Stage 3 COMPLETE organism** + optional drawer note §832 | **Done** — Submissions/Show + ApplicationDrawer Timeline tab |
| **ApplicantResumeOverview** on Submissions/Show | Same pattern as Employer Applications/Show | **Done** |
| DetailHeader adoption (candidate Applications/Show) | **Optional** — FE-102 | **Done** (§27) |

Focused Vitest (`ApplicationTimeline.test.tsx`), `npm run typecheck`, and `npm run build` recorded in task report.

### 26.3 Canonical organisms (unchanged)

DetailHeader, DocumentGroupList, MatchComparisonTable, ScreeningStagesTable, DetailSection, ApplicantSummaryCard, ApplicantResumeOverview, ApplicationTimeline — all **COMPLETE** per §5.

---

## Stop condition (Organism Track)

**Organism Track CLOSED** for §11 inventory and decisions **D-ORG-1 … D-ORG-8**. **Stage 3+** (2026-10-10): ApplicationTimeline §23; D-ORG-6 §24; D-ORG-8 §25; checkpoint §26.

**Still open outside this track:** **Phase 5 P5-16** (deferred); **OrganizationSwitcher** P2; **DetailHeader** on remaining **SectionHeader** pages (optional per D-ORG-5); **PR-5M/O** molecule adoption (separate gate).

---

## 27. Implementation record — DetailHeader production adoption (Stage 1C)

| Field | Value |
|-------|--------|
| **Consumers** | `Pages/Account/Employer/Submissions/Show.tsx`; `Pages/Account/Employer/Applications/Show.tsx` (B1 / FE-101, 2026-10-10); `Pages/Account/Applications/Show.tsx` (B1 / FE-102, 2026-10-10) |
| **Rationale** | Recruitment Partnership submission detail: requirement context (eyebrow, h1 title, description) + candidate entity row (h2, description, **StatusBadge** meta, profile/requirement **actions**) matches Detail pattern §12 without organism contract changes |
| **Integration** | Employer pages: two **DetailHeader** instances inside existing bordered sections — requirement context (h1) + candidate entity (h2, status meta, actions); submission **dl** below candidate header. Candidate **Applications/Show:** application block (status meta, withdraw action) + job block (`headingLevel={2}`, View job / closed copy); **dl** grids below each header. **AccountLayout** breadcrumbs unchanged on all consumers |
| **Out of scope** | `Components/UI/SectionHeader.tsx` pages; admin surfaces |
| **UI Lab** | `detailHeaderCatalog` compatibility updated |
| **Tests** | Existing `DetailHeader.test.tsx` (2); no new page test in this batch |
| **Validation (2026-10-10)** | Vitest (DetailHeader), `npm run typecheck`, `npm run build`; `EmployerSubmissionTest`; FE-102: `ApplicationExperienceTest` (12) |
| **Browser verification** | **FE-103 (2026-10-10)** — Playwright employer application + submission detail + candidate application mobile; see `docs/OURNEAR_QA_FE103_DETAIL_HEADER_BROWSER_REPORT.md` |
| **Remaining optional adoption** | **SectionHeader** public/account pages — product-scoped PRs only |
