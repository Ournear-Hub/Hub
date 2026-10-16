# OurNear Corporate Library — Phase 6 Search System Contract

**Date:** 2026-10-09  
**Repository:** `D:\Ournear Hub\Ournear Hire`  
**Status:** **Documentation only — no code authorized**

**Phase 6 batch:** Search system contract (**S-1**, **S-2** per [OURNEAR_PHASE_6_UX_SYSTEMS_ARCHITECTURE_CHARTER.md](./OURNEAR_PHASE_6_UX_SYSTEMS_ARCHITECTURE_CHARTER.md)).

**Related contracts:** [Form System](./OURNEAR_PHASE_6_FORM_SYSTEM_CONTRACT.md) · [Data List Pattern](./OURNEAR_PHASE_6_DATA_LIST_PATTERN_CONTRACT.md)

**Evidence baseline (not re-audited):** [OURNEAR_SEARCH_ARCHITECTURE_REVIEW_PR5Y.md](./OURNEAR_SEARCH_ARCHITECTURE_REVIEW_PR5Y.md), [OURNEAR_FILTER_ARCHITECTURE_REVIEW_PR5O.md](./OURNEAR_FILTER_ARCHITECTURE_REVIEW_PR5O.md), [OURNEAR_CORPORATE_LIBRARY_SPECIFICATION_v1.0.md](./OURNEAR_CORPORATE_LIBRARY_SPECIFICATION_v1.0.md) §18, [OURNEAR_CORPORATE_ARCHITECTURE_CLOSURE_PR5AE.md](./OURNEAR_CORPORATE_ARCHITECTURE_CLOSURE_PR5AE.md).

**Decision (frozen):** **SearchField remains COMPOSE-ONLY.** This document does **not** authorize new components, npm dependencies, or V2 migration.

---

## 1. Purpose and scope

This contract defines **Search System** behavior for OurNear Hire at the **UX System** layer (spec §15, §18; Unified Platform v1.1 layer 5): how **search-related UI** is divided between the **canonical SearchField molecule**, **application/page code**, and **Laravel/server** query logic.

**In scope:** Responsibilities, boundaries, workflows (S-1, S-2), accessibility and state ownership where evidenced in V2 and frozen DS.

**Out of scope:** Implementation, PR-6A adoption, global search product delivery, typeahead libraries, new canonical components.

---

## 2. Three-layer model (S-1)

| Layer | Owns | Does not own |
|-------|------|----------------|
| **Design system — SearchField** | Search **input chrome**: `type="search"`, leading search icon, `Input` surfaces (`public` / `admin`), forwarding refs and Input props (`value`, `defaultValue`, `onChange`, `disabled`, `error`, `aria-*`) | Query string, debounce, HTTP, Inertia navigation, results UI, filter facets, permissions |
| **Application (pages, V2 components)** | Form submit handlers, `router.get` / `router.visit`, reading **FormData**, controlled/local state for keyword fields, layout of filter forms, empty/loading UX around navigation, domain copy | Canonical search input styling duplicate (prefer SearchField on adoption) |
| **Product / server (Laravel)** | Authorized datasets, filter parsing, pagination, ranking, saved-search records, validation of query params | React components |

**Server source of truth (Unified v1.1, spec §18):** Filtering, ranking, and result sets for authorized data are **always** server-side. Client-only filtering of protected datasets is **forbidden**.

### 2.1 Canonical SearchField contract

**Location:** `resources/js/Components/molecules/SearchField.tsx` (frozen per PR-3J / PR-5Y).

**Documented intent (code comment):** “Does not own query state, debounce, or results.”

**API summary:**

- Composes **Input** with `type="search"` and decorative **OurNearIcon** (`aria-hidden` on icon wrapper).
- Callers supply labeling via visible **FormField** label and/or **`aria-label`** on the input (UI Lab documents this pattern).
- **Controlled or uncontrolled** value is caller-owned via standard input props.

**Production V2 today:** **No** direct SearchField imports on pages (PR-5Y). Admin uses **`Components/Admin/ui/SearchBar.tsx`** (Input `type="search"` without DS icon). Replacing SearchBar with SearchField is **PR-6A consumer adoption**, not Phase 6 system delivery.

### 2.2 V2 surface components (not canonical DS)

| Component | Role in search workflows |
|-----------|---------------------------|
| **Admin SearchBar** | Named field (`name="search"`) inside filter forms; often `defaultValue` from server `filters` |
| **Admin FilterBar** | `<form onSubmit>` grid shell — holds SearchBar and other fields (PR-5O; **deferred** DS canonicalization) |
| **JobsSearchPanel** | Public job discovery form (keyword, location, expandable filters) — **domain** |
| **Employer*Search** (account) | Applicant/staffing/partnership keyword + status — **domain** |
| **Admin TopBar** | Disabled `type="search"` placeholder — **no** handler (product P2) |

FilterBar is part of **filter/search composition** but is **not** the SearchField and is **not** authorized as a DS export in this batch (see Data List Pattern contract).

### 2.3 URL query persistence (S-1)

**Standard admin pattern (evidenced):**

```text
FilterBar onSubmit → read FormData → Inertia router.get(route, params)
```

- Filter values persist in **URL query string** and/or Inertia props (`filters` from controller) after navigation.
- **SearchField** (or SearchBar) does not write URLs; the **page submit handler** does.

**Account employer searches:** Mix of **local `useState`** synced to URL on submit (PR-5Y) — still page-owned.

### 2.4 SearchBar vs SearchField (S-1)

| Aspect | Admin SearchBar (V2) | DS SearchField |
|--------|----------------------|----------------|
| Visual | Plain admin Input | Icon + Input |
| Semantics | `type="search"`, often `aria-label={placeholder}` | Same input type; icon decorative |
| System role | Field inside filter **form** | Canonical **search control** when a search input is intended |

**Charter line:** Mapping SearchBar → SearchField is **adoption**, not Search System implementation.

---

## 3. Workflow matrix (S-2)

| Workflow | UI composition | Submit / query | Results | SearchField role |
|----------|----------------|----------------|---------|------------------|
| **Admin list filter** | FilterBar + SearchBar + Select/Input + Apply/Reset buttons | Form submit → `router.get`; uncontrolled `defaultValue` from `filters` | **DataTable** + **Pagination** (sibling) — see Data contract | None in V2 today; target **SearchField** `surface="admin"` on PR-6A |
| **Public jobs index** | **JobsSearchPanel** (keyword FormField, selects, mobile `<details>`) | Button → `router.get('/jobs', { keyword, … })` | **JobCard** list, split pane, **Pagination** | Keyword could be SearchField + FormField; today plain input in FormField |
| **Account employer search** | Section heading, active count, Clear all, grid / mobile details | Controlled/local `q` + status → Inertia GET | Domain lists (not admin DataTable) | Optional SearchField for keyword; V2 uses raw `input` in places |
| **Saved searches (Candidates admin)** | Domain chips/pills applying stored filter objects | Server records; click applies filters | Same admin list stack | N/A — **domain CRM**, not DS |
| **Global search (future)** | TopBar placeholder only | **Unresolved — product** | **Unresolved — product** | Would compose SearchField + **route/page**; not GlobalSearch molecule |

**Combobox / MultiSelect:** Option pickers with typing (PR-5X) — **not** full-page server search. Job keyword remains free text → SearchField/Input, not Combobox.

---

## 4. Cross-cutting responsibilities

### 4.1 Accessibility

| Concern | Owner |
|---------|--------|
| Visible label or `aria-label` on search input | **Caller** (FormField label or input prop) |
| Decorative search icon | **SearchField** (`aria-hidden`) |
| Error state on input | **Caller** passes `error` + FormField `error` text |
| Form submit (Enter) | Native **form** behavior — FilterBar/pages |
| Results region landmarks | **Page-local** (e.g. jobs `aria-label="Job results"`) — not a DS SearchResults component |
| `role="search"` landmark | **Not** a repeated V2 requirement (PR-5Y); native form semantics sufficient for filter forms |

### 4.2 Controlled state and submission

| State | Owner |
|-------|--------|
| Input value (admin filters) | Usually **uncontrolled** `defaultValue` from server; re-render on navigation |
| Employer keyword/status | **Local state** + submit sync to URL |
| Submit timing | **Explicit submit** (Apply/Search button) — **no** shared debounced typeahead in V2 |
| `processing` / disabled during visit | **Page** (Inertia visit state), not SearchField |

### 4.3 Loading, empty, error

| Concern | Owner | DS compose |
|---------|--------|------------|
| **Loading** during search/navigation | Inertia **page visit** / page-level UI | **Spinner** / **Skeleton** / full-page patterns — **COMPOSE-ONLY**; not built into SearchField |
| **Empty results** | Page copy + layout | **EmptyState** molecule where appropriate |
| **Validation / flash errors** | Server + Inertia | **Alert**, field errors — not search-specific molecules |
| **Search-specific error API** | **None evidenced** | N/A |

Per [OURNEAR_LOADING_ERROR_STATE_ARCHITECTURE_REVIEW.md](./OURNEAR_LOADING_ERROR_STATE_ARCHITECTURE_REVIEW.md): LoadingState/ErrorState organisms are **COMPOSE-ONLY** — pages compose Spinner/Skeleton/Alert as needed.

---

## 5. Spec §18 requirement map

| Spec §18 item | Contract status |
|---------------|-----------------|
| Global search | **DEFER (P2)** — TopBar placeholder; **NEEDS PRODUCT DECISION** |
| Contextual search — admin SearchBar + URL | **Implemented (V2 pattern)** — page + Inertia; DS input via SearchField on adoption |
| Autocomplete | **DEFER (P2)** — no V2 inline suggestion UI |
| Filters — FilterBar + controller facets | **V2 product shell** (PR-5O deferred DS); see Data contract |
| Saved searches / views | **DEFER (P2)** / **domain** (Candidates) |
| Ranking | **Server** |
| No client-only auth filtering | **Policy** — application/server |

---

## 6. Explicit exclusions

This contract and the Phase 6 doc batch **do not** authorize:

| Exclusion | Notes |
|-----------|--------|
| **GlobalSearch**, **SearchPanel**, **SearchSuggestions**, **SearchResults** molecules | PR-5Y rejected |
| **Debounced search hooks** inside SearchField | Would blur into Combobox/domain |
| **Command palette / Cmd+K** | PR-5L — no DS |
| **New npm search libraries** | Zero in `package.json` today |
| **PR-6A** — migrating SearchBar, JobsSearchPanel internals | Separate product gate |
| **Canonical FilterBar** under “Search System” | FilterBar is **Data/list filter shell** (PR-5O) |
| **Phase 6 code implementation** | Charter §9 |
| **Phase 5 shell implementation**, **Phase 9 Storybook** | Deferred elsewhere |

**S-3 (global search placeholder):** Documented as **product P2** only — requires product spec before any implementation work.

---

## 7. Future code approvals (not granted here)

| Work | Approval needed |
|------|-----------------|
| PR-6A: SearchBar → SearchField on admin/public pages | **PR-6A program** |
| Global search feature (TopBar wired) | **Product spec + implementation authorization** |
| Typeahead / suggestions | **Product + architecture** + V2 evidence of repeated identical UX |
| Changes to frozen **SearchField** API | PR-3–PR-5 governance — **not** Phase 6 |

---

## 8. Verification

| Check | Result |
|-------|--------|
| SearchField decision | **COMPOSE-ONLY** — unchanged |
| New code authorized | **No** |
| Consistent with charter §4.3 | **Yes** |
| PR-5Y reopened | **No** |

---

## Stop condition

Search System **documentation contract** complete. No implementation. Do not start PR-6A from this document alone.
