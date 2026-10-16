# OURNEAR Table / DataTable architecture review — PR-5M

**Status:** Review complete — **no canonical Table/DataTable component created** (architecture phase — **V2 not modified**)

## 1. Decision

**TABLE SYSTEM DOCUMENTED — NO COMPONENT**

No `Table.tsx`, `DataTable.tsx`, UI Lab route, molecule exports, or table-specific design tokens in this PR.

## 2. Primary architectural question (A–E)

| Option | Assessment |
|--------|------------|
| **A — Table atom/molecule primitive** | V2 has **no** standalone semantic `Table` wrapper. Markup lives inside `Admin/ui/DataTable.tsx` or is **page-local** `<table>`. Insufficient evidence for a separate low-level Table atom today. |
| **B — DataTable molecule** | V2 **`Admin/ui/DataTable.tsx`** is a **small, stable, column-definition list renderer** (~29 admin consumers). Complexity fits **molecule** level, **not** organism. **Deferred** to explicit V2 adoption phase (see §7) to avoid duplicating the live product component during architecture-only work. |
| **C — DataTable organism** | **Rejected.** Toolbar, filters, server query, pagination, drawers, and domain actions are **page-owned composition**, not a single coordinated V2 component. |
| **D — Product / table UI system** | **Partially evidenced.** Admin index pages repeat **FilterBar → DataTable → Pagination → (optional) Drawer** with Inertia/server paginator data. This is **documented as a product pattern**, not canonicalized as one DS organism in PR-5M. |
| **E — No component at this stage** | **Selected** (same as decision above). |

**Final classification:** **Domain-biased admin list table product component exists in V2; DS canonical molecule deferred; not a DataTable framework; not an organism.**

## 3. Evidence inspected (read-only)

**Search method:** PowerShell `Get-ChildItem -Recurse` + `Select-String` on `D:\Ournear Hub\Ournear Hire\resources\js`.

| Source | Finding |
|--------|---------|
| `Components/Admin/ui/DataTable.tsx` | Sole reusable columnar table implementation. Native `<table>`, caller-defined `columns` / `rows`, embedded empty row, optional `onRowClick` + `selectedKey` highlight. |
| `Components/Admin/ui/index.ts` | Exports `DataTable` and `DataTableColumn<T>`. |
| **29 files** with `<DataTable` JSX | Admin index/workspace list surfaces (Users, Jobs, Candidates, Employers, Finance, Staff, Submissions, etc.) + `ApplicationWorkspacePage.tsx`. |
| Native `<table>` (excluding DataTable internals) | `Pages/Admin/MediaSocial/Index.tsx`, `Pages/Admin/WebsiteContent/Index.tsx`, `Components/Admin/ApplicationWorkspacePage.tsx` (candidate compare matrix), `Components/Admin/ApplicationDrawer.tsx` (2 tables), `ui-lab/sections/SkeletonLabSection.tsx` (demo markup). |
| `Pages/Account/**` | **No** `DataTable`. Lists use **card/article** layouts (e.g. `Account/Employer/Jobs/Index.tsx`) with `@/Components/Pagination` only. |
| Sorting (`sortable`, `aria-sort`, `sort_by`, etc.) | **0** meaningful table sorting implementations in `resources/js`. |
| Bulk selection (`selectedRows`, row checkboxes, bulk actions) | **0** reusable bulk-table mechanics. `selectedKey` = **single-row master–detail** selection, not multi-select. |
| Expandable data rows | **0** stable expandable-row table contract (`isExpanded` / details rows in list tables). |
| `role="grid"` | **0** in Ournear Hire app TSX. |
| UI Lab | **No** existing table/DataTable lab section. |
| DS molecules | **Pagination**, **EmptyState**, **Skeleton**, **SearchField**, **Dialog**, **Drawer** frozen — unchanged in this PR. |

## 4. Table implementation inventory

| Pattern | Where | Reuse | Complexity | Verdict |
|---------|--------|------:|------------|---------|
| Column-definition list table | `Admin/ui/DataTable.tsx` | ~28 consumers + self | Low (presentation + empty + optional row click) | **V2 product molecule** — future DS canonical target |
| Admin index page stack | Most `Pages/Admin/*/Index.tsx` | High (pattern) | Medium (page coordinates filters + Inertia + drawer) | **Product composition** — not one DS component |
| Inline admin data table | MediaSocial, WebsiteContent | 2 pages | Low–medium (custom columns/actions) | **Page-local** — duplicate shell classes |
| Compare / matrix table | `ApplicationWorkspacePage.tsx` | 1 | Medium (sticky headers, row labels) | **Domain-specific** — not DataTable |
| Drawer detail tables | `ApplicationDrawer.tsx` | 1 | Low | **Contextual** — not shared primitive |
| Account list UIs | Account employer/hiring pages | Many | Medium (cards + actions) | **Different contract** — no shared table with admin |
| Skeleton demo table | UI Lab | 1 | N/A | **Documentation only** |

## 5. Simple Table vs DataTable

### A — Simple semantic Table

V2 uses native `<table>`, `<thead>`, `<tbody>`, `<tr>`, `<th>`, `<td>` with Tailwind utility classes. No shared Table atom. Visual shell often repeats:

`overflow-x-auto rounded-xl border border-line bg-white` + `min-w-full text-left text-sm`.

### B — DataTable (industry sense)

V2 **does not** implement a full DataTable system. There is **no** evidence of:

- column sorting UI or state
- integrated filtering (filters are **FilterBar** / forms above the table)
- embedded pagination ( **Pagination** is always sibling composition)
- row checkbox selection / bulk actions
- expandable rows
- loading skeleton rows inside the table component
- client-side table state manager / TanStack Table / column registry

The V2 name **`DataTable`** denotes a **column-rendered admin list table**, not a spreadsheet-style data grid framework.

## 6. Molecule vs organism reasoning

**Molecule (future):** `DataTable` scope in V2 is limited to:

- render header row from column defs
- render body rows via `render(row)`
- show empty state when `rows.length === 0`
- optional click-to-select row styling via `selectedKey` + `onRowClick`

That is appropriate **molecule** complexity if canonicalized later.

**Organism (rejected now):** Admin pages additionally coordinate:

- `FilterBar` + `SearchBar` / `Select` / `Input` (server-side query via Inertia `router.get`)
- Laravel paginator `data` + `links` passed as props
- `Pagination` below the table
- `Drawer` / detail workspace opened from row click or query param
- Domain badges, links, API mutations in column `render` functions

No V2 component owns that full stack. Creating an organism would invent a table **application framework**, which this PR explicitly forbids.

## 7. Why no DS component in PR-5M

1. **Live product canonical already exists** at `Components/Admin/ui/DataTable.tsx` with broad admin adoption. Architecture records the contract; **physical DS extraction waits for an explicit adoption PR** (same boundary as “no V2 migration here”).
2. **Not a universal OurNear table system** — Account/public surfaces do not share the admin DataTable contract.
3. **Inline table duplication** (MediaSocial, WebsiteContent, compare matrix) shows markup specializations that a single generic molecule would not replace without migration design.
4. **EmptyState split** — V2 DataTable embeds `Admin/ui/EmptyState` (minimal title/description). DS **`EmptyState` molecule** is richer (icon, actions, sizes). Canonical table should compose DS EmptyState at adoption, not freeze Admin/ui copy in parallel.
5. **Pagination split** — Admin pages import both `@/Components/Pagination` (legacy) and (in some newer paths) `@/Components/molecules/Pagination`. Table architecture must prefer **composition** with frozen DS Pagination; duplicating a DS DataTable now would not resolve that split.
6. **No sort/selection/bulk framework** — Creating `molecules/DataTable.tsx` in the lab without V2 switch would add a second source of truth with zero production consumers.

## 8. Sorting findings

- **No** client-side column sort.
- **No** `aria-sort` or sortable header controls.
- **No** shared sort query helpers in frontend table code.
- Listing order is **server-defined** (Laravel/Inertia props). Callers own sort semantics via backend/query if added later.

## 9. Filtering findings

- Table filtering is **page-local** **`FilterBar`** forms (`SearchBar`, `Select`, `Input`, etc.).
- Filters submit via Inertia navigation; **SearchField** / field molecules apply at adoption, not embedded in DataTable.
- **No** `TableFilter` molecule warranted.

## 10. Pagination relationship

- V2 pattern: `<DataTable … />` then `<Pagination links={paginator.links} />` (sibling).
- **Caller owns** paginator data and links.
- Frozen **`Components/molecules/Pagination.tsx`** must remain **composed outside** any future DataTable — do not embed unless V2 proves otherwise (it does not).

## 11. EmptyState relationship

- V2 DataTable empty rows use **`Admin/ui/EmptyState`** inside a single `colSpan` cell.
- Domain-specific copy via `emptyTitle` / `emptyDescription` props (caller-owned strings).
- Frozen **`Components/molecules/EmptyState`** should replace Admin/ui empty presentation at adoption (likely `size="sm"` / compact layout in table cell).
- **No** `TableEmptyState` molecule.

## 12. Loading relationship

- **No** standard in-table skeleton or loading overlay in V2 DataTable.
- Pages rely on Inertia visits / full-page or local UI state; **Skeleton** molecule exists but is **not** integrated into table rendering.
- **No** `TableSkeleton` unless independently justified later.

## 13. Selection / bulk action findings

- **`selectedKey` + `onRowClick`**: master–detail pattern (open drawer / set query param). ~34 `onRowClick` references; ~31 `selectedKey` — aligned with drawer selection, **not** bulk ops.
- **No** row checkboxes, select-all, or bulk delete/approve/export table mechanics.
- Bulk workflows remain **domain/product** behavior in column actions or separate flows.

## 14. Expandable-row findings

- **No** repeated expandable list-row pattern in admin tables.
- PR-5J: **Disclosure not created**; **Accordion** frozen — unrelated to data tables here.

## 15. Responsive findings

- Primary responsive strategy: **`overflow-x-auto`** horizontal scroll on table wrapper (DataTable and inline tables).
- Compare matrix uses **sticky** first column — specialized layout.
- Account uses **stacked cards**, not responsive table transforms.
- Do not force responsive column hiding into a generic primitive without stable V2 evidence.

## 16. Accessibility findings

- **Native table semantics** (no `role="grid"`).
- Headers via `<th>`; **no** `scope` attributes observed in DataTable (enhancement candidate at adoption).
- **No** `aria-sort` (no sorting).
- Row click uses `<tr onClick>` — **not** keyboard-equivalent row activation in V2; master–detail often duplicated via explicit action buttons in columns. DS adoption should document keyboard gaps; do not invent grid semantics.
- Empty state: text in cell via EmptyState component — no `caption` element on tables.

## 17. Relationship to frozen components

| Component | Role relative to tables |
|-----------|-------------------------|
| **SearchField** | Filter/query in FilterBar — not inside DataTable |
| **Combobox / MultiSelect / Select** | Filter fields — page-owned |
| **Pagination** | Composed below table — caller-owned links |
| **EmptyState** | Zero-row content — compose inside table at adoption |
| **Skeleton** | Optional page loading — not part of V2 DataTable |
| **Dialog / Drawer** | Detail workspace — composed by page, triggered from row |
| **Accordion** | Unrelated to list tables |

## 18. Future adoption boundary (explicit)

When a dedicated adoption PR is approved:

1. Add **`resources/js/Components/molecules/DataTable.tsx`** mirroring V2 API (evidence-minimal):

   - `columns: DataTableColumn<T>[]` (`key`, `header`, `render`, optional `className`)
   - `rows: T[]`
   - `rowKey: (row: T) => string | number`
   - `emptyTitle`, optional `emptyDescription`
   - optional `onRowClick`, optional `selectedKey`

2. Move visual shell classes to **`design-system/classNames.ts`** (`dataTable*` recipes).

3. Compose frozen **`EmptyState`** for empty rows (replace Admin/ui EmptyState coupling).

4. **Do not** add sorting, filtering, pagination, bulk selection, or data fetching inside DataTable unless new V2 evidence appears.

5. **Do not** migrate Account card lists to DataTable without product decision.

6. Leave **`Components/Admin/ui/DataTable.tsx`** in place until importers are switched; no dual behavior change in architecture PRs.

7. UI Lab route (when created): `/admin/design-system/molecules/data-table`.

**Optional later:** low-level **Table atom** only if multiple non–column-def layouts (matrix, layout tables) need shared semantics — not justified in PR-5M.

## 19. UI Lab route

**N/A** (no component created in PR-5M).

## 20. V2 safety

- V2 files **not modified**.
- No migration, adoption, backend, routing, or dependency changes.
- Frozen **Pagination**, **EmptyState**, **Skeleton**, **Accordion**, and field molecules **unchanged**.

## 21. Stop condition

PR-5M complete. Do not start PR-5N. Do not begin V2 table migration.
