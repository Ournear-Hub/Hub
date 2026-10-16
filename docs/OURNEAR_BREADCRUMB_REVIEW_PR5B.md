# OURNEAR Breadcrumb review — PR-5B

**Status:** Implemented (architecture phase — **V2 not modified**)

## 1. Decision

**Breadcrumb was created** as a canonical **molecule**.

Singular name: **`Breadcrumb`** (`Components/molecules/Breadcrumb.tsx`). V2 **`Breadcrumbs`** (Navigation) remains unchanged.

## 2. Architectural classification

**Molecule** — ordered breadcrumb **landmark** composition (nav + ol/li + links + current page), not generic horizontal layout.

## 3. Rationale

V2 **`Components/Navigation/Breadcrumbs.tsx`** is shared by **AdminLayout** and **AccountLayout** with a **stable** contract:

- `<nav aria-label="Breadcrumb">`
- `<ol>` of items
- Decorative **`/`** separators (`aria-hidden`)
- Ancestors: link styling (V2: Inertia `Link`)
- Final item: **`aria-current="page"`**, no link when last (even if `href` present on data)

Unlike NavLink (PR-5A), breadcrumb **semantics and separator** are consistent across contexts. A molecule governs IA/a11y without router logic.

## 4. API

```tsx
type BreadcrumbItem = { label: string; href?: string };

<Breadcrumb
  items={BreadcrumbItem[]}
  ariaLabel?: string   // default "Breadcrumb"
  className?
/>
```

- Empty `items` → render `null`.
- **Caller** supplies hierarchy and URLs; no URL inspection.
- **Last item** = current page (`aria-current="page"`). Ancestors with `href` and not last → canonical **Link**.

## 5. Semantic contract

```html
<nav aria-label="…">
  <ol>
    <li>…</li>
  </ol>
</nav>
```

No manual `role=list` / `role=navigation`.

## 6. Visual contract

Recipes: `breadcrumbNav`, `breadcrumbList`, `breadcrumbItem`, `breadcrumbLink`, `breadcrumbCurrent` in `classNames.ts` (matches V2 spacing/typography). Ancestors use **Link** (`underline="hover"`, brand). No page header, container, or surface.

## 7. Interaction contract

Native anchors for ancestors; keyboard/focus from **Link**. No custom keyboard model.

## 8. Accessibility contract

- Default **`aria-label="Breadcrumb"`** (overridable).
- **`aria-current="page"`** on final item only (structure-based, not URL-derived).
- Separators **decorative** only.

## 9. Composition contract

Caller-owned `items`; no Inertia/router, cloning, context, or route generation.

## 10. Link relationship

Ancestors render **`Link`** atom (native `<a href>`). Does not replace **Link** for inline/body links.

## 11. Cluster relationship

**Cluster** is generic horizontal layout. **Breadcrumb** owns **ordered list + landmark** semantics — not `Cluster + Link`.

## 12. Heading relationship

**Breadcrumb** is wayfinding; **Heading** is page title/content hierarchy — not included.

## 13. Navigation-shell relationship

Layouts pass `items` from pages/controllers (future migration). Shells keep Inertia V2 component until adoption phase.

## 14. UI Lab route

`/admin/design-system/molecules/breadcrumb`

## 15. V2 inspection status

Read-only — `Navigation/Breadcrumbs.tsx`, `AdminLayout.tsx`, `AccountLayout.tsx`. No chevron separators in app breadcrumbs; slash only.

## 16. V2 modification status

**None.**

## 17. Dependencies

**None.**

## 18. Deviations

Design-system **Link** replaces Inertia for ancestors in the molecule only; V2 file unchanged.
