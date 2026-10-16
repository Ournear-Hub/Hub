# OURNEAR Toolbar / ActionBar architecture review — PR-5P

**Status:** Review complete — **no canonical Toolbar or ActionBar created** (architecture phase — **V2 not modified**)

## 1. Decision

**TOOLBAR SYSTEM DOCUMENTED — NO COMPONENT**

No `Toolbar.tsx`, `ActionBar.tsx`, PageHeader abstraction, UI Lab routes, or toolbar exports in this PR.

## 2. Primary architectural question (A–E)

| Option | Assessment |
|--------|------------|
| **A — Toolbar molecule** | **Not justified.** V2 has **no** shared Toolbar component or stable semantic contract (`role="toolbar"` **0**). Repeated **visual** rows are **page-local** title/action markup, not one reusable API. |
| **B — ActionBar molecule** | **Not justified.** **0** `ActionBar` references. Action groups use **ad hoc** `flex` / frozen **Cluster** / **Button** rows. |
| **C — Toolbar organism** | **Rejected.** No coordinated toolbar system (selection bar, action registry, responsive overflow menu). |
| **D — Product toolbar / header system** | **Evidenced but fragmented:** **Admin TopBar** (app chrome), **AccountLayout** title+`action` slot, admin index **title blocks**, **FilterBar** below title — **separate product patterns**, not one DS toolbar. |
| **E — No component (this PR)** | **Selected.** |

**Final classification:** **Page-level composition + Cluster for horizontal actions; FilterBar for filters; no distinct Toolbar/ActionBar primitive.**

## 3. Evidence inspected (read-only)

**Search method:** PowerShell `Get-ChildItem -Recurse` + `Select-String` on `D:\Ournear Hub\Ournear Hire\resources\js`.

| Term | Result |
|------|--------|
| `Toolbar` / `ToolBar` (component names) | **0** implementation files; **2** UI Lab doc lines (“toolbar-like”, “semantic toolbar injection” as **non-goals** for Cluster) |
| `ActionBar`, `PageHeader`, `HeaderActions`, `ActionGroup`, `PageActions` | **0** |
| `role="toolbar"` | **0** |
| `text-2xl font-semibold tracking-tight` (admin page titles) | **~40** occurrences — **inline** `<h1>`, not a wrapper component |

| Source | Finding |
|--------|---------|
| `Layouts/AdminLayout.tsx` | Shell only: Sidebar, **TopBar**, Breadcrumbs in main, `{children}`. **No** page toolbar slot. |
| `Components/Admin/TopBar.tsx` | App **chrome** `<header>`: menu, disabled global search, alerts menu, profile. **Product shell**, not list/page toolbar. |
| `Layouts/AccountLayout.tsx` | **Page header region:** `flex flex-wrap items-center justify-between gap-3` — title, optional Breadcrumb, **`action` prop** (ReactNode), notifications. **Layout composition**, not exported Toolbar. |
| `Pages/Admin/Jobs/Index.tsx` | **Title row:** `mb-6 flex flex-wrap items-center justify-between gap-3` + `<h1>` + description + **Create job** `Button`. Then **FilterBar** (separate). |
| `Pages/Admin/Candidates/Index.tsx`, `Employers/Index.tsx`, `EmployerEnquiries/Index.tsx` | **Title only:** `mb-6` + `<h1>` + description — **no** primary action in header row (actions in table/drawer). |
| `Components/Admin/ui/FilterBar.tsx` | Filter **form** shell (PR-5O) — **below** title, not merged with toolbar. |
| `Components/atoms/Cluster.tsx` | Frozen **horizontal gap** layout; **non-semantic** `div`. UI Lab: **no toolbar role injection**. |
| `Components/Public/JobActions.tsx` | **Domain** apply/save/share controls for jobs — not generic ActionBar. |
| `Components/Navigation/Breadcrumbs.tsx` / DS **Breadcrumb** | Path hierarchy — separate from toolbar. |

### Page header flex pattern (partial)

Files with `flex flex-wrap items-center justify-between gap-3` include: `Jobs/Index.tsx`, `Candidates/Index.tsx`, `Dashboard.tsx`, `MediaSocial/Index.tsx`, `Users/Show.tsx`, `AccountLayout.tsx`, plus a few profile components — **similar Tailwind, different content** (with/without `action`, with/without description).

## 4. Candidate patterns

| Pattern | Where | Reuse | Complexity | Verdict |
|---------|--------|------:|------------|---------|
| Admin TopBar | `Admin/TopBar.tsx` | 1 layout | App chrome | **Product shell** — not DS Toolbar |
| Account layout header | `AccountLayout.tsx` | Account pages | Title + optional action slot | **Layout composition** — not Toolbar molecule |
| Admin index title + CTA | e.g. `Jobs/Index.tsx` | Few pages | Inline flex + Button | **Page header** — ad hoc |
| Admin index title only | e.g. Candidates, Employers | Many indexes | h1 + paragraph | **Page header** — no action row |
| FilterBar | Admin indexes | ~28 | Form grid | **Filter shell** (PR-5O) — separate |
| Table row/column actions | DataTable `render` | Many | Per-row Button/Link | **Domain** — not table toolbar |
| JobActions | `Public/JobActions.tsx` | Public job surfaces | Apply/save/share | **Domain ActionBar** — not generic |
| Cluster button groups | DS + UI Lab | Composition | Horizontal gap | **Action group** — use Cluster + Button |

## 5. Page Header vs Toolbar distinction

**Page Header (V2 reality):**

- Heading (`h1`) + optional description (`p.text-muted`).
- Optional primary CTA as sibling (Jobs **Create job**).
- Breadcrumbs often on **AdminLayout** above children, or **AccountLayout** header.
- **Not** encapsulated; **no** shared props (`title`, `actions`) except AccountLayout’s `action` **slot**.

**Toolbar (not evidenced as separate contract):**

- V2 does **not** separate a “control region” below the title that combines search + bulk + export in one reusable component.
- Filters → **FilterBar**; title → **page markup**; chrome → **TopBar**.

**Architectural rule:** Do **not** create a generic **PageHeader** or **Toolbar** molecule to deduplicate `h1 + flex + Button` without a **stable API beyond layout** — that would violate PR scope (“do not create generic page header abstraction”).

## 6. FilterBar relationship

- **FilterBar** is a **filter form** container only (PR-5O deferred DS copy).
- Appears **below** page title on admin indexes — **not** embedded in a toolbar row.
- A future Toolbar must **not** absorb FilterBar or duplicate its form semantics.

## 7. Table toolbar relationship

- **No** DataTable-attached toolbar (no search above table inside a table component).
- Sequence: **(optional title block) → FilterBar → DataTable → Pagination**.
- Row actions live in **column definitions** — domain-specific.
- **No** bulk-selection action bar evidenced.

## 8. Stack / Cluster relationship

- **Stack:** vertical spacing between sections (title block, FilterBar, table).
- **Cluster:** horizontal **sibling** groups (buttons, icon+text); **explicit non-goal** for toolbar ARIA in UI Lab.
- **Action groups** in V2: `flex flex-wrap gap-2` or **Cluster** — sufficient without **ActionBar** wrapper.
- **Do not** create Toolbar merely to apply `justify-between` — that is page layout (Container + flex utilities or future layout recipes), not a toolbar primitive.

## 9. Semantic findings

- **TopBar / Account header:** native **`<header>`** or **`<section>`** where appropriate.
- **Page title blocks:** **`div`** + **`h1`** — not wrapped in `<header>` consistently.
- **`role="toolbar"`:** **absent** — V2 does not treat action strips as ARIA toolbars.
- **FilterBar:** **`<form>`** — correct for filters, not toolbar role.

## 10. Keyboard findings

- **No** roving tabindex or ArrowLeft/Right **toolbar** navigation in page action regions.
- **Escape** on TopBar menus — standard menu dismiss.
- **DropdownMenu / Tabs** — own keyboard models (frozen).
- Toolbar keyboard management **not evidenced** — do not add.

## 11. Responsive findings

- Page title rows: **`flex-wrap`** + **`justify-between`** — actions wrap on small screens (Jobs).
- AccountLayout: title truncates; actions **`shrink-0`** cluster.
- **No** pattern of collapsing secondary actions into a **“More”** menu on admin indexes.
- TopBar: hides global search on small viewports (`hidden md:block`).

## 12. Accessibility findings

- Page **`h1`** per view — page responsibility (multiple patterns).
- TopBar controls: **`aria-label`**, **`aria-expanded`** on menus.
- AccountLayout **`action`** slot — caller supplies accessible Button/Link components.
- **No** `aria-labelledby` linking a toolbar region to a title.
- Prefer **native headings + buttons/links** over `role="toolbar"` unless a true toolbar interaction model appears.

## 13. Visual contract (observed, not canonicalized)

| Region | Typical classes |
|--------|-----------------|
| Admin title + action row | `mb-6 flex flex-wrap items-center justify-between gap-3` |
| Admin title only | `mb-6` + `text-2xl font-semibold tracking-tight text-ink` + `mt-1 text-sm text-muted` |
| Account header | `flex flex-wrap items-center justify-between gap-3 py-4 sm:py-5` inside bordered section |
| TopBar | `h-16`, `border-b`, `justify-between`, `px-4 sm:px-6` |
| Inline action cluster | `flex flex-wrap gap-2` or Cluster `gap="sm"` |

**Not stable enough** for one DS component — column counts and presence of actions vary by page.

## 14. State ownership

| Concern | Owner |
|---------|--------|
| Primary/secondary actions | Page — permissions, routing, handlers |
| Filters / search | Page + FilterBar + Inertia (PR-5O) |
| Selection / bulk | Not evidenced as toolbar |
| Title/description copy | Page |
| Layout chrome | AdminLayout / AccountLayout / TopBar |

A canonical Toolbar would own **only presentation** — but V2 has **no single presentation contract** to extract.

## 15. Why rejected

1. **No V2 Toolbar/ActionBar component** or import path.
2. **`role="toolbar"` unused** — regions are headers, forms, or plain flex layouts.
3. **Page headers are inconsistent** (title-only vs title+CTA) — generic Toolbar ≈ forbidden PageHeader abstraction.
4. **FilterBar** already owns filter **form** region; **TopBar** owns app chrome.
5. **Cluster + Button + DropdownMenu** cover horizontal actions without ActionBar.
6. **JobActions** and table column actions are **domain-specific**.
7. Creating Toolbar/ActionBar now would duplicate **Cluster**/flex utilities without migration targets or stable API.

## 16. Future adoption boundary

Revisit **only if** V2 introduces a **repeated** control region with a clear contract, e.g.:

- shared wrapper used across **many** pages with same props/slots, **and**
- distinct from **FilterBar** (not a form) and from **layout title** (not full page header), **and**
- optional: selection-aware bulk bar with evidenced reuse.

Until then:

- **Page title + actions:** compose with **Heading/Text**, **Cluster**, **Button**, **LinkButton**; optional **`pageHeader*`** classNames in adoption if patterns converge — **not** a mandatory Toolbar molecule.
- **Filters:** **FilterBar** (when adopted per PR-5O).
- **Overflow actions:** **DropdownMenu**.
- **Do not** add `role="toolbar"` without keyboard semantics to match.

UI Lab routes **N/A** unless a component is created in a future PR.

## 17. UI Lab route

**N/A** (no component created in PR-5P).

## 18. V2 safety

- V2 files **not modified** (TopBar, layouts, index pages, FilterBar).
- Frozen **Stack**, **Cluster**, **Container**, **Card**, **FilterBar decision**, **DropdownMenu**, **Tabs**, **Breadcrumb** — **unchanged**.
- No backend, routing, or dependency changes.

## 19. Stop condition

PR-5P complete. Do not start PR-5Q. Do not begin V2 toolbar migration.
