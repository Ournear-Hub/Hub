# OURNEAR MultiSelect — PR-3X

**Status:** Implemented (architecture phase — **V2 not modified**)

## 1. Purpose

Searchable **multi-value** selection with removable chips. Canonical complement to single-select Combobox — not a shared framework.

## 2. API

```tsx
<MultiSelect
  options={MultiSelectOption[]}
  value={string[]}
  onChange={(values: string[]) => void}
  placeholder?
  disabled?
  error?
  surface="public" | "account" | "admin" | "corporate"
  className?
  id?
  name?                    // hidden inputs: name[] per value (Laravel-friendly)
  emptyMessage?            // default "No results"
  aria-describedby?
  aria-invalid?
/>
```

## 3. Option model

```ts
type MultiSelectOption = {
  value: string;
  label: string;
  disabled?: boolean;
};
```

Same shape as Combobox options.

## 4. Controlled selected values

`value` and `onChange` own the selection array. No internal copy of selected values.

## 5. Internal transient state

`open`, filter `query`, highlighted option index only.

## 6. Selection behavior

Toggle enabled options: add if absent, remove if present (no duplicates). Popup **stays open** after selection. Search query clears after toggle. Focus remains on the combobox input.

## 7. Removal behavior

Each selected value renders as a chip with a `type="button"` remove control, `aria-label="Remove {label}"`, `OurNearIcon` close. Does not submit the form.

## 8. Filtering

Case-insensitive substring on `label`; local only.

## 9. Keyboard behavior

| Key | Behavior |
|-----|----------|
| ArrowDown / Up | Open; move highlight (skip disabled) |
| Enter / Space | Toggle highlighted option; popup open |
| Escape | Close; selections preserved |
| Home / End | First / last enabled in filtered list |
| Tab | Normal; closes popup |

## 10. Accessibility / ARIA

- Input: `role="combobox"`, `aria-autocomplete="list"`, `aria-expanded`, `aria-controls`, `aria-activedescendant`
- List: `role="listbox"`, **`aria-multiselectable="true"`**
- Options: `role="option"`, `aria-selected` (membership in `value`), `aria-disabled` when disabled

## 11. Disabled options

Not selectable/toggleable; skipped in keyboard navigation. Already-selected disabled values remain in `value` until caller removes them (chips still shown).

## 12. Chip behavior

Wrapping flex layout; truncated labels; remove buttons keyboard-focusable.

## 13. FormField integration

MultiSelect in `controlsWithErrorProp`; FormField passes `id`, ARIA, and `error` to the combobox input (same as Combobox).

## 14. Native form / name

When `name` is set, one `<input type="hidden" name="{name}[]" value="…" />` per selected value (appends `[]` if omitted).

## 15. Non-features

No async/remote search, max/min selection, select-all, clear-all, virtualization, custom render props, or Combobox API changes.

## 16. V2 inspection findings (read-only)

| Finding | Detail |
|---------|--------|
| Reusable MultiSelect | **None** |
| Multi-value UI | Admin Roles: checkbox lists in fieldsets (`permission_ids[]` toggles); User tags via form arrays; native `<Select multiple>` in UI Lab only |
| Chips/tags | No canonical chip multi-select in product forms |
| Options | Server arrays; local static at render |

## 17. V2 was NOT modified

Architecture + UI Lab + docs only.

## 18. Validation results

| Check | Result |
|-------|--------|
| `npm run typecheck` | **PASS** |
| `npm run build` | **PASS** |

## UI Lab

Route: `/admin/design-system/molecules/multi-select`
