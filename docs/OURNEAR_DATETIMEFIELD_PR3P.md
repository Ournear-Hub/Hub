# OURNEAR DateTimeField — PR-3P

**Status:** Implemented

## 1. Purpose

Canonical **native datetime-local** form control (`<input type="datetime-local">`) for the OurNear design system. Intentionally separate from **DateField** (`type="date"`) and **TimeField** (`type="time"`). Composes Input for surfaces, error styling, and showPicker — no custom picker or internal state.

## 2. Canonical file path

- **Component:** `resources/js/Components/molecules/DateTimeField.tsx`
- **Export:** `@/Components/molecules` (`DateTimeField`, `DateTimeFieldProps`)

## 3. API

```tsx
<DateTimeField
  surface="public" | "account" | "admin" | "corporate"
  error={boolean}
  className?
  inputClassName?
  ref  // HTMLInputElement
  // native datetime-local props: value, defaultValue, min, max, step, disabled, required,
  // readOnly, name, form, id, onChange, onBlur, onFocus, aria-*, data-*, autoComplete, …
/>
```

`type` is always `"datetime-local"` and cannot be overridden.

## 4. Default props

| Prop | Default |
|------|---------|
| `surface` | `"public"` |
| `error` | `false` |

## 5. Surface behavior

Uses canonical **Input** surface recipes (`controlBase` / `adminControlBase`), aligned with DateField and TimeField.

## 6. Native datetime-local behavior

- Value format follows the HTML **local** datetime contract (e.g. `YYYY-MM-DDTHH:MM` or with seconds).
- No timezone conversion — “local” is browser/form context, not an app timezone layer.
- Browser provides picker UI and locale display.
- Caller owns `value` / `defaultValue` and `onChange`; no parsing, formatting, or transformation.

## 7. min / max / step

Passed through unchanged; validity is browser-native.

## 8. showPicker behavior

Inherited from **Input** (PR-3C): `datetime-local` is a picker type; click invokes `showPicker()` when supported, skipped for `disabled` / `readOnly`, errors caught. **No duplicate logic in DateTimeField.**

## 9. Accessibility

- Native `input type="datetime-local"`.
- `error={true}` → `controlError` + `aria-invalid={true}` (caller ARIA preserved when not invalid).
- No label/hint/error inside the molecule — use Label, `aria-label`, or FormField.

## 10. ref behavior

Forwarded to native `HTMLInputElement` via Input.

## 11. error behavior

Visual error only; messages live in FormField or page markup.

## 12. FormField composition

```tsx
<FormField id="scheduled-at" label="Scheduled at" hint="…" error="…" required>
  <DateTimeField id="scheduled-at" name="scheduled_at" />
</FormField>
```

`DateTimeField` is in FormField’s `controlsWithErrorProp` set.

## 13. V2 inspection findings

| Finding | Detail |
|---------|--------|
| Reusable DateTimeField | **None** |
| Pattern | **Canonical `Input`** with `type="datetime-local"` |
| Locations | Admin **Tasks** (create/update `due_at`), **Interviews** (reschedule `scheduled_at`), **Campaigns** (starts/ends filters + form), **FollowUpTaskForm** (`due_at`), **ApplicationDrawer** (interview schedule + reschedule) |
| Controlled values | Inertia `useForm` — `value` + `onChange` / `setData` |
| Labels | Mix of **Label** + `htmlFor` (FollowUpTaskForm) and **aria-label** (Campaigns) |
| showPicker | **Input** `isPickerType` includes `datetime-local` |
| Adapters | **None** |

## 14. Compatibility / adapters

- **No** V2 migrations in PR-3P.
- Existing screens keep `Input type="datetime-local"`.
- `Components/Admin/ui/Input.tsx` unchanged.

## 15. Intentional non-features

DateTimeField does **not** provide:

- Custom calendar or clock UI
- Third-party pickers
- Date/time parsing or formatting utilities
- Timezone conversion
- Scheduling or business validation
- Date-range logic
- Backend validation
- Internal value state
- Replacement for separate DateField + TimeField use cases where split controls are intended

## 16. Deviations

- Composes **Input** (same as DateField / TimeField).
- Optional wrapper `div` only when `className` is non-empty.

## 17. Validation

- `npm run typecheck` — PASS
- `npm run build` — PASS

## UI Lab

`/admin/design-system/molecules/date-time-field`
