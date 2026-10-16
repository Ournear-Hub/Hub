# OURNEAR URLField — PR-3S

**Status:** Implemented (architecture phase — **V2 not modified**)

## 1. Purpose

Canonical **native URL** form control (`<input type="url">`) — thin molecule over **Input** for future adoption. No validation, normalization, protocol injection, or internal state.

## 2. Canonical file path

- **Component:** `resources/js/Components/molecules/URLField.tsx`
- **Export:** `@/Components/molecules` (`URLField`, `URLFieldProps`)

## 3. API

```tsx
<URLField
  surface="public" | "account" | "admin" | "corporate"
  error={boolean}
  className?
  inputClassName?
  ref  // HTMLInputElement
  // autoComplete, inputMode, maxLength, pattern, placeholder, value, onChange, aria-*, …
/>
```

`type` is always `"url"`.

## 4. Default props

| Prop | Default |
|------|---------|
| `surface` | `"public"` |
| `error` | `false` |

## 5. Surface behavior

Inherits **Input** `controlBase` / `adminControlBase` and `controlError`.

## 6. Native URL behavior

Browser URL semantics and native `:valid` / `:invalid` where applicable. Caller owns value/onChange. No parsing, https prefix, or regex in URLField.

## 7. autocomplete / inputMode behavior

**Not hard-coded.** Callers pass `autoComplete`, `inputMode`, etc. URLField forwards to Input unchanged.

## 8. Accessibility

Native `type="url"`; `error` → Input styling + `aria-invalid`; Label / `aria-label` / FormField; no internal error text.

## 9. ref behavior

Forwarded to native `HTMLInputElement` via Input.

## 10. error behavior

Visual error only (`error` prop).

## 11. FormField composition

```tsx
<FormField id="website" label="Website" hint="…" error="…" required>
  <URLField id="website" name="website" placeholder="https://example.com" />
</FormField>
```

`URLField` registered in FormField `controlsWithErrorProp`.

## 12. V2 inspection findings (read-only)

| Finding | Detail |
|---------|--------|
| Reusable URLField molecule | **None** |
| `type="url"` usage | **Sparse** — Account **Profile** company website + dynamic social URL fields via **`Components/Public/FormField`** (`type="url"`, placeholders `https://example.com` / `https://`) |
| Canonical `Input type="url"` | **Not observed** in grep sample; legacy Public FormField renders native input internally |
| `autoComplete="url"` | **Not observed** on V2 url fields in inspection |
| Validation | Server-side + page error messages (e.g. `company_website`) — not in control |

## 13. V2 was NOT modified

PR-3S adds canonical architecture and UI Lab only. No V2 migrations, adapters, or Profile refactors.

## 14. Intentional non-features

URLField does **not** provide:

- Custom or regex URL validation
- Parsing or normalization
- Automatic `http://` / `https://` insertion
- Domain or DNS checks
- Link preview
- Backend validation
- Internal value state

## 15. Deviations

Composes Input (frozen stack); optional wrapper when `className` set; **no** default `autoComplete="url"`.

## 16. Validation

- `npm run typecheck` — PASS
- `npm run build` — PASS

## UI Lab

`/admin/design-system/molecules/url-field`
