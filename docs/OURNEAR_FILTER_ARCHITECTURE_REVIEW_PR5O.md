# OURNEAR FilterBar / FilterPanel architecture review — PR-5O

**Status:** Review complete — **no canonical FilterBar or FilterPanel created** (architecture phase — **V2 not modified**)

## 1. Decision

**FILTER SYSTEM DOCUMENTED — NO COMPONENT**

No `FilterBar.tsx`, `FilterPanel.tsx`, UI Lab routes, exports, filter hooks, or filter state utilities in this PR.

## 2. Primary architectural question (A–F)

| Option | Assessment |
|--------|------------|
| **A — FilterBar molecule** | V2 **`Components/Admin/ui/FilterBar.tsx`** is a **stable admin presentation shell** (~28 consumers). Fits **molecule** complexity (form layout only). **Deferred** to explicit adoption — parallel to PR-5M DataTable (live product component; avoid duplicate DS copy during architecture-only phase). |
| **B — FilterPanel molecule** | **Not evidenced.** **0** `FilterPanel` references. Mobile/advanced filters use **page-local `<details>`** or bespoke account sections — not a shared panel component. |
| **C — Filter organism** | **Rejected.** No coordinated filter system (query engine, chips bar, apply pipeline) as one V2 component. |
| **D — Filter UI / product pattern** | **Evidenced.** Admin index **FilterBar + fields + Inertia submit**; public **`JobsSearchPanel`**; account **`Employer*Search`** sections — **domain/product** compositions. |
| **E — No component (this PR)** | **Selected.** |
| **F — FilterBar only / FilterPanel only** | N/A — neither canonicalized in PR-5O. |

**Final classification:** **Admin filter bar shell exists in V2 product UI; account/public filter UX is separate; DS canonical FilterBar deferred; FilterPanel not warranted.**

## 3. Evidence inspected (read-only)

**Search method:** PowerShell `Get-ChildItem -Recurse` + `Select-String` on `D:\Ournear Hub\Ournear Hire\resources\js`.

| Term | Result |
|------|--------|
| `FilterBar` | **87** matches; **28** unique TSX files with `<FilterBar` JSX |
| `FilterPanel`, `FilterForm`, `AdvancedFilters`, `FilterControls` | **0** |
| `SearchPanel` / `JobsSearchPanel` | **3** files — public jobs search only |
| `FilterBar.tsx` | **`Components/Admin/ui/FilterBar.tsx`** only (no DS duplicate) |

| Source | Finding |
|--------|---------|
| `Components/Admin/ui/FilterBar.tsx` | `<form onSubmit={onSubmit}>` with grid shell: `mb-6 grid gap-3 rounded-xl border border-line bg-white p-4 md:grid-cols-4` + optional `className`. **Children-only** composition. **No** built-in apply/reset/query props. |
| `Components/Admin/ui/SearchBar.tsx` | Thin admin search input wrapper over `Input type="search"` — used **inside** FilterBar on index pages; **not** DS `SearchField`. |
| Admin `Pages/Admin/*/Index.tsx` (+ workspace) | FilterBar wraps domain **Select**, **SearchBar**, **Input** (dates/numbers), **Button** rows. Submit handler reads **FormData** → **Inertia `router.get`**. |
| `Pages/Admin/Jobs/Index.tsx`, `Candidates/Index.tsx`, `Users/Index.tsx`, etc. | **Apply** + **Reset filters** as **child** buttons (`visit({}, true)` reset pattern). |
| `Pages/Admin/EmployerEnquiries/Index.tsx` | Single **Filter** submit button — **no** reset pair. |
| `Components/Public/JobsSearchPanel.tsx` | Public job search **form** — labeled selects, primary/secondary filter rows, **`details` + “More filters”** on mobile (`lg:open` on desktop). **`countActiveJobFilters`** helper — domain logic, not shared UI. |
| `Components/Account/Recruiter/EmployerApplicantSearch.tsx` (+ Partnership/Staffing variants) | Bespoke **section** with heading, active filter count, **Clear all**, desktop grid form, mobile **`details`** “Filters · N active”. **Controlled** local state + Inertia — not FilterBar. |
| DS molecules | **SearchField**, **Select**, **FieldGroup**, **FieldSet**, **Accordion**, **Drawer**, **Popover**, **Chip**, **Badge** — **unchanged**; filters compose fields at page level. |

### FilterBar consumer list (representative)

`ApplicationWorkspacePage.tsx`, `ActivityLogs`, `Analytics`, `Campaigns`, `Candidates`, `Commission`, `Crm`, `Employees`, `EmployerEnquiries`, `Employers`, `Finance`, `Freelancers`, `Hiring/CommandCenter`, `Interviews`, `Investors`, `Jobs`, `LoginLogs`, `MasterData`, `Messages`, `Notifications`, `Partners`, `Placements`, `Replacements`, `Staff`, `Submissions`, `Tasks`, `Users/Index`, `Users/Activity`.

## 4. Filter implementation inventory

| Pattern | Where | Reuse | State owner | Verdict |
|---------|--------|------:|-------------|---------|
| Admin FilterBar shell | `Admin/ui/FilterBar.tsx` | ~28 | Page `onSubmit` + Inertia | **Product presentation** — future DS FilterBar target |
| Admin SearchBar field | `Admin/ui/SearchBar.tsx` | Many admin filters | Uncontrolled input name=`search` | **Admin field wrapper** — adopt SearchField later |
| Apply + Reset buttons | Subset of admin indexes | ~6+ pages | Page `visit()` | **Page-owned actions** — not in FilterBar API |
| Single Filter submit | e.g. EmployerEnquiries | Few | Page | **Page-owned** |
| Public JobsSearchPanel | `Public/JobsSearchPanel.tsx` | Public jobs | Page/form submit | **Domain filter UI** |
| Account employer search sections | `Account/Recruiter/*`, `Staffing/*` | 3+ | Local state + Inertia | **Domain filter UI** |
| Saved search chips (candidates) | `Candidates/Index.tsx` | 1 | Staff account API | **Domain feature** — not filter container |

## 5. Filter primitive vs composition

### A — Filter primitives (frozen)

Filtering **inputs** are **SearchField**, **Select**, **Combobox**, **MultiSelect**, **DateField**, **NumberField**, etc. V2 admin pages still use **Admin/ui Input/Select/SearchBar** — adoption replaces field imports, **not** a new filter field type.

### B — Filter composition

Stable **admin** composition: **form grid card** holding many named fields. Unstable across surfaces: column counts (`className` overrides `md:grid-cols-4`, `xl:grid-cols-6`), action rows (Apply/Reset vs Filter only), field sets (domain-specific).

### C — FilterBar (V2)

**One** reusable shell — **presentation only**. Evidence supports **layout molecule** at adoption, not in PR-5O.

### D — FilterPanel (V2)

**Absent.** “Panel” behavior = **native `<details>`** (PR-5J) or **section** markup — page-local.

### E — Domain filter UI

Jobs, candidates, employers, master data, finance each embed **different fields and actions** inside FilterBar or custom forms.

## 6. FilterBar findings

- **API (V2):** `onSubmit`, `children`, optional `className` (grid column overrides).
- **Visual:** white bordered rounded container; responsive CSS grid; `mb-6` spacing.
- **Behavior:** native form submit only — **no** internal query parsing.
- **Actions:** always **children** (Button components) — **not** standardized in FilterBar.
- **Fields:** always **children** — SearchBar, Select, Input, etc.

## 7. FilterPanel findings

- **No** `FilterPanel` component or shared import path.
- **JobsSearchPanel:** “More filters” via **`<details className="group mt-3 lg:open">`** — mobile collapse, desktop open.
- **Account employer searches:** mobile **`<details>`** for secondary filters.
- **Drawer/Popover:** **not** used as generic filter panel wrappers in representative admin indexes.
- **Do not** create FilterPanel molecule duplicating **Drawer** or **Accordion**; use those + fields when a panel pattern is needed.

## 8. State ownership

| Concern | Owner |
|---------|--------|
| Filter values (server) | Laravel + Inertia props (`filters` objects) |
| Submit / navigation | Page handlers — `FormData` or controlled state → `router.get` / `router.visit` |
| Reset | Page — often `visit({}, true)` clearing query params |
| Pagination | Separate — updates with same query |
| Saved searches / chips | Domain pages (e.g. candidates) |
| Active filter count (public/account) | **Local helpers** per component (`countActiveJobFilters`, `countActiveFilters`) |

**No** shared filter store, URL service, or hook framework in V2.

## 9. Clear / Reset / Apply findings

- **Apply / Search / Filter:** submit button (`type="submit"`) triggers `onSubmit`.
- **Reset filters:** explicit button on several admin pages — **outside** FilterBar component API.
- **Clear all:** account employer search sections — **custom** handler clearing state + Inertia.
- **Auto-submit / debounce:** **not** a shared filter pattern; employer mobile uses Enter on keyword input locally.
- **No** universal “apply without submit” pattern.

## 10. Active filter findings

- **No** reusable filter chip bar or `ActiveFilters` component in admin FilterBar pages.
- **Candidates:** saved search **pills** (name + delete) — staff feature, not generic active-filter UI.
- **Public JobsSearchPanel / account searches:** text summary **“N filters active”** — **inline** in domain components.
- Frozen **Chip** / **Badge** — do not add FilterChip in PR-5O; no stable admin chip contract.

## 11. Responsive findings

- **Admin FilterBar:** responsive **grid** (`md:grid-cols-4`, optional `xl:grid-cols-6`); fields stack on small viewports via grid flow — **no** separate mobile panel component.
- **Public jobs:** primary row + **details** for secondary filters on small screens.
- **Account:** duplicate desktop form vs mobile stacked UI + **details**.
- **Accordion (DS):** not used as standard admin index filter collapse; **`<details>`** preferred page-locally (PR-5J).

## 12. Accessibility findings

- FilterBar: **native `<form>`** + submit — good baseline.
- Fields: **`aria-label`** on many admin Select/Input/SearchBar usages; inconsistent **visible labels** (SearchBar uses placeholder as aria-label).
- JobsSearchPanel: explicit **`<label htmlFor>`** on public fields.
- Account sections: **`aria-labelledby`** on section heading.
- **No** `role="search"` requirement evidenced; form semantics sufficient.
- **details/summary:** native disclosure for mobile filter expansion — not tree/menu roles.

## 13. Visual contract (V2 admin FilterBar)

| Token | Value |
|-------|--------|
| Container | `rounded-xl border border-line bg-white p-4` |
| Layout | `grid gap-3`, default `md:grid-cols-4`, overrides via `className` |
| Spacing below | `mb-6` on form |
| Actions | Child `flex flex-wrap gap-2` groups, sometimes `md:col-span-2` |

Public/account patterns differ (public FormField labels, account section headers) — **not** one global visual system.

## 14. Relationship to frozen field primitives

- FilterBar is **container only** — fields remain **SearchField**, **Select**, **Combobox**, etc. after adoption.
- **Do not** embed query or field types inside FilterBar.
- **FormField / FieldGroup / FieldSet** may wrap fields in future admin adoption; FilterBar does not replace them.

## 15. Relationship to Accordion / Drawer / Popover

- **Accordion:** expandable **content sections** — not default for admin FilterBar; use for filter **groups** only if product design requires (not evidenced as standard).
- **Drawer:** edge panel for **detail/edit** workspaces — not V2’s generic filter panel.
- **Popover:** contextual overlays — not admin index filter shell.
- **`<details>`:** valid **page-local** mobile disclosure for extra filters (JobsSearchPanel, account searches).

## 16. Why no DS component in PR-5O

1. **Live product shell** already at `Admin/ui/FilterBar.tsx` with broad adoption — canonicalize in **adoption PR**, not duplicate now (PR-5M parity).
2. **FilterPanel** has **zero** V2 contract — creating it would be speculative.
3. **Account and public** filter UX **does not** use FilterBar — not a universal OurNear filter molecule.
4. **Apply/Reset/active chips** are **page-owned** — not stable enough to bake into FilterBar API yet.
5. A DS FilterBar would be ~15 lines of layout — better delivered with **`filterBar*` classNames** + adoption swap than parallel unmigrated export.

## 17. Future adoption boundary

When an explicit adoption PR is approved:

1. Add **`Components/molecules/FilterBar.tsx`** matching V2 API:

   - `onSubmit: (event: FormEvent<HTMLFormElement>) => void`
   - `children: ReactNode`
   - `className?: string` (grid column overrides)

2. Add **`filterBarRoot`**, **`filterBarGrid`** (or equivalent) to **`design-system/classNames.ts`**.

3. **Optional** props only if V2 gains them consistently: `aria-label` on form — not required today.

4. **Do not** add: `filters`, `onChange`, `onApply`, `onReset`, query types, or chip slots unless V2 standardizes them.

5. **FilterPanel:** only if a **repeated** panel/drawer filter shell appears — prefer **compose Drawer + FieldSet + actions** rather than a second panel primitive.

6. Migrate **Admin/ui/FilterBar** importers to DS molecule; replace **SearchBar** with **SearchField** where appropriate — separate field adoption.

7. UI Lab (when created): `/admin/design-system/molecules/filter-bar`.

8. Leave **JobsSearchPanel** and **account employer search** as product components unless product chooses convergence.

## 18. UI Lab route

**N/A** (no component created in PR-5O).

## 19. V2 safety

- V2 files **not modified** (`FilterBar.tsx`, filter forms, JobsSearchPanel, account searches).
- Frozen field and layout molecules **unchanged**.
- No backend, Inertia, routing, or dependency changes.

## 20. Stop condition

PR-5O complete. Do not start PR-5P. Do not begin V2 filter migration.
