# OURNEAR DateField — PR-3N

**Status:** Implemented

## 1. Purpose

Canonical **native date** form control (`<input type="date">`) for the OurNear design system. Surfaces, error styling, and browser picker behavior align with the Input atom. No custom calendar, parsing, or internal value state.

## 2. Canonical file path

- **Component:** `resources/js/Components/molecules/DateField.tsx`
- **Export:** `@/Components/molecules` (`DateField`, `DateFieldProps`)

## 3. API

```tsx
<DateField
  surface="public" | "account" | "admin" | "corporate"
  error={boolean}
  className?        // optional layout wrapper
  inputClassName?   // classes on the native input (Input)
  ref               // HTMLInputElement
  // native date input props: value, defaultValue, min, max, step, disabled, required,
  // readOnly, name, form, id, onChange, onBlur, onFocus, aria-*, data-*, autoComplete, …
/>
```

`type` is always `"date"` and cannot be overridden.

## 4. Default props

| Prop | Default |
|------|---------|
| `surface` | `"public"` |
| `error` | `false` |

## 5. Surface behavior

Uses canonical **Input** surface recipes:

- **public / account** → `controlBase`
- **admin / corporate** → `adminControlBase`

Same scale and focus ring as other text controls.

## 6. Native date behavior

- Value format is **`YYYY-MM-DD`** (HTML date input).
- Browser provides locale-specific display and picker UI.
- Caller owns `value` / `defaultValue` and `onChange`; DateField does not parse or normalize dates.

## 7. min / max / step

Passed through to the native input unchanged (`min`, `max`, `step` HTML attributes). Constraint enforcement is browser-native.

## 8. showPicker behavior

Implemented on **Input** (PR-3C): for `type="date"`, click invokes `showPicker()` when available, unless `disabled` or `readOnly`. Failures are caught; native picker still works. DateField inherits this by composing Input with `type="date"`.

Also applies `cursor-pointer` on picker types via Input.

## 9. Accessibility

- Native `input type="date"`.
- **`error={true}`** → `controlError` styling and `aria-invalid={true}` (caller `aria-invalid` preserved when not invalid).
- No internal label, hint, or error message — use external **Label**, **aria-label**, or **FormField**.
- Supports `aria-describedby`, `required`, `disabled`, `id` / label `htmlFor`.

## 10. ref behavior

`ref` is forwarded to the **native** `HTMLInputElement` (via Input).

## 11. error behavior

Visual error only (`error` prop). Error copy is rendered by FormField or the page. DateField does not render error text.

## 12. FormField composition

```tsx
<FormField id="start-date" label="Start date" hint="…" error="…" required>
  <DateField id="start-date" name="start_date" />
</FormField>
```

`DateField` is registered in FormField’s `controlsWithErrorProp` set for `id`, `aria-describedby`, `aria-invalid`, and `error` augmentation (PR-3I contract).

## 13. V2 inspection findings

| Finding | Detail |
|---------|--------|
| Reusable DateField | **None** |
| Pattern | **`Input` with `type="date"`** (canonical atom) or raw input on public pages |
| Admin filters | ActivityLogs, LoginLogs, Users (registered/login/activity ranges), Submissions, Replacements — `Input type="date"` + `aria-label` |
| Analytics | `Input type="date"` for from/to range |
| Account | Employer Documents — `type="date"` on form fields |
| Public | ForEmployers — `type="date"` |
| datetime-local | Admin Tasks, Interviews — separate type; **not** DateField scope |
| Third-party picker | **None** in V2 |
| showPicker | **Input atom** only (date, datetime-local, time, month, week) |

## 14. Compatibility / adapters

- **No** V2 screen migrations in PR-3N.
- **No** adapter component; existing `Components/Admin/ui/Input.tsx` remains the admin Input adapter.
- New code should import `DateField` from `@/Components/molecules` when a named date molecule is desired.

## 15. Intentional non-features

DateField does **not** provide:

- Custom calendar UI
- Third-party date picker
- Date parsing or formatting helpers
- Business date validation
- Date-range pairing logic
- Backend validation
- Internal value state
- datetime-local / time / month / week (use Input with the appropriate `type`)

## 16. Deviations

- Built on **Input** (like SearchField / PasswordField), not a standalone raw `<input>` duplicate — preserves one styling and showPicker implementation.
- Optional `className` wrapper only when non-empty (avoids extra DOM for typical FormField usage).

## 17. Validation

- `npm run typecheck` — (see completion report)
- `npm run build` — (see completion report)

## UI Lab

`/admin/design-system/molecules/date-field`
