# OURNEAR AspectRatio — PR-4V

**Status:** Implemented (architecture phase — **V2 not modified**)

## 1. Architectural classification

**Atom** — layout-only box establishing a CSS `aspect-ratio` between width and height.

## 2. Purpose

Reusable **ratio container** for media and content, separate from **Image** (src/fit), **Card** (surface), and **Avatar** (identity). Frozen Image intentionally does not own aspect ratio.

## 3. API

```tsx
<AspectRatio ratio="square" | "video" | "portrait" className?>
  {children}
</AspectRatio>
```

`ratio` is **required** (no default — caller must choose). Root `<div>`.

## 4. Ratio contract

| preset | CSS | Evidence |
|--------|-----|----------|
| square | `aspect-square` (1 / 1) | V2 logos/thumbs use equal h/w (JobCard, profile photos) |
| video | `aspect-video` (16 / 9) | Standard embed/thumbnail ratio; no V2 utility usage yet |
| portrait | `aspect-[4/5]` (4 / 5) | V2 About founder image + placeholder |

No `wide` (21:9) or numeric ratio prop in PR-4V.

## 5. Children contract

Caller-owned children; no cloning, no Image import, no auto object-fit.

## 6. Overflow contract

**Not** set by default. V2 mixes approaches (object-cover on `<img>`, optional parent clip). Callers add `overflow-hidden` via `className` when clipping is needed (Image lab pattern).

## 7. Positioning contract

No `relative` / `absolute` / inset / overlay behavior.

## 8. Visual contract

Layout only: `aspectRatioRoot` (`w-full`) + ratio class. No background, border, radius, shadow, or padding from the atom.

## 9. Accessibility contract

Non-semantic div. Image (or other media) children own alt and roles.

## 10. Responsive contract

CSS `aspect-*` only; width from parent. No JS or breakpoint props.

## 11. Image relationship

```tsx
<AspectRatio ratio="video" className="overflow-hidden rounded-md">
  <Image src="..." alt="..." className="h-full w-full" fit="cover" />
</AspectRatio>
```

## 12. Card relationship

Card provides surface; AspectRatio provides media box inside (Card unchanged).

## 13. Avatar relationship

Avatar size/shape presets remain identity-specific — AspectRatio is not a substitute.

## 14. UI Lab route

`/admin/design-system/atoms/aspect-ratio`

## 15. V2 evidence (read-only)

- `aspect-[4/5]` on About founder block
- Widespread fixed `h-* w-*` + `object-cover` for logos
- Hero/banner: `h-64` / `h-48` + `w-full object-cover` (height-fixed, not aspect utility)
- Image UI lab: fixed `h-40` boxes — formalized by AspectRatio + Image composition
- No V2 AspectRatio component

## 16. Explicit non-goals

Object-fit, src/fallback, captions, Card/Avatar changes, numeric ratios, responsive ratio arrays, V2 migration.

## 17. Migration status

**V2 was inspected READ-ONLY. No V2 application code was modified or rebuilt.**
