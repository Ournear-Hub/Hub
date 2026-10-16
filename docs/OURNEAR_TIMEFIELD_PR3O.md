# OURNEAR TimeField — PR-3O

**Status:** Implemented

## 1. Purpose

Canonical **native time** form control (`<input type="time">`) for the OurNear design system. Surfaces, error styling, and browser picker behavior align with the Input atom. No custom clock, parsing, timezone logic, or internal value state.

## 2. Canonical file path

- **Component:** `resources/js/Components/molecules/TimeField.tsx`
- **Export:** `@/Components/molecules` (`TimeField`, `TimeFieldProps`)

## 3. API

```tsx
<TimeField
  surface="public" | "account" | "admin" | "corporate"
  error={boolean}
  className?        // optional layout wrapper
  inputClassName?   // classes on the native input (Input)
  ref               // HTMLInputElement
  // native time input props: value, defaultValue, min, max, step, disabled, required,
  // readOnly, name, form, id, onChange, onBlur, onFocus, aria-*, data-*, autoComplete, …
/>
```

`type` is always `"time"` and cannot be overridden.

## 4. Default props

| Prop | Default |
|------|---------|
| `surface` | `"public"` |
| `error` | `false` |

## 5. Surface behavior

Uses canonical **Input** surface recipes:

- **public / account** → `controlBase`
- **admin / corporate** → `adminControlBase`

Same scale and focus ring as Input, DateField, and other text controls.

## 6. Native time behavior

- Value format is **`HH:MM`** or **`HH:MM:SS`** per HTML time input rules.
- Browser provides locale-specific display and picker UI.
- Caller owns `value` / `defaultValue` and `onChange`; TimeField does not parse, format, or normalize times.

## 7. min / max / step

Passed through to the native input unchanged. Constraint enforcement is browser-native.

## 8. showPicker behavior

Implemented on **Input** (PR-3C): for `type="time"`, click invokes `showPicker()` when available, unless `disabled` or `readOnly`. Failures are caught; native behavior still works. TimeField inherits this by composing Input with `type="time"` — **no duplicate showPicker logic in TimeField**.

Also applies `cursor-pointer` on picker types via Input.

## 9. Accessibility

- Native `input type="time"`.
- **`error={true}`** → `controlError` styling and `aria-invalid={true}` (caller `aria-invalid` preserved when not invalid).
- No internal label, hint, or error message — use **Label**, **aria-label**, or **FormField**.
- Supports `aria-describedby`, `required`, `disabled`, `readOnly`, `id` / label `htmlFor`.

## 10. ref behavior

`ref` is forwarded to the **native** `HTMLInputElement` (via Input).

## 11. error behavior

Visual error only (`error` prop). Error copy is rendered by FormField or the page. TimeField does not render error text.

## 12. FormField composition

```tsx
<FormField id="start-time" label="Start time" hint="Select a time" error="…" required>
  <TimeField id="start-time" name="start_time" />
</FormField>
```

`TimeField` is registered in FormField’s `controlsWithErrorProp` set for `id`, `aria-describedby`, `aria-invalid`, and `error` augmentation (PR-3I contract).

## 13. V2 inspection findings

| Finding | Detail |
|---------|--------|
| Reusable TimeField | **None** |
| `type="time"` usage | **None observed** in `resources/js` |
| `datetime-local` | Admin Tasks, Interviews, Campaigns; `FollowUpTaskForm`, `ApplicationDrawer` — typically **Input** or native input with `type="datetime-local"` |
| showPicker | **Input atom** treats `time` as a picker type (with date, datetime-local, month, week) |
| Third-party time picker | **None** in V2 |

## 14. datetime-local scope boundary

**TimeField is `type="time"` only.**

`datetime-local` combines date and time in one control and is **out of scope** for TimeField (and for DateField). Existing V2 `datetime-local` fields remain on **Input** or raw inputs — unchanged in PR-3O. A future **DateTimeField** (if added) would be a separate molecule.

## 15. Compatibility / adapters

- **No** V2 screen migrations in PR-3O.
- **No** adapter component.
- `Components/Admin/ui/Input.tsx` unchanged.

## 16. Intentional non-features

TimeField does **not** provide:

- Custom clock UI
- Third-party time picker
- Time parsing or formatting helpers
- Timezone conversion
- Scheduling or business-hour validation
- `datetime-local` behavior
- Backend validation
- Internal value state

## 17. Deviations

- Built on **Input** (same pattern as DateField) — single styling and showPicker implementation.
- Optional `className` wrapper only when non-empty.

## 18. Validation

- `npm run typecheck` — PASS
- `npm run build` — PASS

## UI Lab

`/admin/design-system/molecules/time-field`
