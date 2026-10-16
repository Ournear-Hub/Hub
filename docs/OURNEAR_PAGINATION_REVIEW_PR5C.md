# OURNEAR Pagination review — PR-5C

**Status:** Implemented (architecture phase — **V2 not modified**)

## 1. Decision

**Pagination was created** as a canonical **molecule**.

Singular name: **`Pagination`** (`Components/molecules/Pagination.tsx`). V2 **`Components/Pagination.tsx`** remains unchanged.

## 2. Architectural classification

**Molecule** — paginated **landmark navigation** (nav + caller-supplied page links), distinct from breadcrumb hierarchy, tabs, segmented filters, and inline text links.

## 3. Evidence inspected (read-only)

| Source | Finding |
|--------|---------|
| `Components/Pagination.tsx` | Single shared implementation: `links` prop, Inertia `Link`, `nav aria-label="Pagination"`, pill styling, `url === null` → disabled span, `active` → brand fill, `links.length <= 3` → `null`. |
| Import graph | **39** consumers (Admin index pages, Account lists, public `Pages/Jobs/Index.tsx`, `Components/Admin/ApplicationWorkspacePage.tsx`). Uniform `<Pagination links={…} />` usage with Laravel/Inertia paginator `.links`. |
| Alternate UI | No second pagination component; no other `aria-label="Pagination"` markup in `resources/js`. |
| Mobile/compact | No separate compact variant — V2 uses `flex flex-wrap gap-1` only. |

## 4. Rationale

Unlike PR-5A (NavLink rejected), pagination is **one component, one data shape, one visual/interaction pattern** repeated across admin, account, and public job search. Central governance preserves:

- Laravel-compatible **link model** without coupling to PHP classes
- **Landmark** semantics and current-page indication
- Shared **pill** visual contract
- **Native anchors** in the design system (no Inertia in the molecule)

Heterogeneity is low; repetition is high.

## 5. API contract

```tsx
type PaginationLink = {
  url: string | null;
  label: string;
  active: boolean;
};

<Pagination
  links={PaginationLink[]}
  ariaLabel?: string   // default "Pagination"
  className?
/>
```

- **`links`**: caller-owned (typically `paginator.links` from the server). No URL generation in the component.
- **`links.length <= 3`**: render `null` (V2 hide rule for single-page result sets).
- **No** Inertia, route detection, fetch, or paginator type imports.

## 6. Semantic contract

```html
<nav aria-label="Pagination">
  <!-- mix of <a> (Link atom), span[aria-current=page], span[disabled styling] -->
</nav>
```

Flat list of controls inside `nav` (matches V2). Not an ordered list; not a tablist.

## 7. Accessibility contract

- Default **`aria-label="Pagination"`** (overridable).
- **Current page**: non-link **`span`** with **`aria-current="page"`** when `active` (preferred over linking the current page).
- **Disabled / ellipsis**: `url === null` → non-interactive **`span`**; ellipsis **`...`** is **`aria-hidden`**.
- **Previous/next**: visible Laravel labels when present; glyph-only **`«` / `»`** links get **`aria-label`** (`Previous page` / `Next page`).
- No redundant `role=navigation` on inner elements.

## 8. Visual contract

Recipes in `classNames.ts` (aligned with V2):

- `paginationNav` — `mt-2 flex flex-wrap gap-1 lg:mt-0`
- `paginationItemBase` — rounded pill padding, `text-sm`
- `paginationItemLink` — border, white surface, hover surface
- `paginationItemCurrent` — brand fill, white text
- `paginationItemDisabled` — `text-disabled`

Labels: decode `&laquo;` / `&raquo;` to `«` / `»` as text (no `dangerouslySetInnerHTML` in the molecule).

## 9. Interaction contract

- Navigable pages: **GET-style** navigation via **Link** (`href` from caller).
- Keyboard/focus from native anchors.
- No client-side page state, callbacks, or disabled-button semantics on anchors.

## 10. Composition / data contract

- Caller passes **full link array** including Previous, numeric pages, ellipsis, Next.
- Component does **not** compute page numbers, totals, or hrefs.
- **Hide-when-few-links** is presentational guard only (not business logic).

## 11. Link relationship

Page links use **`Link`** atom (`underline="none"`, pill classes). Does not replace **Link** for body/inline navigation or **LinkButton** for CTA styling.

## 12. Relationship to other navigation components

| Component | Role |
|-----------|------|
| **Breadcrumb** | Hierarchy / IA trail |
| **Pagination** | Index pages within a result set |
| **SegmentedControl** | In-page mode/filter (radiogroup) |
| **NavLink** (not created) | Ad hoc sidebar/header items |
| **Tabs** (future) | Same-page section switching |

## 13. UI Lab route

`/admin/design-system/molecules/pagination`

## 14. Explicit non-goals

- Laravel `LengthAwarePaginator` / Inertia types in the molecule
- URL/query building, `router.visit`, or `window.location`
- Table-embedded pagination UX, infinite scroll, load-more buttons
- First/last icons beyond what Laravel labels provide
- V2 migration or replacing `Components/Pagination.tsx`

## 15. V2 inspection status

**Read-only** — complete for pagination surfaces listed above.

## 16. V2 modification status

**None.**

## 17. Migration status

**Not started** (architecture only). Future adoption may swap import to `@/Components/molecules/Pagination` and wrap with Inertia at the page layer if needed.

## 18. Dependencies

**None** added.

## 19. Deviations from V2 (molecule only)

- **Link** atom instead of Inertia `Link`
- **Current page** as non-link **`span`** + **`aria-current="page"`** (V2 kept active items as styled links)
- Text labels instead of **`dangerouslySetInnerHTML`**
- Optional **`aria-label`** for glyph-only prev/next

V2 file and all consumers **unchanged**.

## 20. Verification

- `npm run typecheck` — run in PR-5C completion
- `npm run build` — run in PR-5C completion
- UI Lab route wired in `routes/admin.php`, `DesignSystemController`, `Show.tsx`, `metadata.ts`
