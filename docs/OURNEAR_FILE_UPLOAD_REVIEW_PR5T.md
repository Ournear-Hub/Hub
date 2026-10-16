# OURNEAR File upload / uploader architecture review — PR-5T

**Status:** Review complete — **no upload components beyond frozen FileField** (architecture phase — **V2 not modified**)

## 1. Decision

**FILE UPLOAD SYSTEM DOCUMENTED — NO NEW COMPONENT**

Frozen **FileField** (PR-3M) remains the canonical **native file selection** molecule. V2 does **not** demonstrate a reusable **Uploader**, **Dropzone**, **upload progress**, or **attachment list** interaction worth canonicalizing separately.

## 2. Architectural classification

| Class | Finding |
|-------|---------|
| **Native file input** | **Dominant** — ~19 `type="file"` across ~13 TSX files |
| **FileField (DS)** | Frozen — styled native input; **no upload logic** |
| **FileUploader / Dropzone / UploadList** | **0** shared components |
| **Drag-and-drop upload** | **0** handlers / libraries |
| **Client pre-upload preview** | **0** `URL.createObjectURL` / FileReader patterns surveyed |
| **Upload progress (percent)** | **0** — only **`processing` / “Uploading…”** copy |
| **Server media display** | CMS **Media & Social** table thumbnails — **existing assets**, not uploader UI |
| **Domain workflows** | Resume, documents, photo, logo, bulk import — **page-owned** Inertia forms |

## 3. Evidence inspected (read-only)

**Frontend:** `D:\Ournear Hub\Ournear Hire\resources\js` (PowerShell `Get-ChildItem -Recurse` + `Select-String`), `package.json`, `Components/molecules/FileField.tsx`, UI Lab FileField section if present.

**Backend (UI contract only, not modified):** Inferred from frontend `forceFormData`, multipart posts, delete routes — no shared upload SDK.

| Term | App count |
|------|-----------|
| `FileUploader`, `FileUpload`, `Dropzone`, `UploadProgress`, `AttachmentList` | **0** |
| `react-dropzone`, `uppy`, `filepond`, `uploadcare`, `tus`, `resumable` | **0** |
| `onDragEnter` / `onDrop` / `dropzone` | **0** |
| `type="file"` | **~19** |
| `FileField` references | **~24** (includes DS molecule + UI Lab) |

### Files with `type="file"` (unique)

| File | Domain |
|------|--------|
| `Pages/Account/Resumes/Index.tsx` | Candidate resume |
| `Pages/Account/Documents/Index.tsx` | Private documents |
| `Pages/Account/Employer/Documents/Index.tsx` | Employer documents |
| `Pages/Account/Profile.tsx` | Profile photo |
| `Pages/Jobs/Apply.tsx` | Apply-with-resume |
| `Pages/Admin/MediaSocial/Index.tsx` | CMS image upload |
| `Pages/Admin/Candidates/ResumeImport.tsx` | Bulk resume ZIP/files |
| `Pages/Admin/Employers/Index.tsx` | Employer logo (drawer) |
| `Pages/Admin/Users/Show.tsx`, `Admin/Profile/Show.tsx`, `Admin/Settings.tsx` | Staff/admin profile assets |
| `Pages/Freelancers/Index.tsx` | Freelancer intake |
| `Components/molecules/FileField.tsx` | DS canonical |

## 4. File input findings

**Patterns:**

1. **Visible native `<input type="file">`** with Tailwind/`inputClass` (MediaSocial, Profile, ResumeImport).
2. **Hidden input + Button “Choose file”** (`sr-only` input, ref click) — Account Resumes.
3. **DS FileField** — UI Lab / design system; **not** yet dominant in production pages (most V2 uses raw input).
4. **`accept`** attributes per domain (PDF/DOC, images, ZIP).
5. **`multiple`** — bulk resume import only.

**No** `capture`, `webkitdirectory`, or directory upload evidenced.

## 5. Actual upload UI findings

Upload UX is **form submit**, not a dedicated uploader widget:

- **Inertia `useForm`** + **`form.post(..., { forceFormData: true })`** or **`router.post`** with FormData.
- Submit buttons use **`disabled={form.processing}`** or **`loading={form.processing}`** (Button).
- Copy: **“Uploading…”** (MediaSocial) — **binary busy state**, not progress percentage.
- **Errors:** server validation via `form.errors.*` + inline `text-danger`.
- **Post-upload lists:** server-rendered tables/lists (media library, import batches, document lists) — **CRUD pages**, not `UploadList` molecule.

## 6. Drag-and-drop findings

**None.** No drag event handlers, no dropzone styling system, no DnD upload libraries.

## 7. Preview findings

| Type | Evidence |
|------|----------|
| **Local pre-upload image/PDF preview** | **Not found** (no object URL preview pipeline) |
| **Selected filename text** | Resumes — shows **`file.name`** before submit |
| **Server-backed thumbnails** | MediaSocial table **`img`** for stored assets; Profile shows **existing `photo_url`** |
| **Replace / remove** | Profile **Remove photo**; Media **Delete** with confirm; Resumes **Replace** flow |

Previews are **domain display** or **filename label**, not a reusable preview component.

## 8. Upload progress / async state findings

- **No** `onUploadProgress`, **no** XMLHttpRequest progress listeners, **no** percent **`progressbar`** for uploads.
- **Inertia `processing`** flag drives disabled buttons / loading spinner on Button — **generic form busy**, not upload-specific Progress molecule.
- **No** retry/cancel/abort upload UI (no in-flight multipart cancellation).
- Resume import shows **batch results** after server processing — **server job**, not client upload queue.

Do **not** confuse **Inertia navigation progress** (global) with **byte-level upload progress** — neither is modeled as a DS upload progress component in V2.

## 9. Backend / storage contract findings (read-only, from frontend calls)

- **Multipart:** `forceFormData: true` on posts with `File` / `File[]` in form data.
- **Endpoints:** page-specific routes (e.g. `/admin/media-social/media`, `/account/resumes`, `/jobs/{slug}/apply/resume`, `/admin/candidates/imports`).
- **Media:** upload returns to paginated **media** list; delete by asset id; **in_use** guard in UI.
- **No** frontend shared **media ID picker**, **signed URL uploader**, or **tus/resumable** client.

Contract is **per-feature Laravel + Inertia**, not a reusable upload API layer in JS.

## 10. Dependency / library findings

`package.json` dependencies: `@inertiajs/react`, `@tabler/icons-react`, `react`, `react-dom` only.

**No** upload or dropzone packages installed.

## 11. Accessibility findings

- **Labels:** `label htmlFor` + hidden input pattern (Resumes); `aria-label` on some admin fields; Profile photo input with visible label section.
- **`aria-invalid`:** via FileField when used; inline errors often **`role="alert"`** on Resumes.
- **No** `aria-valuenow` upload progress bars.
- **No** live region for upload status beyond error alerts.
- Drag-and-drop **not present** — no keyboard gap for dropzones.

## 12. Keyboard findings

- File selection via **native file picker** (button activates hidden input or direct input).
- **No** custom keyboard DnD reorder/upload.

## 13. State ownership findings

| State | Owner |
|-------|--------|
| Selected file(s) | **Inertia `useForm` data** or local `setData` on change |
| Upload execution | **Page** — `post`/`put` with `forceFormData` |
| Processing UI | **`form.processing`** (Inertia) |
| Validation errors | **Server** → `form.errors` |
| Uploaded asset catalog | **Server props** (paginators, batches) |
| Media delete / replace rules | **Domain page** logic |

**No** shared upload store, context, or service in frontend.

## 14. Responsive findings

- Mobile-friendly **min-h-11** buttons on Account Resumes.
- Same native file input on mobile/desktop — **no** mobile-specific uploader variant.
- **No** responsive dropzone layout system.

## 15. Visual contract

Repeated **ad hoc** patterns, not one DS upload surface:

- Bordered **form card** + heading + helper text + file input + primary submit.
- **Admin MediaSocial:** inline file input + brand button; **`disabled:bg-disabled`** when processing.
- **Account Resumes:** hidden input + outline **Choose file** + primary upload with **Button loading**.
- **Error:** `text-sm text-danger` below control.

**No** shared dropzone dashed border, upload queue rows, or progress strip.

## 16. Domain vs design-system classification

| Area | Classification |
|------|----------------|
| Resume / apply / documents | **Domain workflow** — candidate/employer product rules |
| Profile / admin photo | **Domain workflow** — avatar with remove |
| Employer logo in drawer | **Domain** — employer CRM |
| Media & Social CMS | **Product admin** — library table + simple upload form |
| Bulk resume import | **Domain batch job** — results table, not uploader queue |

**Generic reusable uploader contract:** **not evidenced.**

## 17. Relationship to existing FileField

- **FileField** = **only** canonical **file selection** control (native input + surface/error classes).
- V2 production mostly uses **raw `<input type="file">`** — future **adoption** can swap to FileField **without** adding Uploader.
- **Do not expand FileField** with upload, preview, progress, or drag/drop (PR-3M boundary preserved).

## 18. Relationship to Progress / Alert / Toast / Card / Image / Avatar

- **Button `loading` / `processing`:** sufficient for V2 busy upload state.
- **Alert / inline errors:** validation feedback — not upload-status Alert molecule.
- **No DS Progress molecule** in OurNear Hire `Components/` for upload bytes (references repo only).
- **Image / Avatar:** display **server URLs** — separate from upload pipeline.
- **Card:** wraps forms — not upload-specific.

Compose existing primitives on pages; **no** UploadProgress wrapper justified.

## 19. Candidate patterns

| Pattern | Where | Reuse | Verdict |
|---------|--------|------:|---------|
| Native file + submit | Many pages | Per domain | **FileField** target |
| Hidden input + Choose file button | Account Resumes | 1 | Page pattern |
| forceFormData Inertia post | Documents, resumes, media, apply | Several | **Caller-owned** |
| processing / Uploading… | MediaSocial, forms | Several | **Button busy** — not UploadProgress |
| Media library table | MediaSocial | 1 | **Admin product UI** |
| Import batch results | ResumeImport | 1 | **Domain batch** |

## 20. Why created / rejected

**Rejected:** FileUploader, Dropzone, UploadList, UploadProgress molecules/organisms.

1. **No** shared upload component or import path in V2.
2. **No** drag-and-drop upload interaction.
3. **No** percentage progress or retry/cancel upload queue.
4. **No** client preview framework — filename or server thumbnail only.
5. **No** upload npm libraries.
6. Upload flows are **Inertia form posts** with **domain-specific** copy, validation, and post-upload screens.
7. **FileField already** covers the only repeated low-level need: **file selection**.

## 21–25. Contracts (if created)

**N/A** — no new components.

## 26. State ownership contract

Caller/page owns: files in form state, submit route, errors, lists of uploaded entities. DS **FileField** owns: native input presentation only.

## 27. UI Lab route

**No new routes.** Existing FileField lab (if wired) remains the file **selection** demo.

## 28. Documentation path

`docs/OURNEAR_FILE_UPLOAD_REVIEW_PR5T.md`

## 29. Files created

- `D:\Ournear Hub\Ournear Hire\docs\OURNEAR_FILE_UPLOAD_REVIEW_PR5T.md`

## 30. Files modified

**None** (including **FileField unchanged**)

## 31. V2 inspection status

**Complete (read-only)**

## 32. V2 modification status

**None**

## 33. Dependency changes

**None**

## 34. TypeScript result

**Not run** (documentation-only)

## 35. Build result

**Not run** (documentation-only)

## 36. Deviations

N/A

## 37. V2 migration / adoption status

**Not started.** Optional future work: replace raw file inputs with **FileField** on pages — **not** introduce Uploader without new V2 evidence (e.g. repeated dropzone + progress UX).

## 38. Stop condition

**PR-5T complete.** Do not start PR-5U. Do not expand FileField. Do not install upload libraries.
