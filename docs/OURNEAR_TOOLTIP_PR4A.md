# OURNEAR Tooltip — PR-4A

**Status:** Implemented (architecture phase — **V2 not modified**)

## 1. Purpose

Transient, supplementary contextual hint tied to a trigger (hover/focus). Not a label, error, notification, popover, menu, or modal.

## 2. Appropriate use cases

Icon-only action clarification, compact admin controls, truncated labels, non-essential help. Trigger must already have an accessible name when it is icon-only.

## 3. Inappropriate use cases

Primary labels, FormField hints/errors, required instructions, critical status, interactive content, or anything that must be read to use the UI.

## 4. API

```tsx
<Tooltip
  content={ReactNode}
  side="top" | "right" | "bottom" | "left"   // default top
  align="start" | "center" | "end"           // default center
  disabled?                                   // disabled trigger wrapper
  delay?                                      // show delay ms, default 400
  className?
>
  {single focusable child}
</Tooltip>
```

## 5. Interaction model

Show on pointer enter and focus entering the trigger wrapper; hide on leave/blur (unless focus moves within wrapper). No click-to-open. Hide on Escape while open.

## 6. Keyboard/focus behavior

Focus on the real trigger (or disabled wrapper) shows the tooltip. Tooltip panel is not tabbable and does not receive focus.

## 7. Accessibility relationship

Portaled content: `role="tooltip"`, stable `id`. While visible, trigger gets `aria-describedby` merging existing ids (single React element child only, via minimal `cloneElement`). Tooltip does not set `aria-label` on triggers.

## 8. Trigger semantics

Child remains the control (button, IconButton, link). Wrapper is `inline-flex` with pointer/focus listeners only — no `role="button"`, no extra `tabIndex` except disabled mode.

## 9. Disabled-control behavior

Pass `disabled` on Tooltip when wrapping a native disabled control: focusable wrapper (`tabIndex={0}`) receives hover/focus; child stays disabled; `aria-describedby` on wrapper while visible.

## 10. Positioning

Fixed coordinates from trigger `getBoundingClientRect`, portaled to `document.body`. Simple viewport clamp (8px padding). No collision engine or Floating UI. Limitation: very long content may still clip near edges.

## 11. Timing

Default **400ms** show delay; hide is immediate. Per-instance `delay` prop only — no global manager.

## 12. Interactive-content boundary

Content is non-interactive (`pointer-events-none`). Buttons/links/inputs belong in a future Popover.

## 13. Animation decision

Optional opacity transition via existing Tailwind `duration-150` on tooltip recipe; functionality does not depend on animation.

## 14. V2 inspection findings (read-only)

- **No tooltip library** in `package.json` (React, Inertia, Tabler icons only).
- **No reusable Tooltip component** in V2.
- **`title` attribute** used for native browser tooltips on some disabled/placeholder admin controls (e.g. Application workspace submit, Sidebar/TopBar “coming later”, ApplicationDrawer).
- **`aria-describedby`** used on form/molecule controls (FormField, Combobox, FieldGroup), not for hover tooltips.
- No `data-tooltip` or custom tooltip JS found in product UI.

## 15. Dependency decision

**No new packages.** Implementation uses React state, `react-dom` `createPortal`, and CSS/Tailwind recipes.

## 16. Non-features

No async/loading content, popover interactions, child `onClick`/className injection, global tooltip manager, Radix/Headless/Floating UI, or V2 migration.

## 17. Confirmation V2 was not modified

V2 inspected read-only; no V2 files changed in PR-4A.

## 18. Validation results

- `npm run typecheck` — **PASS**
- `npm run build` — **PASS**
