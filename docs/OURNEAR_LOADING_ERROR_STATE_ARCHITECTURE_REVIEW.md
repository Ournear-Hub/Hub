# OURNEAR LoadingState / ErrorState architecture review

**Status:** Review complete — **no canonical LoadingState or ErrorState molecules created** (architecture phase — **V2 not modified**)

**Date:** 2026-10-09  
**Repository:** `D:\Ournear Hub\Ournear Hire`  
**Authorization:** Post–PR-5AE Phase 3 read-only architecture review (this document only). No separate PR number assigned.

---

## 1. Executive recommendation

| Molecule | Decision | Summary |
|----------|----------|---------|
| **LoadingState** | **COMPOSE-ONLY** | V2 `LoadingState` has **zero** production consumers. Loading in Hire is dominated by **Inertia `processing` / `disabled` on actions** (~50 files) and the frozen **Spinner** atom on **Button** loading. Section-level loading should be composed from **Spinner** (+ optional **Text**) or **Skeleton** for layout placeholders—not a dedicated molecule today. |
| **ErrorState** | **COMPOSE-ONLY** | V2 `ErrorState` has **zero** production consumers. Operation/section errors use **V2 `UI/Alert`** directly (5 pages/components) or field-level errors (PR-5Z). The frozen **Alert** molecule (`variant="error"`) plus caller-owned **action** nodes is sufficient; a wrapper adds no stable contract beyond what V2 already inlined. |

**Neither molecule warrants `CREATE` at this time.** Evidence does not show a repeated, shared API across pages comparable to **EmptyState** (PR-4F, 7+ V2 import paths and 14+ `<EmptyState` JSX sites).

---

## 2. Evidence table

**Search method:** PowerShell `Get-ChildItem -Recurse` over `resources/js/**/*.tsx`, excluding `node_modules`. Counts = **unique `.tsx` files** unless noted.

| Artifact | Files mentioning / importing | JSX consumers | Representative pattern |
|----------|------------------------------|---------------|-------------------------|
| **`Components/UI/LoadingState.tsx`** | **2** (`LoadingState.tsx`, `Components/UI/index.ts` barrel export) | **0** | N/A — unused export |
| **`Components/UI/ErrorState.tsx`** | **2** (`ErrorState.tsx`, `Components/UI/index.ts`) | **0** | N/A — unused export |
| **`SpinnerIcon` (legacy)** | **4** (`LoadingState.tsx`, `icons.tsx`, `atoms/Spinner.tsx`, `icons/OurNearIcon.tsx`) | **1** effective UI use via unused `LoadingState` | Legacy SVG spinner; canonical **Spinner** uses **OurNearIcon** |
| **`form.processing` / `disabled={…processing}`** | **54** / **50** | Widespread | Submit buttons disabled while Inertia form posts |
| **`Button` `loading={…}`** | **4** (incl. atom + Image + Apply + Resumes) | Action-level busy | Canonical Button embeds spinner |
| **`<Skeleton` JSX** | **1** | UI Lab demo only (`ui-lab/sections/SkeletonLabSection.tsx`) | Layout loading primitive exists but not adopted in product pages |
| **`@/Components/UI/Alert` imports** | **6** | Inline error/info banners | Apply, JobDetail, ResumeReadinessPanel, ApplicationConfirmation, ProfileModuleContinueBar; **ErrorState** imports Alert internally only |
| **`@/Components/molecules` Alert** | **0** in Pages/Components (excl. lab) | DS Alert not on production pages yet | Adoption deferred (PR-5AE) |
| **`UI/EmptyState` imports** | **7** | Mixed with DS `<EmptyState` on some pages | Contrast: empty state *did* justify canonical molecule (PR-4F) |
| **`flash.error` rendering** | **2** | Admin **FlashMessage**, one employer jobs page | Session flash, not ErrorState component |
| **`role="alert"`** | **7** | Mostly **FormField** / OTP / Apply validation | Field-level, not section ErrorState |
| **Retry / “Try again” copy** | **0** | — | No shared retry CTA pattern |
| **“Something went wrong”** | **0** outside `ErrorState.tsx` default title | — | Default string never used in product |

---

## 3. Existing V2 behavior and API contracts

### 3.1 `Components/UI/LoadingState.tsx`

```tsx
type Props = { label?: string; className?: string };
// default label: 'Loading…'
```

- Layout: `flex items-center justify-center gap-2 py-8`
- Icon: **`SpinnerIcon`** from `@/Components/UI/icons` (legacy SVG, `aria-hidden`)
- Label: `typography.secondary` on a `<span>`
- Container: `role="status"` **`aria-live="polite"`**

**Export:** re-exported from `Components/UI/index.ts` only. **No page or feature imports this module.**

### 3.2 `Components/UI/ErrorState.tsx`

```tsx
type Props = {
  title?: string;       // default: 'Something went wrong'
  message: string;
  action?: ReactNode;
  className?: string;
};
```

- Wraps **`Components/UI/Alert`** with `tone="error"` and optional `title`
- Optional **`action`** rendered in `mt-3` below Alert
- **No** retry prop, router integration, or live-region semantics beyond V2 Alert

**Export:** barrel only. **No production consumers.**

### 3.3 Dominant V2 loading patterns (not LoadingState)

| Pattern | Where | Contract |
|---------|--------|----------|
| **Inertia `useForm` + `processing`** | ~55 form files (PR-5Z) | Disable submit / show busy on primary **Button** |
| **Local `useState` + `router.post` `onFinish`** | e.g. `Pages/Jobs/Apply.tsx` | Ad hoc `submitting` flag on public apply flow |
| **Inertia visit / full navigation** | App-wide | No shared section `<LoadingState>`; no `Deferred` / `isLoading` usage found in TSX scan |
| **Drawer `loading` prop** | PR-5I | **0** JSX consumers — prop unused in V2 |

### 3.4 Dominant V2 error patterns (not ErrorState)

| Pattern | Where | Contract |
|---------|--------|----------|
| **V2 `UI/Alert`** | 5 direct importers (+ ErrorState wrapper) | `tone`, optional `title`, `role="status"` for **all** tones including error |
| **Field errors** | Forms (PR-5Z) | `form.errors`, FormField, `role="alert"` on fields |
| **Flash / session** | Admin FlashMessage, page banners (PR-5V) | Semantically Alert-equivalent transport |
| **Empty collections** | EmptyState / UI EmptyState | **Not** error — PR-4F §5 |

---

## 4. Comparison to frozen canonical library

| Concern | Frozen primitive | Relationship to LoadingState / ErrorState |
|---------|------------------|-------------------------------------------|
| **Empty vs loading vs error** | **EmptyState** (PR-4F §5) | Empty = successful zero data; loading = pending; error = failed operation |
| **Layout loading** | **Skeleton** atom (PR-4G) | Placeholder shapes while content pending—not a centered spinner row |
| **Indeterminate busy** | **Spinner** atom | `role="status"`, `aria-label` (default “Loading”); used on **Button** `loading` |
| **Inline / section errors** | **Alert** molecule (PR-4D) | `variant="error"` → **`role="alert"`**; info/success/warning → `role="status"` |
| **Actions** | **Button**, **LinkButton** | Retry/CTA supplied by caller—no DS action registry |
| **Typography** | **Text** atom / `typography.*` | Visible loading copy can use **Text** or secondary typography class |
| **Layout** | **Stack**, **Cluster** | Compose spinner + message + actions |

### 4.1 Recommended compositions (COMPOSE-ONLY)

**Section / panel loading (when needed on adoption):**

```tsx
// Pattern A — icon + visible label (replaces unused V2 LoadingState intent)
<Stack direction="row" align="center" gap="sm" className="justify-center py-8">
  <Spinner size="md" label="Loading" /> {/* aria-label on spinner */}
  <Text tone="secondary">Loading…</Text>  {/* optional visible redundancy — product choice */}
</Stack>

// Pattern B — layout-preserving (preferred when structure is known)
<Skeleton variant="rect" height={120} />
```

**Section / operation error (when needed on adoption):**

```tsx
<Stack gap="md">
  <Alert variant="error" title="Unable to save changes">
    {message}
  </Alert>
  {action /* e.g. <Button onClick={retry}>Try again</Button> */}
</Stack>
```

These patterns mirror V2 **ErrorState** structure without a wrapper component. Prefer **canonical Alert** over **UI/Alert** on adoption for correct **`role="alert"`** on errors (PR-4D §6 vs V2 Alert always `role="status"`).

---

## 5. Accessibility findings

### 5.1 LoadingState (V2)

| Finding | Assessment |
|---------|------------|
| `role="status"` + `aria-live="polite"` on wrapper | Reasonable for **non-blocking** section updates |
| Visible label in `<span>` + decorative **SpinnerIcon** | Text provides accessible name; icon hidden |
| **Redundant live region risk** if nested inside another polite region | Caller responsibility—document in composition guidance |
| vs **Spinner** atom | Spinner exposes **`aria-label`**; visible text optional—avoid duplicating the same string on both spinner label and adjacent text unless intentional |

**Requirement (evidence-based):** Section loading must expose a **programmatic name** (“Loading…”) via visible text and/or **`Spinner` `label`**. Do not rely on motion alone (PR-4G / Spinner atom).

### 5.2 ErrorState (V2)

| Finding | Assessment |
|---------|------------|
| Uses **UI/Alert** with `tone="error"` but **`role="status"`** always | **Weaker** than canonical **Alert** `variant="error"` → **`role="alert"`** (PR-4D §6) |
| Optional **action** below | Buttons/links must keep their own accessible names |
| No default live region beyond Alert | Appropriate for persistent section error |

**Requirement (evidence-based):** Section-level failures should use **`Alert variant="error"`** (canonical) for urgent feedback, not V2 Alert’s all-`status` roles. **Retry** is caller-owned—no DS router coupling.

### 5.3 Distinctions (PR-4F §5, spec §31)

| State | UI treatment | DS owner |
|-------|--------------|----------|
| **loading** | Spinner / Skeleton / Button loading | Atoms + page layout |
| **skeleton** | Skeleton variants | Atom |
| **empty** | EmptyState | Molecule (frozen) |
| **error** | Alert (+ optional action) | Molecule (frozen) |
| **not found** | Page/route template | Page — not EmptyState or ErrorState |

---

## 6. Proposed API

**Not applicable — `CREATE` not recommended.**

If product later mandates ergonomic wrappers after adoption, minimum evidence bar would be: **≥3 non-test production call sites** sharing the same props shape (mirroring PR-5M / PR-5O deferral rationale). Today: **0** call sites for either V2 component.

---

## 7. Rationale and trade-offs

### 7.1 Why not CREATE LoadingState?

- **Zero** V2 usage despite barrel export—spec §9 reference is aspirational, not proven product contract.
- Phase 2 plan estimated “~1 consumer”; repository state shows **none** (export-only).
- **Spinner** + **Skeleton** + **Button.loading** already partition indeterminate vs layout vs action loading (PR-4G, PR-5Q).
- A canonical molecule would largely duplicate **~15 lines** of V2 code and entrench legacy **SpinnerIcon** instead of **OurNearIcon** Spinner.

### 7.2 Why not CREATE ErrorState?

- **Zero** V2 usage; errors already expressed via **UI/Alert** or field errors.
- Wrapper adds **`action` spacing** only—trivial **Stack** composition on adoption.
- Canonical **Alert** already covers message + title + optional actions in **children** (PR-4D §11).

### 7.3 Why not DEFER?

- Evidence is sufficient to decide **COMPOSE-ONLY** now; deferral would imply missing product input. The open question is **not** “whether to build” but **how to document composition** for Phase 3/4 patterns and future PR-6A adoption—not a blocker to this decision.

### 7.4 Trade-offs of COMPOSE-ONLY

| Pros | Cons |
|------|------|
| No duplicate API surface | Slightly more boilerplate on adoption |
| Aligns with PR-5Z (no FormLayout molecule) | Spec §9 still lists molecules—**spec drift** (see §8) |
| Preserves PR-5AE freeze | UI Lab has no dedicated loading/error section until pattern doc or adoption |

---

## 8. Exclusions (unchanged boundaries)

- V2 file modifications, PR-6A adoption, DataTable/FilterBar, shells, ConfirmDialog export, organism chrome, Storybook/Phase 9
- Reopening PR-3–PR-5 / PR-5AA–PR-5AE frozen atoms/molecules
- Implementing LoadingState/ErrorState molecules in this review

---

## 9. Unresolved questions (non-blocking)

1. **Spec §9 vs implementation:** Library spec lists **LoadingState** and **ErrorState** as molecules; repository evidence supports **composition documentation** instead. Update spec/registry in a future governance pass—not required to execute COMPOSE-ONLY.
2. **UI Lab:** No requirement to add labs for compose-only patterns; optional Phase 4 pattern doc (List/Dashboard **empty/error** slots).
3. **Orphan V2 exports:** `LoadingState` / `ErrorState` remain in `Components/UI/index.ts` until an explicit adoption/cleanup PR—out of scope here.

---

## 10. Governance alignment

| Source | Relevance |
|--------|-----------|
| `OURNEAR_CORPORATE_LIBRARY_SPECIFICATION_v1.0.md` §9, §31 | Names molecules; state model includes **loading** / **error** |
| `OURNEAR_EMPTYSTATE_PR4F.md` §5 | State separation rules applied above |
| `OURNEAR_CORPORATE_ARCHITECTURE_CLOSURE_PR5AE.md` | Atoms/molecules frozen; components tier not fully built—compose-only fits Phase 3 without new exports |
| `OURNEAR_CONTROLLED_PHASE_2_IMPLEMENTATION_PLAN_v1.0.md` §4–§7 | Scheduled LoadingState/ErrorState for Phase 3+—**resolved as COMPOSE-ONLY**, not implementation |

---

## 11. Files touched in this review

| File | Action |
|------|--------|
| `docs/OURNEAR_LOADING_ERROR_STATE_ARCHITECTURE_REVIEW.md` | **Created** |
| Application code (`resources/js/**`) | **Not modified** |

---

## 12. Stop condition

LoadingState / ErrorState architecture review is **complete**.

- **Do not** implement canonical LoadingState or ErrorState molecules without a **separate explicit authorization** and new V2 evidence.
- **Do not** start PR-6A or V2 adoption from this review alone.
- **Do not** begin DataTable, FilterBar, shell formalization, or Phase 9 registry work as a follow-on to this document.

**Next Phase 3 architecture candidates (not authorized here):** component-tier governance doc, pattern/template documentation (Phase 4), or other deferred surfaces (DataTable/FilterBar) per PR-5M/PR-5O—each requires its own charter.
