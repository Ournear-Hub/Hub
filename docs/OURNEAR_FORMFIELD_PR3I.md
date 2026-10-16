# OURNEAR FormField — PR-3I

**Status:** Implemented

## Purpose

First molecule: composes **Label + control + hint/error** without owning form state, validation rules, or Inertia.

## Responsibilities

| Owns | Does not own |
|------|----------------|
| Label (`htmlFor` → `id`) | `useForm`, server errors wiring |
| Hint / error messaging | `value`, `onChange`, schema |
| `aria-describedby` / `aria-invalid` on child | Business logic |

## API

```tsx
<FormField
  id="email"
  label="Email address"
  hint="Use your work email."
  error={errors.email}
  required
  className=""
>
  <Input id="email" name="email" type="email" />
</FormField>
```

## Accessibility contract

- **Label:** `htmlFor={id}` when `label` is provided.
- **IDs:** `${id}-hint`, `${id}-error` (deterministic, SSR-safe).
- **Child (single React element):** merged when safe:
  - `id` if child omits it
  - `aria-describedby` — merges hint and/or error ids (preserves existing values)
  - `aria-invalid={true}` when `error` has content
  - `error={true}` only on **Input, Textarea, Select, Checkbox, Radio** (visual error tokens)
  - **Switch:** `aria-invalid` + describedby only (no `error` prop)

Does not override `value`, `checked`, `onChange`, `name`, `disabled`, `required`, or `ref`.

## Hint vs error

When **error** is present, **hint is hidden** (matches `Components/Public/FormField.tsx`). Error uses `role="alert"`.

## Required

`required` on FormField drives **Label** required indicator (`*` + sr-only “(required)”). Does **not** auto-set `required` on the child — consumer sets native `required` when needed.

## Compatibility

- **`Components/Public/FormField.tsx`** — legacy monolithic wrapper (raw input/textarea classes). **Unchanged**, no adapter.
- Admin forms use **Label + Admin/ui Input** patterns; not migrated.

## UI Lab

`/admin/design-system/molecules/form-field`

## Validation

- `npm run typecheck` — PASS
- `npm run build` — PASS

## Deferred

SearchField, PasswordField, DateField, FileField, CheckboxGroup, RadioGroup, SwitchGroup, SettingRow, form context, validation framework.
