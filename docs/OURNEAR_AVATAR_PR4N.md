# OURNEAR Avatar — PR-4N

**Status:** Implemented (architecture phase — **V2 not modified**)

## 1. Purpose

Canonical **Avatar** atom for visual identity: user, candidate, employer, and profile surfaces. Display-only primitive — no profile business logic, uploads, or persistence.

## 2. Atom classification

**Atom** at `resources/js/Components/atoms/Avatar.tsx`. Single canonical component (no AvatarBase, UserAvatar, etc.).

## 3. API

```tsx
<Avatar
  src?
  alt?
  name?
  size="xs" | "sm" | "md" | "lg" | "xl"   // default md
  shape="circle" | "square"                 // default circle
  className?
/>
```

## 4. Size contract

| Size | Box | Typical use |
|------|-----|-------------|
| xs | 24px (`h-6 w-6`) | Compact lists |
| sm | 32px (`h-8 w-8`) | Header triggers (V2 `h-8`) |
| md | 40px (`h-10 w-10`) | Default control-aligned |
| lg | 48px (`h-12 w-12`) | Profile rows |
| xl | 64px (`h-16 w-16`) | Feature profile |

Recipes: `avatarSizeXs` … `avatarSizeXl` in `design-system/classNames.ts`.

## 5. Shape contract

- **`circle`** (default): `rounded-full` — dominant V2 pattern (`ProfileAvatar`).
- **`square`**: `rounded-md` — card/admin surfaces where square crops are used.

## 6. Image behavior

When `src` is a non-empty string and loads successfully, render `<img>` with `object-cover` filling the box. No fetch, upload, or validation logic.

## 7. Initials behavior

When no usable image:

1. If `name` is provided, derive initials deterministically:
   - Trim whitespace, split on spaces.
   - **One token** → first character uppercased (`"John"` → `"J"`).
   - **Two or more tokens** → first character of **first two tokens** uppercased (`"John Doe"` → `"JD"`).
2. Render on **brand** background with white semibold text (V2 initials evidence).

## 8. Fallback behavior

No `src` and no usable initials → neutral **surface** background + **OurNearIcon** `user` (decorative). No business-specific placeholder art.

## 9. Image failure behavior

On `img` `error`: local state marks failure; fall back to initials (if `name`) or neutral icon. No retry, no broken-image chrome.

## 10. Accessibility contract

- **Image:** caller-owned `alt`; use `alt=""` when identity is named by a parent.
- **Initials / neutral:** `aria-hidden` on the wrapper — surrounding list/menu/heading provides the accessible name (canonical atom does not invent semantics).
- No auto `aria-label` from `name`, no `role="img"`, no focus or click handling.

## 11. Icon boundary

Neutral fallback only: **`OurNearIcon` `name="user"`**. No direct Tabler imports (PR-4M).

## 12. Token / design-system usage

Uses existing `classNames` recipes and theme tokens (`brand`, `surface`, `muted`, `line`). No new token file or hex literals.

## 13. UI Lab route

`/admin/design-system/atoms/avatar` — `AvatarLabSection` + `avatarCatalog`.

## 14. V2 evidence (read-only)

- **`resources/js/Components/Public/Header.tsx`**: local `ProfileAvatar` — `photo_url` → `img` (`rounded-full`, `object-cover`, caller-style alt); else brand initials span (up to two word-initials). Sizes e.g. `h-8 w-8`, `h-11 w-11`.
- **No reusable V2 Avatar component** found; pattern is ad hoc.

**Default shape:** `circle` from V2 `rounded-full` usage.

## 15. Explicit non-goals

Upload, cropping, storage, Gravatar, API/auth integration, status/badge/online props, interactive avatar links, V2 migration, admin UI redesign.

## 16. Migration status

**V2 is NOT modified or migrated in PR-4N.** Design-system atom + UI Lab only.
