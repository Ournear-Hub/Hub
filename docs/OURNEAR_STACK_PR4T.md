# OURNEAR Stack — PR-4T

**Status:** Implemented (architecture phase — **V2 not modified**)

## 1. Architectural classification

**Atom** — vertical sibling spacing via `flex-col` + CSS `gap`.

## 2. Purpose

Consistent **vertical rhythm** between sibling elements (forms, card bodies, settings groups). Not a grid, shell, or Container.

## 3. API

```tsx
<Stack
  gap="xs" | "sm" | "md" | "lg" | "xl"     // default md
  align="stretch" | "start" | "center" | "end"   // default stretch
  className?
>
  {children}
</Stack>
```

## 4. Gap contract

| gap | Tailwind | px (spacingScalePx) | Notes |
|-----|----------|---------------------|--------|
| xs | gap-1 | 4 | tight |
| sm | gap-2 | 8 | FieldGroup `sm` |
| md | gap-4 | 16 | default; FieldGroup `md` |
| lg | gap-6 | 24 | UI Lab `space-y-6`; FieldGroup `lg` |
| xl | gap-8 | 32 | section spacing |

## 5. Alignment contract

Maps to `align-items`: stretch (default), start, center, end. No justify/direction/wrap props.

## 6. Layout contract

`stackRoot` → `flex flex-col` + gap class. Native CSS gap only — no margin hacks or child selectors.

## 7. Spacing ownership

Stack owns **sibling gap** only. Outer margin, padding, and width remain caller/Container responsibilities.

## 8. Responsive boundary

No breakpoint props or JS viewport logic.

## 9. Semantic contract

Plain `<div>`. No roles, landmarks, or list semantics.

## 10. Composition contract

Caller-owned children; no cloning or child-type inference.

## 11. Relationship to Container

```tsx
<Container>
  <Stack>...</Stack>
</Container>
```

Container = horizontal boundary; Stack = vertical spacing.

## 12. Relationship to Card

Card = visual surface. Stack may lay out content inside a Card; Card is not modified to embed Stack.

## 13. Relationship to forms

Stack may wrap FormFields for layout. Stack does not provide labels, validation, or FieldGroup legend semantics — use **FieldGroup** / **FormField** for form structure.

## 14. UI Lab route

`/admin/design-system/atoms/stack`

## 15. V2 evidence (read-only)

- Repeated **`space-y-*`** (e.g. UI Lab `space-y-6`).
- **FieldGroup** — `flex flex-col` + gap-2/4/6 with form legend (not duplicated).
- No canonical V2 Stack component.

## 16. Explicit non-goals

Generic flexbox API, responsive arrays, V2 migration, FieldGroup replacement, Card/Container changes.

## 17. Migration status

**V2 was inspected READ-ONLY. No V2 application code was modified or rebuilt.**
