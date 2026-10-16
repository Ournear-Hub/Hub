# OURNEAR Skeleton — PR-4G

**Status:** Implemented (architecture phase — **V2 not modified**)

## 1. Purpose

Low-level loading primitive: content is expected but not yet available. Communicates loading through shape and motion.

## 2. Skeleton vs EmptyState

EmptyState = zero data after load completes. Skeleton = content still loading.

## 3. Skeleton vs Alert / Toast / error

Not feedback about events or failures. Not transient notifications.

## 4. Skeleton vs Spinner (future atom)

Spinner = indeterminate busy indicator on a control or small region. Skeleton = layout-preserving placeholders for upcoming content.

## 5. API

```tsx
<Skeleton
  variant="text" | "rect" | "circle"   // default text
  width?                               // string | number (px if number)
  height?
  className?
/>
```

## 6. Variants

| Variant | Default shape |
|---------|----------------|
| `text` | Full-width line, `h-4`, rounded |
| `rect` | Full-width block, `min-h-5`, `rounded-md` |
| `circle` | `size-10`, `rounded-full` |

`width` / `height` apply inline styles. For `circle`, a single dimension sets both axes when the other is omitted.

## 7. Motion

Tailwind `animate-pulse` on `bg-surface`. **`motion-reduce:animate-none`** — static `bg-line` when user prefers reduced motion (no custom keyframes in app.css).

## 8. Accessibility

Each `Skeleton` is **decorative**: `aria-hidden="true"`, `role="presentation"`.

Loading must be announced on a **parent** region:

- `aria-busy="true"` while loading
- Visible and/or `sr-only` loading label (e.g. “Loading jobs”)

Do not put `role="status"` or live regions on individual skeleton shapes.

## 9. Composition

Pages/organisms compose multiple Skeleton atoms (cards, table rows, form fields). PR-4G does not ship preset templates — UI Lab shows reference compositions only.

## 10. Styling

Recipes: `skeletonBase`, `skeletonVariantText`, `skeletonVariantRect`, `skeletonVariantCircle` in `classNames.ts`. Uses foundation `surface` / `line` tokens — no second palette.

## 11. V2 inspection findings (read-only)

1. **No reusable Skeleton component** in V2.
2. **No `animate-pulse` / shimmer** usage found under `resources/js`.
3. **No centralized skeleton recipes** — loading UX is mostly immediate render or control-level busy states.
4. **`prefers-reduced-motion`** — not applied to loading placeholders in V2 (no skeleton layer to adjust).
5. **`aria-busy`** — used on canonical **Button** and **IconButton** when `loading` (spinner icon), not on content placeholder regions.
6. **Placeholder content** — tables use **EmptyState** when `rows.length === 0`, not skeleton rows while fetching.

## 12. Non-features

No skeleton molecule, no fake text/lorem generator, no global loading provider, no data fetching integration, no V2 migration, no new dependencies.

## 13. Confirmation V2 was not modified

V2 unchanged.

## 14. UI Lab

`/admin/design-system/atoms/skeleton` — variants, dimensions, card/table compositions, parent `aria-busy` pattern.

## 15. Validation results

- `npm run typecheck` — **PASS**
- `npm run build` — **PASS**

## 16. Next work

**STOP** — PR-4G frozen; do not start PR-4H automatically.
