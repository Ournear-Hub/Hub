# OURNEAR Input — PR-3C

**Status:** Implemented  
**Scope:** Canonical Input atom + UI Lab only.

## Purpose

Single OurNear-owned text control primitive using existing `controlBase` / `adminControlBase` recipes. No FormField, no validation messaging inside the atom.

## Canonical component

- **Path:** `resources/js/Components/atoms/Input.tsx`
- **Export:** `@/Components/atoms`

## API

| Prop | Description |
|------|-------------|
| `surface?` | `public` \| `account` \| `admin` \| `corporate` (default `public`) |
| `error?` | Applies `controlError` + sets `aria-invalid` when true |
| `className?` | Additional classes |
| `ref` | Forwarded to native `<input>` |
| … | Standard `InputHTMLAttributes` |

No built-in label or error text.

## Supported states

- Normal, focus (existing focus tokens), disabled, readonly (admin recipe includes read-only surface styling)
- Error via `error` prop only — no success variant (not in V2 control pattern)

## Accessibility

- Native `<input>`
- `aria-invalid` when `error` or explicit `aria-invalid`
- Parent supplies `aria-describedby` for hints/errors
- Label via separate `Label` atom (PR-3B)

## Token / class dependencies

- `controlBase`, `controlError`, `focusRing` — public/account
- `adminControlBase` — admin/corporate (defined in `design-system/classNames.ts`)

## Compatibility

- **`Components/Admin/ui/Input.tsx`** — thin adapter: `surface="admin"`, re-exports `adminControlClass` alias
- No migration of admin pages or forms
- Raw `<input className={controlBase}>` elsewhere unchanged

## UI Lab

- **Route:** `/admin/design-system/atoms/input`
- Demonstrates types, surfaces, states, Label + describedby pattern

## Validation

- `npm run typecheck`
- `npm run build`

## Deferred

FormField, SearchField, PasswordField, DateField, Select, Textarea, Checkbox, Radio, Switch, FileField, form context, schema validation.
