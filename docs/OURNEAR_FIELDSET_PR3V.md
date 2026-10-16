# OURNEAR FieldSet — PR-3V

**Status:** Implemented (architecture phase — **V2 not modified**)

## 1. Purpose

Semantic **section** molecule for a logically related collection of controls. Always renders native `<fieldset>` + `<legend>`. Not layout-first and not a form engine.

## 2. API

```tsx
<FieldSet
  legend={ReactNode}              // required
  description?
  error?
  required={boolean}              // default false — legend indicator only
  className?
>
  {children}
</FieldSet>
```

No `surface`, `value`, `onChange`, `name`, validation schema, or child augmentation.

## 3. FieldSet vs FieldGroup

| | FieldGroup | FieldSet |
|---|------------|----------|
| Primary role | Layout (orientation, gap) | Semantic section boundary |
| Wrapper | `<div>` when unlabeled; optional fieldset when `label` set | **Always** `<fieldset>` + `<legend>` |
| Naming | `label`, `hint` | `legend`, `description` |
| Use when | Arranging controls in a row/column | Named form section (Personal info, Availability, …) |

FieldSet does not replace FieldGroup. Compose: `FieldSet` → `FieldGroup` → `FormField` → control.

## 4. Relationship to FormField

FormField continues to own per-control label, hint, error, id, and ARIA. FieldSet does not clone or augment FormField or inputs. Group `description` / `error` apply to the **section**, not individual fields.

## 5. Composition model

```
FieldSet (semantic boundary)
  → FieldGroup (optional layout)
    → FormField (field semantics)
      → Input / field molecules / InputGroup
```

## 6. Accessibility semantics

- Native `<fieldset>` / `<legend>` — no redundant `role="group"`.
- Legend provides the accessible name; no `aria-label` on the fieldset.
- `description` → stable id + `aria-describedby` on the fieldset when present.
- `error` → `role="alert"` on group message; **does not** set `aria-invalid` on children.
- Tab order and control naming remain on child controls.

## 7. Legend behavior

Required `legend` prop rendered as `<legend>` with `labelBase` styling (aligned with V2 ad hoc fieldsets).

## 8. Description behavior

Optional section copy via `description`. Hidden when `error` is shown (same precedence as FieldGroup hint vs error). Does not replace FormField hints.

## 9. Required semantics

`required={true}` adds visible asterisk + sr-only “(required)” on the **legend only**. Does not mark nested fields as HTML `required` or imply every child is mandatory.

## 10. Group error semantics

Optional `error` communicates a **section-level** constraint (e.g. incomplete section). Distinct from FormField validation messages. No child prop injection.

## 11. Non-features

FieldSet does not manage form state, discover fields, validate, submit, or inspect React child types.

## 12. V2 inspection findings (read-only)

| Finding | Detail |
|---------|--------|
| Reusable FieldSet molecule | **None** |
| Patterns | Ad hoc `<fieldset>` / `<legend className="mb-2 text-sm font-medium text-ink">` in Register (account type), Profile / Employers (company locations), CandidateProfessionalSections (preferred locations/job types), Freelancers (geographic coverage), Reviews (rating), Admin Roles (permission groups), UI Lab Radio section |
| Visual treatment | Borderless fieldsets; legend as section heading; spacing via margins on children |
| V2 unchanged | No migration in PR-3V |

## 13. V2 was NOT modified

Architecture + UI Lab + docs only.

## 14. Validation results

| Check | Result |
|-------|--------|
| `npm run typecheck` | **PASS** |
| `npm run build` | **PASS** |
