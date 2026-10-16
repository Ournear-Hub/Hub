# OURNEAR Checkbox — PR-3F

**Status:** Implemented

## Purpose

Native `<input type="checkbox">` with OurNear focus, border, and accent-color treatment. Not a custom toggle or CheckboxGroup.

## Component

- **Path:** `resources/js/Components/atoms/Checkbox.tsx`
- **Export:** `@/Components/atoms`

## API

| Prop | Description |
|------|-------------|
| `surface?` | `public` \| `account` \| `admin` \| `corporate` (default `public`) |
| `error?` | `checkboxError` border/ring + `aria-invalid` |
| `className?` | Additional classes |
| `ref` | Forwarded |
| … | Standard checkbox `InputHTMLAttributes` (excluding `type`) |

## Surfaces

- **public / account:** `checkboxBase` (`accent-accent`)
- **admin / corporate:** `checkboxAdminBase` (`accent-brand`)

## Indeterminate

**Not implemented.** No `indeterminate` usage found in Hire `resources/js`. Can be added later via ref + `HTMLInputElement.indeterminate` if product requires it.

## V2 compatibility

Most forms use unstyled native `<input type="checkbox">` with no shared component. **No legacy adapter** — existing pages unchanged.

## UI Lab

`/admin/design-system/atoms/checkbox`

## Validation

- `npm run typecheck` — PASS
- `npm run build` — PASS

## Deferred

Radio, Switch, CheckboxGroup, FormField, validation framework.
