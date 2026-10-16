# OURNEAR InputGroup — PR-3U

**Status:** Implemented (architecture phase — **V2 not modified**)

## 1. Purpose

Compound **layout** molecule: one caller-owned canonical control plus optional leading/trailing addons or an adjacent action. Not another Input implementation.

## 2. Canonical file path

- **Component:** `resources/js/Components/molecules/InputGroup.tsx`
- **Join recipes:** `resources/js/design-system/classNames.ts` (`inputGroup*`)
- **Export:** `@/Components/molecules` (`InputGroup`, `InputGroupProps`)

## 3. API

```tsx
<InputGroup
  leading?
  trailing?
  action?
  className?
>
  {singleControl}
</InputGroup>
```

No `label`, `hint`, `error`, `value`, `onChange`, `name`, `type`, or validation props.

## 4. Composition model

```
Caller-owned control (Input, SearchField, NumberField, URLField, …)
        ↓
InputGroup (flex layout + decorative addons + optional action slot)
        ↓
FormField (optional — label/hint/error/id/ARIA on inner control)
        ↓
FieldGroup / pages
```

InputGroup does **not** clone children, inspect React types, or inject props into arbitrary children. Border-radius/border join uses descendant `[&_input]` recipes on the control slot only.

## 5. Relationship to FormField

| | FormField | InputGroup |
|---|-----------|------------|
| Scope | Label, hint, error, id, aria on **control** | Adjacent layout only |
| Augmentation | `cloneElement` on direct child or **inner** child when wrapper is InputGroup | None |

When the FormField child is `InputGroup`, FormField augments the **nested** control (same as wrapping `URLField` directly). InputGroup itself never performs that forwarding.

## 6. Supported patterns

- Leading prefix (`https://`, `$`, `+91`)
- Trailing unit (`kg`, `/ yr`)
- SearchField + Button action
- NumberField / URLField / Input as child
- Nested in FormField with error/required on the field, not the group

## 7. Accessibility

- No `role="group"` for styling.
- Leading/trailing addons render in `aria-hidden` spans so prefix/suffix text does not become the input accessible name.
- `action` must be its own accessible control (e.g. `Button` with visible text or label).
- Native tab order and keyboard behavior unchanged; no focus trap.

## 8. Non-features

InputGroup does **not** own value state, validation, submission, parsing, formatting, loading, async behavior, ID assignment (except FormField passthrough target), or custom input styling.

## 9. Why no child prop injection

Keeps composition predictable: the control remains the single source of truth for input semantics. Visual joining is CSS-only on a wrapper slot, matching the architecture rule against mini form systems and implicit magic.

## 10. V2 inspection findings (read-only)

| Finding | Detail |
|---------|--------|
| Reusable InputGroup / input-addon primitive | **None** |
| Search + filter | Admin `SearchBar` (plain `type="search"` Input) in FilterBar — separate from input, no shared addon component |
| URL hints | Placeholders like `https://` on Profile/Employers/MediaSocial — not structural prefixes |
| Password visibility | `PasswordField`-style relative wrapper + IconButton — field-specific, not generalized addon |
| OTP | `OtpSixInput` — separate pattern |

## 11. V2 was NOT modified

Architecture, UI Lab, and docs only.

## 12. Validation results

| Check | Result |
|-------|--------|
| `npm run typecheck` | **PASS** |
| `npm run build` | **PASS** |

## 13. UI Lab

Route: `/admin/design-system/molecules/input-group`  
Section: `InputGroupLabSection.tsx`
