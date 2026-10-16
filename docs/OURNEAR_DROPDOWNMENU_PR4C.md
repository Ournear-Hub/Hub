# OURNEAR DropdownMenu — PR-4C

**Status:** Implemented (architecture phase — **V2 not modified**)

## 1. Purpose

Command/action menu from an interactive trigger: edit, archive, delete, export, etc.

## 2. DropdownMenu vs Popover

Popover holds arbitrary interactive panels (forms, filters). DropdownMenu is a **command list** with `role="menu"` and menu keyboard navigation.

## 3. DropdownMenu vs Select/Combobox

Select/Combobox choose values. DropdownMenu invokes **`onSelect` actions** — not form field semantics.

## 4. DropdownMenu vs navigation

Not site navigation; action commands only (no link/menuitem href API in this PR).

## 5. Compound API

```tsx
<DropdownMenu open? defaultOpen? onOpenChange? className?>
  <DropdownMenuTrigger>{single control}</DropdownMenuTrigger>
  <DropdownMenuContent side? align? className?>
    <DropdownMenuItem onSelect? disabled? destructive?>…</DropdownMenuItem>
    <DropdownMenuSeparator />
  </DropdownMenuContent>
</DropdownMenu>
```

## 6. Trigger semantics

Wrapper span + limited `cloneElement`: `aria-haspopup="menu"`, `aria-expanded`, `aria-controls`, click toggle, keyboard open keys. Caller provides accessible name.

## 7. Menu semantics

Portaled `role="menu"` container with stable `id`.

## 8. Menu item semantics

Native `button type="button" role="menuitem"`. Roving `tabIndex` (0 on active item, -1 on others). `onSelect` on activation.

## 9. Keyboard behavior

**Trigger (closed):** Enter/Space/ArrowDown → open + focus first enabled; ArrowUp → open + focus last enabled.

**Menu (open):** ArrowDown/Up move between enabled items; Home/End first/last; Enter/Space activate; Escape close + focus trigger; Tab closes (no trap).

## 10. Focus behavior

On open, focus first or last enabled item per open gesture. After item select or Escape, focus returns to trigger. No focus trap.

## 11. Outside click

Capture-phase `pointerdown` outside trigger/content closes menu.

## 12. Positioning

Same anchor/clamp model as Tooltip/Popover (`side`, `align`, portal, 8px clamp). No flip engine.

## 13. Disabled items

Native `disabled` on button; skipped in arrow/Home/End navigation.

## 14. Destructive styling

Optional `destructive` boolean → danger text/hover recipe only.

## 15. Separator

`role="separator"` non-interactive divider.

## 16. Explicitly excluded submenu architecture

No nested menus, submenu positioning, or hierarchical keyboard.

## 17. Explicitly excluded checkbox/radio items

No CheckboxItem/RadioItem.

## 18. Explicitly excluded typeahead

No typeahead/command palette (future if V2 requires).

## 19. V2 inspection findings (read-only)

- No menu library in `package.json`.
- Ad hoc menus: `Header.tsx` (account `role="menu"` + `menuitem` links), `TopBar.tsx` (notifications/account panels with `aria-haspopup="menu"`).
- Click-outside via refs in V2; **limited roving keyboard** on those menus.
- No reusable DropdownMenu component.

## 20. Dependency decision

None — React + `createPortal` + local keyboard/outside handlers.

## 21. Non-features

No `items[]` API, submenus, links, typeahead, command registry, FormField coupling, auto-close on every click inside panel, V2 migration.

## 22. Confirmation V2 was not modified

Read-only inspection; no V2 changes.

## 23. Validation results

- `npm run typecheck` — **PASS**
- `npm run build` — **PASS**
