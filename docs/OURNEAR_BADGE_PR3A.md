# OURNEAR Badge / StatusBadge — PR-3A

**Status:** Implemented (Badge foundation)

## Canonical paths

| Component | Path |
|-----------|------|
| **Badge** | `resources/js/Components/atoms/Badge.tsx` |
| **StatusBadge** (semantic tone) | `resources/js/Components/atoms/StatusBadge.tsx` |

Barrel: `@/Components/atoms` exports `Badge`, `StatusBadge`.

## Semantic variants

Mapped to `statusTone` in `design-system/classNames.ts`:

- `success`
- `warning`
- `error`
- `neutral`
- `info`
- `brand`

No domain variants (candidate, job, application, etc.) on Badge.

## Sizes

- `sm` — compact padding
- `md` — default (matches existing V2 pill badges)

## Architecture

```text
Badge (domain-neutral presentation)
  ↓
StatusBadge (tone / variant prop + children)
  ↓
Surface adapters (domain status string → presentation classes)
  ├── Components/UI/StatusBadge.tsx → re-export atoms/StatusBadge
  ├── Components/Admin/ui/StatusBadge.tsx → adminStatusPresentation map
  └── Components/Account/StatusBadge.tsx → accountStatusPresentation map
```

Domain features pass **status labels as data**; adapters map label → Tailwind presentation via `toneClassName` on Badge.

## Known cross-surface differences

Same English label can map to different presentation on **account** vs **admin** (preserved intentionally):

| Label | Account | Admin |
|-------|---------|-------|
| `Under Review` | accent tint | warning tint |
| `Reviewing` | accent tint | brand tint |
| `Shortlisted` | brand tint | success tint |
| `On Hold` | surface + ink | surface + muted |

Do not merge maps without an explicit product decision.

## Compatibility

- Existing import paths unchanged.
- ~46 StatusBadge consumers unchanged in this PR.
- Admin map vocabulary unchanged (90+ status keys).

## Proof surface

- **Canonical:** `Components/Profile/ProfileCompletionCenter.tsx` uses `Badge` with local module status → semantic variant mapping.
- **Legacy:** remaining consumers continue `@/Components/UI/StatusBadge`, `@/Components/Admin/ui`, or `@/Components/Account/StatusBadge`.

## Deferred

Mass migration of StatusBadge consumers, FilterChip molecule, Input/Label/Typography atoms (separate PRs).
