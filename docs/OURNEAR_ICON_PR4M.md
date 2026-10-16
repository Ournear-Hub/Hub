# OURNEAR Icon foundation — PR-4M

**Status:** Hardened (architecture phase — **V2 not modified**)

## 1. Purpose

Document and enforce the canonical **OurNearIcon** contract. No second icon component, no registry redesign, no V2 migration.

## 2. Canonical surface

| Layer | Path |
|-------|------|
| Component | `resources/js/icons/OurNearIcon.tsx` |
| Registry | `resources/js/icons/registry.ts` |
| Tabler boundary | `resources/js/icons/tablerComponents.ts` |
| Sizes | `resources/js/icons/sizes.ts` |
| Stroke default | `foundation/tokens.ts` → `iconDefaults.stroke` |

## 3. Naming

- Pass **`name`** matching registry `name` or an **alias** (case-insensitive).
- Add new icons: registry entry + Tabler import in `tablerComponents.ts` only.
- **`status: 'deprecated'`** entries are not resolved.

## 4. Accessibility contract

- **`decorative={true}` (default)** → `aria-hidden` on SVG (or spinner inline).
- **`decorative={false}`** → `role="img"` + **`aria-label`** (dev warning if label missing).
- Icon-only **buttons/links**: accessible name on the **control**; icon stays decorative.

## 5. Size contract

`size`: `xs` | `sm` | `md` | `lg` | `xl` → pixel map in `sizes.ts`. Omitted `size` uses entry `defaultSize`.

## 6. className & SVG props

Forwarded to Tabler SVG: **`size`**, **`stroke`**, **`className`**, plus a11y props. Use `className` for `text-*`, `animate-spin`, layout. No generic passthrough of arbitrary DOM attrs in PR-4M.

## 7. Unknown / misconfigured icons

- Dev **`console.warn`** `[OurNearIcon] …`
- Render **`MissingIconPlaceholder`**: `data-missing-icon`, `aria-hidden`, `role="presentation"`

## 8. Tabler prohibition

`@tabler/icons-react` imports **only** in `tablerComponents.ts`. Pages, molecules, and V2 may keep legacy patterns — unchanged in PR-4M.

## 9. V2 inspection (read-only, design-system usage)

Canonical atoms/molecules already use **`OurNearIcon`** with explicit **`decorative`** where paired with text or control labels (Button, IconButton, Alert, Toast, SearchField, etc.). V2 **`Components/UI/icons`** legacy SVGs remain separate.

## 10. UI Lab

`/admin/design-system/icons` — registry browser + PR-4M contract panel + `ourNearIconCatalog` InfoPanel.

## 11. Validation results

- `npm run typecheck` — **PASS**
- `npm run build` — **PASS**

## 12. Next work

**STOP** — PR-4M frozen.
