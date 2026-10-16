# OURNEAR Radio — PR-3G

**Status:** Implemented

## Purpose

Native `<input type="radio">` with OurNear focus, border, and accent-color treatment. No RadioGroup, context, or custom roving tabindex.

## Component

- **Path:** `resources/js/Components/atoms/Radio.tsx`
- **Export:** `@/Components/atoms`

## API

| Prop | Description |
|------|-------------|
| `surface?` | `public` \| `account` \| `admin` \| `corporate` (default `public`) |
| `error?` | `radioError` border/ring + `aria-invalid` |
| `className?` | Additional classes |
| `ref` | Forwarded |
| `name`, `value`, … | Standard radio `InputHTMLAttributes` (excluding `type`) |

## Native grouping

Mutually exclusive selection is handled by the browser via shared **`name`**. The atom does not track group selection.

## Surfaces

- **public / account:** `radioBase` (`accent-accent`, `rounded-full`)
- **admin / corporate:** `radioAdminBase` (`accent-brand`)

## Visual treatment

V2 uses **unstyled native radios** in Account register and review rating forms (label wraps input, no custom SVG). The canonical atom adds **16×16**, **rounded-full**, border, accent-color, focus, disabled, and error styling — same family as Checkbox, with circular shape.

## V2 compatibility

No reusable `Radio` component found. **No adapter**; existing pages unchanged.

## UI Lab

`/admin/design-system/atoms/radio`

## Validation

- `npm run typecheck` — PASS
- `npm run build` — PASS

## Deferred

Switch, RadioGroup, CheckboxGroup, FormField, validation framework, and other field molecules.
