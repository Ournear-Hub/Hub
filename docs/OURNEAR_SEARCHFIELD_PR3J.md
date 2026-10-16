# OURNEAR SearchField — PR-3J

**Status:** Implemented

## Purpose

Search-oriented **composition** around canonical `Input`: leading search icon + `type="search"`. No query engine, debounce, autocomplete, or results.

## Component

- **Path:** `resources/js/Components/molecules/SearchField.tsx`
- **Export:** `@/Components/molecules`

## API

Inherits canonical **Input** props (except `type`), plus:

| Prop | Description |
|------|-------------|
| `surface?` | Same as Input (default `public`) |
| `error?` | Passed to Input |
| `className?` | Wrapper |
| `inputClassName?` | Extra classes on Input |
| `ref` | Forwarded to the native input |

Standard input attributes: `value`, `defaultValue`, `placeholder`, `name`, `id`, `disabled`, `readOnly`, `required`, `aria-*`, `onChange`, `onBlur`, `onFocus`, `onKeyDown`, etc.

## Icon

- Registry name: **`search`** (`OurNearIcon`, decorative / `aria-hidden` on wrapper)
- Not interactive; naming comes from label / `aria-label` / FormField

## Clear action

**Not implemented.** V2 `SearchBar` and job search use plain inputs with no clear control. Can add later with `IconButton` + `close` if product requires it.

## Loading

**Not implemented.** No loading pattern on V2 search controls at this layer.

## Submit / Enter

No Enter interception. Works inside `<form>` with native submit behavior.

## FormField

`FormField` treats `SearchField` like other controls for `error` + ARIA augmentation when composed as the single child. Props on `SearchField` forward to inner `Input` (including `id`, `aria-describedby`, `aria-invalid`).

## Compatibility

- **`Components/Admin/ui/SearchBar.tsx`** — Admin adapter, `type="search"`, no icon. **Unchanged.**
- Job/public search pages **not migrated**.

## UI Lab

`/admin/design-system/molecules/search-field`

## Validation

- `npm run typecheck` — PASS
- `npm run build` — PASS

## Deferred

Autocomplete, Combobox, global search, debounce services, clear button (until product need), PasswordField, etc.
