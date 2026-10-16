# OURNEAR Combobox — PR-3W

**Status:** Implemented (architecture phase — **V2 not modified**)

## 1. Purpose

Single-select, locally filtered option control with combobox/listbox interaction. For searchable selection UX beyond native `<Select>`.

## 2. API

```tsx
<Combobox
  options={ComboboxOption[]}
  value={string}                    // selected value; '' = none
  onChange={(value: string) => void}
  placeholder?
  disabled?
  error?                            // visual invalid; message stays on FormField
  surface="public" | "account" | "admin" | "corporate"
  className?
  id?
  name?                             // optional hidden input for native forms
  emptyMessage?                     // default "No results"
  aria-describedby?
  aria-invalid?
/>
```

## 3. Option model

```ts
type ComboboxOption = {
  value: string;
  label: string;
  disabled?: boolean;
};
```

Duplicate `value` entries are caller responsibility (React keys use `value`).

## 4. Controlled selection

`value` + `onChange` are required. No uncontrolled selected state.

## 5. Internal transient state

Component-owned only: `open`, filter `query`, highlighted option index. Selection is never internal.

## 6. Filtering behavior

Case-insensitive substring match on `label` while the list is open. No fuzzy/remote/debounce.

## 7. Keyboard behavior

| Key | Behavior |
|-----|----------|
| ArrowDown | Open if closed; else next **enabled** option |
| ArrowUp | Open if closed (last enabled); else previous enabled |
| Enter | Select highlighted enabled option |
| Escape | Close; **does not** clear selection |
| Home / End | First / last enabled in filtered list |
| Tab | Normal tab order; closes list |

## 8. Accessibility / ARIA

- Input: `role="combobox"`, `aria-autocomplete="list"`, `aria-expanded`, `aria-controls`, `aria-activedescendant`
- Popup: `role="listbox"`
- Items: `role="option"`, `aria-selected`, `aria-disabled` when disabled
- Option ids generated internally (`useId` + value slug)
- Focus stays on input; highlight via `aria-activedescendant`

## 9. Focus behavior

Focus remains on the text input. `mousedown` on options uses `preventDefault` to avoid blur before select. After selection, popup closes and input keeps focus.

## 10. Disabled options

Skipped for highlight navigation; not selectable; styled with `comboboxOptionDisabled`.

## 11. Empty state

Non-interactive list row with `emptyMessage` when filter yields zero options.

## 12. FormField integration

Combobox is in `controlsWithErrorProp`. FormField passes `id`, `aria-describedby`, `aria-invalid`, and `error` to the combobox input directly (no nested passthrough).

## 13. Non-features

No async/API, multi-select, virtualization, clear button, custom option renderers, fuzzy search, or form submission logic.

## 14. V2 inspection findings (read-only)

| Finding | Detail |
|---------|--------|
| Combobox / listbox component | **None** |
| Dependencies | React, Inertia, Tabler icons — **no** Headless UI / Radix |
| Filters | Admin/public filters use `<Select>`, SearchBar, or link chips — not combobox |
| JobForm | Filters `{ id, label, selectable }` for native select options — unchanged |
| Options | Server-provided arrays; local/static at render time |

## 15. V2 was NOT modified

Architecture + UI Lab + docs only.

## 16. Validation results

| Check | Result |
|-------|--------|
| `npm run typecheck` | **PASS** |
| `npm run build` | **PASS** |

## UI Lab

Route: `/admin/design-system/molecules/combobox`
