# OurNear Corporate Library — Phase 6 UX Systems Architecture Charter

**Date:** 2026-10-09  
**Repository:** `D:\Ournear Hub\Ournear Hire`  
**Status:** **Planning complete — Phase 6 implementation not authorized**

**Baseline:** Corporate Library **Phase 5** closed **`CLOSE WITH EXPLICIT DEFERRALS`** (2026-10-09). Atom/molecule tier **freeze-ready** per PR-5AE. V2 production unchanged.

**Authoritative inputs (not re-audited here):**

| Document | Use in this charter |
|----------|---------------------|
| [OURNEAR_CORPORATE_LIBRARY_SPECIFICATION_v1.0.md](./OURNEAR_CORPORATE_LIBRARY_SPECIFICATION_v1.0.md) | §15 UX Systems overview; **§16 Form**, **§17 Data**, **§18 Search**; **§37 Phase 6**; §10 component names (historical taxonomy) |
| [OURNEAR_UNIFIED_PLATFORM_ARCHITECTURE_v1.1.md](./OURNEAR_UNIFIED_PLATFORM_ARCHITECTURE_v1.1.md) | Layer 5 UX/Product Systems; server source of truth; dependency direction |
| [OURNEAR_PHASE_5_CLOSURE_GATE.md](./OURNEAR_PHASE_5_CLOSURE_GATE.md) | Phase 5 prerequisite met; Phase 6 charter allowed as planning; implementation requires separate authorization |
| [OURNEAR_CORPORATE_ARCHITECTURE_CLOSURE_PR5AE.md](./OURNEAR_CORPORATE_ARCHITECTURE_CLOSURE_PR5AE.md) | Frozen exports; PR-5M/O/Y outcomes; PR-6A boundary |
| [OURNEAR_FORM_ARCHITECTURE_REVIEW_PR5Z.md](./OURNEAR_FORM_ARCHITECTURE_REVIEW_PR5Z.md) | Form system evidence |
| [OURNEAR_TABLE_ARCHITECTURE_REVIEW_PR5M.md](./OURNEAR_TABLE_ARCHITECTURE_REVIEW_PR5M.md) | DataTable / table pattern evidence |
| [OURNEAR_FILTER_ARCHITECTURE_REVIEW_PR5O.md](./OURNEAR_FILTER_ARCHITECTURE_REVIEW_PR5O.md) | FilterBar / filter pattern evidence |
| [OURNEAR_SEARCH_ARCHITECTURE_REVIEW_PR5Y.md](./OURNEAR_SEARCH_ARCHITECTURE_REVIEW_PR5Y.md) | SearchField / search workflow evidence |
| [OURNEAR_TOOLBAR_ARCHITECTURE_REVIEW_PR5P.md](./OURNEAR_TOOLBAR_ARCHITECTURE_REVIEW_PR5P.md) | List page chrome (no Toolbar DS) |
| [OURNEAR_LOADING_ERROR_STATE_ARCHITECTURE_REVIEW.md](./OURNEAR_LOADING_ERROR_STATE_ARCHITECTURE_REVIEW.md) | LoadingState / ErrorState **COMPOSE-ONLY** |

**Terminology guardrails:**

| Term | Meaning in this charter |
|------|-------------------------|
| **Phase 6 UX Systems** | Spec §37 Phase 6: **Form, Data, Search wrappers** — system-level contracts and documentation above frozen molecules, **not** V2 migration |
| **Wrapper** | Thin **system API / pattern / adapter** (e.g. documented Inertia form contract, list-stack pattern). **Not** permission to add canonical `Form`, `DataTable`, or `FilterBar` molecules unless a **separate adoption authorization** explicitly says so |
| **PR-6A** | V2 consumer adoption (replace Admin/ui, Public FormField, SearchBar, etc.) — **separate product gate**, not Phase 6 |
| **Phase 5 shell implementation** | Layout renames, `*Shell` modules, nav refactor — **deferred** per Phase 5 closure §1.3 |

---

## 1. Phase 6 objectives and specification references

### 1.1 Objective (Corporate Library §37 Phase 6)

Establish **first-class UX/Product Systems** for **Form**, **Data**, and **Search** that:

- Sit **above** frozen atoms/molecules and **below** features/pages (spec §15).
- Define **APIs, behavior, and composition rules** without owning routes or domain logic.
- Preserve **Laravel + Inertia** as authoritative for validation, authorization, filtering, sorting, pagination, and search results (spec §17, §18; Unified v1.1 §5).
- Keep V2 **working** after each increment (spec §37 rule).

### 1.2 What Phase 6 is not

| Out of scope for Phase 6 (unless separately authorized) |
|--------------------------------------------------------|
| Migrating V2 pages to DS molecules (**PR-6A**) |
| Creating or exporting canonical **`DataTable`**, **`FilterBar`**, **`Form`**, **`FormActions`**, **`SearchPanel`**, **`GlobalSearch`** components |
| **DataGrid v2** (column tooling, saved views, virtual scroll — spec §17 P2) |
| **FormContext** runtime with client schema libraries (no RHF/Formik in V2 today — PR-5Z) |
| Global search, typeahead, command palette (spec §18 P2; PR-5Y, PR-5L) |
| Phase **5** shell implementation (deferred) |
| Phase **9** registry / Storybook / DocumentationShell |
| Reopening PR-3–PR-5 or PR-5AA–PR-5AE freeze decisions |

### 1.3 Spec mapping (Form / Data / Search only)

| Spec section | Declared system responsibility | PR-5 architecture outcome |
|--------------|----------------------------------|---------------------------|
| **§16 Form System** | Conceptual `FormContext`; Inertia-first; server validation; optional multi-step (Stepper — **no DS Stepper**, PR-5E) | **Field/composition molecules sufficient** for presentation; **no** form-level DS component (PR-5Z) |
| **§17 Data System** | DataTable v1 extend; DataGrid v2 future; server-driven filter/sort/page | **No DS DataTable**; V2 `Admin/ui/DataTable` documented; **deferred to adoption PR** (PR-5M); filters via **FilterBar shell deferred** (PR-5O) |
| **§18 Search System** | Global P2; contextual admin search; FilterBar; saved searches P2 | **SearchField sufficient**; workflows are page + Inertia GET (PR-5Y) |

Phase 6 **narrows** spec §16–18 to **wrappers = documented contracts + optional non-breaking adapters**, reconciled with PR-5Z/M/O/Y — not a wholesale implementation of every bullet in the specification’s aspirational lists.

---

## 2. Current capability inventory (code + documentation)

### 2.1 Frozen canonical library (PR-5AE — do not extend without new PR)

**Atoms (29 exports)** and **molecules (28 exports)** per `Components/atoms/index.ts`, `Components/molecules/index.ts`, and PR-5AE §5.

**Form-related frozen molecules:** FormField, FieldGroup, FieldSet, InputGroup; all specialized field molecules (Input, Select, DateField, FileField, etc.).

**Search-related frozen molecule:** SearchField (PR-3J) — input chrome only; **no** query, debounce, or results.

**Data-related frozen molecules:** Pagination (PR-5C), EmptyState, Skeleton, Tabs, Breadcrumb; **not** table or filter shells.

### 2.2 V2 production (surface layer — adoption deferred)

| Capability | V2 location | Approx. adoption | Role |
|------------|-------------|------------------|------|
| Data entry forms | ~55 files using Inertia **`useForm`** | N/A — pattern, not component | State + submit + `form.errors` |
| Legacy labeled fields | `Components/Public/FormField.tsx` (~16 consumers) | PR-6A | Parallel to DS FormField |
| Admin field row | Label + Admin/ui Input/Select + inline errors | PR-6A | Parallel to FormField |
| Admin list table | `Components/Admin/ui/DataTable.tsx` (~29 JSX consumers) | **Explicit adoption PR** (PR-5M §18) | Column-def list renderer only |
| Admin filter shell | `Components/Admin/ui/FilterBar.tsx` (~28 consumers) | **Explicit adoption PR** (PR-5O §17) | Form grid container only |
| Admin search field | `Components/Admin/ui/SearchBar.tsx` (~24 admin pages) | PR-6A → SearchField | Uncontrolled `name="search"` |
| Public job discovery | `JobsSearchPanel` + JobCard list | Domain | Not FilterBar |
| Account employer filters | EmployerApplicant/Staffing/Partnership search sections | Domain | Controlled/local + Inertia |
| Pagination | `Components/Pagination.tsx` (~39 consumers) | PR-6A optional | Same contract as DS Pagination |
| List page title/actions | Inline `<h1>` + Button rows (PR-5P) | Page composition | No Toolbar molecule |
| Global search UI | Admin TopBar disabled placeholder | Product P2 | No handler |

### 2.3 Documented admin **list stack** (product pattern — not a DS organism)

Repeated composition (PR-5M, PR-5O, PR-5P):

```text
[Page title / optional primary action — page-local]
  → FilterBar (form submit → FormData → router.get)
  → DataTable (rows from server; optional onRowClick / selectedKey)
  → Pagination (sibling; paginator.links)
  → optional Drawer / workspace (page-owned)
```

Account/public lists often use **cards + Pagination**, not DataTable.

### 2.4 UI Lab

Form, SearchField, Pagination, EmptyState, Skeleton, and field molecules have lab sections. **No** DataTable, FilterBar, or Form System wrapper lab (PR-5M/PR-5O/PR-5Z).

---

## 3. Settled decisions that must remain closed

| ID | Decision | Source | Phase 6 must not… |
|----|----------|--------|-------------------|
| **FZ-Form-1** | No DS **Form**, FormLayout, FormActions, ValidationSummary, FormWizard, FormProvider | PR-5Z §1, §19 | Add form-level wrapper components “for parity” with spec diagram alone |
| **FZ-Form-2** | **Inertia `useForm`** remains page-owned state | PR-5Z §5, §14 | Introduce RHF/Formik/Zod without new product authorization |
| **FZ-Form-3** | Form-level error banner → compose **Alert**; filters ≠ Form organism | PR-5Z §7, §16 | Conflate FilterBar workflows with Form System |
| **FZ-Data-1** | **No DS DataTable** in architecture phase; V2 Admin/ui is product molecule | PR-5M §1, §7 | Ship `molecules/DataTable.tsx` under “Phase 6 Data wrapper” |
| **FZ-Data-2** | **No** sort UI, bulk selection, embedded pagination/filter inside table | PR-5M §5–§6, §18 | Extend DataTable into DataGrid-like framework |
| **FZ-Data-3** | **No DS FilterBar / FilterPanel**; V2 shell deferred | PR-5O §1, §16 | Ship `molecules/FilterBar.tsx` under Phase 6 without **adoption PR** |
| **FZ-Data-4** | Pagination **always composed outside** table | PR-5M §10 | Embed pagination in a “Data wrapper” |
| **FZ-Search-1** | **SearchField** is the only canonical search **input** | PR-5Y §1, §18 | Add SearchSuggestions, SearchResults, SearchPanel organism |
| **FZ-Search-2** | Server-side query via Inertia; no client full-text index | PR-5Y §15–§16 | Add debounced typeahead library without evidence |
| **FZ-Toolbar-1** | No Toolbar / ActionBar primitive | PR-5P | Fold toolbar into Phase 6 Data system as new component |
| **FZ-Freeze** | Atom/molecule contracts frozen | PR-5AE | Modify frozen molecules for “system convenience” |
| **FZ-Adoption** | V2 migration = **PR-6A+**, not Phase 6 | PR-5AE §7, PR-5Z §38 | Treat Phase 6 wrapper as migration batch |

---

## 4. Proposed scope and acceptance criteria (by workstream)

### 4.1 Form system

**Spec intent (§16):** Registration of field values/errors, Inertia/API modes, server validation display, optional dirty navigate warn (P2).

**What exists:** Frozen FormField family + Inertia `useForm` on pages; Admin/Public parallel field markup.

**What Phase 6 Form wrapper may add (if authorized):**

| Deliverable | Description |
|-------------|-------------|
| **F-1** | **Form System contract doc** (or spec appendix): map spec §16 fields to Inertia `useForm` shape (`data`, `errors`, `processing`, `setData`, submit handlers); field-level vs form-level errors; multipart `forceFormData` as page option |
| **F-2** | **Composition recipes** (markdown): auth forms, admin CRUD drawer, filter forms (cross-link Data/Search — filters **not** inside Form System) |
| **F-3** | **Optional thin adapter** (future code): e.g. `createInertiaFormBridge(useFormReturn)` exposing read-only **FormContext-like** surface for shared hooks/tests/mobile — **no** new UI components |

**Explicit non-goals:** FormLayout molecule, ValidationSummary, wizard, client schema layer, changes to frozen FormField API.

**Acceptance criteria (Form — documentation / adapter track):**

1. Contract doc published; every spec §16 requirement labeled **implemented today**, **compose-only**, **P2**, or **deferred**.
2. No new exports under `Components/molecules` or `Components/atoms` for form wrappers.
3. PR-5Z conclusions cited; no contradiction in governance docs.
4. If adapter code is ever added: zero V2 page imports until **PR-6A** or explicit pilot authorization.

---

### 4.2 Data system

**Spec intent (§17):** Table/grid behavior, server pagination, selection/export (future), loading/empty/error states; **DataGrid v2** explicitly future.

**What “Data wrappers” means for Phase 6 (charter interpretation):**

- **Document** the **List Pattern** and **state ownership** (server paginator, FilterBar submit, sibling Pagination, EmptyState composition).
- **Optionally** publish TypeScript **types** for shared paginator/filter props shapes (page ↔ controller contract) — types only, no UI.
- **Do not** canonicalize `Admin/ui/DataTable` or `FilterBar` under Phase 6 naming.

**Anti-accidental-authorization checklist (mandatory in any Phase 6 Data PR description):**

- [ ] PR title/body does **not** claim “Phase 6 implements DataTable/FilterBar”.
- [ ] No new `DataTable.tsx` / `FilterBar.tsx` in `Components/molecules` without **PR-5M/PR-5O adoption authorization** (separate from Phase 6).
- [ ] No DataGrid features (column visibility, virtual scroll, saved views).
- [ ] Pagination remains **outside** any table wrapper doc examples.

**Acceptance criteria (Data — documentation track):**

1. **List Pattern** documented with V2 references (admin stack vs account card list).
2. Loading/empty/error: reference **Skeleton**, **EmptyState**, **Alert** / flash (LoadingState **COMPOSE-ONLY** per loading review) — no TableSkeleton unless new evidence PR.
3. PR-5M and PR-5O deferrals and adoption boundaries (§18 / §17) attached verbatim by reference.
4. Spec §17 “Extend DataTable v1” items each mapped to **defer** (sort UI, row selection, bulk bar) until product + V2 evidence.

---

### 4.3 Search system

**Spec intent (§18):** Global search P2; contextual admin search; filters via FilterBar; saved searches P2; server ranking.

**What Phase 6 Search wrapper may add (if authorized):**

| Deliverable | Description |
|-------------|-------------|
| **S-1** | **Search System contract doc**: responsibilities of SearchField vs page vs backend; URL query persistence; FilterBar + SearchBar relationship |
| **S-2** | **Workflow matrix**: admin FilterBar GET; public JobsSearchPanel; account employer searches; saved searches (Candidates domain) |
| **S-3** | **Global search placeholder** (TopBar): document as **product P2** — requires product spec before any DS work |

**Explicit non-goals:** SearchSuggestions, SearchResults molecule, GlobalSearch component, debounce hooks in SearchField, command palette.

**Acceptance criteria (Search — documentation track):**

1. SearchField boundaries unchanged (frozen).
2. Clear line: **replacing SearchBar with SearchField = PR-6A**, not Phase 6 system delivery.
3. No new search npm dependencies without architecture review.

---

## 5. Workstream decisions (CREATE / COMPOSE-ONLY / DEFER / NEEDS PRODUCT DECISION)

| Workstream | Phase 6 charter decision | Rationale |
|------------|----------------------------|-----------|
| **Form — field presentation** | **COMPOSE-ONLY** (frozen molecules) | PR-5Z closed; sufficient for V2 |
| **Form — system wrapper (FormContext / adapter)** | **DEFER** (documentation-first); adapter **NEEDS PRODUCT DECISION** before code | No V2 need for FormProvider; spec §16 conceptual; API v1/mobile may later justify adapter |
| **Form — documentation / contract (F-1, F-2)** | **CREATE** (docs only) | Aligns §37 Phase 6 without code risk |
| **Data — List Pattern + server contract docs** | **CREATE** (docs only) | Fulfills “Data wrapper” without DataTable |
| **Data — DS DataTable molecule** | **DEFER** | PR-5M — **adoption PR**, not Phase 6 |
| **Data — DS FilterBar molecule** | **DEFER** | PR-5O — **adoption PR**, not Phase 6 |
| **Data — DataGrid v2** | **DEFER** (spec P2) | **NEEDS PRODUCT DECISION** on timing |
| **Data — sort / bulk / in-table loading** | **DEFER** | No V2 evidence (PR-5M) |
| **Search — SearchField** | **COMPOSE-ONLY** (frozen) | PR-5Y |
| **Search — workflow documentation (S-1, S-2)** | **CREATE** (docs only) | Phase 6 appropriate |
| **Search — global / typeahead / saved search platform** | **DEFER** | P2 / domain (PR-5Y) |
| **Search — Global search implementation** | **NEEDS PRODUCT DECISION** | TopBar placeholder only today |
| **Toolbar / page header for lists** | **COMPOSE-ONLY** (page + Cluster) | PR-5P — not Phase 6 deliverable |
| **LoadingState / ErrorState in systems** | **COMPOSE-ONLY** | Prior architecture review |

**Summary:** Phase 6 **implementation** authorized in this charter = **documentation CREATE only**. Any **code** for adapters or molecules requires **separate per-track authorization** below.

---

## 6. Recommended implementation order (with dependencies)

| Order | Track | Deliverable | Depends on | Notes |
|-------|-------|-------------|------------|-------|
| **6.0** | Governance | **This charter** approved by product + architecture | Phase 5 closed | Complete with this file |
| **6.1** | Cross-cutting | **Phase 6 boundary one-pager** (or §1–3 of this doc) linked from Phase 5 gate §7 | 6.0 | Prevents Phase 6 / PR-6A / adoption confusion |
| **6.2** | Search | Search System contract doc (**S-1**, **S-2**) | 6.0 | **Complete** — [OURNEAR_PHASE_6_SEARCH_SYSTEM_CONTRACT.md](./OURNEAR_PHASE_6_SEARCH_SYSTEM_CONTRACT.md) |
| **6.3** | Form | Form System contract doc (**F-1**, **F-2**) | 6.0 | **Complete** — [OURNEAR_PHASE_6_FORM_SYSTEM_CONTRACT.md](./OURNEAR_PHASE_6_FORM_SYSTEM_CONTRACT.md) |
| **6.4** | Data | List Pattern + server authority doc (**§4.2**) | 6.2, 6.3 | **Complete** — [OURNEAR_PHASE_6_DATA_LIST_PATTERN_CONTRACT.md](./OURNEAR_PHASE_6_DATA_LIST_PATTERN_CONTRACT.md) |
| **6.5** | Optional code | Inertia form bridge (**F-3**) | 6.3 + **product approval** | Only if API v1 or cross-app form API required |
| **6.6** | Adoption (not Phase 6) | DS DataTable / FilterBar + V2 import migration | **Separate PR-5M/5O adoption authorization** | **PR-6A** may overlap — sequence with product |
| **6.7** | Later | DataGrid, global search, FormContext full | Product roadmap P2 | Out of Phase 6 v1 |

**Rationale:** Search and Form docs reduce risk of mis-scoping Data work as “build DataTable.” Data pattern doc last among docs because it composes Form + Search workflows.

---

## 7. Explicit exclusions and cross-phase boundaries

| Boundary | Rule |
|----------|------|
| **Frozen atoms/molecules** | Phase 6 docs may **reference** exports; may **not** change APIs without PR-3–PR-5-style review |
| **Phase 6 vs PR-6A** | Phase 6 = **system contracts**; PR-6A = **replace V2 imports** (Public FormField, SearchBar, Pagination path, etc.). Phase 6 completion **does not** authorize PR-6A |
| **Phase 6 vs Phase 5 shells** | Shell contracts closed (Phase 5); shell **implementation** (rename, nav config) remains deferred |
| **Phase 6 vs Phase 9** | No Storybook, DocumentationShell, or registry host in Phase 6 |
| **Phase 6 vs spec §10 names** | Spec lists DataTable/FilterBar as “Component Library” — **PR-5M/O supersede** for Hire V2: those names are **adoption targets**, not Phase 6 deliverables |
| **Phase 6 wrapper ≠ organism** | Wrappers are **documentation + optional adapters**; organisms (full list stack) remain **page/feature** owned |
| **Backend** | Phase 6 frontend charter does not change Laravel controllers, query shapes, or permissions |

---

## 8. Readiness status and approvals required before implementation

### 8.1 Readiness (as of 2026-10-09)

| Gate | Status |
|------|--------|
| Phase 5 prerequisite | **Met** — [OURNEAR_PHASE_5_CLOSURE_GATE.md](./OURNEAR_PHASE_5_CLOSURE_GATE.md) |
| Phase 6 defined in spec §37 | **Yes** |
| Evidence reviews (Form/Data/Search) | **Complete** — PR-5Z, PR-5M, PR-5O, PR-5Y |
| Phase 6 charter document | **Complete** — this file |
| **Ready to begin Phase 6 implementation (any code)** | **No** |
| **Ready to begin Phase 6 documentation implementation** | **Yes**, after **§8.2 sign-off** |

### 8.2 Approvals required

| Approval | Owner | Required for |
|----------|-------|--------------|
| **Charter acceptance** | Product + architecture | Any Phase 6 doc or code work |
| **Documentation-only Phase 6 batch** | Product | §6.1–6.4 markdown deliverables |
| **Form adapter code (F-3)** | Product + architecture | Optional hook/bridge |
| **DataTable / FilterBar canonical molecules** | **Separate** from Phase 6 | PR-5M §18 / PR-5O §17 **adoption PR** |
| **PR-6A V2 adoption program** | Product | Consumer migration |
| **DataGrid v2 / global search** | Product | P2 features |

### 8.3 Suggested sign-off block (when product accepts charter)

| Role | Date | Outcome |
|------|------|---------|
| Product | _pending_ | Accept / amend charter; authorize doc-only Phase 6 batch (Y/N) |
| Architecture | _pending_ | Confirm boundaries §7; no DataTable/FilterBar via Phase 6 |

---

## 9. Bounded first implementation prompt

**Phase 6 code implementation is not authorized by this planning task.**

**Documentation-only batch (§6.2–6.4):** **Complete** (2026-10-09) — see contract paths in §6 table.

**Next bounded work (requires separate authorization):** Phase 6 **code** (F-3 adapter), PR-5M/PR-5O adoption molecules, or **PR-6A** V2 migration — **not** started from the doc batch.

**If product later authorizes adoption (not Phase 6):** use PR-5M §18 / PR-5O §17 checklists as the first implementation prompt — still **not** labeled Phase 6.

---

## 10. Verification (charter task)

| Check | Result |
|-------|--------|
| Application code modified | **No** |
| Canonical components / V2 consumers modified | **No** |
| PR-5Z / PR-5M / PR-5O / PR-5AE reopened | **No** |
| Phase 6 implementation started | **No** |
| PR-6A / Phase 9 started | **No** |

---

## Stop condition

**Phase 6 UX Systems architecture charter is complete.** Phase 6 **feature or molecule implementation is not authorized.** Await product/architecture charter acceptance and optional documentation-only authorization (§8.2, §9).
