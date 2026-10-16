# OURNEAR Container — PR-4S

**Status:** Implemented (architecture phase — **V2 not modified**)

## 1. Architectural classification

**Atom** — generic horizontal content boundary (`<div>`). Not a page shell, grid, or visual surface.

## 2. Purpose

Standard **max-width**, **centering** (`mx-auto`), and optional **horizontal gutters** for pages, sections, and future organisms/templates.

## 3. API

```tsx
<Container
  size="sm" | "md" | "lg" | "xl" | "2xl" | "full"   // default md
  gutter="none" | "sm" | "md" | "lg"                 // default md
  className?
>
  {children}
</Container>
```

No `surface` prop — not a Card.

## 4. Width contract

| size | Tailwind | V2 reference |
|------|----------|--------------|
| sm | max-w-3xl (48rem) | PageContainer `narrow` |
| md | max-w-6xl (72rem) | PageContainer `default`, AdminContainer |
| lg | max-w-7xl (80rem) | PageContainer `wide` |
| xl | max-w-screen-xl | extended scale |
| 2xl | max-w-screen-2xl | extended scale |
| full | max-w-none | uncapped width |

Shared: `containerRoot` → `mx-auto w-full min-w-0`.

## 5. Gutter contract

| gutter | Classes |
|--------|---------|
| none | (shell owns padding — admin main pattern) |
| sm | px-4 |
| md | px-4 sm:px-6 lg:px-8 (V2 PageContainer) |
| lg | px-6 sm:px-8 lg:px-10 |

Default **md** matches public page gutters.

## 6. Responsive contract

Gutters use CSS breakpoints only. No JS viewport logic.

## 7. Semantic contract

Plain `<div>`. No `role`, `aria-*`, or landmarks.

## 8. Composition contract

Caller-owned `children` only. No slots or child inspection.

## 9. Distinction from Card

| Container | Card |
|-----------|------|
| Width + gutters | Visual bounded surface |
| No bg/border/shadow | bg, border, radius, shadow |

Composable: `<Container><Card>…</Card></Container>`.

## 10. UI Lab route

`/admin/design-system/atoms/container`

## 11. V2 evidence (read-only)

- **`PageContainer`**: `max-w-6xl` / `3xl` / `7xl` + responsive px.
- **`AdminContainer`**: `max-w-6xl` only; **`AdminLayout`** `main` uses `p-4 sm:p-6`.
- Differences are **shell vs inner width**, not four product containers — one canonical **Container** with `gutter="none"` for admin-style composition.

## 12. Explicit non-goals

PageContainer/AdminContainer migration, Public/Admin/Account variants, grid/flex layout system, interactive cards, V2 shell redesign.

## 13. Migration status

**V2 was inspected READ-ONLY. No V2 application code was modified or rebuilt.**
