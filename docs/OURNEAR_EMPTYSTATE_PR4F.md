# OURNEAR EmptyState — PR-4F

**Status:** Implemented (architecture phase — **V2 not modified**)

## 1. Purpose

Communicate that a page, section, search, filter, or collection has **no content to show yet** — not an error, not loading, not a missing route.

## 2. EmptyState vs Alert

Alert is event/condition feedback (success, warning, failure). EmptyState is a neutral zero-data layout.

## 3. EmptyState vs Toast

Toast is transient portaled feedback. EmptyState is persistent in-flow content.

## 4. EmptyState vs FormField error

Field errors attach to a control. EmptyState describes absence of records/results at section level.

## 5. EmptyState vs loading / error / 404

- **Loading / skeleton** — content pending.
- **Error state** — operation failed.
- **NotFound / 404** — resource or route does not exist.
- **EmptyState** — successful empty result or empty collection.

## 6. API

```tsx
<EmptyState
  icon?              // registry name or ReactNode
  title?
  description?
  action?
  secondaryAction?
  align="center" | "start"   // default center
  size="sm" | "md" | "lg"    // default md
  className?
>
  {children}
</EmptyState>
```

## 7. Content model

Optional icon, title, description, `children`, and one or two action slots. Caller supplies action nodes (e.g. canonical `Button`). No built-in routing or CTA registry.

## 8. Layout & styling

Dashed bordered surface (`emptyStateRoot`), flex column, align and size recipes in `classNames.ts`. Matches V2 public empty panel vocabulary (rounded-xl, border-dashed, bg-white) without changing V2 components.

## 9. Icon behavior

String `icon` resolves via `OurNearIcon`. Custom nodes render in a muted wrapper. Decorative when paired with title/description.

## 10. Accessibility

- No default `role="alert"` or live region — not urgent feedback.
- Title is styled `<p>`, not an arbitrary heading level (page owns heading hierarchy).
- Action controls must expose their own accessible names.

## 11. Align & size

- **center** — table/card-style empty panels (default).
- **start** — inline sections (e.g. admin table body).
- **sm / md / lg** — padding and title scale only.

## 12. Form / data integration

Composable inside cards, table empty rows, dashboard sections. No FormField coupling.

## 13. V2 inspection findings (read-only)

- **`Components/UI/EmptyState.tsx`**: dashed border, centered, `h2` + section title typography, optional description and single `action`.
- **`Components/Admin/ui/EmptyState.tsx`**: minimal centered title + description (no border/icon/actions); used from `DataTable` via `emptyTitle` / `emptyDescription`.
- **Home / listings**: “no jobs” / “no companies” patterns with CTA buttons.
- Two parallel V2 components — canonical molecule unifies optional icon, secondary action, align, and size for **new** usage only.

## 14. Non-features

No illustrations asset pipeline, no global empty-state provider, no automatic “Create your first …” copy, no V2 adapter or migration, no new dependencies.

## 15. Confirmation V2 was not modified

V2 `Components/UI/EmptyState.tsx`, `Components/Admin/ui/EmptyState.tsx`, and product pages unchanged.

## 16. Validation results

- `npm run typecheck` — **PASS**
- `npm run build` — **PASS**
