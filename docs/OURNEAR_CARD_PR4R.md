# OURNEAR Card — PR-4R

**Status:** Implemented (architecture phase — **V2 not modified**)

## 1. Architectural classification

**Atom** — single generic bounded surface (`<div>` + children). No CardHeader/Body/Footer; composition stays caller-owned.

## 2. Purpose

Structural container for grouping related content (dashboard blocks, settings sections, previews). Not business-specific cards.

## 3. API

```tsx
<Card
  padding="none" | "compact" | "standard" | "feature"   // default standard
  className?
>
  {children}
</Card>
```

No `surface` prop — V2/admin/public structural treatment is the same white + `border-line` baseline.

## 4. Surface contract

Bounded surface: **`bg-white`**, **`border border-line`**, canonical radius from **`cardDensity`** (`rounded-lg` compact, `rounded-xl` standard/feature). Shadow only via existing density recipes (`shadow-sm` / `shadow-md` on feature).

## 5. Padding contract

Maps to pre-existing **`cardDensity`** in `classNames.ts`:

| padding | Recipe |
|---------|--------|
| none | Border + bg only (caller inner spacing) |
| compact | `p-3`, rounded-lg, shadow-sm |
| standard (default) | `p-5 sm:p-6`, rounded-xl, shadow-sm |
| feature | `p-6 sm:p-8`, rounded-xl, shadow-md |

## 6. Visual contract

Uses foundation tokens only — no new colors, radius tokens, or shadow system.

## 7. Interaction boundary

No `href`, `onClick`, `selected`, or interactive roles. Clickable cards = caller wraps content or places Link/LinkButton inside.

## 8. Accessibility contract

Non-semantic **`<div>`**. No auto `role`, `aria-label`, or landmarks. Callers supply headings (`Heading`) or regions when needed.

## 9. Composition rules

Plain `children` — no cloning, slot inference, or injected spacing between regions.

## 10. Relationship to existing primitives

May wrap Heading, Text, Badge, Chip, Avatar, Image, Button, Link, LinkButton, etc. Must not import JobCard or other V2 business components.

**Avatar / Image / EmptyState** remain separate primitives (EmptyState has its own dashed surface).

## 11. UI Lab route

`/admin/design-system/atoms/card`

## 12. V2 evidence (read-only)

- **`cardDensity`** already in design-system (foundation tier).
- **`Components/Admin/ui/Card.tsx`** — simple `section` + `rounded-xl border bg-white p-5` (not migrated).
- **JobCard** — interactive, domain-specific styling (selection, urgent, etc.).
- No shared generic V2 Card used app-wide.

## 13. Explicit non-goals

JobCard, compound subcomponents, surface variants without evidence, migration of Admin/ui/Card, loading/error/status inside Card.

## 14. Migration status

**V2 was inspected READ-ONLY. No V2 application code was modified or rebuilt.**
