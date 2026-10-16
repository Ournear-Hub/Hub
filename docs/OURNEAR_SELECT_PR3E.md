# OURNEAR Select — PR-3E

**Status:** Implemented

## Purpose

Native HTML `<select>` foundation aligned with Input/Textarea control styling. No custom dropdown, combobox, or searchable select.

## Component

- **Path:** `resources/js/Components/atoms/Select.tsx`
- **Export:** `@/Components/atoms`

## API

| Prop | Description |
|------|-------------|
| `surface?` | `public` \| `account` \| `admin` \| `corporate` (default `public`) |
| `error?` | `controlError` + `aria-invalid` |
| `className?` | Additional classes |
| `ref` | Forwarded to `<select>` |
| `children` | Native `<option>` / `<optgroup>` |
| … | Standard `SelectHTMLAttributes` including `multiple`, `size` |

No placeholder prop — use `<option value="">…</option>`.

## States

Normal, focus, disabled, error. No success state. No custom chevron API (browser native appearance).

## Accessibility

Native select semantics; `aria-invalid` when `error`; parent supplies `Label` and `aria-describedby`.

## Compatibility

`Components/Admin/ui/Select.tsx` delegates to canonical atom with `surface="admin"`. Public `FormField` / `selectControlClass` unchanged. No consumer migrations.

## UI Lab

`/admin/design-system/atoms/select`

## Validation

- `npm run typecheck` — PASS
- `npm run build` — PASS

## Deferred

Combobox, SearchField, custom MultiSelect UX, FormField, Checkbox, Radio, Switch, FileField, validation framework.
