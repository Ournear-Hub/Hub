# OURNEAR SegmentedControl — PR-3Y

**Status:** Implemented (architecture phase — **V2 not modified**)

## 1. Purpose

Compact **mutually exclusive** UI state selector (view mode, period, active/archived). Not tabs, not navigation, not a form Radio replacement.

## 2. vs Radio

| Radio | SegmentedControl |
|-------|------------------|
| Form field semantics | UI/mode selection |
| FormField + label typical | `aria-label` on group |
| Native input | Styled segment buttons |

## 3. vs Tabs

Tabs use tablist/tab/tabpanel and switch content regions. SegmentedControl has **no** tab roles and does not own panels.

## 4. vs Navigation

Navigation changes URL/route. SegmentedControl only calls `onChange(value)`; parent decides meaning (including whether to navigate).

## 5. API

```tsx
<SegmentedControl
  options={SegmentedControlOption[]}
  value={string}
  onChange={(value: string) => void}
  aria-label={string}          // required
  disabled?
  size="sm" | "md"             // default md
  className?
  name?                        // optional hidden input
/>
```

## 6. Option model

```ts
type SegmentedControlOption = {
  value: string;
  label: ReactNode;
  disabled?: boolean;
};
```

## 7. Controlled state

`value` + `onChange` only — no internal selection state.

## 8. Disabled behavior

Whole control: `aria-disabled`, all segments disabled. Per-option: segment disabled, skipped by arrow Home/End navigation.

## 9. Keyboard behavior

On radiogroup: ArrowRight/Down → next enabled; ArrowLeft/Up → previous; Home/End → first/last enabled. Tab → selected segment (tabIndex 0). Space/Enter on segment → native button activation.

## 10. Accessibility semantics

**Chosen model:** `role="radiogroup"` + `role="radio"` + `aria-checked` + required `aria-label`. Not tablist, not links.

## 11. Focus behavior

Roving tabindex: selected segment `tabIndex={0}`, others `-1`. Arrow keys move selection and focus; click does not steal focus unexpectedly.

## 12. Form integration

Optional `name` → single hidden input with current `value`. No FormField augmentation (intentional).

## 13. Visual behavior

Connected control: surface tray, white selected segment, muted unselected, shared `focusRing`, `sm`/`md` heights.

## 14. UI Lab route

`/admin/design-system/molecules/segmented-control`

## 15. V2 inspection findings (read-only)

| Finding | Detail |
|---------|--------|
| Reusable SegmentedControl | **None** |
| Similar UI | Admin Users index: row of pill `button`s for saved segments (triggers Inertia `visit` — navigation/filter, not a shared component) |
| SaveJobButton | `aria-pressed` toggle — different pattern |
| Sidebar | segment links as hrefs — navigation |

## 16. Non-features

No tabs, routes, URL sync, orientation prop, internal state, FormField coupling, select-all, or new dependencies.

## 17. V2 was NOT modified

Architecture + UI Lab + docs only.

## 18. Validation results

| Check | Result |
|-------|--------|
| `npm run typecheck` | **PASS** |
| `npm run build` | **PASS** |
