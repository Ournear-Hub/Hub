# OURNEAR Toast — PR-4E

**Status:** Implemented (architecture phase — **V2 not modified**)

## 1. Purpose

Transient, portaled feedback for short-lived success/info/warning/error messages. Caller mounts/unmounts via `onDismiss`.

## 2. Toast vs Alert

| Alert | Toast |
|-------|-------|
| Inline in document flow | Portal to viewport (`fixed`) |
| Persistent until caller removes | Default 5s auto-dismiss |
| Page/section message | Overlay feedback |

## 3. Toast vs flash / notification center

V2 `FlashMessage` is Inertia session flash rendered inline in admin layout — not a toast stack or library. PR-4E does not replace it.

## 4. API

```tsx
<Toast
  variant="info" | "success" | "warning" | "error"
  title?
  icon?              // default true
  duration?          // ms, default 5000; 0 = no auto-dismiss
  dismissible?       // default true
  onDismiss?
  placement?         // bottom-end | bottom-start | top-end | top-start
  className?
>
  {message}
</Toast>
```

## 5. Variants & semantics

Same four variants as Alert, reusing `statusTone` and border recipes.

- **`error`** → `role="alert"`
- **`info` / `success` / `warning`** → `role="status"`

## 6. Auto-dismiss

Single `setTimeout` per instance; cleared on unmount and manual dismiss. No global timer manager.

## 7. Dismiss

Optional close control (`IconButton`, label **Dismiss**). `onDismiss` invoked on timer or click — caller removes toast from React tree.

## 8. Positioning

Fixed anchor classes per `placement` (default `bottom-end`). `z-[60]`. No collision engine. Multiple simultaneous toasts are not coordinated in PR-4E (documented limitation).

## 9. Portal

`createPortal` → `document.body`. `pointer-events-auto` on surface.

## 10. Icons

Same mapping as Alert (`info`, `check`, `alert`). Decorative when text present.

## 11. Non-features

No toast queue/provider, no pause-on-hover, no action buttons, no typeahead, no V2 FlashMessage migration, no new dependencies.

## 12. V2 inspection findings (read-only)

- **No toast library** in `package.json`.
- **`FlashMessage.tsx`**: inline success/error/warning blocks from Inertia `flash` — no portal, no auto-dismiss, no `role` attributes.
- **Public pages**: flash success text inline in forms — not toast UI.
- No reusable toast component or notification center in V2 scan.

## 13. Confirmation V2 was not modified

V2 unchanged.

## 14. Validation results

- `npm run typecheck` — **PASS**
- `npm run build` — **PASS**
