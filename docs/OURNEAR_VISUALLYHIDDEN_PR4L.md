# OURNEAR VisuallyHidden — PR-4L

**Status:** Implemented (architecture phase — **V2 not modified**)

## 1. Purpose

Accessibility primitive: content available to assistive technologies, not shown in normal visual layout.

## 2. VisuallyHidden vs other hiding

| VisuallyHidden | Not equivalent |
|----------------|----------------|
| SR-only presentation | `aria-hidden` (removed from AT tree) |
| | `display: none` |
| | `visibility: hidden` |
| | `opacity: 0` alone |

## 3. API

```tsx
<VisuallyHidden
  as="span" | "div" | "p" | "label"   // default span
  className?
>
  Accessible text
</VisuallyHidden>
```

Standard attributes for the chosen element (e.g. `htmlFor` when `as="label"`) pass through.

## 4. Styling recipe

`visuallyHidden` in `classNames.ts` → Tailwind **`sr-only`** (controlled single recipe, no duplicate clip CSS).

## 5. Non-features

Not `not-sr-only`, not a focus trap, no V2 migration of existing `sr-only` spans, no new dependencies.

## 6. V2 inspection findings (read-only)

1. **Tailwind `sr-only`** used ad hoc (Label required suffix, FieldGroup/FieldSet, Admin TopBar, account/job forms, UI lab demo).
2. **No `not-sr-only`** found in quick scan.
3. **No reusable VisuallyHidden component.**
4. **No custom clip-path CSS** beyond Tailwind utility.

## 7. Confirmation V2 was not modified

V2 unchanged (including canonical Label inline `sr-only`).

## 8. UI Lab

`/admin/design-system/atoms/visually-hidden`

## 9. Validation results

- `npm run typecheck` — **PASS**
- `npm run build` — **PASS**

## 10. Next work

**STOP** — PR-4L frozen.
