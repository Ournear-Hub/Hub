# OURNEAR Switch — PR-3H

**Status:** Implemented

## Purpose

Boolean ON/OFF setting control (notifications, preferences, feature flags). Not a Radio, CheckboxGroup, or segmented control.

## Semantic decision

| Choice | Rationale |
|--------|-----------|
| `<input type="checkbox">` + **`role="switch"`** | Native checked/unchecked, Space toggle, predictable form submission (`name` / `value`), no button-toggle pattern in V2 |
| Visual track + knob | V2 has **no** existing switch UI — communication prefs use **square checkboxes** (`Communication.tsx`). Canonical switch introduces a restrained track for new/library use only |
| No `role="switch"` on `<button>` | Product does not use button toggles for settings |

**One model:** checkbox input state + switch role for AT; do not add parallel button semantics.

## Component

- **Path:** `resources/js/Components/atoms/Switch.tsx`
- **Export:** `@/Components/atoms`

## API

| Prop | Description |
|------|-------------|
| `surface?` | `public` \| `account` \| `admin` \| `corporate` (default `public`) |
| `className?` | Wrapper span |
| `ref` | Forwarded to the native input |
| `checked` / `defaultChecked` | ON / OFF — no internal state |
| … | Other checkbox `InputHTMLAttributes` (excluding `type`) |

## Error state

**Not exposed.** V2 toggle usage has no invalid/error styling requirement. Add later only if product needs it.

## Surfaces

- **public / account:** ON track `accent`
- **admin / corporate:** ON track `brand`

## V2 compatibility

No reusable Switch component. Account **Switch.tsx** is an account-switch **page**, not this control. **No adapter**; settings pages unchanged.

## UI Lab

`/admin/design-system/atoms/switch`

## Validation

- `npm run typecheck` — PASS
- `npm run build` — PASS

## Deferred

SwitchGroup, SettingRow, FormField, validation framework, ToggleGroup, segmented controls.
