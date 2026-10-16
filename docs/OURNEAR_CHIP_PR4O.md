# OURNEAR Chip — PR-4O

**Status:** Implemented (architecture phase — **V2 not modified**)

## 1. Naming decision: Chip vs Tag

**Canonical name: Chip.**

- V2 compound control recipes use **`multiSelectChip*`** (not “tag”).
- Product language for skills/filters/keywords maps to compact **metadata chips**; **Badge** already covers status “tags” in the UI sense.
- One primitive only — no `Tag.tsx`.

## 2. Purpose

Compact **content/user metadata** display: skills, categories, keywords, filter values, selected values. Presentation-only; no filtering, CRUD, or form semantics.

## 3. Atom classification

**Atom** at `resources/js/Components/atoms/Chip.tsx`.

## 4. Distinction from Badge

| | **Badge** (PR-3A) | **Chip** (PR-4O) |
|---|-------------------|------------------|
| Role | Status / state / category indicator | Descriptive metadata & selections |
| Shape | `rounded-full` | `rounded-md` |
| Tones | Full `statusTone` (success, warning, …) | `neutral`, `accent`, `brand` metadata palette |
| Remove | No | Optional `onRemove` + `removeLabel` |
| Interaction | Non-interactive | Non-interactive body; optional remove button |

**StatusBadge** remains a thin tone adapter over Badge — not replaced by Chip.

## 5. API

```tsx
<Chip
  variant="neutral" | "accent" | "brand"   // default neutral
  size="sm" | "md"                       // default sm
  icon?                                  // ReactNode, caller-owned
  onRemove?                              // () => void
  removeLabel?                           // required when onRemove is set
  className?
>
  {children}
</Chip>
```

## 6. Variant contract

- **neutral** — `border-line`, `bg-surface`, `text-ink` (MultiSelect chip baseline).
- **accent** — accent tint border/background/text.
- **brand** — brand tint border/background/text.

No arbitrary colors; recipes in `design-system/classNames.ts`.

## 7. Size contract

- **sm** — `text-xs`, compact padding (default; aligns with MultiSelect chips).
- **md** — `text-sm`, slightly taller padding.

## 8. Icon contract

Optional leading **`icon`** prop (`ReactNode`). Caller may pass **`OurNearIcon`** (PR-4M). Chip does not resolve icon names. Decorative icons should use `decorative` on OurNearIcon when redundant with visible text.

Remove affordance uses built-in **`OurNearIcon` `close`** (same boundary as MultiSelect inline remove).

## 9. Remove-action contract

When **`onRemove`** is provided:

- Render **`button type="button"`** with **`aria-label={removeLabel}`** (TypeScript requires `removeLabel`).
- Only the remove control is interactive; label/body is not clickable.
- No confirmation, tooltip, or internal state.

## 10. Accessibility contract

- Wrapper: **`span`**, no interactive ARIA roles.
- Text content available to assistive technologies.
- Remove: native button, keyboard focusable, caller-supplied accessible name.
- No `role=button` on Chip, no option/listbox/checkbox semantics.

## 11. Form / selection boundary

Chip is **not** a form control: no `name`, `value`, `checked`, or hidden inputs. **MultiSelect** retains selection, listbox semantics, and Laravel `name[]` serialization.

## 12. UI Lab route

`/admin/design-system/atoms/chip`

## 13. V2 evidence (read-only)

- **`MultiSelect`**: `multiSelectChip` + remove button + `OurNearIcon` close — rounded-md surface metadata (not Badge pills).
- **`Badge` atom**: rounded-full `statusTone` for semantic status.
- No reusable V2 Chip/Tag component; distinction between status pills and metadata chips is visible in code.

**Justification:** Badge does not provide removable metadata affordance or the compact squared radius used for selected values; a separate Chip primitive is warranted.

## 14. Explicit non-goals

Filter/search logic, chip input, drag/drop, taxonomy, persistence, navigation, selection state, V2 migration, MultiSelect refactor.

## 15. Migration status

**V2 is NOT modified or migrated in PR-4O.**
