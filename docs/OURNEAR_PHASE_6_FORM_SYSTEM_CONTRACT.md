# OurNear Corporate Library — Phase 6 Form System Contract

**Date:** 2026-10-09  
**Repository:** `D:\Ournear Hub\Ournear Hire`  
**Status:** **Documentation only — no code authorized**

**Phase 6 batch:** Form system contract (**F-1**, **F-2** per [OURNEAR_PHASE_6_UX_SYSTEMS_ARCHITECTURE_CHARTER.md](./OURNEAR_PHASE_6_UX_SYSTEMS_ARCHITECTURE_CHARTER.md)).

**Related contracts:** [Search System](./OURNEAR_PHASE_6_SEARCH_SYSTEM_CONTRACT.md) · [Data List Pattern](./OURNEAR_PHASE_6_DATA_LIST_PATTERN_CONTRACT.md)

**Evidence baseline (not re-audited):** [OURNEAR_FORM_ARCHITECTURE_REVIEW_PR5Z.md](./OURNEAR_FORM_ARCHITECTURE_REVIEW_PR5Z.md), [OURNEAR_CORPORATE_LIBRARY_SPECIFICATION_v1.0.md](./OURNEAR_CORPORATE_LIBRARY_SPECIFICATION_v1.0.md) §16, [OURNEAR_CORPORATE_ARCHITECTURE_CLOSURE_PR5AE.md](./OURNEAR_CORPORATE_ARCHITECTURE_CLOSURE_PR5AE.md).

**Decision (frozen):** **Field molecules remain COMPOSE-ONLY.** **F-3 (FormContext / adapter) is DEFERRED** — this document does **not** authorize FormContext, adapter code, or V2 migration.

---

## 1. Purpose and scope

This contract maps the **Form System** (spec §16) to **OurNear Hire V2 reality**: frozen **FormField family** for presentation, **Inertia `useForm`** for data-entry state on pages, and **Laravel** for authoritative validation.

**In scope:** F-1 (Inertia ↔ system contract mapping), F-2 (composition recipes), caller responsibilities per PR-5Z.

**Out of scope:** New canonical Form/FormSection/FormActions, client schema libraries, filter forms as “Form organism” (see Search/Data contracts), PR-6A.

---

## 2. PR-5Z settled decision (must not reopen)

**Outcome:** **EXISTING FORM SYSTEM SUFFICIENT — NO NEW COMPONENT**

| Rejected (remain closed) | Accepted |
|--------------------------|----------|
| Form, FormLayout, FormActions, ValidationSummary, FormWizard, FormProvider | Frozen **FormField**, **FieldGroup**, **FieldSet**, **InputGroup** + field atoms/molecules |
| React Hook Form, Formik, Zod/Yup in frontend | **Inertia `useForm`** (~55 page files evidenced) |
| Form-level DS wrapper | **Alert** for rare form-level banners; **Button** + **Cluster** for actions |

Phase 6 **does not** overturn PR-5Z. Spec §16 **FormContext** diagram is **conceptual** — not implemented and **not authorized** in this batch (F-3).

---

## 3. Frozen FormField family — composition responsibilities

### 3.1 Molecules (canonical, frozen)

| Molecule | Responsibility |
|----------|----------------|
| **FormField** | Single control: optional label, hint, error, required; wires `id`, `aria-describedby`, `aria-invalid`, `error` prop to known child controls (Input, Select, specialized fields, SearchField, Combobox, MultiSelect, InputGroup) |
| **FieldGroup** | Related controls with group legend-style label, orientation/gap, group hint/error (does not invalidate children) |
| **FieldSet** | Native `<fieldset>` + `<legend>`, description, group error |
| **InputGroup** | One control with leading/trailing slots (distinct from FieldGroup) |

**Location:** `resources/js/Components/molecules/` — exports per PR-5AE inventory.

**Molecules must not:** fetch data, own submit lifecycle, or call Inertia `router` (library dependency rules).

### 3.2 V2 parallels (production — adoption deferred)

| V2 pattern | Consumers (evidenced) | PR-6A direction |
|------------|----------------------|-----------------|
| **Public/FormField.tsx** | ~16 files | Migrate to **molecules/FormField** + canonical field child |
| **Admin Label + Input/Select + inline error** | Admin CRUD, drawers | **FormField** + fields |
| **CmsEditor local Field** | CMS only | **FormField** on adoption |
| **FieldGroup / FieldSet / InputGroup** on pages | UI Lab only | Optional adoption where grouping needed |

---

## 4. System contract F-1 — Inertia `useForm` mapping

Spec §16 conceptual **FormContext** fields mapped to **Inertia v2 `useForm`** (page-owned):

| Spec / system concern | Inertia / V2 owner | Notes |
|----------------------|-------------------|--------|
| **values** | `form.data`, `setData`, `setData(key, value)` | Controlled fields typical |
| **errors** | `form.errors` (server-shaped keys) | Field-level dominant |
| **processing** | `form.processing` | Disables submit buttons on pages |
| **isDirty** | **Not** first-class in V2 | Spec P2 “warn on navigate” — **DEFER** |
| **setField** | `setData` per field | Page handlers |
| **submit** | `form.post` / `put` / `patch` + `onSubmit` preventDefault | Page-owned routes |
| **reset** | `form.reset()` where used | Page-owned |
| **mode: inertia \| api** | **inertia** only in V2 web | API v1 parity — **DEFER** (F-3 / separate mobile work) |

### 4.1 Validation and errors

| Concern | Owner |
|---------|--------|
| **Authoritative validation** | **Laravel** → Inertia response |
| **Field error display** | Page passes `errors.*` into FormField / Public FormField / Admin `fieldError()` |
| **Nested keys** | e.g. `form.errors['sections.0.title']` — page mapping |
| **Client format checks** | HTML5 `required`, page-specific UI (Register password match, username availability) — **not** a shared DS schema |
| **Form-level summary** | Rare (e.g. CmsEditor banner) — compose **Alert**, not ValidationSummary molecule |

### 4.2 Submission and multipart

| Concern | Owner |
|---------|--------|
| Submit handler | **Page** — `event.preventDefault()` → `form.post(route, options)` |
| File uploads | **Page** — `forceFormData: true` where evidenced (resumes, media) |
| Upload progress (spec P2) | **DEFER** — not a DS contract today |

### 4.3 Filter forms (boundary)

**GET filter forms** (FilterBar, JobsSearchPanel) use **native form submit** + **FormData** or controlled state → **`router.get`**. They are **Search/Data list workflows**, not data-entry Form System instances. Fields inside filters still use atoms/molecules when adopted; **no** separate “FilterForm” system.

Cross-reference: [OURNEAR_PHASE_6_SEARCH_SYSTEM_CONTRACT.md](./OURNEAR_PHASE_6_SEARCH_SYSTEM_CONTRACT.md) §3, [OURNEAR_PHASE_6_DATA_LIST_PATTERN_CONTRACT.md](./OURNEAR_PHASE_6_DATA_LIST_PATTERN_CONTRACT.md) §2.

---

## 5. System contract F-2 — composition recipes

Recipes describe **who composes what**. They do **not** introduce new components.

### 5.1 Auth (login, register, reset)

| Element | Composition |
|---------|-------------|
| Fields | **Public FormField** today → target **FormField** + Input/PasswordField/EmailField |
| State | **useForm** |
| Errors | Server → `errors.email`, etc. |
| Actions | Single **Button** submit; `disabled={form.processing}` |
| Layout | **Card** + `space-y-4` — page Tailwind, not FormLayout |

### 5.2 Admin CRUD (drawer / modal form)

| Element | Composition |
|---------|-------------|
| Fields | Admin Label+control → **FormField** + Input/Select/Textarea |
| State | **useForm** |
| Layout | **grid** / **Stack** inside **Drawer** or **Dialog** |
| Actions | `flex gap-2` Save/Cancel — **Button** + optional **Cluster** |
| Domain rules | Job publishing, hiring gates — **page/domain** |

### 5.3 CMS / structured editors

| Element | Composition |
|---------|-------------|
| Fields | Local Field wrappers → adopt **FormField** |
| Form-level error | **Alert** (error variant) when `Object.keys(form.errors).length > 0` |
| State | **useForm** with nested keys |

### 5.4 Profile / account modules

| Element | Composition |
|---------|-------------|
| Shell | **ProfileModuleEditorShell** — **domain layout**, not DS Form |
| Fields | Mixed Public FormField / custom |
| Multistep wizard | **None** reusable (PR-5Z); Stepper **not** in DS (PR-5E) |

### 5.5 Apply-with-resume / file-heavy forms

| Element | Composition |
|---------|-------------|
| Files | **FileField** molecule inside **FormField** |
| Submit | **useForm** + `forceFormData: true` |

---

## 6. Caller responsibilities (exact — PR-5Z §14)

| Concern | Owner |
|---------|--------|
| Field values (data entry) | **Inertia form** or FormData on filter submit |
| Validation messages | **Server** → `form.errors` |
| Submit lifecycle | **Page** handler |
| Field a11y wiring | **FormField** or page (Admin gaps: `aria-describedby` on some errors — adoption fix) |
| Business rules | **Domain** |
| Multipart / files | **Page** + Laravel |
| Action row layout | **Page** — Button groups |
| Grid / responsive layout | **Page** — Stack, Card, Tailwind grid |
| Focus first error | **Not** standardized in V2 — **unresolved** for platform; not a Phase 6 deliverable |

---

## 7. Spec §16 requirement map

| Spec §16 requirement | Status |
|---------------------|--------|
| Controlled fields; Inertia first-class | **Implemented** — useForm |
| Server validation authoritative | **Implemented** |
| Client validation optional | **Partial** — page-local only |
| Dirty / unsaved navigate warn | **DEFER (P2)** |
| File uploads forceFormData | **Implemented** — page option |
| Upload progress | **DEFER (P2)** |
| Multi-step Stepper | **DEFER** — no DS Stepper |
| Autosave | **DEFER (P2)** — domain-specific |
| A11y labels, aria-invalid, error ids | **Implemented** via FormField / patterns; admin adoption improves consistency |
| API v1 same error shape | **DEFER** — F-3 / mobile |

---

## 8. F-3 FormContext / adapter — deferred

**Charter deliverable F-3:** Optional thin adapter (e.g. `createInertiaFormBridge(useFormReturn)`) exposing a read-only FormContext-like surface for tests, shared hooks, or API v1.

| Item | Status |
|------|--------|
| F-3 implementation | **NOT AUTHORIZED** |
| Product decision | **REQUIRED** before any adapter code |
| V2 imports of adapter | **Forbidden** until PR-6A or explicit pilot |

This contract documents F-3 only as a **future optional track** — not Phase 6 v1 doc batch scope beyond this paragraph.

---

## 9. Explicit exclusions

| Exclusion | Rationale |
|-----------|-----------|
| Canonical **Form**, **FormSection**, **FormActions** | PR-5Z |
| **ValidationSummary**, **FormWizard**, **FormProvider** | PR-5Z |
| RHF / Formik / Zod | PR-5Z §15 — zero deps |
| V2 migration (Public FormField, Admin Label pattern) | **PR-6A** |
| Changing frozen **FormField** API | PR-5AE freeze |
| FilterBar / list filters as Form System | Search/Data contracts |
| Phase 6 **code** | Charter |

---

## 10. Future code approvals (not granted here)

| Work | Approval needed |
|------|-----------------|
| PR-6A field consolidation | **PR-6A program** |
| F-3 Inertia form bridge | **Product + architecture** (F-3) |
| API v1 shared form error types | **Platform / mobile** gate |
| New form-level molecule | **Contradicts PR-5Z** unless new evidence PR |

---

## 11. Verification

| Check | Result |
|-------|--------|
| PR-5Z reopened | **No** |
| FormContext / adapter authorized | **No** |
| F-1 / F-2 covered | **Yes** |
| Application code changed | **No** (this task) |

---

## Stop condition

Form System **documentation contract** complete. No implementation. Do not start PR-6A or F-3 from this document alone.
