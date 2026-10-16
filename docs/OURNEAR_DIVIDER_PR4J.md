# OURNEAR Divider — PR-4J

**Status:** Implemented (architecture phase — **V2 not modified**)

## 1. Purpose

Structural visual separation between adjacent content regions — not card chrome, input borders, or table row lines.

## 2. Divider vs other borders

| Use Divider | Do not use Divider for |
|-------------|-------------------------|
| Section breaks in stacked content | Card `border`, table `border-b`, control borders |
| Inline vertical split in toolbars | `DropdownMenuSeparator` (menu molecule) |

## 3. API

```tsx
<Divider
  orientation="horizontal" | "vertical"   // default horizontal
  decorative?                             // default true
  className?
/>
```

## 4. Visual recipe

- **Horizontal:** `border-t border-line`, full width (`dividerHorizontal`).
- **Vertical:** `w-px bg-line`, `self-stretch`, `min-h-[1em]` (`dividerVertical`) — parent flex row should use `items-stretch`.

## 5. Semantics

- **`decorative={true}` (default):** visual only — horizontal `<hr aria-hidden>`, vertical `role="presentation"`.
- **`decorative={false}`:** semantic break — horizontal native `<hr>`, vertical `role="separator"` + `aria-orientation="vertical"`.

## 6. Non-features

Not a generic border utility, not menu separator, not focusable, no label slot, no V2 migration, no new dependencies.

## 7. V2 inspection findings (read-only)

1. **No reusable Divider component.**
2. **No `<hr>`** in quick scan — separation via **`border-t border-line`** (Drawer footer, ProfileShell nav, layouts, admin/public sections).
3. **Vertical** separation less common; occasional flex + border patterns.
4. **`DropdownMenuSeparator`:** `role="separator"`, `h-px bg-line my-1` — menu-specific, unchanged in PR-4J.

## 8. Confirmation V2 was not modified

V2 unchanged.

## 9. UI Lab

`/admin/design-system/atoms/divider`

## 10. Validation results

- `npm run typecheck` — **PASS**
- `npm run build` — **PASS**

## 11. Next work

**STOP** — PR-4J frozen.
