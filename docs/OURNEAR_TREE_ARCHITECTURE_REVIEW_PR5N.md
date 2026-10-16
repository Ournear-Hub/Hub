# OURNEAR Tree / TreeView architecture review — PR-5N

**Status:** Review complete — **Tree NOT created** (architecture phase — **V2 not modified**)

## 1. Decision

**TREE SYSTEM DOCUMENTED — NO COMPONENT**

No `Tree.tsx`, `TreeView.tsx`, UI Lab route, exports, tree state utilities, or tree-specific tokens in this PR.

## 2. Primary architectural question (A–E)

| Option | Assessment |
|--------|------------|
| **A — Tree atom/molecule** | **No** V2 reusable tree renderer or shared node component. |
| **B — TreeView molecule** | **Not justified** — no stable tree interaction contract in V2. |
| **C — Tree organism** | **Rejected** — no coordinated hierarchical UI system beyond product navigation. |
| **D — Hierarchical navigation / product UI** | **Evidenced** — Admin Sidebar sections, nested admin links, Account nav groups, UI Lab catalog nav. These are **navigation/list patterns**, not tree semantics. |
| **E — No canonical Tree** | **Selected.** |

**Final classification:** Hierarchical **navigation and flat/grouped lists** cover V2 needs; **no Tree/TreeView primitive** warranted.

## 3. Evidence inspected (read-only)

**Search method:** PowerShell `Get-ChildItem -Recurse` + `Select-String` on `D:\Ournear Hub\Ournear Hire\resources\js`.

| Term / pattern | Count / result |
|----------------|----------------|
| `TreeView`, `TreeNode`, `Tree.tsx`, `function Tree` | **0** |
| `role="tree"`, `role="treeitem"` | **0** |
| `aria-level`, `aria-setsize`, `aria-posinset` | **0** |
| `aria-selected` (app TSX) | **0** |
| Word `\bTree\b` in app code | **0** (no tree UI vocabulary) |
| `hierarchical`, `hierarchy`, `parent_id`, `parent-child`, `nested navigation`, `nested menu` | **0** meaningful tree UI matches (only Breadcrumb doc comment in `classNames.ts` / UI Lab catalog) |

| Source | Finding |
|--------|---------|
| `Components/Admin/Sidebar.tsx` | `<nav aria-label="Admin">`. Sections toggle via **button** + `aria-expanded`. One level of **nested Inertia links** (`item.children` → `NavLink` with `pl-6`). **Not** `role="tree"`. No arrow-key tree navigation. |
| `Components/Account/AccountNav.tsx` | Flat **link list** with optional **group headings** (uppercase labels). No expand/collapse per node. |
| `Layouts/AccountLayout.tsx` | Mobile menu button uses `aria-expanded`; not hierarchical tree. |
| `Components/Account/CandidateMobileNav.tsx` | `aria-expanded` on mobile control. |
| `ui-lab/UiLabLayout.tsx` | Static **nested `<ul>`** for design-system section links; subsection labels always visible; link-based — not an expandable tree widget. |
| `Components/molecules/Breadcrumb.tsx` | Linear **ancestor trail** (`nav` + ordered list) — hierarchy display, **not** tree interaction. |
| `Pages/Admin/MasterData/Index.tsx` | **Tabs** + **DataTable** + drawer forms; relational fields (`industry_id`, `job_category_id`) via **Select** filters/forms — **CRUD tables**, not tree editors. |
| `Components/molecules/Accordion.tsx` | Frozen expandable **sections** (`<details>` / summary + `aria-expanded` on trigger) — **not** a tree. |
| `Components/molecules/DropdownMenu.tsx` | Menu `aria-expanded` on trigger; flat/ menu list keyboard model — **not** tree. |
| `Components/molecules/Combobox.tsx`, `MultiSelect.tsx`, `Tabs.tsx` | List keyboard navigation — **option/menu/tab** patterns, not hierarchical tree. |

## 4. Candidate patterns

| Pattern | Where | Reuse | Interaction | Verdict |
|---------|--------|------:|-------------|---------|
| Admin sidebar sections + nested links | `Admin/Sidebar.tsx` | 1 admin shell | Section expand/collapse; links navigate | **Navigation** — not Tree |
| Account nav + groups | `Account/AccountNav.tsx` | Account layout | Flat links, group labels | **Navigation** — not Tree |
| UI Lab nested section list | `ui-lab/UiLabLayout.tsx` | 1 | Static nested links | **Catalog nav** — not Tree |
| Breadcrumb trail | `molecules/Breadcrumb.tsx` | Pages | Linear path | **Breadcrumb** — frozen |
| Accordion collections | `molecules/Accordion.tsx` | DS + V2 patterns | Native disclosure / expand one or many | **Accordion** — frozen |
| Master data parent FKs | `MasterData/Index.tsx` | 1 workspace | Table + select filters | **Domain CRUD** — not Tree |
| Dropdown / Combobox lists | DS molecules | Many | Menu/listbox keys | **Not tree** |

## 5. Keyboard findings

- **No** V2 implementation of hierarchical **ArrowLeft/ArrowRight** (expand/collapse node) outside frozen molecules.
- **ArrowUp/ArrowDown/Enter/Escape** appear in **Combobox**, **DropdownMenu**, **MultiSelect**, **Tabs** — **flat lists**, not parent/child trees.
- **No** **Home/End** tree focus patterns in app code.
- Sidebar and account nav rely on **native links and buttons** — no roving tabindex tree model.

**Classification:** Keyboard behavior is **component-specific (menus/listboxes/tabs)** or **default link/button**, not **tree navigation**.

## 6. ARIA findings

- **`role="tree"` / `role="treeitem"`:** **absent** in Ournear Hire `resources/js`.
- **`aria-expanded`:** Present on **Sidebar section toggles**, **Accordion** triggers, **Combobox/DropdownMenu/Popover/MultiSelect** triggers, **TopBar/Header** menus, **UI Lab** mobile menu — all **disclosure/menu** patterns, **not** treeitems.
- **`aria-level`, `aria-setsize`, `aria-posinset`:** **absent**.
- **`aria-selected`:** **absent** in app TSX surveyed.

Do **not** map Sidebar `aria-expanded` section buttons to tree semantics — they are **navigation region** controls, not `treeitem` expanders.

## 7. Selection findings

- **No** tree node selection model (single/multi/checkbox).
- **Active route** styling on Sidebar/Account links (`isActive`, `matchHref`) is **navigation current page**, not `aria-selected` tree nodes.
- Master-data selection is **row selection in DataTable** / drawer edit — unrelated to tree.

## 8. Expansion findings

- **Sidebar sections:** Mixed **manual** toggle state (`useState` record) + **auto-open when section contains active URL** (`sectionHasActive`). Not URL-driven per node; not persisted except nav scroll in `sessionStorage`.
- **Users nested links:** Children always rendered when parent section open — **no per-parent expand** on “Users” item (children always visible under section).
- **Accordion:** Caller/native `<details>` open state — frozen separate contract.
- **No** global or shared expansion store for hierarchical data.

## 9. Navigation relationship

- **Admin Sidebar** remains the **product navigation system** — static config (`sections` array), permission filtering, nested **link** children for Users segments.
- **Account** uses **AccountNav** (+ candidate mobile nav) — flat/grouped links from server props.
- **Breadcrumb** (DS molecule) expresses **page hierarchy** in account/admin headers where used.
- **Do not** replace Sidebar with a generic Tree component; **do not** abstract Sidebar sections into DS Tree in architecture phase.

## 10. Accordion relationship

- **Accordion** (frozen) covers **expandable content sections** (single/multiple open).
- Accordion is **not** a substitute for Tree and **must not** be repurposed as Tree semantics.
- V2 mobile filter panels and similar may use **page-local `<details>`** (PR-5J) — still not Tree.

## 11. DropdownMenu relationship

- **DropdownMenu** (frozen) covers **action/option menus** with menu keyboard model.
- Nested visual grouping in menus does **not** appear as a reusable multi-level tree in V2.
- Menus ≠ Tree.

## 12. Master-data findings

- Industries, categories, job titles, locations, etc. are managed via **tabbed workspace**, **search/filter**, and **DataTable** listing.
- Parent relationships (e.g. category → industry) are **form/select** and **filter query** fields, not expandable tree editors or pickers.
- **No** evidence of hierarchy visualization/editing UI requiring Tree.

## 13. Why rejected

1. **Zero** V2 Tree/TreeView components or imports.
2. **Zero** WAI-ARIA tree roles and treeitem attributes in application code.
3. **No** hierarchical keyboard interaction model separate from frozen Combobox/DropdownMenu/Tabs/Accordion.
4. Hierarchical UX in V2 is **navigation** (Sidebar, Account nav, UI Lab catalog) or **linear Breadcrumb** — correct boundaries without Tree.
5. Parent/child **business data** does not imply Tree UI; master-data uses **tables and forms**.
6. Introducing Tree now would be **speculative** and risk duplicating Sidebar/Accordion/menu patterns.

## 14. Responsive findings

- Sidebar: **off-canvas** on small screens (`translate-x`), scrollable nav — layout concern, not tree.
- Account nav: hidden/shown via menu button — same.
- No tree-specific responsive behavior evidenced.

## 15. Future adoption boundary

Revisit a canonical **Tree** or **TreeView** molecule **only if** a future V2 feature introduces a **repeated** pattern with several of:

- explicit parent/child **node** rendering (not only links),
- expand/collapse per node (controlled or uncontrolled),
- optional selection of nodes,
- documented keyboard behavior for hierarchy,
- and deliberate ARIA tree or equally justified semantics.

Until then:

- **Navigation hierarchy** → keep **Sidebar / AccountNav** product components.
- **Expandable sections** → **Accordion** or native `<details>`.
- **Path hierarchy** → **Breadcrumb**.
- **Pick from hierarchy** → likely **Combobox** / cascaded **Select** fields unless a true tree picker appears in V2.

**Do not** add: tree stores, drag-and-drop trees, virtualization, or generic hierarchy services without V2 proof.

## 16. UI Lab route

**N/A** (no component created in PR-5N).

## 17. V2 safety

- V2 files **not modified** (including **Sidebar**, master-data, navigation).
- Frozen **Accordion**, **DropdownMenu**, **Combobox**, **Breadcrumb**, **Tabs** **unchanged**.
- No migration, backend, routing, or dependency changes.

## 18. Stop condition

PR-5N complete. Do not start PR-5O. Do not begin V2 tree/navigation migration.
