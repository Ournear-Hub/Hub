# OURNEAR Progress — PR-4I

**Status:** Implemented (architecture phase — **V2 not modified**)

## 1. Purpose

Determinate completion between a minimum (0) and maximum — uploads, imports, profile completion, onboarding steps.

## 2. Progress vs Spinner / Skeleton

| Progress | Spinner | Skeleton |
|----------|---------|----------|
| Measurable value | Indeterminate busy | Content shape placeholder |

## 3. Progress vs Alert / Toast / Badge

Not messaging or status chips.

## 4. API

```tsx
<Progress
  value={number}
  max?={number}              // default 100
  label?
  showValue?                 // default false — shows rounded %
  size="sm" | "md" | "lg"    // default md
  className?
/>
```

## 5. Value handling

`value` clamped to `[0, max]`. Fill width = `(value / max) * 100`. `aria-valuenow` uses the clamped value in `max` units (not only 0–100).

## 6. Visual recipe

Surface track, **brand** fill, rounded-full — aligned with V2 `ProfileCompletionCenter` bar (read-only reference).

## 7. Accessibility

- **`role="progressbar"`**
- **`aria-valuemin={0}`**, **`aria-valuemax={max}`**, **`aria-valuenow={clampedValue}`**
- **`aria-label`**: `label`, or `{percent}% complete` when `showValue` only, or **Progress**

## 8. Non-features

No indeterminate mode, no native `<progress>`, no ring variant, no Spinner fusion, no V2 migration, no new dependencies.

## 9. V2 inspection findings (read-only)

- **`ProfileCompletionCenter.tsx`**: custom div bar — `h-2`, `bg-surface` track, `bg-brand` fill, inline `width` %, `role="progressbar"` + `aria-valuenow/min/max`, separate `{completion}%` text.
- No reusable Progress component elsewhere; no native `<progress>` in quick scan.
- “Profile completion” appears in account/admin copy and filters — numeric display, not always a bar.
- No indeterminate progress pattern found.

## 10. Confirmation V2 was not modified

V2 unchanged.

## 11. UI Lab

`/admin/design-system/atoms/progress`

## 12. Validation results

- `npm run typecheck` — **PASS**
- `npm run build` — **PASS**

## 13. Next work

**STOP** — PR-4I frozen.
