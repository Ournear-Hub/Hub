# OURNEAR Typography — PR-3B

**Status:** Implemented  
**Scope:** Canonical Text, Heading, and Label atoms only.

## Purpose

Establish governed typography primitives that consume the existing OurNear foundation (`app.css` @theme, `foundation/tokens.ts`, `design-system/classNames.ts`) without replacing or redesigning those layers.

## Canonical components

| Atom | Path |
|------|------|
| Text | `resources/js/Components/atoms/Text.tsx` |
| Heading | `resources/js/Components/atoms/Heading.tsx` |
| Label | `resources/js/Components/atoms/Label.tsx |

Barrel export: `@/Components/atoms`.

## API summary

### Text

- `as?: 'p' | 'span' | 'div'` (default `p`)
- `variant?: 'body' | 'secondary' | 'caption' | 'eyebrow'` → `typography.*`
- `tone?: 'default' | 'muted' | 'subtle' | 'disabled' | 'danger' | 'success' | 'warning'`
- `className`, `children`

### Heading

- `level: 1 | 2 | 3 | 4 | 5 | 6` → native `h1`–`h6`
- Level styles map to existing typography utilities (pageTitle, sectionTitle, cardTitle, body/secondary/caption semibold)
- `className`, `children`

### Label

- `htmlFor?`, `required?`, `className`, `children`
- Uses `labelBase` from `classNames.ts`
- Required: visible `*` + `sr-only` “(required)”

## Dependencies

- **No new tokens or font sizes**
- **No changes** to `app.css`, `classNames.ts`, or `tokens.ts` in this PR

## Accessibility

- Text: semantic element via `as`
- Heading: correct heading level in DOM
- Label: native `<label>` association via `htmlFor`

## Compatibility

- No mass migration of pages
- `Components/Admin/ui/Label.tsx` unchanged (same visual recipe; future optional re-export)
- Existing Tailwind typography usage continues to work

## UI Lab

- **Location:** `/admin/design-system/foundation#typography-atoms`
- Nav: **Typography** + atom links under **Atoms**
- Uses real imports from `@/Components/atoms`

## Validation

- `npm run typecheck` — required
- `npm run build` — required

## Deferred (not PR-3B)

Input, FormField, Badge lab entry, Avatar, Divider, Spinner, molecules, Admin Label alias, page migrations.
