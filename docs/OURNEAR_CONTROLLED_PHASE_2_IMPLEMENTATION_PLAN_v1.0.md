# OURNEAR CONTROLLED PHASE 2 IMPLEMENTATION PLAN v1.0

**Type:** Planning only (no code changes authorized by this document)  
**Project:** `D:\Ournear Hub\Ournear Hire`  
**Prerequisite:** [OURNEAR_PHASE_1_CLOSURE.md](./OURNEAR_PHASE_1_CLOSURE.md) — **PASS**

---

## 1. Phase 1 Closure

Phase 1 delivered governed foundation + first actions primitives.

| Package | Scope | Status |
|---------|--------|--------|
| PR-1 | `foundation/tokens.ts`, `icons/` + OurNearIcon, `@tabler/icons-react` | **PASS** |
| PR-2 | `Components/atoms/Button`, `IconButton`, V2 aliases, About proof import | **PASS** |

**Verified in repo:**

- Canonical token ownership documented and implemented (see [OURNEAR_FOUNDATION_PR1.md](./OURNEAR_FOUNDATION_PR1.md))
- Loading states on Button/IconButton use registry `spinner` via `OurNearIcon` (not a separate Spinner atom)
- No Laravel / routes / migrations / domain logic changed in Phase 1
- `npm run typecheck` and `npm run build` reported **PASS** after PR-2

**Intentionally not done in Phase 1:** typography atoms, Input unification, Badge unification, Avatar, Divider, Card, EmptyState, molecules, shells, data/search/form systems, page migration.

---

## 2. Phase 1 Final Gate

**Verdict: PASS**

**Follow-up (non-blocking):**

- Icon host for Storybook/docs shell — Phase 9
- Legacy `Components/UI/icons.tsx` — incremental migration only
- Windows path casing: use `@/Components/atoms` (capital `Components`) consistently

No manufactured blockers for Phase 2.

---

## 3. Phase 2 Objective

Expand the **OurNear Corporate UI library** with the next **canonical atoms** and minimal **compatibility aliases**, preserving all V2 behavior.

Phase 2 is **UI-library only** — not product redesign.

---

## 4. Current Primitive Inventory (post–Phase 1)

| Area | Existing location | Canonical? |
|------|-------------------|------------|
| Tokens | `app.css`, `foundation/tokens.ts`, `classNames.ts` | Yes (PR-1) |
| Icons | `icons/OurNearIcon`, legacy `UI/icons.tsx` | Partial |
| Button / IconButton | `Components/atoms/*` + aliases | Yes (PR-2) |
| Typography | `classNames.typography` + ad hoc Tailwind | No |
| Label | `Admin/ui/Label.tsx`, `labelBase` in classNames | No |
| Input | `Admin/ui/Input.tsx` + `adminControlClass`; public `controlBase` on raw `<input>` | No |
| Badge | `UI/StatusBadge` (tone), `Admin/ui/StatusBadge` (status string map) | No |
| Avatar | Inline in `Public/Header`, `AccountLayout`, etc. | No |
| Divider | Tailwind `border-t`, `divide-y` in pages | No |
| Spinner | Registry + `SpinnerIcon` legacy; Button loading uses OurNearIcon | Partial |
| Card | `UI/Card`, `Admin/ui/Card` | No (Phase 3+) |
| EmptyState | `UI/EmptyState`, `Admin/ui/EmptyState` | No (Phase 3+) |
| LoadingState / ErrorState | `UI/LoadingState`, `ErrorState` | Molecules — Phase 3+ |

---

## 5. Usage Evidence (repository counts, 2026-10-08)

Counts = **number of `.tsx` files** under `resources/js` matching pattern (`rg -l`), unless noted.

| Signal | Count | Notes |
|--------|------:|-------|
| Files referencing `StatusBadge` | **46** | Heavy admin lists + recruitment statuses |
| Files importing `@/Components/Admin/ui` (barrel) | **39** | Input, Label, Select, DataTable, etc. |
| Files referencing `EmptyState` | **16** | Duplicated UI + Admin implementations |
| Files referencing `SpinnerIcon` | **3** | Legacy; Button/IconButton use OurNearIcon `spinner` |
| Files using `controlBase` | **2** | Public control styling |
| Files referencing `Label` / Admin Label patterns | **12** | Admin forms + pages |
| Direct import `@/Components/UI/StatusBadge` | **1** | Most admin uses barrel |
| Direct import `@/Components/UI/Card` | **14** | Account/public cards |
| Files using `classNames.typography.*` | **4** | Low direct adoption; many pages use equivalent Tailwind |
| Direct import `@/Components/Admin/ui/Input` | **3** | More via barrel re-exports |
| `LoadingState` / `ErrorState` component files + consumers | **~1 each** | Thin usage today |

**Interpretation:** Highest leverage Phase 2 targets are **Badge (46)**, **Input/Label (admin kit 39+)**, then **Typography helpers**, then **Avatar** ( scattered inline). **Spinner** does not justify a separate atom yet.

---

## 6. Primitive Prioritization

| Primitive | Current implementations | Evidence | Reuse value | Migration risk | Priority |
|-----------|-------------------------|----------|-------------|----------------|----------|
| **Label** | `Admin/ui/Label`, `labelBase` | 12+ Label refs; admin forms | High — FormField precursor | Low | **P0** |
| **Input** | `Admin/ui/Input`, raw `<input>` + `controlBase` | 39 admin ui consumers; public forms ad hoc | High | Medium (picker behavior) | **P0** |
| **Badge** | UI tone badge + Admin status map | 46 StatusBadge refs | High | Medium (keep domain map) | **P0** |
| **Text** | Ad hoc + `typography.body/secondary` | 4 explicit typography imports; widespread Tailwind | Medium | Low | **P1** |
| **Heading** | Ad hoc + `typography.pageTitle` etc. | Same | Medium | Low | **P1** |
| **Avatar** | Inline img/initials in layouts | Layouts + Header | Medium | Low–medium | **P1** |
| **Spinner** | Registry + legacy SVG | 3 legacy files; loading via OurNearIcon | Low as atom | Low | **P2 / defer** |
| **Divider** | Utility classes only | Ubiquitous patterns, no component | Low | Very low | **P2** |

**Phase 2 selected set (implement):** Label, Input, Badge, Text, Heading  
**Phase 2 optional tail:** Avatar  
**Deferred past Phase 2:** Spinner atom, Divider atom, Card, EmptyState, molecules, systems

---

## 7. Dependency Order

```text
Phase 1 foundation (tokens, OurNearIcon, Button)
        ↓
Typography atoms (Text, Heading) — no deps on Input
        ↓
Label atom — uses typography tokens
        ↓
Input atom — uses labelBase/control tokens + focusRing (FormField later)
        ↓
Badge atom — uses statusTone tokens; Admin status map stays adapter layer
        ↓
Avatar atom (optional) — independent of Input
        ↓
(Spinner atom optional — only if product wants non-OurNearIcon export)
        ↓
Phase 3: FormField molecule, EmptyState, Card
```

**Input does not block Badge.** Typography and Label should land before or with Input in the same PR if Input proof includes labeled fields.

---

## 8. Spinner Decision

| Question | Answer |
|----------|--------|
| Current loading UI | `OurNearIcon name="spinner"` + legacy `SpinnerIcon` in `UI/icons.tsx` |
| Button/IconButton | Already use registry spinner (PR-2) |
| Dedicated `<Spinner />` atom? | **Not required for Phase 2** |
| Recommendation | **Keep registry-level spinner**; add thin `Spinner` re-export only if a non-Button consumer needs it (**P2**, optional PR-3C tail) |
| Migration risk | Low if re-export wraps `OurNearIcon` |

---

## 9. Typography Plan

### Text

- **Role:** Body and secondary copy; maps to `typography.body`, `typography.secondary`, `typography.caption`
- **API (draft):** `variant?: 'body' | 'secondary' | 'caption'`, `as?: 'p' | 'span' | 'div'`, `className?`
- **No new font sizes** — use existing `classNames.typography` keys only

### Heading

- **Role:** Semantic headings; maps to `display`, `pageTitle`, `sectionTitle`, `cardTitle`
- **API (draft):** `level: 1–4` or `variant` aligned to typography map; enforce single `h1` per page remains page responsibility
- **A11y:** Correct heading level prop required

### Label

- **Role:** Form labels; aligns with `labelBase`
- **API (draft):** `htmlFor`, `required?`, `children`
- **Compat:** `Admin/ui/Label.tsx` → re-export canonical with `surface` default `admin` if needed (styling identical)

---

## 10. Input Plan

### Current split

- **Admin:** `Input.tsx` + `adminControlClass`, date `showPicker()` helper
- **Public/account:** raw `<input>` + `controlBase` / inline classes (e.g. `Pages/Admin/Auth/Login.tsx`)

### Canonical contract (primitive only — not Form System)

- Native `<input>` wrapper with unified control classes from tokens/`classNames`
- **Props:** standard `InputHTMLAttributes` + `invalid?` (applies `controlError`), `surface?: 'public' | 'admin'`
- **Preserve:** admin date/time picker click behavior
- **Server errors:** remain parent/`useForm` — Input shows `invalid` when passed
- **Compat:** `Admin/ui/Input.tsx` re-exports; export `adminControlClass` as alias to shared control recipe

**No FormField molecule in Phase 2.**

---

## 11. Badge Plan

### Strategy

- **One visual Badge atom** (`tone` / `variant` using `statusTone`)
- **Admin recruitment statuses:** keep string→class map in **`Admin/ui/StatusBadge.tsx` as compatibility adapter** importing canonical Badge (domain labels stay server/UI copy, not new components like `JobBadge`)

### UI StatusBadge

- Today: `tone: StatusTone` — maps to canonical Badge

### Avoid

- CandidateBadge, EmployerBadge, etc.

---

## 12. Avatar Plan

- **Atom only:** image | initials fallback, sizes `sm | md | lg`, shape `circle | rounded` (employer vs candidate convention from layouts)
- **No** profile business logic in Avatar
- **Compat:** optional helper `getInitials(name)` in utils — not mixed into atom
- **Priority:** P1 optional in Phase 2 if PR capacity allows

---

## 13. Divider Plan

- **Recommendation:** **CSS utility / semantic `<hr>`** documented in foundation — not a React component unless repeated non-border patterns appear
- **Phase 2:** document pattern in foundation doc only, or minimal `Divider` atom (**P2 defer**)

---

## 14. Molecule Readiness (after Phase 2)

| Molecule | Requires | Phase |
|----------|----------|-------|
| FormField | Label + Input + error/helper | **Phase 3** |
| SearchField | Input + Button/IconButton | Phase 3 |
| PasswordField | Input | Phase 3 |
| FilterChip | Badge | Phase 3 |
| Pagination | — (exists) | Later standardize |
| EmptyState / LoadingState / ErrorState | Typography + Button | Phase 3 |
| Stat/Metric | Typography + Card (later) | Phase 4+ |
| UserMenu | Avatar + Dropdown | Phase 4+ |

**Do not implement molecules in Phase 2.**

---

## 15. Compatibility Strategy

For each Phase 2 primitive:

```text
Existing module (Admin/ui/*, UI/*, raw markup)
        ↓
Thin re-export or wrapper (default surface)
        ↓
Components/atoms/<Primitive>.tsx
```

- **No** removal of old import paths in Phase 2
- **No** mass codemod
- Admin status badge map **stays** in adapter file

---

## 16. Proof Surface

| PR | Recommended proof | Rationale |
|----|-------------------|-----------|
| Typography | **None required** (alias-only) OR add one `Text`/`Heading` on `Pages/Legal/PrivacyPolicy.tsx` hero subtext | Read-only legal page, no business logic |
| Input + Label | **`Pages/Contact.tsx`** — replace one labeled field with canonical Input+Label | Public, low-risk; demonstrates `controlBase` + errors |
| Badge | **Alias validation only** (no page required) OR single row on low-traffic admin index if needed | High admin coupling |
| Avatar | **Defer** or Header optional later phase | Layout touch = higher risk |

**Avoid:** ATS, applications, employer hiring, recruitment pipeline, account security/OTP.

---

## 17. Proposed PR Structure

**Recommended: 3–4 PRs** (Badge authorized as PR-3A ahead of typography)

### PR-3A — Badge atom — **DONE**

- Canonical `Badge` + tone-based `StatusBadge` in `Components/atoms/`
- Adapters: `UI/StatusBadge`, `Admin/ui/StatusBadge`, `Account/StatusBadge` delegate to Badge; admin/account maps preserved
- Proof: `ProfileCompletionCenter` → canonical `Badge`
- See [OURNEAR_BADGE_PR3A.md](./OURNEAR_BADGE_PR3A.md)

### PR-3B — Typography atoms (next, awaiting authorization)

- `Text`, `Heading`, `Label` canonical in `Components/atoms/`
- Alias `Admin/ui/Label.tsx`
- Docs update; optional PrivacyPolicy proof line

### PR-3C — Input atom

- Canonical `Input` (public + admin surfaces, picker behavior preserved)
- Alias `Admin/ui/Input.tsx`; document `controlBase` relationship
- Optional **Contact** proof field

**Optional PR-3D (only if approved):** Avatar atom + Header compat — **not** in initial Phase 2 authorization by default.

**Spinner atom:** **not** in Phase 2 PR list.

---

## 18. Test Strategy

| Check | Phase 2 |
|-------|---------|
| `npm run typecheck` | Required every PR |
| `npm run build` | Required every PR |
| PHPUnit full suite | **Not** required unless backend touched |
| Manual | Primary + invalid Input, Badge tones, admin status badge unchanged labels |
| a11y spot | Label/`htmlFor`, Input focus ring, Badge text contrast |
| Visual | Compare Contact/Proof before-after screenshots (informal) |
| Registry self-check | Run if icon-related (usually N/A in Phase 2) |

Introduce Vitest **only** if explicitly approved — not default for Phase 2.

---

## 19. Non-Scope

Explicitly **out of Phase 2:**

- Card, EmptyState consolidation
- DataTable, DataGrid, Search System, Form System (FormField)
- Product Shells, navigation, dashboards
- Feature/page migration beyond one proof field/page
- ATS, candidate, employer, recruitment, workflow, notifications, communication
- Laravel, API, database, auth

---

## 20. Risk Register

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| Admin Input picker regression | Medium | Medium | Preserve `showPicker` logic in canonical Input |
| Badge visual drift on 46 files | Medium | High | Adapter preserves class map; visual diff admin lists |
| Input public/admin style merge | Medium | Medium | `surface` prop + shared token recipes |
| Over-building Typography variants | Low | Low | Lock to existing `typography` map only |
| Premature FormField | Medium | Medium | Phase 2 gate excludes molecules |
| Scope creep to Card/EmptyState | Medium | High | Non-scope list + PR boundaries |
| a11y regression on Contact proof | Low | Medium | Keep labels and error text |

---

## 21. Phase 2 Acceptance Gate

Phase 2 complete when:

- [ ] Canonical Label, Input, Badge, Text, Heading exist under `Components/atoms/`
- [ ] V2 aliases preserve prior import paths and behavior
- [ ] Admin status badge map still works unchanged for consumers
- [ ] Optional proof surface(s) validated
- [ ] No backend/domain changes
- [ ] No mass migration
- [ ] `npm run typecheck` + `npm run build` pass
- [ ] `OURNEAR_FOUNDATION_PR1.md` or Phase 2 appendix updated
- [ ] Rollback = revert frontend-only commits

---

## 22. Recommended Implementation Order

1. Authorize Phase 2 and PR-3A scope  
2. PR-3A: Text, Heading, Label + Admin Label alias  
3. PR-3B: Input + Contact proof (optional)  
4. PR-3C: Badge + StatusBadge adapters  
5. Phase 2 closure doc  
6. Authorize Phase 3 (FormField, EmptyState, Card)

---

## 23. Final Recommendation

- **Phase 1 gate:** **PASS** — proceed to Phase 2 when authorized  
- **Phase 2 PR count:** **3** (optional 4th for Avatar only if explicitly scoped)  
- **Implement in Phase 2:** Label, Input, Badge, Text, Heading  
- **Defer:** Spinner atom, Divider atom, Avatar (default defer), Card, EmptyState, molecules, systems  
- **First Phase 2 PR:** **PR-3A (Typography + Label)** — lowest risk, unlocks Input proof  
- **Blocking conditions:** None — awaiting explicit **implementation authorization** for PR-3A  

---

*End of OURNEAR CONTROLLED PHASE 2 IMPLEMENTATION PLAN v1.0*
