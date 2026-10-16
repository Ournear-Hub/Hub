# OurNear Corporate Library — Phase 6 Data List Pattern Contract

**Date:** 2026-10-09  
**Repository:** `D:\Ournear Hub\Ournear Hire`  
**Status:** **Documentation only — no code authorized**

**Phase 6 batch:** Data System **List Pattern** and server-side responsibilities (charter §4.2).

**Related contracts:** [Search System](./OURNEAR_PHASE_6_SEARCH_SYSTEM_CONTRACT.md) · [Form System](./OURNEAR_PHASE_6_FORM_SYSTEM_CONTRACT.md)

**Evidence baseline (not re-audited):** [OURNEAR_TABLE_ARCHITECTURE_REVIEW_PR5M.md](./OURNEAR_TABLE_ARCHITECTURE_REVIEW_PR5M.md), [OURNEAR_FILTER_ARCHITECTURE_REVIEW_PR5O.md](./OURNEAR_FILTER_ARCHITECTURE_REVIEW_PR5O.md), [OURNEAR_PAGINATION_REVIEW_PR5C.md](./OURNEAR_PAGINATION_REVIEW_PR5C.md), [OURNEAR_TOOLBAR_ARCHITECTURE_REVIEW_PR5P.md](./OURNEAR_TOOLBAR_ARCHITECTURE_REVIEW_PR5P.md), [OURNEAR_CORPORATE_LIBRARY_SPECIFICATION_v1.0.md](./OURNEAR_CORPORATE_LIBRARY_SPECIFICATION_v1.0.md) §17, [OURNEAR_LOADING_ERROR_STATE_ARCHITECTURE_REVIEW.md](./OURNEAR_LOADING_ERROR_STATE_ARCHITECTURE_REVIEW.md).

**Decision:** **Documentation contract only.** Does **not** implement or authorize **DataTable**, **FilterBar**, **DataGrid**, or PR-6A migration.

**Organism register (2026-10-10):** Spec §11 **FilterWorkspace** is **REJECTED BY DECISION (D-ORG-8)** — this List Pattern document is the authoritative mapping for “filters + results layout”; no `FilterWorkspace` organism. See [OURNEAR_ORGANISM_TRACK_REGISTER.md](./OURNEAR_ORGANISM_TRACK_REGISTER.md) §25.

---

## 1. Purpose and scope

Spec §17 **Data System** spans table/grid behavior, server pagination, and future DataGrid v2. For Phase 6, Hire defines the **List Pattern**: how **pages** compose **presentation**, **filters**, **tables or cards**, and **pagination** while **Laravel** owns query, authorization, and paginator data.

This document is the **anti-accidental-authorization** guard for Phase 6: completing List Pattern docs **must not** be interpreted as shipping canonical `molecules/DataTable.tsx` or `molecules/FilterBar.tsx`.

---

## 2. List Pattern — admin stack (primary)

**Evidenced V2 composition** (PR-5M, PR-5O, PR-5P):

```text
[Page title / optional primary action — inline markup, not Toolbar DS]
  → FilterBar (V2: Components/Admin/ui/FilterBar.tsx)
       children: SearchBar, Select, Input, Button (Apply / Reset)
       onSubmit → FormData → Inertia router.get
  → DataTable (V2: Components/Admin/ui/DataTable.tsx)
       props: columns, rows, rowKey, emptyTitle, optional onRowClick, selectedKey
  → Pagination (V2: Components/Pagination.tsx or DS molecule on adoption)
       props: links from Laravel paginator
  → optional Drawer / workspace (page-owned, triggered from row or query param)
```

**~28–29 admin consumers** of FilterBar / DataTable (PR-5M/O inventories). **Not** a single DS organism — **page coordinates** the stack.

### 2.1 Presentation vs application/server

| Concern | Layer | Owner |
|---------|--------|--------|
| Column definitions, cell render (badges, links, actions) | **Page / feature** | React column `render` functions |
| Row data, total counts, current page | **Server** | Controller + Inertia props |
| Filter param parsing, policy checks | **Server** | Laravel |
| Sort order (if any) | **Server** | No V2 column sort UI (PR-5M) |
| When to show table vs cards | **Product / page** | Admin → table pattern; many Account lists → **cards** |
| Empty copy | **Page** (strings passed to table or EmptyState) | |
| Loading during fetch | **Page / Inertia** | Not inside DataTable component |
| Master–detail selection | **Page** | `selectedKey` + `onRowClick` — not bulk selection |

### 2.2 V2 DataTable scope (product molecule — not DS)

**PR-5M decision:** **TABLE SYSTEM DOCUMENTED — NO COMPONENT** (no DS export).

**Allowed V2 behavior only:**

- Render header row from column defs
- Render body via `render(row)`
- Embedded empty row (`emptyTitle` / `emptyDescription`)
- Optional row click + highlight via `selectedKey`

**Explicitly not in V2 DataTable:** sorting UI, filtering, pagination, bulk checkboxes, expandable rows, in-table skeleton, data fetching.

### 2.3 V2 FilterBar scope (product shell — not DS)

**PR-5O decision:** **FILTER SYSTEM DOCUMENTED — NO COMPONENT**.

**API (V2 only):** `onSubmit`, `children`, optional `className` (grid columns). **No** built-in apply/reset/query props — buttons are **children**.

---

## 3. List Pattern — card-based presentation

**When to compose cards instead of DataTable** (evidenced):

| Surface | Pattern | Pagination |
|---------|---------|------------|
| **Account** employer/hiring job lists, etc. | **Article/card** layouts with actions | `@/Components/Pagination` |
| **Public jobs** | **JobCard** list (+ split pane on desktop) | Pagination sibling |
| **Admin** compare/matrix, MediaSocial, WebsiteContent | **Inline `<table>`** or custom markup | Page-specific |

**PR-5M:** Do **not** migrate Account card lists to DataTable without **product decision**.

**Responsive (spec §17):** Card list fallback for mobile is a **product/layout** choice on pages that use cards; admin tables use **`overflow-x-auto`** horizontal scroll.

---

## 4. Frozen DS molecules in list workflows

| Molecule | Role in List Pattern | Composition rule |
|----------|----------------------|------------------|
| **Pagination** | Page navigation landmark | **Always sibling** below table/cards — **never** embedded in DataTable (PR-5M §10) |
| **EmptyState** | Zero-hit or empty collections | Page or inside table cell at adoption; V2 DataTable uses **Admin/ui/EmptyState** today |
| **Skeleton** | Loading placeholders | Page-level — **not** in V2 DataTable |
| **SearchField** | Filter form text search | Inside filter form — see Search contract |
| **Select / Combobox / …** | Filter fields | Page-owned inside FilterBar children |
| **Dialog / Drawer** | Detail workspace | Page-owned |
| **Alert / flash** | Errors | Not table-specific |

**LoadingState / ErrorState (UI):** **COMPOSE-ONLY** — use Spinner/Skeleton/Alert patterns per loading review; no TableSkeleton authorized here.

---

## 5. Search and filter integration

Filter submit workflows are **Search System + page** owned:

- Admin: FilterBar GET — [OURNEAR_PHASE_6_SEARCH_SYSTEM_CONTRACT.md](./OURNEAR_PHASE_6_SEARCH_SYSTEM_CONTRACT.md) §3 row “Admin list filter”.
- Public/account: domain panels — same contract, not FilterBar.

**Data System does not own** SearchBar, keyword state, or URL building — only documents that **list results** follow server response after navigation.

---

## 6. Spec §17 “extend DataTable v1” — deferral map

| Spec extension | V2 evidence | Phase 6 / charter status |
|----------------|-------------|---------------------------|
| Loading skeleton rows | None in DataTable | **DEFER** — compose Skeleton at page |
| Sort header UI + server sort params | No sort UI | **DEFER** — needs product + backend contract |
| Row selection | `selectedKey` = single row only | **DEFER** bulk/multi-select |
| Bulk action bar slot | No bulk mechanics | **DEFER** |
| **DataGrid v2** (columns, views, virtual scroll) | Not in repo | **DEFER (P2)** — **NEEDS PRODUCT DECISION** |

---

## 7. PR-5M and PR-5O adoption boundaries (reference — not reopened)

Canonical DS molecules **`molecules/DataTable.tsx`** and **`molecules/FilterBar.tsx`** are **explicit adoption PRs**, separate from Phase 6 documentation:

| PR | Trigger for future code | Phase 6 batch |
|----|-------------------------|---------------|
| **PR-5M §18** | Product authorizes adoption; mirror V2 API; compose DS EmptyState; UI Lab route | **Does not satisfy or replace** adoption authorization |
| **PR-5O §17** | Same for FilterBar shell + classNames recipes | **Does not satisfy or replace** adoption authorization |

Until adoption: **`Components/Admin/ui/DataTable.tsx`** and **`FilterBar.tsx`** remain **production source of truth**.

---

## 8. Anti-accidental-authorization checklist

Any future PR touching “Phase 6 Data” or “List Pattern implementation” must satisfy **all**:

- [ ] Title/body does **not** claim “Phase 6 implements DataTable/FilterBar/DataGrid”.
- [ ] No new `DataTable.tsx` / `FilterBar.tsx` under `Components/molecules` without **PR-5M/PR-5O adoption authorization**.
- [ ] No DataGrid features (column visibility, reorder, grouping, saved views, virtual scroll).
- [ ] **Pagination** remains documented and implemented as **sibling** composition.
- [ ] No sorting/bulk/embedded filter APIs added without new V2 evidence + product sign-off.
- [ ] Phase 6 doc completion cited as **governance only**, not implementation gate for molecules.

**This documentation batch:** all boxes satisfied by **not shipping code**.

---

## 9. Explicit exclusions

| Exclusion | Notes |
|-----------|--------|
| Canonical **DataTable**, **FilterBar**, **DataGrid** | PR-5M/O; charter §7 |
| **Table atom** | PR-5M — optional later if matrix layouts need shared semantics |
| **FilterPanel** molecule | PR-5O — zero V2 contract; use `<details>` / Drawer + fields |
| **Toolbar / PageHeader** DS | PR-5P |
| **PR-6A** — Pagination path, Admin/ui swaps | Separate gate |
| **Phase 5 shells**, **Phase 9 Storybook** | Deferred |
| **Type-only shared paginator/filter TypeScript** | Charter optional — **not included** in this batch (no code/files added) |

---

## 10. Unresolved / product decisions

| Topic | Status |
|-------|--------|
| **DataGrid v2** timing and scope | **NEEDS PRODUCT DECISION** |
| **Column sorting** on admin lists | **NEEDS PRODUCT DECISION** + backend sort API |
| **Bulk row actions** | **NEEDS PRODUCT DECISION** — no V2 pattern |
| **Converge JobsSearchPanel / account searches with FilterBar** | **NEEDS PRODUCT DECISION** — PR-5O kept domain separate |
| **Shared TS types for paginator/filters props** | Optional future doc/code — not in this batch |

---

## 11. Future code approvals (not granted here)

| Work | Approval needed |
|------|-----------------|
| DS DataTable molecule + V2 import migration | **PR-5M adoption authorization** |
| DS FilterBar molecule + migration | **PR-5O adoption authorization** |
| PR-6A list page field/table swaps | **PR-6A program** |
| DataGrid v2 | **Product + architecture** |
| Sort/bulk/in-table loading | **Product + evidence PR** |

---

## 12. Verification

| Check | Result |
|-------|--------|
| PR-5M / PR-5O reopened | **No** |
| DataTable/FilterBar implementation authorized | **No** |
| List Pattern + server split documented | **Yes** |
| Charter §4.2 acceptance criteria | **Yes** (doc track) |
| Application code changed | **No** (this task) |

---

## Stop condition

Data List Pattern **documentation contract** complete. No data-display implementation. Do not start PR-6A or PR-5M/O adoption from this document alone.
