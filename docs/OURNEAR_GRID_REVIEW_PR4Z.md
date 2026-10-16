# OURNEAR Grid review — PR-4Z

**Status:** Review complete — **no Grid primitive**. **V2 not modified.**

## Decision

**Grid was reviewed and not justified as a canonical design-system primitive.**

## Classification

No new component. Use native CSS Grid (Tailwind `grid`, `grid-cols-*`, `gap-*`) with **Container**, **Stack**, **Cluster**, **Card**, **AspectRatio**.

## V2 evidence (read-only)

- **Common:** card grids `md:grid-cols-2 xl:grid-cols-3` (`Home.tsx`); KPI grids `sm:grid-cols-2 xl:grid-cols-5` (`Admin/Dashboard.tsx`); asymmetric `lg:grid-cols-[minmax(0,16rem)_minmax(0,1fr)]` (`About.tsx`); lab `lg:grid-cols-[1fr_320px]`.
- **No** shared `Grid.tsx` / `ResponsiveGrid` / `CardGrid`.
- **Gaps** often match Stack scale (`gap-4`, `gap-6`) but **column breakpoints differ per page**.
- **Forms:** `FieldGroup` uses flex, not a grid atom.

## Why not justified

PR-4Z requires a **durable shared contract** and **small API**. V2 grids are **page-specific** (2→3 vs 1→2→5 vs template columns). A canonical Grid would duplicate Tailwind or need many responsive props — both forbidden.

## Composition guidance

```tsx
<Container>
  <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">{children}</div>
</Container>
```

1D rhythm: **Stack** / **Cluster**. Surfaces: **Card**. Media cells: **AspectRatio** + **Image**.

## Relationships

| Primitive | Role |
|-----------|------|
| Container | Width/gutters |
| Stack / Cluster | 1D spacing |
| Card | Item surface |
| AspectRatio | Ratio in cell |

## Responsive / a11y

CSS-only breakpoints on grid classes. Neutral wrapper; children own semantics.

## UI Lab

None.

## Non-goals

No ResponsiveGrid, CardGrid, DashboardGrid, FormGrid, or grid token scale. No V2 changes.

## Validation

Documentation-only — typecheck/build not run.
