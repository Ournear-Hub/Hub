# OURNEAR Select / dropdown / menu architecture review — PR-5X

**Status:** Review complete — **no new selection/menu components** (architecture phase — **V2 not modified**)

## 1. Decision

**EXISTING SELECTION/MENU SYSTEM SUFFICIENT — NO NEW COMPONENT**

Frozen **Select** (PR-3E), **Combobox** (PR-3W), **MultiSelect** (PR-3X), **DropdownMenu** (PR-4C), **Popover** (PR-4B), and **SegmentedControl** (PR-3Y) cover all **reusable interaction classes** evidenced in V2. No **ContextMenu**, **Autocomplete**, **Submenu system**, or **UniversalDropdown** is justified.

## 2. Architectural classification

| Interaction class | Canonical DS | V2 production usage |
|-------------------|--------------|---------------------|
| **Native form value selection** | **Select** (atom + Admin/ui adapter) | **Dominant** — Admin `Select`, public raw `<select>` + `selectControlClass` |
| **Searchable single select** | **Combobox** | **UI Lab + FormField wiring only** — no page consumers |
| **Multi-value selection** | **MultiSelect** | **UI Lab + FormField wiring only** — no page consumers |
| **Action / command menu** | **DropdownMenu** | **UI Lab only** — no page consumers |
| **Generic positioned surface** | **Popover** | **UI Lab only** |
| **In-page exclusive choice** | **SegmentedControl** | **UI Lab only** |
| **Account / admin chrome menus** | Maps to **DropdownMenu** on adoption | **Bespoke** — `role="menu"` + **Link** panels (TopBar, Public Header) |
| **App navigation** | **Layout / Sidebar / links** | **Admin Sidebar** nested sections; public **Header** nav — not form Select |
| **Context (right-click) menu** | N/A | **None** |
| **Command palette** | N/A (PR-5L) | **None** — no contradictory evidence |

## 3. Evidence inspected (read-only)

**Root:** `D:\Ournear Hub\Ournear Hire\resources\js`, `package.json`.

**Frozen implementations reviewed:** `atoms/Select.tsx`, `Admin/ui/Select.tsx`, `molecules/Combobox.tsx`, `MultiSelect.tsx`, `DropdownMenu.tsx`, `Popover.tsx`, `SegmentedControl.tsx`, `molecules/FormField.tsx`.

**V2 chrome / filters read:** `Public/Header.tsx`, `Admin/TopBar.tsx`, `Public/JobsSearchPanel.tsx`, `Profile/ProfileShareMenu.tsx`.

**UI Lab:** Select, Combobox, MultiSelect, DropdownMenu, Popover, SegmentedControl sections + `ui-lab/catalog.ts`.

| Search | Hire `resources/js` result |
|--------|----------------------------|
| `ContextMenu`, `onContextMenu`, `contextmenu` | **0** |
| `CommandPalette`, `cmdk` | **0** (consistent with PR-5L) |
| Third-party select/menu libs (Radix, Headless UI, Downshift, floating-ui, React Aria) | **0** in `package.json` / imports |
| Files with raw `<select` | **~19** |
| `Combobox` / `MultiSelect` / `DropdownMenu` in `Pages/` + `Components/` (excl. molecules + DS Show) | **0** |
| `role="menu"` in Components/Pages/Layouts | **TopBar** (2), **Public Header** (1 panel), **DropdownMenu.tsx** (canonical) |
| `role="menuitem"` outside DropdownMenu molecule | **Public Header** account **Link** items |
| Native `select multiple` as product filter UI | **Not evidenced** |
| `autoComplete` attribute | **HTML form autofill** on inputs — not Combobox |

## 4. Select findings

**Canonical:** `Components/atoms/Select.tsx` — styled native `<select>`, `surface`, `error`, `aria-invalid`. **Admin/ui/Select** delegates with `surface="admin"`.

**V2 patterns:**

1. **Admin CRUD / filters** — `import { Select } from '@/Components/Admin/ui'` with `<option>` children; value bound to Inertia forms or filter forms (widespread across admin pages).
2. **Public / account raw `<select>`** — e.g. **JobsSearchPanel** (`selectControlClass` from Public FormField), CMS media pickers, registration/profile enums, employer search panels.
3. **FormField molecule** — can wrap **Select** for labeled fields (design-system composition).

**Not Select (by semantics):** Sidebar links, tabs, and route navigation.

## 5. DropdownMenu findings

**Frozen:** `Components/molecules/DropdownMenu.tsx` — compound API, portal positioning, viewport clamp, **role="menu"** / **menuitem**, keyboard navigation, destructive/disabled items.

**Production V2:** **No imports** outside UI Lab and Design System catalog.

**Page-local menus (adoption candidates):**

| Surface | Behavior |
|---------|----------|
| **Admin TopBar** — notifications | Toggle + **Link** list + mark-all-read |
| **Admin TopBar** — account | Profile / logout **Link**s |
| **Public Header** — account | **role="menu"** + **Link** `menuitem` |
| **ProfileShareMenu** | **Dialog** / bottom sheet — not dropdown menu |

**No** nested submenu / flyout API in frozen DropdownMenu; **no** V2 submenu flyouts.

## 6. Combobox findings

**Frozen:** Controlled value, filter-as-you-type, combobox + listbox + option ARIA, in-flow listbox.

**Production V2:** **Zero** page usage. Filters use **Select** or **SearchBar** text + server-side filter.

**Autocomplete:** Browser `autoComplete` on inputs only — **Combobox** covers searchable selection; separate Autocomplete not warranted.

## 7. MultiSelect findings

**Frozen:** Chip display, multiselect listbox, controlled `string[]`.

**Production V2:** **Zero** page usage. Filters use single **Select** or checkboxes.

## 8. Popover-based selection findings

**Popover** is UI Lab only. Combobox/MultiSelect use in-flow listboxes per catalog. No repeated Popover+option-list pattern in V2.

## 9. Navigation menu findings

**Admin Sidebar:** section toggles + nested links (navigation, PR-5N). **Public Header:** primary nav + mobile drawer. Account dropdowns are **navigation menus**, not **DropdownMenu** component today — adopt frozen molecule later without new architecture.

## 10. Context menu findings

**None** — no right-click handlers or ContextMenu components.

## 11. Accessibility findings

Frozen **DropdownMenu** and **Combobox** implement menu/listbox/combobox roles. **Header/TopBar** use partial manual menu semantics (`aria-expanded`, `aria-haspopup`, `role="menu"` / `menuitem` on links). Consolidation target on adoption: **DropdownMenu** — no new primitive.

## 12. Keyboard findings

Native **select** uses browser keys. Frozen Combobox/MultiSelect/DropdownMenu own documented key models. TopBar/Header: **Escape** + tab to links; no arrow-roving on notification lists.

## 13. Positioning / overflow findings

**DropdownMenu** portal + viewport clamp covers action menus. TopBar/Header use absolute positioned panels with scroll — same problem class, page-local today.

## 14. Dependency / library findings

No third-party select/menu libraries in `package.json`.

## 15. State ownership findings

Select values: Inertia forms or URL query on GET filters. Menu open: local state in TopBar/Header. Combobox/MultiSelect/DropdownMenu: caller-controlled when adopted. No global menu context.

## 16. Responsive findings

Account menu: desktop dropdown; mobile nav via **PublicMobileMenu**. ProfileShareMenu uses bottom sheet on small screens (dialog pattern). Selects use responsive grids (`min-w-0`).

## 17. Visual contract

Admin **Select** and public **selectControlClass** share input-family tokens. Chrome menus match dropdown panel styling manually. Visual-only differences do not justify new components.

## 18. Domain-vs-design-system classification

Filters and enums → **Select**. Row actions → **DropdownMenu** (future). Chrome link menus → **DropdownMenu** or layout. Share flow → **Dialog**.

## 19. Relationship to frozen Select

Canonical native form dropdown; raw public `<select>` is adoption alignment only.

## 20. Relationship to frozen DropdownMenu

Canonical action menu; TopBar/Header are adoption targets, not new types.

## 21. Relationship to frozen Combobox

For future searchable pickers; current search pages use SearchBar + server results.

## 22. Relationship to frozen MultiSelect

For future multi-enum controls; V2 uses checkboxes / single selects.

## 23. Relationship to frozen Popover

Generic positioned content — not a select primitive.

## 24. Candidate patterns

See §18 and completion report §4.

## 25. Why created / rejected

No ContextMenu, Autocomplete, Submenu, or UniversalDropdown evidence. V2 gap is **adoption** of frozen molecules, not missing architecture. Frozen components not modified.

## 26–32. Contracts (if created)

**N/A** — no new components.

## 33. UI Lab route (if created)

**N/A** — no changes.

## 34. Documentation path

`docs/OURNEAR_SELECT_DROPDOWN_MENU_REVIEW_PR5X.md`

## 35. Files created

- `D:\Ournear Hub\Ournear Hire\docs\OURNEAR_SELECT_DROPDOWN_MENU_REVIEW_PR5X.md`

## 36. Files modified

**None**

## 37. V2 inspection status

**Complete (read-only)**

## 38. V2 modification status

**None**

## 39. Dependency changes

**None**

## 40. TypeScript result

**Not run** (documentation-only)

## 41. Build result

**Not run** (documentation-only)

## 42. Deviations

None.

## 43. V2 migration / adoption status

**Not started.** Optional: public `<select>` → **Select**; TopBar/Header → **DropdownMenu**; Combobox/MultiSelect when product requires. **PR-5L unchanged.**

## 44. Stop condition

**PR-5X complete. Stop. Do not start PR-5Y. Do not begin V2 adoption/migration.**
