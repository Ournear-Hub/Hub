# OURNEAR Tabs review — PR-5D

**Status:** Implemented (architecture phase — **V2 not modified**)

## 1. Decision

**Tabs was created** as a canonical **molecule**.

Singular name: **`Tabs`** (`Components/molecules/Tabs.tsx`). V2 **`Components/Admin/ui/Tabs.tsx`** remains unchanged.

## 2. Architectural classification

**Molecule** — **tablist / tab / tabpanel** composition for in-page section switching, distinct from radiogroup (**SegmentedControl**), route navigation (**Link**), hierarchy (**Breadcrumb**), and disclosure (**Accordion**).

## 3. Evidence inspected (read-only)

| Source | Finding |
|--------|---------|
| `Components/Admin/ui/Tabs.tsx` | Shared admin component: `role="tablist"`, `role="tab"`, `aria-selected`, optional single `role="tabpanel"` via `children`. **No** `aria-controls` / stable ids, **no** keyboard. |
| Admin consumers | **~15 files**, **~17** usages via `@/Components/Admin/ui` (Campaigns, Commission, MasterData, Partners, Investors, Finance, Candidates, Employers, Employees, Jobs, Placements, Replacements, Submissions, Users/Show, ApplicationDrawer, etc.). |
| `Pages/Admin/Users/Show.tsx` | Local `useState` + `<Tabs …>{conditional panels}</Tabs>` — true in-page tabs. |
| URL/query switching | Campaigns/MasterData/Partners/Investors/Commission/Finance use Admin `Tabs` + Inertia `visit({ tab \| view })` at **page layer** (not inside molecule). |
| Ad-hoc pill rows | `Admin/Settings`, `MediaSocial/Index`, `WebsiteContent/Index` — `<button>` rows, **no** tab roles. |
| `ProfileShell` | Inertia **links** in `<nav aria-label="Profile sections">` — **route navigation**, not tabs. |
| `Account/Employer/Jobs/Index` | Filter **links** with `aria-current="page"` — navigation. |
| `SegmentedControl` (frozen) | `role="radiogroup"` / `role="radio"` — **0** production pages; ui-lab only. |
| Other ARIA | **No** other `role="tablist"` in app code besides Admin/ui/Tabs. |

## 4. Rationale

V2 admin workspaces repeat a **true tabs** intent (exclusive section + associated content) through one shared component with **15+** consumers. Patterns are **not** interchangeable with **SegmentedControl** (no tabpanels, radiogroup semantics) or **Link** navigation (ProfileShell, settings routes).

The canonical molecule **hardens** the durable contract (ids, `aria-controls`, keyboard, `panels` API) while preserving the **flat** V2 API (`tabs`, `active`, `onChange`) — no compound/context API required by evidence.

URL-synced “tabs” stay **caller-owned** (`onChange` → `router.get`); the molecule does not detect routes.

## 5. API contract

```tsx
type TabItem = { id: string; label: string; disabled?: boolean };

<Tabs
  tabs={TabItem[]}
  active={string}
  onChange={(id: string) => void}
  aria-label={string}          // required
  panels?: Partial<Record<string, ReactNode>>  // preferred
  children?: ReactNode        // single active panel slot (V2 Users/Show style)
  className?: string
/>
```

- **Caller-controlled** `active` / `onChange` (local state or Inertia at page layer).
- **`panels`**: full tab ↔ tabpanel wiring (recommended).
- **`children`**: one tabpanel for active content; tabs link via `aria-controls`.
- **Neither** `panels` nor `children`: **tablist only** (head-only — matches many V2 pages with sibling content; migration should adopt `panels`).

## 6. Semantic contract

- Container: non-landmark `div`.
- **Tab list:** `role="tablist"`, `aria-orientation="horizontal"`.
- **Tab:** `role="tab"`, `type="button"`, stable `id`, `aria-selected`.
- **Tab panel:** `role="tabpanel"`, stable `id`, `aria-labelledby`, `hidden` when inactive.

## 7. Accessibility contract

- **`aria-label` required** on tablist (V2 omitted this — corrected in molecule).
- **`aria-controls`** / panel **`id`** when `panels` or `children` provided.
- **Roving `tabIndex`:** selected tab `0`, others `-1`.
- **Ellipsis / decorative:** N/A (text labels only).
- Does **not** implement `aria-labelledby` from visible heading (caller may add later).

## 8. Keyboard contract

On tablist (horizontal):

- **ArrowLeft / ArrowRight** — move selection + focus (wrap).
- **Home / End** — first / last enabled tab.
- **Click** — select tab.
- **Space / Enter** — native button activation (select focused tab).

No ArrowUp/Down (horizontal-only evidence). No focus trap inside panels.

## 9. Interaction contract

- Mutually exclusive **active** tab id.
- Disabled tabs: `disabled` button, skipped in keyboard wrap.
- No automatic URL updates inside the component.

## 10. Visual contract

Recipes: `tabsRoot`, `tabsList`, `tabsTabBase`, `tabsTabSelected`, `tabsTabUnselected`, `tabsTabDisabled`, `tabsPanel` — aligned with V2 Admin/ui border-bottom row and brand selected state.

## 11. State ownership

**Fully caller-controlled:** `active` + `onChange`. Component is stateless. Page may sync to query params outside the molecule.

## 12. SegmentedControl relationship

| | SegmentedControl | Tabs |
|---|------------------|------|
| ARIA | `radiogroup` / `radio` | `tablist` / `tab` / `tabpanel` |
| Panels | None | Associated content |
| Use | Compact mode/value | Workspace sections |
| Frozen | Yes — **not modified** | New molecule |

Do not use Tabs for filter chips without panels; do not use SegmentedControl for multi-panel workspaces.

## 13. Link / navigation relationship

- **ProfileShell**, **SettingsNav**, employer listing pills → **navigation** (`nav` + `Link`, `aria-current`).
- **Tabs** → in-page section switching only.
- Route changes belong at caller layer, not inside Tabs.

## 14. UI Lab route

`/admin/design-system/molecules/tabs`

## 15. Explicit non-goals

- Inertia/router/URL detection inside Tabs
- Vertical tabs, icons, dismissible tabs, overflow menus
- Migrating Admin/ui/Tabs consumers or ad-hoc Settings/Website Content rows
- Merging with SegmentedControl or Accordion

## 16. V2 inspection status

**Read-only — complete** for tab-like patterns listed above.

## 17. V2 modification status

**None.**

## 18. Migration status

**Not started.** Future adoption may point admin pages at `@/Components/molecules/Tabs` and supply `aria-label` + `panels`.

## 19. Dependencies

**None** added.

## 20. Deviations (molecule vs V2 Admin/ui/Tabs)

- Required **`aria-label`**
- **`aria-controls`** + panel ids when panels/children wired
- **Keyboard** navigation
- **`panels`** map API
- Active tab uses **roving tabindex** (V2 all tabs tabbable)

V2 file **unchanged**.

## 21. Verification

- `npm run typecheck`
- `npm run build`
- UI Lab route wired
