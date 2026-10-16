# OurNear foundation — PR-1 (tokens + OurNearIcon)

## Token ownership

| Tier | Location | Purpose |
|------|----------|---------|
| 1 — Primitives | `resources/css/app.css` (`@theme`) | Colors, radius, shadows, font (source of truth for CSS) |
| 1 — JS mirror | `resources/js/foundation/tokens.ts` | Documented constants for TS consumers |
| 2 — Semantic | Tailwind utilities (`text-ink`, `bg-canvas`, …) | Use in components |
| 3 — Component | `resources/js/design-system/classNames.ts` | Focus ring, controls, typography, card density |

**Rule:** Change visual primitives in `app.css` first, then sync `foundation/tokens.ts` if values are documented there.

## Icons

- Registry: `resources/js/icons/registry.ts`
- Wrapper: `import { OurNearIcon } from '@/icons'`
- Tabler imports allowed **only** in `resources/js/icons/tablerComponents.ts`

### New code

Use `OurNearIcon` with registry `name` (kebab-case).

### Existing V2

`Components/UI/icons.tsx` remains valid; do not mass-migrate in PR-1.

### Invalid names

Development: `console.warn` + empty placeholder (`data-missing-icon`). Production: same placeholder, no throw.

### Sizes

`xs` 16px, `sm` 20px, `md` 24px, `lg` 32px, `xl` 40px.

## PR-2 — Button & IconButton

| Item | Location |
|------|----------|
| Canonical Button | `resources/js/Components/atoms/Button.tsx` |
| Canonical IconButton | `resources/js/Components/atoms/IconButton.tsx` |
| Public alias | `Components/Public/Button.tsx` → `surface="public"` |
| Admin alias | `Components/Admin/ui/Button.tsx` → `surface="admin"` |
| IconButton alias | `Components/UI/IconButton.tsx` → `surface="public"` |

**Primary by surface:** public/account → accent `#F15A24`; admin/corporate → brand `#214449`.

**Proof import (optional):** `@/Components/atoms` (e.g. `Pages/About.tsx`).

## Phase 1 closure

See [OURNEAR_PHASE_1_CLOSURE.md](./OURNEAR_PHASE_1_CLOSURE.md) (**PASS**). Phase 2 plan: [OURNEAR_CONTROLLED_PHASE_2_IMPLEMENTATION_PLAN_v1.0.md](./OURNEAR_CONTROLLED_PHASE_2_IMPLEMENTATION_PLAN_v1.0.md).

## PR-3A — Badge & StatusBadge

| Item | Location |
|------|----------|
| Canonical Badge | `resources/js/Components/atoms/Badge.tsx` |
| Tone StatusBadge | `resources/js/Components/atoms/StatusBadge.tsx` |
| UI alias | `Components/UI/StatusBadge.tsx` |
| Admin adapter | `Components/Admin/ui/StatusBadge.tsx` (status string map) |
| Account adapter | `Components/Account/StatusBadge.tsx` |

Details: [OURNEAR_BADGE_PR3A.md](./OURNEAR_BADGE_PR3A.md).
