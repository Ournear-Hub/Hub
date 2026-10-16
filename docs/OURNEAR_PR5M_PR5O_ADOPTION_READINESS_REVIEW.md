# OurNear Hire — PR-5M / PR-5O adoption-readiness review

**Date:** 2026-10-09  
**Repository:** `D:\Ournear Hub\Ournear Hire`  
**Mode:** Planning and review only — **not implementation authorization**

**Purpose:** Determine whether **PR-5M (DataTable)** and **PR-5O (FilterBar)** have sufficient evidence for a **separately authorized adoption PR**, define **bounded scope** if so, and list **unresolved prerequisites**.

---

## 1. Executive decision summary

| Workstream | Readiness outcome | One-line rationale |
|------------|-------------------|--------------------|
| **PR-5M — DataTable** | **READY FOR SEPARATE ADOPTION AUTHORIZATION** | Single stable V2 molecule API, **29** verified admin consumers, behavior scope is narrow and documented; adoption can **mirror** existing implementation with DS **EmptyState** + **classNames** — no redesign required. |
| **PR-5O — FilterBar** | **READY FOR SEPARATE ADOPTION AUTHORIZATION** | Minimal V2 shell API, **28** verified admin consumers, **`className` grid overrides** evidenced; adoption can **lift** layout unchanged; **does not** require JobsSearchPanel or account search convergence. |

**Joint statement:** Both workstreams are **independent**. Either, neither, or both may receive **explicit adoption authorization**; readiness **does not** permit implementation, PR-6A, Phase 6 code, or consumer migration without that authorization.

**Recommended sequencing (if product approves both):** **FilterBar adoption PR first or in parallel with DataTable “introduce molecule” PR** — FilterBar has no EmptyState coupling and lower visual risk; **consumer import migration** may be one or two PRs per component per PR-5M/PR-5O §18/§17 step 6.

---

## 2. Authoritative prior decisions (not reopened)

| Record | Binding outcome |
|--------|-----------------|
| [OURNEAR_TABLE_ARCHITECTURE_REVIEW_PR5M.md](./OURNEAR_TABLE_ARCHITECTURE_REVIEW_PR5M.md) | **No DS DataTable** in architecture phase; V2 **`Admin/ui/DataTable.tsx`** is product **molecule-level** list renderer; **organism rejected**; adoption boundary **§18** |
| [OURNEAR_FILTER_ARCHITECTURE_REVIEW_PR5O.md](./OURNEAR_FILTER_ARCHITECTURE_REVIEW_PR5O.md) | **No DS FilterBar** in architecture phase; V2 **`Admin/ui/FilterBar.tsx`** is presentation shell; **FilterPanel** not warranted; adoption boundary **§17** |
| [OURNEAR_CORPORATE_ARCHITECTURE_CLOSURE_PR5AE.md](./OURNEAR_CORPORATE_ARCHITECTURE_CLOSURE_PR5AE.md) | Admin **DataTable** / **FilterBar** are **intentional V2 surface**, not missing DS bugs |
| [OURNEAR_PHASE_6_DOCUMENTATION_CLOSURE_GATE.md](./OURNEAR_PHASE_6_DOCUMENTATION_CLOSURE_GATE.md) | Phase 6 docs **closed**; **DataTable/FilterBar implementation not authorized** by Phase 6 |
| [OURNEAR_PHASE_6_DATA_LIST_PATTERN_CONTRACT.md](./OURNEAR_PHASE_6_DATA_LIST_PATTERN_CONTRACT.md) | List Pattern + **anti-accidental-authorization** checklist; PR-5M/PR-5O adoption gates **by reference** |
| [OURNEAR_CORPORATE_LIBRARY_SPECIFICATION_v1.0.md](./OURNEAR_CORPORATE_LIBRARY_SPECIFICATION_v1.0.md) §17, §10 | Spec names DataTable/FilterBar — **Hire V2 + PR-5M/O** govern adoption, not Phase 6 |

PR-5Z, PR-5AE atom/molecule freeze, Phase 5 shell deferrals, Phase 9 deferrals, and **PR-6A** separation remain unchanged.

---

## 3. PR-5M evidence summary — DataTable

### 3.1 Implementation location and responsibilities

| Item | Evidence |
|------|----------|
| **Path** | `resources/js/Components/Admin/ui/DataTable.tsx` |
| **Export** | `Components/Admin/ui/index.ts` — `DataTable`, `DataTableColumn<T>` |
| **Responsibility** | **Presentational** column-definition table: `<thead>` from columns, `<tbody>` from `rows`, embedded empty row, optional row click + highlight |

**Not in component (page / server):** data fetching, filters, sort, pagination, bulk actions, loading skeleton rows, authorization.

### 3.2 Consumers (verified 2026-10-09)

**Method:** `Get-ChildItem -Recurse` + `Select-String "<DataTable"` on `resources/js/**/*.tsx`, excluding `DataTable.tsx` and ui-lab.

**Count:** **29** unique files.

**Paths:**

- `Components/Admin/ApplicationWorkspacePage.tsx`
- `Pages/Admin/ActivityLogs/Index.tsx`
- `Pages/Admin/Analytics/Index.tsx`
- `Pages/Admin/Automations/Index.tsx`
- `Pages/Admin/Campaigns/Index.tsx`
- `Pages/Admin/Candidates/Index.tsx`
- `Pages/Admin/Commission/Index.tsx`
- `Pages/Admin/Crm/Index.tsx`
- `Pages/Admin/Employees/Index.tsx`
- `Pages/Admin/EmployerEnquiries/Index.tsx`
- `Pages/Admin/Employers/Index.tsx`
- `Pages/Admin/Finance/Index.tsx`
- `Pages/Admin/Freelancers/Index.tsx`
- `Pages/Admin/Interviews/Index.tsx`
- `Pages/Admin/Investors/Index.tsx`
- `Pages/Admin/Jobs/Index.tsx`
- `Pages/Admin/LoginLogs/Index.tsx`
- `Pages/Admin/MasterData/Index.tsx`
- `Pages/Admin/Messages/Index.tsx`
- `Pages/Admin/Notifications/Index.tsx`
- `Pages/Admin/Partners/Index.tsx`
- `Pages/Admin/Placements/Index.tsx`
- `Pages/Admin/Replacements/Index.tsx`
- `Pages/Admin/Roles/Index.tsx`
- `Pages/Admin/Staff/Index.tsx`
- `Pages/Admin/Submissions/Index.tsx`
- `Pages/Admin/Tasks/Index.tsx`
- `Pages/Admin/Users/Activity.tsx`
- `Pages/Admin/Users/Index.tsx`

**Asymmetric usage (same file may lack FilterBar):** `Automations/Index.tsx`, `Roles/Index.tsx` use **DataTable without FilterBar** in-file — valid; list pattern does not require filters on every table page.

Prior review cited ~29 consumers — **consistent**.

### 3.3 Supported behavior matrix

| Capability | In V2 DataTable? | Owner if absent |
|------------|------------------|-----------------|
| Column defs (`key`, `header`, `render`, optional `className` on `<td>`) | **Yes** | — |
| Row identity `rowKey` | **Yes** | — |
| Empty title / optional description | **Yes** — via **Admin/ui/EmptyState** in cell | Copy from caller props |
| **Loading** | **No** | Page / Inertia |
| **Pagination** | **No** | Sibling **Pagination** component + server paginator |
| **Filtering / sort** | **No** | FilterBar + server (PR-5O) |
| **Responsive** | **Yes** — wrapper `overflow-x-auto` | Horizontal scroll |
| **Selection / actions** | **Partial** — `onRowClick`, `selectedKey` (single-row highlight) | **Not** bulk checkboxes (PR-5M) |
| **Accessibility** | **Partial** — native `<table>`, `<th>` without `scope` observed; row `onClick` on `<tr>` **without** keyboard parity | Page often duplicates actions in columns; **enhancement not required for adoption parity** |

**Repo search (indicative):** `onRowClick` **34** matches; `selectedKey` **31** matches in `resources/js` (includes non-DataTable contexts) — consistent with master–detail drawer pattern per PR-5M.

### 3.4 Presentational vs page / Inertia / backend

| Concern | Layer |
|---------|--------|
| Column cells (badges, links, buttons) | **Page** `render` |
| `rows`, paginator | **Server** → Inertia props |
| `onRowClick`, `selectedKey`, drawer/query param | **Page** |
| Table shell, empty row layout | **DataTable** |
| Empty copy strings | **Page** props |

### 3.5 Consumer differences (canonicalization risk)

| Risk | Severity | Mitigation in bounded adoption |
|------|----------|--------------------------------|
| Column `render` diversity | **Low** for component API | API unchanged; pages keep custom renders |
| **Inline `<table>`** elsewhere (MediaSocial, WebsiteContent, compare matrix) | **Out of scope** | PR-5M — do not force migration |
| **Admin/ui/EmptyState** vs **molecules/EmptyState** | **Medium (visual)** | Adoption uses DS EmptyState **`size="sm"`** (or documented compact props) — **acceptance: no layout break in cell** |
| Row click a11y gap | **Low for parity PR** | Document known gap; do not expand scope to grid semantics |
| Account/public **card lists** | **N/A** | Explicit exclusion — no DataTable adoption |

No evidence that consumers rely on **undocumented** DataTable props beyond the typed API.

### 3.6 Reuse without redesign

**Yes.** PR-5M §18: add `Components/molecules/DataTable.tsx` **mirroring** V2 props; move classes to `design-system/classNames.ts` (**not present today** — verified no `dataTable*` / `filterBar*` in `resources/js/design-system/classNames.ts` at review time). Replace Admin EmptyState import with **molecules/EmptyState** inside DS DataTable only.

**Duplication strategy:** Keep `Admin/ui/DataTable.tsx` as re-export or unchanged until import migration PR (PR-5M §18.6).

---

## 4. PR-5O evidence summary — FilterBar

### 4.1 Implementation location and responsibilities

| Item | Evidence |
|------|----------|
| **Path** | `resources/js/Components/Admin/ui/FilterBar.tsx` |
| **Export** | `Components/Admin/ui/index.ts` |
| **Responsibility** | **Presentation only:** native `<form onSubmit>`, grid container, **`children` only** |

### 4.2 Consumers (verified 2026-10-09)

**Count:** **28** unique files (excluding `FilterBar.tsx`).

**Paths:** Same admin index set as PR-5O review, plus **`Pages/Admin/Hiring/CommandCenter.tsx`**; **minus** `Automations/Index.tsx`, `Roles/Index.tsx` (no FilterBar in file).

Prior review ~28 `<FilterBar` JSX — **consistent**.

### 4.3 Supported behavior matrix

| Capability | In V2 FilterBar? | Owner |
|------------|------------------|--------|
| Submit handler | **Prop `onSubmit`** | **Page** — typically `preventDefault`, FormData, `router.get` |
| Fields (SearchBar, Select, Input, buttons) | **children** | **Page** |
| Grid layout / border / padding | **Yes** — default `md:grid-cols-4` | Component |
| Column override | **Yes** — `className` e.g. `md:grid-cols-6`, `xl:grid-cols-6`, `md:grid-cols-3` | **Verified** on multiple Index pages + CommandCenter |
| URL / query sync | **No** | **Page** Inertia navigation |
| Reset / Apply buttons | **No** — always children | **Page** |
| **Responsive** | CSS grid wrap | Component + page field layout |
| **Accessibility** | Native form submit | Field-level labels vary (SearchBar placeholder as aria-label) — **field adoption (PR-6A), not FilterBar shell** |

### 4.4 Presentational vs page / URL / backend

| Concern | Layer |
|---------|--------|
| Filter param names and values | **Page** + **server** |
| `router.get` / `visit({}, true)` reset | **Page** |
| Visual shell | **FilterBar** |

### 4.5 Consumer differences (convergence)

| Surface | Uses FilterBar? | Notes |
|---------|-----------------|-------|
| Admin indexes + CommandCenter + ApplicationWorkspacePage | **Yes** | Shared shell |
| **JobsSearchPanel** (public) | **No** | Domain — PR-5O §17.8 |
| **Account employer searches** | **No** | Domain — controlled state, `<details>` mobile |

**Filter-UI convergence across admin / jobs / account is not a product decision** — adoption must **not** force JobsSearchPanel or account components into FilterBar.

### 4.6 Reuse without redesign

**Yes.** ~18 lines; adoption = same API + `filterBar*` classNames (PR-5O §17). **SearchBar → SearchField** is **separate** (PR-6A / field track), not required for FilterBar shell adoption.

---

## 5. Readiness decisions

### 5.1 PR-5M — **READY FOR SEPARATE ADOPTION AUTHORIZATION**

**Evidence:** Stable typed API, high reuse, narrow scope, explicit adoption checklist already in PR-5M §18, no contradictory consumer props, List Pattern documented in Phase 6.

**Not chosen:**

- **CONDITIONALLY READY** — EmptyState-in-cell is a **defined adoption acceptance** item, not missing evidence.
- **NOT READY** — evidence sufficient for bounded PR.
- **NO ADOPTION NEEDED** — contradicts PR-5M deferral to explicit adoption and PR-5AE surface strategy.

### 5.2 PR-5O — **READY FOR SEPARATE ADOPTION AUTHORIZATION**

**Evidence:** Identical API across consumers, override pattern stable, no FilterPanel, admin-only scope clear.

**Not chosen:** **NOT READY** / **NO ADOPTION NEEDED** — same reasoning as PR-5M.

---

## 6. Proposed bounded adoption PR scope and acceptance criteria

### 6.1 PR-5M — suggested two-phase boundary

**Phase M-A — Introduce canonical molecule (no V2 import switch required in same PR if risky)**

| In scope | Out of scope |
|----------|--------------|
| Add `resources/js/Components/molecules/DataTable.tsx` — **same public API** as V2 | Sort headers, filters, pagination inside table |
| Export `DataTableColumn<T>` from `molecules/index.ts` | DataGrid v2 |
| Add `dataTable*` recipes to `design-system/classNames.ts` | Migrate Account lists |
| Compose **molecules/EmptyState** for empty row (`size="sm"` or agreed compact) | Change row click keyboard model |
| UI Lab section + route per PR-5M §18.7 | Inline custom tables migration |
| Unit/visual check in lab only | PR-6A bulk page migration |

**Measurable acceptance (M-A):**

1. TypeScript: molecule props **structurally equal** to V2 `DataTableProps` / columns type.
2. Visual parity: table shell classes match V2 (border, thead bg, row hover/selected).
3. Empty row: DS EmptyState renders in colspan cell without overflow/clipping regressions in lab fixture.
4. **No** new features beyond V2 API.
5. Frozen molecules **unchanged** except new export; **Admin/ui/DataTable.tsx** still default for all 29 consumers until M-B.

**Phase M-B — Consumer migration (requires authorization; may be PR-6A or dedicated adoption PR)**

| In scope | Out of scope |
|----------|--------------|
| Switch admin imports from `@/Components/Admin/ui` to `@/Components/molecules` (or re-export shim) | Backend/query changes |
| Optional: `Admin/ui/DataTable` re-exports molecule then deprecate | Account/public surfaces |

**Measurable acceptance (M-B):**

1. All **29** consumer files render unchanged behavior (smoke/manual or existing e2e if any).
2. **Pagination** remains sibling — no import moved into DataTable.
3. `selectedKey` / `onRowClick` drawer flows unchanged on representative pages (e.g. Jobs, Candidates).

### 6.2 PR-5O — suggested two-phase boundary

**Phase O-A — Introduce canonical molecule**

| In scope | Out of scope |
|----------|--------------|
| Add `resources/js/Components/molecules/FilterBar.tsx` — API: `onSubmit`, `children`, `className?` | `filters` / `onReset` / chip slots props |
| `filterBarRoot` / grid classes in `design-system/classNames.ts` | FilterPanel molecule |
| Export from `molecules/index.ts` | JobsSearchPanel / account search refactors |
| UI Lab section per PR-5O §17.7 | SearchBar → SearchField in same PR |

**Measurable acceptance (O-A):**

1. Default classes match V2: `mb-6 grid gap-3 rounded-xl border border-line bg-white p-4 md:grid-cols-4` + merged `className`.
2. Lab demonstrates `md:grid-cols-6` override (Jobs-scale grid).
3. Native form submit still delegates to `onSubmit` only.

**Phase O-B — Migrate 28 admin consumers**

**Measurable acceptance (O-B):**

1. All **28** FilterBar call sites behave identically (submit → same Inertia GET).
2. **CommandCenter** `md:grid-cols-3` override preserved.
3. Pages **without** FilterBar (Automations, Roles) untouched.

---

## 7. Compatibility risks and unresolved questions

| ID | Topic | Workstream | Status |
|----|--------|------------|--------|
| R1 | **EmptyState visual change** in table empty cell when switching Admin/ui → DS | PR-5M | Resolve in M-A lab review; product **ack** if copy/spacing visible to admins |
| R2 | **Dual export period** — Admin/ui vs molecules importers | Both | Architecture: re-export shim vs big-bang M-B/O-B |
| R3 | **UI Lab registration** — `DesignSystem/Show.tsx`, `ui-lab/metadata.ts` | Both | Required for M-A/O-A; touches app **only when authorized** — not part of this review task |
| R4 | **Pagination** still `@/Components/Pagination` on admin pages | PR-5M | PR-6A overlap — sequence with product |
| R5 | **Row `<tr onClick>` keyboard** | PR-5M | Document gap; **not** adoption blocker per PR-5M |
| R6 | **EmployerEnquiries** single Filter button vs Apply+Reset | PR-5O | Page-owned children — no FilterBar API change |
| R7 | **Th `scope` attributes** | PR-5M | Optional enhancement — exclude from adoption unless product asks |

**Missing evidence (not guessed):** Automated visual regression coverage for 29 tables — **unverified**; manual smoke assumed unless CI exists.

---

## 8. Product and architecture approval gates

| Gate | Required before |
|------|-----------------|
| **“PR-5M adoption authorized”** (M-A, optionally M-B scope) | Any DataTable molecule or classNames code |
| **“PR-5O adoption authorized”** (O-A, optionally O-B scope) | Any FilterBar molecule or classNames code |
| **EmptyState-in-cell treatment sign-off** | M-A merge if visual delta non-trivial |
| **Migration batch authorization (M-B / O-B)** | Changing any of 29/28 consumer imports |
| **PR-6A program** | Broader V2→DS field/shell migration (orthogonal) |
| **Phase 6 implementation** | Unrelated — remains blocked per Phase 6 closure gate |

---

## 9. Exclusions and cross-phase boundaries

| Exclusion | Applies to |
|-----------|------------|
| DataTable **organism** (filters + table + pagination) | PR-5M |
| Sort, bulk select, in-table loading | PR-5M |
| DataGrid v2 | Spec P2 — not adoption |
| FilterPanel; admin/account/public **convergence** | PR-5O |
| SearchBar → SearchField | PR-6A / Search contract |
| Phase 5 shells, Phase 9 Storybook | Deferred |
| Phase 6 doc closure **≠** adoption auth | Both |

---

## 10. Authorization matrix — readiness vs permission

| Question | PR-5M DataTable | PR-5O FilterBar |
|----------|-----------------|-----------------|
| **Readiness outcome** | **READY FOR SEPARATE ADOPTION AUTHORIZATION** | **READY FOR SEPARATE ADOPTION AUTHORIZATION** |
| **Evidence sufficient for bounded PR?** | **Yes** | **Yes** |
| **Implementation authorized by this review?** | **No** | **No** |
| **Phase 6 / doc closure authorizes?** | **No** | **No** |
| **Next step** | Product + architecture **“PR-5M adoption authorized”** (scope M-A / M-B) | Product + architecture **“PR-5O adoption authorized”** (scope O-A / O-B) |

---

## 11. Comparison table (joint)

| Dimension | PR-5M DataTable | PR-5O FilterBar |
|-----------|-----------------|-----------------|
| **Readiness** | READY FOR SEPARATE ADOPTION AUTHORIZATION | READY FOR SEPARATE ADOPTION AUTHORIZATION |
| **V2 implementation** | `Components/Admin/ui/DataTable.tsx` | `Components/Admin/ui/FilterBar.tsx` |
| **Consumer count (verified)** | **29** TSX files | **28** TSX files |
| **Proposed PR boundary** | **M-A:** molecule + classNames + EmptyState + lab; **M-B:** migrate imports | **O-A:** molecule + classNames + lab; **O-B:** migrate imports |
| **Prerequisites** | classNames recipes; EmptyState sm in cell; UI Lab wiring when coding | classNames recipes; UI Lab wiring when coding |
| **Product/architecture approvals** | Explicit **PR-5M adoption** + optional M-B; EmptyState ack | Explicit **PR-5O adoption** + optional O-B |
| **Explicitly excluded** | Organism, sort, bulk, pagination inside table, account cards, inline tables | FilterPanel, query props, Jobs/account convergence, SearchField swap |

---

## 12. Verification (this review task)

| Check | Result |
|-------|--------|
| Application code modified | **No** |
| PR-5M / PR-5O / PR-5AE decisions reopened | **No** |
| Implementation started | **No** |
| Commit / push / deploy | **No** |

---

## 13. Adoption authorization log (dated decisions)

### 2026-10-09 — PR-5O O-B consumer migration

| Item | Record |
|------|--------|
| **PR-5O O-B** | **Explicitly authorized and executed** — migrate **28** admin production consumers to `@/Components/molecules/FilterBar`. |
| **PR-6A, Phase 5 shell implementation, Phase 9** | **Not authorized** by this decision. |

### 2026-10-09 — PR-5M M-B consumer migration

| Item | Record |
|------|--------|
| **Product acknowledgment (DataTable empty row)** | Visual delta accepted for migration: canonical **EmptyState** in table cells — **`py-6` vs V2 `py-10`**, title **`font-semibold` vs `font-medium`**. |
| **PR-5M M-B** | **Explicitly authorized and executed** — migrate **29** admin production consumers to `@/Components/molecules/DataTable`. |
| **Legacy V2 modules** | `Components/Admin/ui/DataTable.tsx` and `FilterBar.tsx` **retained unchanged** (no re-export shim); not removed from `Admin/ui` barrel. |
| **PR-6A, Phase 5 shell implementation, Phase 9** | **Not authorized**. |

Prior sections (readiness outcomes, PR-5M/PR-5O architecture decisions) remain unchanged.

---

## Stop condition

Adoption-readiness review **complete**. **PR-5M M-A/M-B** and **PR-5O O-A/O-B** foundation + admin consumer migrations recorded in §13. Further V2→DS work (**PR-6A**, remaining `Admin/ui` fields, Pagination path, etc.) requires **separate explicit authorization**.
