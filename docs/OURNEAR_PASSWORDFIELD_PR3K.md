# OURNEAR PasswordField — PR-3K

**Status:** Implemented

## Purpose

Password-specific **Input** composition with optional show/hide visibility. No validation, strength, or auth logic.

## V2 vs library

| V2 | Canonical molecule |
|----|----------------------|
| Plain `<input type="password">` / Public FormField | `PasswordField` + canonical `Input` |
| No visibility toggle in login/register/reset | **`showToggle` default `true`** for corporate UX; set `showToggle={false}` to match V2 |

## Component

- **Path:** `resources/js/Components/molecules/PasswordField.tsx`
- **Export:** `@/Components/molecules`

## API

Inherits **Input** props except `type` and `className`:

| Prop | Description |
|------|-------------|
| `surface?` | Input surface (default `public`) |
| `error?` | Passed to Input |
| `showToggle?` | Visibility IconButton (default `true`; hidden when `disabled`) |
| `className?` | Wrapper |
| `inputClassName?` | On Input |
| `ref` | Native `<input>` |
| `autoComplete`, `value`, `name`, … | Caller-controlled (not hard-coded) |

Internal state: **visibility only** (`password` ↔ `text`).

## Icons

Registry entries added: **`eye`**, **`eye-off`** (Tabler wired in `tablerComponents.ts` only).

## Accessibility

- Toggle: **IconButton** `type="button"`, labels **“Show password”** / **“Hide password”**
- Icons decorative on button; input keeps name from Label / `aria-label` / FormField
- ARIA/error props pass through to Input

## FormField

`PasswordField` registered in FormField error augmentation (same as SearchField).

## UI Lab

`/admin/design-system/molecules/password-field`

## Validation

- `npm run typecheck` — PASS
- `npm run build` — PASS

## Deferred

PasswordStrength, policy engines, auth/reset migrations, DateField, etc.
