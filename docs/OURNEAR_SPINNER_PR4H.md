# OURNEAR Spinner — PR-4H

**Status:** Implemented (architecture phase — **V2 not modified**)

## 1. Purpose

Compact active-loading indicator for in-progress operations (panels, pages, async actions). Not a content placeholder.

## 2. Spinner vs Skeleton

| Skeleton | Spinner |
|----------|---------|
| Shape of upcoming content | Active operation |
| Lists/cards/tables layout | Buttons, regions, overlays |

## 3. Spinner vs Progress

Progress (future) communicates measurable completion. Spinner is indeterminate.

## 4. Spinner vs Toast / Alert

Not transient or persistent feedback messages.

## 5. API

```tsx
<Spinner
  size="sm" | "md" | "lg"   // default md
  label?                     // aria-label; default "Loading"
  className?                 // on status wrapper (e.g. text-brand)
/>
```

## 6. Icon architecture

Uses **`OurNearIcon` `name="spinner"`** (registry inline → `SpinnerIcon` SVG). No direct Tabler loader imports in the atom.

Size map: `sm` → icon `xs`, `md` → `sm`, `lg` → `md` (aligned with Button loading icon scale).

## 7. Motion

`animate-spin` applied inside OurNearIcon inline spinner path. **`motion-reduce:animate-none`** on icon via `spinnerIcon` recipe.

## 8. Accessibility

- Wrapper: **`role="status"`** + **`aria-label`** (default **Loading** or custom `label`).
- Icon: **decorative** (`aria-hidden` via OurNearIcon).
- Large regions: combine with parent **`aria-busy="true"`** when appropriate (lab demo).

## 9. Button / IconButton boundary

Canonical **Button** and **IconButton** keep inline **`OurNearIcon` spinner** + **`aria-busy`** on the control — unchanged in PR-4H. Spinner atom is for standalone loading UI, not a forced refactor of buttons.

## 10. Styling

`spinnerRoot`, `spinnerIcon` in `classNames.ts`. `text-current` inherits tone from parent (`text-brand`, `text-accent`, etc.).

## 11. V2 inspection findings (read-only)

1. **Registry** — `spinner` entry: `source: 'inline'`, `inlineKey: 'spinner'`, alias `loading`.
2. **OurNearIcon** — inline spinner renders `Components/UI/icons` **`SpinnerIcon`** with **`animate-spin`** (not Tabler `Loader2`).
3. **No standalone V2 Spinner component.**
4. **Button / IconButton** — `loading` prop → decorative `OurNearIcon` spinner; native button **`aria-busy={loading}`**.
5. **No broad `animate-spin`** usage outside the icon layer in quick scan of `resources/js`.
6. **No page/table overlay spinner library** — loading UX is control-level or full page render via Inertia.

## 12. Non-features

No progress bar, no skeleton fusion, no global loading provider, no V2 Button migration to Spinner atom, no new dependencies.

## 13. Confirmation V2 was not modified

V2 unchanged.

## 14. UI Lab

`/admin/design-system/atoms/spinner`

## 15. Validation results

- `npm run typecheck` — **PASS**
- `npm run build` — **PASS**

## 16. Next work

**STOP** — PR-4H frozen; do not start PR-4I automatically.
