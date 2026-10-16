# OURNEAR Textarea — PR-3D

**Status:** Implemented

## Purpose

Canonical multiline control aligned with Input styling and V2 min-height conventions.

## Component

- **Path:** `resources/js/Components/atoms/Textarea.tsx`
- **Export:** `@/Components/atoms`

## API

| Prop | Description |
|------|-------------|
| `surface?` | `public` \| `account` \| `admin` \| `corporate` (default `public`) |
| `error?` | `controlError` + `aria-invalid` |
| `className?` | Additional classes |
| `ref` | Forwarded to `<textarea>` |
| … | Standard `TextareaHTMLAttributes` (`rows`, `cols`, etc.) |

## Surfaces & min-height

- **public / account:** `controlBase` + `min-h-28` (matches public `FormField`)
- **admin / corporate:** `adminControlBase` + `min-h-24` (matches legacy admin Textarea)

## Resize

No custom resize API — browser default (typically vertical resize on textarea).

## States

Normal, focus, disabled, readonly, error — no success state.

## Accessibility

Native `<textarea>`, `aria-invalid` when `error`, parent provides `aria-describedby` and `Label`.

## Compatibility

`Components/Admin/ui/Textarea.tsx` delegates to canonical atom with `surface="admin"`. No form migrations.

## UI Lab

`/admin/design-system/atoms/textarea`

## Validation

- `npm run typecheck` — PASS
- `npm run build` — PASS

## Deferred

Select, Checkbox, Radio, Switch, FormField, and other form molecules.
