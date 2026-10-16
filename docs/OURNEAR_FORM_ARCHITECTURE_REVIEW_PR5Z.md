# OURNEAR Form architecture review — PR-5Z

**Status:** Review complete — **no form-level components beyond frozen field/composition molecules** (architecture phase — **V2 not modified**)

## 1. Decision

**EXISTING FORM SYSTEM SUFFICIENT — NO NEW COMPONENT**

Frozen **field atoms/molecules** (Input, Select, specialized fields, **FormField**, **FieldGroup**, **FieldSet**, **InputGroup**) plus layout primitives (**Stack**, **Cluster**, **Card**, **Button**) fully cover V2 **form presentation**. **Inertia `useForm`** and Laravel validation cover **state and errors**. V2 does **not** prove a distinct reusable **Form**, **FormActions**, **ValidationSummary**, **FormLayout**, or **FormWizard** contract.

## 2. Architectural classification

| Layer | Canonical DS | V2 production |
|-------|--------------|---------------|
| **Single control + label/hint/error** | **FormField** (+ field molecules) | **Admin:** Label + control + `form.errors.*`; **Public/Account:** legacy **Public/FormField** (~16 files) |
| **Related fields (no native fieldset)** | **FieldGroup** | UI Lab only |
| **Semantic field groups** | **FieldSet** | UI Lab; V2 uses ad hoc `<fieldset>` (e.g. Register account type) |
| **Prefix/suffix on one control** | **InputGroup** | UI Lab only |
| **Form `<form>` + submit** | **Page/domain** | **~55** files with **`useForm`** |
| **Validation** | Server → **`form.errors`**; inline per field | Dominant pattern |
| **Form-level error banner** | **Alert** (semantic) | Rare — e.g. CmsEditor one-liner |
| **Action rows** | **Button** + **Cluster** / `flex gap-2` | Page-local (Save/Cancel, Apply/Reset) |
| **Multistep / wizard** | N/A (PR-5E no Stepper) | **None** reusable |
| **Filter forms** | **FilterBar** shell (PR-5O) | Admin GET filters — not data-entry Form organism |

## 3. Evidence inspected (read-only)

**Root:** `D:\Ournear Hub\Ournear Hire\resources\js`, `package.json`.

**Reviewed:** `molecules/FormField.tsx`, `FieldGroup.tsx`, `FieldSet.tsx`, `InputGroup.tsx`, `Public/FormField.tsx`, `Admin/JobForm.tsx`, `Admin/ui` (Label, Input, Select), `Pages/Account/Register.tsx`, `Pages/Contact.tsx`, `Pages/Admin/WebsiteContent/CmsEditor.tsx`, `Profile/ProfileModuleEditorShell.tsx`, `Account/Hiring/HiringEntryOnboarding.tsx`, UI Lab form sections.

| Search | Hire app |
|--------|----------|
| `FormProvider`, `FormContext`, `FormWizard`, `MultiStepForm`, `ValidationSummary`, `FormActions`, `SubmitBar` | **0** |
| `react-hook-form`, `formik`, `zod`, `yup`, `valibot` | **0** |
| `useForm` (Inertia) files | **~55** |
| `from '@/Components/molecules'` on Pages/Components | **0** (DS catalog only) |
| **Public/FormField** consumers | **~16** |
| **FieldGroup / FieldSet / InputGroup** in Pages/Components | **Design System Show + molecule definitions only** |
| `Object.keys(form.errors)` top banner | **CmsEditor** (and similar one-offs) |

## 4. Existing form component findings

**Canonical molecules (frozen):**

- **FormField:** label, hint, error, required; clones `id`, `aria-describedby`, `aria-invalid`, `error` prop onto known controls (Input through MultiSelect, InputGroup).
- **FieldGroup:** legend-style label, orientation/gap, group-level hint/error (does not invalidate children).
- **FieldSet:** native `<fieldset>` + `<legend>`, description, group error.
- **InputGroup:** single control with leading/trailing slots (distinct from FieldGroup).

**V2 parallel (legacy):**

- **Components/Public/FormField.tsx** — all-in-one label + input/textarea + error/helper; used on account auth, contact, freelancers, etc.
- **Admin pattern** — **Label** + **Input** / **Select** / **Textarea** + helper `fieldError('name')` rendering `text-danger` under field.
- **CmsEditor local `Field`** — label + input/textarea (CMS-only).

**No** shared `Form`, `FormSection`, or `FormLayout` React component in V2.

## 5. Form state findings

**Inertia `useForm`:**

- `data` / `setData`, `post` / `put` / `patch`, `processing`, `errors`, `reset` (where used).
- Submit handlers: `event.preventDefault()` → `form.post(route)`.
- **Multipart:** `forceFormData: true` on file uploads (resumes, media, apply-with-resume) — caller option, not DS.

**Controlled vs uncontrolled:**

- **Controlled:** most Inertia forms (`value={form.data.x}`, `onChange` → `setData`).
- **Uncontrolled:** many **FilterBar** filters (`defaultValue` from server `filters`, submit reads FormData).
- **Local state:** employer search panels, Register username availability (async fetch + debounce) — **domain**, not form library.

**No** `FormProvider`, dirty tracking API, or global form context in frontend.

## 6. Validation findings

- **Primary:** Laravel server validation → **`form.errors`** on Inertia response.
- **Inline:** per-field `errors.email`, `form.errors['sections.0.title']`, Admin `fieldError()` pattern, Public FormField `error={errors.name}`.
- **Client:** HTML5 `required`, Register password match UI, username availability — **page-specific**.
- **Cross-field:** handled server-side; no repeated client schema layer (no Zod/Yup).
- **Async validation:** Register username check only (debounced fetch) — not a generic DS contract.

## 7. Error summary findings

**Dominant:** field-level messages only.

**Form-level summary (rare):**

- **CmsEditor:** if `Object.keys(form.errors).length > 0`, show red banner “Please correct the highlighted fields…” — could map to **Alert** on adoption, not **ValidationSummary** molecule.
- Flash / session errors separate (PR-5V).

**No** repeated error list summarizing all field messages, no focus-first-error utility, no `aria-live` form summary region as a shared component.

## 8. Form layout / composition findings

Repeated **presentation** patterns (page-owned):

| Pattern | Example |
|---------|---------|
| `grid gap-4 md:grid-cols-2` / `md:grid-cols-3` | JobForm, admin drawers |
| `space-y-4` card form | Register, Contact |
| **FilterBar** 4-column grid | Admin indexes (PR-5O) |
| **Drawer** / **Modal** wrapping form | Applications, CRM |
| **ProfileModuleEditorShell** | back header + card + optional sticky footer |
| **Accordion** / profile modules | Account profile sections (domain) |

These compose **Stack**, **Card**, Tailwind grid — **not** a stable identical “FormLayout” API across products.

## 9. Form actions findings

Common footer idiom:

```text
<div className="flex gap-2">
  <Button type="submit" disabled={form.processing}>Save</Button>
  <Button type="button" variant="secondary" onClick={onCancel}>Cancel</Button>
</div>
```

Variations: Apply/Reset on filters, single Submit on auth forms, sticky footer in profile editor.

**Not** identical enough (alignment, sticky vs static, destructive third actions, employer vs admin copy) to justify **FormActions** / **SubmitBar** molecule — use **Cluster** + **Button** when adopting DS.

## 10. Multistep / wizard findings

- **No** FormWizard, step indicator controlling form pages, or shared next/previous form contract.
- **PR-5E:** No Stepper component; no contradictory evidence.
- **HiringEntryOnboarding:** checklist + cards — **domain** setup flow, not stepped form wizard.
- **Profile modules:** navigate between sections — **hub/editor** pattern, not wizard with shared step state machine.
- Register **fieldset** for role — single step form.

## 11. Accessibility findings

- **FormField / Public FormField:** `htmlFor` + id, `aria-invalid` on error, `role="alert"` on error text, helper `aria-describedby` (canonical merges ids).
- **FieldSet:** native **legend** semantics (frozen).
- **Register:** `<fieldset>` + `<legend>` for account type.
- **Admin Label htmlFor** + control id where implemented.
- **Gaps (adoption):** not all admin fields wire `aria-describedby` for errors; no systematic focus-on-first-error.

No evidence requiring a new a11y primitive beyond existing molecules + **Alert**.

## 12. Keyboard findings

- Native tab order through fields and buttons.
- **Combobox/DropdownMenu** keyboard models isolated to those widgets (PR-5X).
- **No** form-wide keyboard shortcuts or wizard step keys.

## 13. Responsive findings

- Grids collapse to single column on small screens.
- **ProfileModuleEditorShell** sticky action footer on mobile.
- Drawer forms full-width on mobile.
- FilterBar stacks (`md:grid-cols-4`).

Layout responsibility stays with **page + Stack/Card/grid**, not Form organism.

## 14. State ownership findings

| Concern | Owner |
|---------|--------|
| Field values (data entry) | **Inertia form** or FormData on filter submit |
| Validation messages | **Server** → `form.errors` |
| Submit lifecycle | **Page** handler |
| Field a11y wiring | **FormField** / page |
| Business rules | **Domain** (job publishing, hiring gate, CMS schema) |
| Multipart / files | **Page** + Laravel |

## 15. Dependency findings

`package.json`: `@inertiajs/react`, React only — **no** React Hook Form, Formik, or schema libraries.

## 16. Domain-vs-design-system classification

| Form type | DS role |
|-----------|---------|
| Auth (login/register/reset) | Public FormField → adopt **FormField** |
| Admin CRUD drawers | Label + Admin ui → adopt **FormField** + fields |
| Job / candidate / employer | Domain schemas; same molecules |
| CMS CmsEditor | Domain structured editor |
| Master data | Admin grids + modals |
| Filters | **FilterBar** + fields (PR-5O) — not Form organism |
| Apply with resume | Domain + **FileField** |

## 17. Relationship to frozen form components

- **Do not** add **Form** wrapper that merely renders `<form>`.
- **FormField** remains single-control wrapper; **FieldGroup** / **FieldSet** for grouping; **InputGroup** for affixes.
- Specialized fields (**DateField**, **FileField**, etc.) stay separate molecules composed inside **FormField**.
- **Alert** covers optional form-level failure banner; **Toast** for transient post-submit (PR-5V).

## 18. Candidate patterns

| V2 pattern | Target |
|------------|--------|
| Admin Label + Input + error text | **FormField** + **Input** |
| Public FormField | **molecules/FormField** + field child |
| CmsEditor Field | **FormField** (adoption) |
| Grid + Save/Cancel | **Stack** / grid + **Button** |
| CmsEditor error banner | **Alert** variant error |
| Filter Apply/Reset | **FilterBar** + buttons |
| Profile editor shell | Stay domain layout |

## 19. Why created / rejected

**Rejected — FormLayout, FormActions, ValidationSummary, FormWizard, FormProvider:**

- Layouts and footers vary by page; action rows are trivial **Button** groups; one form-level banner maps to **Alert**; no wizard.
- Would duplicate **FormField** family and Inertia without new interaction semantics.

**Rejected — modifying frozen molecules.**

**Accepted — documentation only.**

## 20–26. Contracts (if created)

**N/A** — no new components.

## 27. UI Lab route (if created)

**N/A** — existing FormField, FieldGroup, FieldSet, InputGroup lab sections unchanged.

## 28. Documentation path

`docs/OURNEAR_FORM_ARCHITECTURE_REVIEW_PR5Z.md`

## 29. Files created

- `D:\Ournear Hub\Ournear Hire\docs\OURNEAR_FORM_ARCHITECTURE_REVIEW_PR5Z.md`

## 30. Files modified

**None**

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

None.

## 37. V2 migration / adoption status

**Not started.** Adoption path (documented only):

- Consolidate **Public/FormField** and Admin Label+control → **molecules/FormField** + canonical fields.
- Use **FieldSet** / **FieldGroup** where V2 uses raw fieldset or related control clusters.
- Form-level errors → **Alert**; actions → **Cluster** + **Button**.
- Keep **useForm** on pages; do not introduce FormProvider.

## 38. Stop condition

**PR-5Z complete. Stop. Do not start PR-6A. Do not begin V2 adoption/migration.**
