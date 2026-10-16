# OURNEAR Link — PR-4K

**Status:** Implemented + routing correction (architecture phase — **V2 not modified**)

## 1. Purpose

Canonical **navigation styling** on a native anchor — consistent OurNear link appearance without owning routing policy.

## 2. Link vs Button / IconButton / URLField

| Link atom | Not Link atom |
|-----------|----------------|
| Styled `<a href>` | Button actions, menu commands, URL **input** |

## 3. API

```tsx
<Link
  href={string}
  variant="default" | "muted" | "subtle"     // default default
  underline="none" | "hover" | "always"      // default hover
  className?
  {...standardAnchorAttributes}                // target, rel, onClick, aria-*, etc.
>
  {children}
</Link>
```

**Removed from atom (correction):** `external` prop and any automatic Inertia / `_blank` / `rel` behavior.

## 4. Routing boundary (non-negotiable)

The Link atom **always** renders:

```tsx
<a href="…" className="…">…</a>
```

- **No** Inertia `Link` inside the atom.
- **No** automatic internal vs external detection.
- **No** default `target="_blank"` or `rel="noopener noreferrer"`.

SPA navigation (Inertia), full page loads, and external-tab policy are **caller / page / shell** concerns. V2 continues to use `@inertiajs/react` `Link` directly where needed; the atom can wrap styling only when product chooses to adopt it later.

## 5. Variants & underline

- **default** — `text-brand` (aligns with Button link variant tone).
- **muted** — `text-muted` → `hover:text-ink`.
- **subtle** — softened ink → full ink on hover.
- Underline: none, hover-only (default), or always (`underline-offset-2`).

## 6. Accessibility

`focusRing` on the anchor. Not a `<button>`. Caller sets `target` / `rel` for new tabs when required.

## 7. Non-features

No routing policy, no disabled/active props, no icon slot, no V2 migration, no new dependencies.

## 8. V2 inspection findings (read-only)

1. **No reusable Link atom** — pages/layouts import Inertia `Link` with local class strings.
2. **Common treatments** — brand/muted text, hover underline; header nav active state on parent wrappers.
3. **Button `variant="link"`** — action styled as link; unchanged on Button.

## 9. Confirmation V2 was not modified

V2 unchanged.

## 10. UI Lab

`/admin/design-system/atoms/link`

## 11. Validation results

- `npm run typecheck` — **PASS** (after correction)
- `npm run build` — **PASS**

## 12. Next work

**STOP** — PR-4K frozen after this correction; do not start PR-4L.
