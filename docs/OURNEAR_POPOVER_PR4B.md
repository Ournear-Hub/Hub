# OURNEAR Popover — PR-4B

**Status:** Implemented (architecture phase — **V2 not modified**)

## 1. Purpose

Click-triggered floating surface for **interactive** contextual UI: filters, quick settings, compact forms, action panels. Not supplementary-only (Tooltip), not command menus, not blocking dialogs.

## 2. Tooltip vs Popover

| Tooltip | Popover |
|---------|---------|
| Hover/focus supplementary hint | Intentional open (click toggle) |
| Non-interactive (`pointer-events-none`) | Buttons, links, fields allowed |
| `aria-describedby` | `aria-expanded` / `aria-controls` |
| No focus into panel | Focus may move into content |

## 3. Popover vs Dropdown/Menu

Popover is a layout/interaction shell without `role="menu"` or roving menu keyboard model. Command lists get a future Menu molecule.

## 4. Popover vs Modal/Drawer

No backdrop, no focus trap, no `role="dialog"` by default. Escape/outside dismiss without blocking the page.

## 5. API

```tsx
<Popover open? defaultOpen? onOpenChange? className?>
  <PopoverTrigger>{single interactive element}</PopoverTrigger>
  <PopoverContent side? align? className?>{interactive content}</PopoverContent>
</Popover>
```

## 6. Compound architecture

React context shares open state, content id, trigger/content refs, and focus-return flag. Only `PopoverTrigger` uses limited `cloneElement` (expanded/controls/click/className).

## 7. Controlled/uncontrolled state

`open` → controlled. Otherwise `defaultOpen` + internal state. `onOpenChange` on every transition. No URL/persistence.

## 8. Trigger semantics

Single element child (Button, IconButton, etc.). Wrapper `span` holds layout anchor ref. Trigger gets `aria-expanded`, `aria-controls`. Caller supplies accessible name on the control.

## 9. Content semantics

Portaled `div` with stable `id`. No default `role="dialog"` or `role="menu"`. Caller may add dialog semantics only when content truly warrants it.

## 10. Focus behavior

On open: focus **first focusable** element inside content when one exists; set flag to return focus on close. On close: if flag set, focus trigger’s first focusable (usually the button). **No focus trap.**

## 11. Escape behavior

Escape closes popover; focus returns per rule above.

## 12. Outside-click behavior

Capture-phase `pointerdown` on `document` while open; ignore events inside trigger wrapper or content.

## 13. Positioning

Anchor from trigger wrapper `getBoundingClientRect`; fixed portal coords; `side` + `align`; 8px viewport clamp (same model as Tooltip). No flip/auto-placement engine.

## 14. Scroll/resize behavior

Reposition on window scroll (capture) and resize while open; listeners removed when closed.

## 15. Disabled trigger behavior

Disabled native controls do not fire toggle click. No disabled-wrapper pattern (use Tooltip for disabled hints).

## 16. Interactive-content boundary

Any ReactNode including FormField, Select, Combobox, buttons. Popover does not clone content children.

## 17. Form behavior

Forms may live inside content; Popover does not handle submit or reset on close.

## 18. Animation decision

None required; no motion library.

## 19. V2 inspection findings (read-only)

- **No overlay library** in `package.json` (React, Inertia, Tabler icons only).
- **No Popover / popper / floating-ui** usage in V2 TSX.
- **Only portaled overlay in design system** before this PR: Tooltip (`createPortal`).
- Combobox/MultiSelect listboxes render in-flow (not body portal).
- V2 uses native `title=` and modals/drawers for blocking patterns — not popover equivalents.

## 20. Dependency decision

**No new packages.** `createPortal`, local listeners, duplicated anchor math from Tooltip.

## 21. Non-features

No nested overlay manager, async content, auto-close on every click, focus trap, menu/dialog roles by default, collision flipping, V2 migration.

## 22. Confirmation V2 was not modified

V2 inspected read-only; no product files changed in PR-4B.

## 23. Validation results

- `npm run typecheck` — **PASS**
- `npm run build` — **PASS**
