# OURNEAR FileField — PR-3M

**Status:** Implemented

## Purpose

Native **file selection** control for forms. No upload, storage, progress, preview, drag-and-drop, or validation services.

## V2 inspection

| Finding | Detail |
|---------|--------|
| Reusable component | **None** |
| Usage | ~18 raw `<input type="file">` across Account (resumes, documents, profile), Admin (import, settings, media), Jobs Apply, Freelancers |
| Styling | Often `w-full text-sm text-ink` or `block w-full text-sm`; some **sr-only** + “Choose file” button (Apply, Resumes) |
| accept | PDF/DOC/DOCX resumes; images for avatars; mixed for documents |
| multiple | Admin ResumeImport |
| capture | Not observed |
| Adapters | **None** — consumers unchanged |

## Component

- **Path:** `resources/js/Components/molecules/FileField.tsx`
- **Export:** `@/Components/molecules`
- **Not** built on `Input` atom — native file control uses `fileInputBase` / `fileInputAdminBase`

## API

```tsx
<FileField
  surface="public" | "account" | "admin" | "corporate"  // default public
  error={boolean}
  className?        // wrapper div
  inputClassName?   // native input
  ref               // HTMLInputElement
  accept, multiple, capture, disabled, required, name, onChange, aria-*, …
/>
```

`type` is always `"file"`. No internal file list state.

## Error

- `error` → `controlError` classes + `aria-invalid={true}` (preserves explicit `aria-invalid` when not invalid)

## FormField

`FileField` registered for FormField `error` + ARIA augmentation (PR-3I contract).

## UI Lab

`/admin/design-system/molecules/file-field`

## Validation

- `npm run typecheck` — PASS
- `npm run build` — PASS

## Intentional non-features

Upload API, progress, DnD, previews, size/type validation, Dropzone.
