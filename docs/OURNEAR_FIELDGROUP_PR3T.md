# OURNEAR FieldGroup — PR-3T

**Status:** Implemented (architecture phase — **V2 not modified**)

## 1. Purpose

Structural molecule for **related form controls** — layout, optional group label/hint/error. Not a form manager.

## 2. Canonical file path

- **Component:** `resources/js/Components/molecules/FieldGroup.tsx`
- **Export:** `@/Components/molecules` (`FieldGroup`, `FieldGroupProps`, orientation/gap types)

## 3. API

```tsx
<FieldGroup
  label?
  hint?
  error?
  required={boolean}              // default false — legend indicator only
  orientation="vertical" | "horizontal"  // default vertical
  gap="sm" | "md" | "lg"          // default md
  className?
>
  {children}
</FieldGroup>
```

No `name`, `value`, `onChange`, validation callbacks, or surface prop.

## 4. Relationship to FormField

| | FormField | FieldGroup |
|---|-----------|------------|
| Scope | One control + label/hint/error | Multiple related children |
| Child augmentation | Clones control for id/ARIA/error | **None** — no cloneElement |
| Use together | Yes — nest FormFields inside FieldGroup |

## 5. Grouping semantics

- **With `label`:** renders `<fieldset>` + `<legend>` (related controls).
- **Without `label`:** non-semantic `<div>` wrapper for layout only.
- No automatic `role="group"` for styling.

## 6. Accessibility

- Group hint/error ids via `useId()`; `aria-describedby` on fieldset or div when hint/error present.
- Group **error does not** set `aria-invalid` on children.
- Group **required** asterisk on legend only — does not imply each child is `required`.

## 7. Layout / orientation

- **vertical:** `flex flex-col` + gap token.
- **horizontal:** column on small screens, `sm:flex-row sm:flex-wrap sm:items-end` + gap.

## 8. Child composition rules

- Pass any React children; no type inspection, prop injection, or state.

## 9. Error / hint / required behavior

- Same typography classes as FormField (`helperText`, `errorText`).
- Group error uses `role="alert"`; child errors remain on FormField.

## 10. V2 inspection findings (read-only)

| Finding | Detail |
|---------|--------|
| FieldGroup molecule | **None** |
| Patterns | Ad hoc `<fieldset>` / `<legend>` in Register, Profile (locations), Reviews, Freelancers, Employers, CandidateProfessionalSections; UI Lab Radio grouping |
| No migration | V2 fieldsets unchanged |

## 11. V2 was NOT modified

Architecture + UI Lab only.

## 12. Intentional non-features

FieldGroup does **not** provide:

- Form state or submission
- Validation engine or schema
- Field registration
- Child `aria-invalid` propagation
- Business logic
- Value management

## 13. Deviations

- Uses `useId()` for group hint/error ids (no caller `id` prop in minimal API).
- No `surface` prop — layout/structure only.

## 14. Validation

- `npm run typecheck` — PASS
- `npm run build` — PASS

## UI Lab

`/admin/design-system/molecules/field-group`
