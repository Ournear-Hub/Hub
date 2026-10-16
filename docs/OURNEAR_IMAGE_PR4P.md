# OURNEAR Image — PR-4P

**Status:** Implemented (architecture phase — **V2 not modified**)

## 1. Purpose

Canonical **generic media** `<img>` primitive for content, thumbnails, banners, and card imagery. Not for identity (see Avatar).

## 2. Atom classification

**Atom** at `resources/js/Components/atoms/Image.tsx`. Single canonical Image — no MediaImage, Picture, etc.

## 3. API

```tsx
<Image
  src={string}
  alt={string}
  width?: number | string
  height?: number | string
  fit="cover" | "contain" | "fill" | "none"   // default cover
  loading="lazy" | "eager"                     // default lazy
  className?
  {...nativeImgAttributes}
/>
```

`forwardRef` → `HTMLImageElement`.

## 4. src contract

Required caller-owned URL. No fetch, upload, or storage.

## 5. alt contract

Required string; **`alt=""`** for intentionally decorative images. No derivation from filename or `src`.

## 6. width/height contract

Optional native HTML `width` / `height` attributes only — not a design-system size scale.

## 7. fit contract

| fit | Tailwind |
|-----|----------|
| cover (default) | `object-cover` |
| contain | `object-contain` |
| fill | `object-fill` |
| none | `object-none` |

**Default `cover`:** strongest V2 evidence (`JobCard` logos, profile photos, Avatar inner img).

## 8. loading contract

Native `loading` attribute. **Default `lazy`** — V2 does not consistently set loading; lazy is the design-system default for non-critical media. Use **`eager`** when caller needs immediate load (e.g. LCP hero).

## 9. className contract

Caller extends styling (radius, border, dimensions, layout). Image atom does not impose radius, shadow, or aspect ratio.

## 10. Accessibility contract

Native `<img>` semantics. Meaningful `alt` from caller; decorative `alt=""`. No redundant `role="img"`. Additional `aria-*` may pass through when valid on `img`.

## 11. Image failure behavior

No error state, placeholder, or fallback. Browser broken-image behavior is acceptable (distinct from Avatar’s identity fallback).

## 12. Native attribute behavior

`Omit` only duplicates of the canonical props (`src`, `alt`, `width`, `height`, `loading`, `className`). Other standard `ImgHTMLAttributes` and event handlers spread to `<img>`.

## 13. Relationship to Avatar

| **Image** | **Avatar** |
|-----------|------------|
| Generic media | Person/company identity |
| Required `src` | Optional `src` + initials/icon fallback |
| No shape/size presets | xs–xl, circle/square |
| No error fallback UI | Image error → initials/neutral |

**PR-4N Avatar unchanged** — does not depend on Image in PR-4P.

## 14. UI Lab route

`/admin/design-system/atoms/image`

## 15. V2 evidence (read-only)

- Widespread raw `<img>` with caller `className` (`object-cover`, `rounded-md`, `border-line`, explicit dimensions).
- Examples: `Components/Public/JobCard.tsx` (company logo), `Components/Public/Header.tsx` (profile photo via local ProfileAvatar).
- **No generic V2 Image abstraction.**

## 16. Explicit non-goals

srcset/sizes, upload, skeleton/placeholder, captions, links, Avatar merge, V2 migration, application UI changes.

## 17. Migration status

**V2 was inspected READ-ONLY. No V2 application code was modified.**
