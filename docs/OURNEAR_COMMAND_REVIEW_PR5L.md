# OURNEAR Command / Command Palette review — PR-5L

**Status:** Review complete — **Command NOT created** (architecture phase — **V2 not modified**)

## 1. Decision

**Command was NOT created.**

No `Command.tsx`, compound command primitives, UI Lab route, exports, or command-specific tokens.

## 2. Architectural classification

| Class | Finding | Verdict |
|-------|---------|---------|
| **A — SearchField (DS)** | Query input composition; V2 `Admin/ui/SearchBar` wraps `Input type="search"` on index filters | **Frozen — covers search/query** |
| **B — Combobox (DS)** | Searchable option list + ArrowUp/Down + Enter selection | **Frozen — covers searchable pick-from-list** |
| **C — DropdownMenu (DS)** | Menu actions + keyboard menu navigation | **Frozen — covers action menus** |
| **D — Popover (DS)** | Anchored contextual panels (filters in UI Lab) | **Frozen — not a command launcher** |
| **E — Dialog (DS)** | Modal shell | **Frozen — could host a palette later, but is not Command** |
| **F — Command / Command Palette** | No V2 component, no global shortcut, no action-search launcher | **Not evidenced — reject canonical Command** |

## 3. Evidence inspected (read-only)

| Source | Finding |
|--------|---------|
| `Components/Admin/TopBar.tsx` | **Disabled** `type="search"` input: placeholder “Search candidates, jobs, employers”; title “Global search will be available in a later V1.1 phase”. **Not** a command palette; no keyboard shortcut; no results list. |
| `Components/Admin/ui/SearchBar.tsx` | Thin wrapper: `Input type="search"` with `name`, `placeholder`, optional `disabled`. Used on admin index **FilterBar** forms — page-local table filtering. |
| `Components/Admin/ui/FilterBar.tsx` | Form layout wrapper for filters — not command UI. |
| `Components/molecules/SearchField.tsx` | DS search input (icon + search type); docs: does not own query/results. |
| `Components/molecules/Combobox.tsx` | Filterable listbox pattern: ArrowUp/Down, Enter, Escape — **option selection**, not action execution. |
| `Components/molecules/DropdownMenu.tsx` | Menu semantics + arrow navigation — **menu actions**, not global command search. |
| `Components/molecules/MultiSelect.tsx` | Multi option selection with keyboard — not command palette. |
| `Pages/Admin/Hiring/CommandCenter.tsx` | **Product page** (“Hiring Command Center” dashboard: metrics, queues, filters) — name only; not a UI command primitive. |
| `design-system/classNames.ts` | Comment: “DropdownMenu - **command menu** surface” — terminology for **DropdownMenu**, not a Command molecule. |
| Name search `CommandPalette`, `CommandMenu`, `CommandDialog`, `CommandBar` | **0** matches in `resources/js`. |
| `Ctrl+K`, `Meta+K`, `Cmd+K`, `⌘K` | **0** matches in `resources/js`. |
| `shortcut`, `palette`, `launcher`, `action launcher`, `quick actions` (meaningful) | **0** app matches (excluding unrelated reference repos if any stray). |
| `window` / document `keydown` listeners | Modal/Drawer/Dialog Escape; TopBar menu Escape; Popover/DropdownMenu/Tooltip — **overlay/menu dismiss**, not command infrastructure. |
| Molecules with ArrowUp/Down | **Combobox**, **DropdownMenu**, **MultiSelect**, **SegmentedControl**, **Tabs** — each frozen for its own contract. |
| Search method | PowerShell `Get-ChildItem -Recurse` + `Select-String` on `D:\Ournear Hub\Ournear Hire\resources\js`. |

## 4. Candidate patterns

| Pattern | Where | Verdict |
|---------|--------|---------|
| Disabled global search field | Admin TopBar | **Placeholder / future work** — not Command |
| Page SearchBar + FilterBar | ~20+ admin index pages | **SearchField / form filter** domain |
| Combobox filterable lists | DS + UI Lab | **Combobox** — not Command |
| DropdownMenu actions | DS + UI Lab | **DropdownMenu** — not Command |
| Hiring “Command Center” page | Admin route | **Dashboard** — naming only |
| Popover filter panels | UI Lab / patterns | **Popover** — not Command |

## 5. Keyboard / shortcut findings

- **No** application-wide **Ctrl/Cmd+K** (or similar) launcher.
- **No** shortcut hint badges (e.g. ⌘K) on admin chrome.
- **ArrowUp/ArrowDown/Enter/Escape** appear in **Combobox**, **DropdownMenu**, **MultiSelect**, **Tabs** — localized to those frozen molecules.
- **Escape** on modals/menus/drawers — dismiss only.
- **No** reusable “command list” keyboard model separate from Combobox/DropdownMenu.

Classification: **(B) local / component-specific keyboard behavior**, not **(A) reusable command system**.

## 6. Global invocation findings

**No global command palette** or universal action overlay exists in V2.

The only “global search” UI is a **disabled** TopBar search input with an explicit **later phase** message.

Navigation remains **sidebar**, **links**, and **Inertia routing** — not a searchable command index.

## 7. Why rejected

1. **No shared V2 implementation** — zero CommandPalette-style components or imports.
2. **No distinct interaction contract** beyond what **SearchField + Combobox + DropdownMenu + Dialog** already cover separately (query input, searchable options, action menus, optional modal shell).
3. **No keyboard shortcut culture** in V2 — creating Command would be speculative, not evidence-driven.
4. **“Command Center”** is a **business dashboard**, not a design pattern to canonicalize.
5. Introducing Command now would duplicate Combobox/DropdownMenu list navigation and invite a second action-search API without migration targets.

## 8. Relationship to frozen components

| Component | Boundary |
|-----------|----------|
| **SearchField** | Text query input only; no action list. |
| **Combobox** | Choose **one** value from a **defined option set** (forms/filters). |
| **DropdownMenu** | **Menu** of actions/items with menu ARIA and keyboard. |
| **Popover** | **Anchored** contextual content; optional filters. |
| **Dialog** | **Modal** shell; could wrap a future palette UI but is not Command. |
| **Command (rejected)** | Would imply global/action-search launcher — **not in V2**. |

Do not alias Command ↔ Combobox or Command ↔ DropdownMenu.

## 9. Semantic contract (if created — N/A)

N/A — not created. If ever justified by future V2 evidence, semantics must be chosen explicitly (e.g. combobox vs listbox vs dialog-hosted list) — **not invented in PR-5L**.

## 10. Accessibility contract

N/A — not created. Existing patterns: search inputs use `type="search"` / `aria-label`; Combobox/DropdownMenu carry their own ARIA from DS reviews.

## 11. Keyboard contract

N/A — not created.

## 12. Interaction contract

N/A — not created.

## 13. Visual contract

No stable command-palette visual in V2 (no modal search overlay, no grouped action list with shortcuts). TopBar search is a single disabled field matching standard admin input styling.

## 14. State ownership

N/A for Command. Existing V2: callers own filter query params, Combobox value, menu open state, routing.

## 15. Final API

**Not created.**

## 16. UI Lab route

**Not created.**

## 17. Files created

- `docs/OURNEAR_COMMAND_REVIEW_PR5L.md`

## 18. Files modified

**None** (all frozen molecules untouched)

## 19. Dependencies

**None**

## 20. TypeScript result

**Not run** — documentation-only PR.

## 21. Build result

**Not run** — documentation-only PR.

## 22. Deviations

N/A

## 23. V2 untouched confirmation

**Confirmed** — no V2 files modified.

## 24. Migration / adoption confirmation

**Not started.**

**Future adoption rule:** Introduce a Command molecule only if V2 (or an approved product spec) ships a **repeated** action-search launcher with evidence distinct from Combobox + DropdownMenu + SearchField — e.g. global shortcut, grouped executable commands, and stable visual/keyboard contract. Until then, compose frozen primitives or page-local search/filter UI.

## Explicit non-goals (PR-5L)

- CommandProvider / command registry / global keyboard manager
- Implementing TopBar “V1.1 global search” as Command
- Renaming Hiring Command Center
- Extending Combobox or DropdownMenu “for command palette”
