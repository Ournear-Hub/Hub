# OURNEAR Rich text / editor architecture review — PR-5U

**Status:** Review complete — **no rich-text editor components** (architecture phase — **V2 not modified**)

## 1. Decision

**RICH TEXT / EDITOR SYSTEM DOCUMENTED — NO COMPONENT**

Frozen **Textarea** (PR-3D) remains the canonical **plain multiline text** control. V2 does **not** demonstrate a reusable **RichTextEditor**, **MarkdownEditor**, **formatting toolbar**, **contenteditable surface**, or **editor-specific progress/state** worth canonicalizing.

## 2. Architectural classification

| Class | Finding |
|-------|---------|
| **Plain `<textarea>` / Textarea** | **Dominant** — admin, account, public, and CMS surfaces |
| **Structured CMS (`CmsEditor`)** | **Domain workflow** — section nav + fields + sidebar preview; **not** WYSIWYG |
| **RichTextEditor / WYSIWYG / MarkdownEditor** | **0** shared components |
| **contentEditable / execCommand** | **0** in `resources/js` |
| **Editor libraries (TipTap, Quill, Lexical, etc.)** | **0** in `package.json` or imports |
| **Formatting toolbar (bold/lists/links)** | **0** editor toolbars |
| **HTML authoring UI** | **0** — display uses **`whitespace-pre-line`** plain text |
| **Laravel Markdown mail** | **Server-side** Blade markdown templates — **not** frontend editor |

## 3. Evidence inspected (read-only)

**Frontend:** `D:\Ournear Hub\Ournear Hire\resources\js` — `rg` searches across Pages, Components, ui-lab, types; `package.json`.

**Representative surfaces read:** `Pages/Admin/WebsiteContent/CmsEditor.tsx`, `WebsiteContent/Index.tsx`, `Components/Admin/JobForm.tsx`, `Components/Public/JobDetail.tsx`, `Components/atoms/Textarea.tsx`, `Components/Admin/ui/Textarea.tsx`, `Components/molecules/FormField.tsx`, `Components/Public/FormField.tsx`, `ui-lab/sections/TextareaLabSection.tsx`, `types/cms.ts`.

**Backend (representation only):** CMS types store **string** fields (`body`, `heading`, `description`, `extra` map); Laravel `Mail` classes use **`markdown:`** views — no frontend editor contract.

| Term | App count (`resources/js`) |
|------|----------------------------|
| `RichText`, `TextEditor`, `ContentEditor`, `MarkdownEditor`, `WYSIWYG` | **0** |
| `contentEditable`, `execCommand` | **0** |
| `ProseMirror`, `TipTap`, `Quill`, `Lexical`, `Slate`, `CKEditor`, `TinyMCE` | **0** |
| `dangerouslySetInnerHTML` | **1** file — `Components/Pagination.tsx` (unrelated to authoring) |
| Raw `<textarea` in TSX | **~8** direct markup instances |
| `Textarea` import / Admin `ui` usage | **~30+** files (JobForm, Messages, CRM, Placements, etc.) |

## 4. Editor component findings

**No** named editor components:

- **`CmsEditor`** is a **structured page-section editor** (Inertia form, section list, text inputs/textareas, media `<select>`, live preview panel). It does **not** expose rich formatting, markdown, or contenteditable.
- **No** `RichTextEditor`, `TextEditor`, `Composer` (message composer is plain **Textarea**), or WYSIWYG wrappers.

## 5. Editor library findings

`package.json` dependencies: `@inertiajs/react`, `@tabler/icons-react`, `react`, `react-dom` only.

**No** `@tiptap/*`, `quill`, `react-quill`, `lexical`, `slate-react`, `draft-js`, `@ckeditor/*`, `@tinymce/*`, `froala`, `editor.js`, or `prosemirror*` in the Hire application tree.

## 6. Formatting toolbar findings

Matches for “toolbar”, “bold”, “italic”, etc. in TSX are **typography/CSS** (Tailwind `font-bold`, UI Lab copy, page chrome) — **not** formatting command bars.

- **No** `role="toolbar"` for editors.
- **No** bold/italic/list/link **command buttons** tied to an editing surface.
- **`aria-pressed`** appears on **SaveJobButton** (saved state) — unrelated to text formatting.

## 7. Contenteditable / editor-state findings

**None.** No `contentEditable`, `document.execCommand`, editor `Selection`/`Range` handling, HTML/Markdown serialization on the client, or JSON document models for rich content.

All multiline editing is **controlled string state** on native `<textarea>` via `value` + `onChange` (Inertia `useForm` or local `useState`).

## 8. Content storage findings

| Surface | Stored shape | Editing UI |
|---------|--------------|------------|
| CMS sections (`CmsPage`) | Plain **strings** (`body`, `heading`, `subtitle`, `extra.*`) | Textarea / input in `CmsEditor` |
| CMS item bullets | Newline-joined string in form → array server-side | Textarea, hint “One point per line” |
| Jobs (`JobForm`) | Plain text columns (`description`, `requirements`, …) | Admin **Textarea** |
| Messages / notes / CRM | Plain text | **Textarea** |
| Campaigns / automations | Plain text notes/descriptions | **Textarea** |
| Email notifications | Laravel **markdown** Blade views | **Not** edited in React UI |
| Public job display | Plain text | Rendered with **`whitespace-pre-line`** in `JobDetail` |

**Stored HTML in DB does not appear as an authoring requirement** — public rendering avoids `dangerouslySetInnerHTML` for job/CMS body content surveyed.

## 9. CMS / content-authoring findings

| Surface | Pattern | Reusable editor? |
|---------|---------|------------------|
| **Admin → Website Content → `CmsEditor`** | Multi-column layout: section nav, field grid, preview aside | **No** — product-specific CMS schema |
| **Website Content → Contact enquiries** | Filters, drawer, note **textarea** | Domain CRM |
| **Media & Social** | File upload + metadata (not rich body editor) | Out of PR-5U scope (see PR-5T) |
| **Job descriptions** | Four large textareas in `JobForm` | Domain job workflow |
| **Scorecard templates, roles, settings** | Textareas for criteria/notes | Domain admin forms |
| **Account profile / reviews** | Public `FormField as="textarea"` or raw textarea | Domain account |

**Preview in `CmsEditor`** is a **stylistic mock** (`whitespace-pre-line` on strings) — not WYSIWYG HTML preview.

## 10. Accessibility findings

Plain textarea patterns only:

- **Labels:** `Label` + `htmlFor`, or `FormField` / `Field` local labels; some **`aria-label`** on admin compact forms (Campaigns).
- **`aria-invalid`:** Canonical **Textarea** and **FormField** pass through when `error` is set.
- **`aria-describedby`:** **FormField** molecule merges hint/error ids; UI Lab Textarea documents error association.
- **No** editor toolbar button labels, **no** `aria-pressed` on format toggles, **no** contenteditable `role="textbox"` editor region beyond native textarea semantics.

No editor-specific accessibility contract is justified.

## 11. Keyboard findings

- Native textarea behavior only (typing, selection, platform shortcuts inside the control).
- **No** custom editor shortcuts (e.g. Ctrl+B), **no** toolbar roving tabindex, **no** slash commands.

## 12. Responsive findings

- Textareas use shared **`min-h-24`** (admin) / **`min-h-28`** (public) recipes; CMS uses **`min-h-28`** on local `Field` textarea.
- **No** collapsible mobile formatting bars or fullscreen editor modes.
- `CmsEditor` layout: responsive grid (`md:`, `xl:`) for **page structure**, not editor chrome.

## 13. State ownership findings

| Owner | Usage |
|-------|--------|
| **Inertia `useForm`** | CMS save, jobs, admin CRUD — string fields |
| **Local `useState`** | Reply/notes fields on some index pages |
| **Server props** | CMS page payload, job record — rehydrated into form |

**No** editor instance ref, **no** debounced HTML sync, **no** shared editor context.

## 14. Domain-vs-design-system classification

| Interaction | Classification |
|-------------|----------------|
| Multiline notes, descriptions, messages | **Generic plain text** → **Textarea** (+ **FormField** wrapper) |
| CMS section/item editing | **Domain** — schema-driven website content |
| Job long-form fields | **Domain** — recruiting |
| Bullet lists as newline text | **Domain convention**, not Markdown editor |
| Email template authoring | **Not in V2 UI** (server markdown mail) |

**No** repeated generic **rich-text toolbar + editing surface + serialization** contract across domains.

## 15. Relationship to Textarea

- **Textarea** (atom, PR-3D) is the **only** canonical multiline **input** control.
- **Admin/ui/Textarea** is a thin **surface="admin"** adapter.
- **FormField** (molecule) composes **Textarea** with label/hint/error/`aria-describedby`.
- **Public FormField** embeds raw **textarea** with `controlBase` — parallel pattern, not rich text.
- **Do not** extend Textarea with contenteditable, toolbars, or HTML value props.

## 16. Relationship to Button / IconButton / DropdownMenu / Popover / Tooltip

V2 does **not** compose these into an editor toolbar. Buttons on CMS pages are **Save**, **Preview**, **Reset** — page actions, not formatting.

If rich text were adopted in the future, toolbars would likely compose **IconButton**, **Button**, **Popover** (link URL), **Tooltip** — but **no V2 evidence** to canonicalize that now.

## 17. Candidate patterns

| Pattern | Evidence | DS action |
|---------|----------|-----------|
| Plain multiline field | Widespread **Textarea** | **Keep frozen Textarea** |
| Label + hint + error + textarea | **FormField**, public **FormField** | Use existing molecules |
| CMS structured editor | **`CmsEditor`** only | **Domain** — do not generalize to ContentEditor organism |
| Live plain-text preview | **`CmsEditor`** aside | Page-local; not Markdown/HTML preview component |
| Job body display | **`whitespace-pre-line`** | Typography/layout — not editor |
| Laravel markdown email | PHP `Mail` classes | Backend — not DS editor |

## 18. Why created / rejected

**Rejected — RichTextEditor / MarkdownEditor / ContentEditor organism / EditorToolbar:**

- Zero editor libraries and zero contenteditable/formatting UI.
- All multiline authoring maps to **string** fields and native textarea UX.
- **`CmsEditor`** name suggests editing but implementation is **forms + preview**, not reusable rich text.
- Creating an editor would be **speculative** and would violate PR-5U stop conditions (no install-without-evidence).

**Accepted — documentation only:**

- Preserves **Textarea-first** strategy aligned with PR-3D and native-first date/file patterns (PR-5S, PR-5T).
- Clarifies boundary: **CMS ≠ rich-text design system**.

## 19. Final API contract (if created)

**N/A** — no new components.

**Textarea** (unchanged): native textarea props + `surface`, `error`, optional `className`; forwards ref.

## 20. Semantic contract (if created)

**N/A** for rich text.

**Plain multiline:** semantic **`<textarea>`**; value is **plain text** string. Line breaks preserved; public display may use **`whitespace-pre-line`**.

## 21. Accessibility contract (if created)

**N/A** for editor.

Continue using **FormField** / **Label** + **`aria-invalid`** + **`aria-describedby`** for textareas (as UI Lab and molecules document).

## 22. Keyboard contract (if created)

**N/A** for editor — platform textarea behavior only.

## 23. Interaction contract (if created)

**N/A** for rich text.

**Plain multiline:** user types in textarea → controlled state → form submit → server validation → Inertia response.

## 24. State ownership contract

- **Caller / Inertia form** owns string value and validation errors.
- **Textarea** is presentational/control only — no internal document model.
- **CmsEditor** owns section selection and nested section/item patches — **page component**, not DS.

## 25. Serialization / value contract

- **Value type:** `string` (UTF-8 plain text).
- **Newlines:** significant for display (`whitespace-pre-line`) and CMS bullets (split/join on `\n`).
- **No** HTML sanitization pipeline on client (none needed without HTML authoring).
- **No** Markdown parse/render in React authoring paths surveyed.

## 26. UI Lab route (if created)

**N/A** — no new components. Existing **`atoms-textarea`** section unchanged.

## 27. Documentation path

`docs/OURNEAR_RICH_TEXT_EDITOR_REVIEW_PR5U.md`

## 28. Files created

- `D:\Ournear Hub\Ournear Hire\docs\OURNEAR_RICH_TEXT_EDITOR_REVIEW_PR5U.md`

## 29. Files modified

**None**

## 30. V2 inspection status

**Complete (read-only)**

## 31. V2 modification status

**None**

## 32. Dependency changes

**None**

## 33. TypeScript result

**Not run** (documentation-only — no implementation changed)

## 34. Build result

**Not run** (documentation-only — no implementation changed)

## 35. Deviations

None.

## 36. V2 migration / adoption status

**Not started.** Optional future work: replace ad hoc raw `<textarea className={inputClass}>` in `CmsEditor`, `Settings`, and similar with canonical **Textarea** or **FormField** — **plain text only**, not rich-text adoption.

**Do not** introduce TipTap/Quill/etc. without new repeated V2 product evidence.

## 37. Stop condition

**PR-5U complete. Stop. Do not start PR-5V. Do not begin V2 adoption/migration.**
