# OURNEAR Alert — PR-4D

**Status:** Implemented (architecture phase — **V2 not modified**)

## 1. Purpose

Persistent inline feedback in the current page context: success, info, warning, and non-field errors.

## 2. Alert vs Toast

Alert stays in layout until the caller unmounts it. Toast (future) is transient notification chrome.

## 3. Alert vs FormField error

FormField owns field-level validation messaging. Alert is page/section-level feedback, not tied to a single control id.

## 4. Alert vs Badge/StatusBadge

Badges are compact status chips. Alert is a full message surface with optional title and body.

## 5. Variants

`info` | `success` | `warning` | `error` — mapped to existing `statusTone` + border recipes.

## 6. Semantic role/live-region decision

- **`error`** → `role="alert"` (urgent, assertive announcement).
- **`info`**, **`success`**, **`warning`** → `role="status"` (polite live region).
- No redundant `aria-live` alongside these roles.

## 7. Accessibility

Readable text, border + icon + tone (not color alone). Title uses styled `<p>`, not arbitrary heading levels. Dismiss uses `IconButton` with label **Dismiss**.

## 8. Icon behavior

Default variant icons via `OurNearIcon` (`info`, `check`, `alert` for warning/error). Decorative when text is present. `icon={false}` hides. Registry has no `alert-circle`; both warning and error use `alert` with distinct surfaces.

## 9. Dismissible behavior

`dismissible` + `onDismiss` — no internal hidden state; caller removes Alert from tree. Dev warning if dismissible without `onDismiss`.

## 10. Content model

`children` as `ReactNode`; optional `title` string. No string parsing or HTML injection.

## 11. Action-content boundary

Primary API is message-only. Callers may embed links/buttons in `children` without Alert intercepting clicks (no action registry).

## 12. Styling

Recipes: `alertRoot`, borders, `statusTone`, typography helpers. Reuses foundation tokens — no parallel palette.

## 13. Color/contrast decisions

Uses existing `statusTone` and semantic borders from the design system. Warning body text remains `text-ink` per token recipe; icon and border convey variant.

## 14. Form integration

May sit above/below forms or in FieldSet/Popover; no FormField coupling.

## 15. V2 inspection findings (read-only)

- **`Components/UI/Alert.tsx`**: `tone` prop, `role="status"` for all tones, bordered + `statusTone`, optional title — **no dismiss, no icons, no error-specific role**.
- No separate canonical molecule; ad hoc inline messages elsewhere; no widespread `role="alert"` pattern found in quick scan.

## 16. Non-features

No auto-dismiss, toast queue, async/retry, size variants, neutral/loading variants, V2 migration of `UI/Alert`.

## 17. Confirmation V2 was not modified

V2 `Components/UI/Alert.tsx` and product pages unchanged.

## 18. Validation results

- `npm run typecheck` — **PASS**
- `npm run build` — **PASS**
