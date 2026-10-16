# OURNEAR LinkButton — PR-4Q

**Status:** Implemented (architecture phase — **V2 not modified**)

## 1. Purpose

Navigation that **looks like Button** but stays a **native anchor** — CTAs such as “View Jobs”, “Learn More”, “Go to Dashboard” without action/button semantics.

## 2. Atom classification

**Atom** at `resources/js/Components/atoms/LinkButton.tsx`.

## 3. Button vs Link vs LinkButton

| | **Button** (PR-2) | **Link** (PR-4K) | **LinkButton** (PR-4Q) |
|---|-------------------|------------------|------------------------|
| Element | `<button>` (or frozen Inertia `Link` when `href` on Button) | `<a>` | `<a>` |
| Semantics | Actions, submit | Inline text navigation | Button-styled navigation |
| Visual | Button chrome | Link typography | Button chrome |
| Routing | Button `href` uses Inertia for `/…` (frozen) | Caller `href` only | Caller `href` only — **no Inertia** |

## 4. API

```tsx
<LinkButton
  href={required}
  surface="public" | "account" | "admin" | "corporate"
  variant={ButtonVariant}
  size="sm" | "md" | "lg"
  fullWidth?
  leadingIcon?: ReactNode
  trailingIcon?: ReactNode
  disabled?
  className?
  {...AnchorHTMLAttributes}
/>
```

`forwardRef` → `HTMLAnchorElement`.

## 5. Visual contract

Matches frozen **Button** variants, sizes, radius, spacing, and disabled opacity via `design-system/buttonVisuals.ts` (Button component unchanged).

## 6. Native anchor contract

Always renders `<a href="…">`. Supports `target`, `rel`, `download`, `id`, `data-*`, `aria-*`, `title`, `ref`, and other anchor attributes via spread.

## 7. Routing boundary

**No** `@inertiajs/react`, **no** URL inspection, **no** automatic `target`/`rel`. Caller owns navigation intent.

## 8. Disabled behavior

Anchors have no native `disabled`. When `disabled={true}`:

- `aria-disabled="true"`
- `tabIndex={-1}` (unless caller overrides via spread order — avoid overriding when disabled)
- `pointer-events-none opacity-60` (Button-aligned)
- `click` → `preventDefault` + `stopPropagation`

## 9. Icon contract

Optional **`leadingIcon` / `trailingIcon`** as caller **`ReactNode`** (e.g. **`OurNearIcon`** decorative). No icon-name resolver on LinkButton (PR-4M).

## 10. Accessibility contract

Native link keyboard behavior (when not disabled). No `role="button"`. No auto `aria-label`. Visible text remains the primary name.

## 11. Form boundary

Not a form control — no `type`, `name`, `value`, or `form`.

## 12. UI Lab route

`/admin/design-system/atoms/link-button`

## 13. V2 evidence (read-only)

- **CtaSection** — `Button href={actionHref}` for marketing CTAs (uses frozen Button + Inertia for internal URLs).
- Header/auth patterns — button-class anchors in places.
- **Link** atom does not provide Button visuals.

**Justification:** Design-system needs native-anchor, button-visual navigation without router coupling (complements frozen Button `href` behavior, does not replace V2 yet).

## 14. Explicit non-goals

V2 migration, Inertia integration, URL heuristics, form submit, new button variants/tokens, Button refactor.

## 15. Migration status

**V2 was inspected READ-ONLY. No V2 application code was modified.**
