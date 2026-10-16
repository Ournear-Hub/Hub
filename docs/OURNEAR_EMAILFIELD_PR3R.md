# OURNEAR EmailField — PR-3R

**Status:** Implemented (architecture phase — **V2 not modified**)

## 1. Purpose

Canonical **native email** form control (`<input type="email">`) — thin molecule over **Input** for future adoption. No validation, normalization, or internal state.

## 2. Canonical file path

- **Component:** `resources/js/Components/molecules/EmailField.tsx`
- **Export:** `@/Components/molecules` (`EmailField`, `EmailFieldProps`)

## 3. API

```tsx
<EmailField
  surface="public" | "account" | "admin" | "corporate"
  error={boolean}
  className?
  inputClassName?
  ref  // HTMLInputElement
  // autoComplete, inputMode, maxLength, minLength, pattern, placeholder, value, onChange, aria-*, …
/>
```

`type` is always `"email"`.

## 4. Default props

| Prop | Default |
|------|---------|
| `surface` | `"public"` |
| `error` | `false` |

## 5. Surface behavior

Inherits **Input** `controlBase` / `adminControlBase` and `controlError`.

## 6. Native email behavior

Browser email semantics and `:valid` / `:invalid` where applicable. Caller owns value/onChange. No regex, parsing, or lowercase transforms in EmailField.

## 7. autocomplete / inputMode behavior

**Not hard-coded.** Callers pass `autoComplete`, `inputMode`, etc. EmailField forwards props to Input unchanged.

## 8. Accessibility

Native `type="email"`; `error` → Input styling + `aria-invalid`; Label / `aria-label` / FormField; no internal error text.

## 9. ref behavior

Forwarded to native `HTMLInputElement` via Input.

## 10. error behavior

Visual error only (`error` prop).

## 11. FormField composition

```tsx
<FormField id="email" label="Email address" hint="…" error="…" required>
  <EmailField id="email" name="email" autoComplete="email" />
</FormField>
```

`EmailField` registered in FormField `controlsWithErrorProp`.

## 12. V2 inspection findings (read-only)

| Finding | Detail |
|---------|--------|
| Reusable EmailField molecule | **None** |
| Pattern | **`Input type="email"`** (Admin auth, Account register/forgot/reset, Staff, Users, CRM, Partners, Investors, Contact, ForEmployers, ChangeEmailPanel, etc.) |
| `autoComplete="email"` | Register, ChangeEmailPanel, some UI Lab / Public FormField examples; **not universal** on every email input |
| `inputMode` | Rare on email; `inputMode="numeric"` used on OTP, not email fields |
| Legacy | `Components/Public/FormField.tsx` monolithic wrapper with `type="email"` (Freelancers) — **unchanged**, separate from canonical molecule FormField |
| Validation | Server/Laravel + page-level messages — not in Input |

## 13. V2 was NOT modified

PR-3R adds canonical architecture and UI Lab only. **No** V2 file changes, migrations, adapters, or auth/admin refactors.

## 14. Intentional non-features

EmailField does **not** provide:

- Custom or regex email validation
- Normalization or lowercase transformation
- Domain / disposable-email checks
- Duplicate detection
- Backend validation
- Internal value state
- Email verification UI

## 15. Deviations

Composes Input (frozen stack); optional wrapper when `className` set; **no** default `autoComplete="email"`.

## 16. Validation

- `npm run typecheck` — PASS
- `npm run build` — PASS

## UI Lab

`/admin/design-system/molecules/email-field`
