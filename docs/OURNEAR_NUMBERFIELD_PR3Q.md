# OURNEAR NumberField — PR-3Q

**Status:** Implemented

## 1. Purpose

Canonical **native number** form control (`<input type="number">`) — thin molecule over **Input**. UI control only; no formatting, masking, or internal numeric state.

## 2. Canonical file path

- **Component:** `resources/js/Components/molecules/NumberField.tsx`
- **Export:** `@/Components/molecules` (`NumberField`, `NumberFieldProps`)

## 3. API

```tsx
<NumberField
  surface="public" | "account" | "admin" | "corporate"
  error={boolean}
  className?
  inputClassName?
  ref  // HTMLInputElement
  // min, max, step, value, defaultValue, placeholder, disabled, required, readOnly, onChange, aria-*, …
/>
```

`type` is always `"number"`.

## 4. Default props

| Prop | Default |
|------|---------|
| `surface` | `"public"` |
| `error` | `false` |

## 5. Surface behavior

Inherits **Input** `controlBase` / `adminControlBase` and `controlError`.

## 6. Native number behavior

Browser handles number semantics, spin buttons (where shown), and constraint validation. Caller owns `value` / `onChange`. No keyboard filtering or character blocking in NumberField.

## 7. min / max / step

Passed through unchanged (e.g. `min={0}`, `max={100}`, `step={1}`, `step={0.01}`).

## 8. Integer and decimal behavior

Controlled via native **`step`**: `step={1}` (or default browser step) for integers; fractional `step` (e.g. `0.01`) for decimals. No app-side parsing or formatting.

## 9. Accessibility

Native `type="number"`; `error` → Input error styling + `aria-invalid`; works with Label, `aria-label`, FormField. No internal error text.

## 10. ref behavior

Forwarded to native `HTMLInputElement` via Input.

## 11. error behavior

Visual error only (`error` prop).

## 12. FormField composition

```tsx
<FormField id="quantity" label="Quantity" hint="…" error="…" required>
  <NumberField id="quantity" name="quantity" min={1} />
</FormField>
```

`NumberField` registered in FormField `controlsWithErrorProp`.

## 13. V2 inspection findings

| Finding | Detail |
|---------|--------|
| Reusable NumberField | **None** |
| Pattern | **Canonical `Input`** with `type="number"` |
| Usage | JobForm vacancies (`min={1}`), ApplicationWorkspace match % filters (`0–100`), Candidates filters/forms, Automations, MasterData, ApplicationDrawer, CandidateProfessionalSections (experience-style fields), ForEmployers |
| showPicker | **Not** a picker type on Input — no showPicker for number |
| Adapters | **None** |

## 14. Compatibility / adapters

No V2 migrations in PR-3Q. Existing `Input type="number"` unchanged.

## 15. Intentional non-features

NumberField does **not** provide:

- Numeric / currency / percentage formatting
- Masking
- Custom spinner or ± buttons
- Custom keyboard filtering
- Locale conversion
- Business or backend validation
- Internal value state

## 16. Deviations

Composes Input (frozen architecture); optional wrapper when `className` set.

## 17. Validation

- `npm run typecheck` — PASS
- `npm run build` — PASS

## UI Lab

`/admin/design-system/molecules/number-field`
