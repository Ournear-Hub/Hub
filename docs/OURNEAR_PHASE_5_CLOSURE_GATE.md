# OurNear Corporate Library — Phase 5 closure gate (Product Shells)

**Date:** 2026-10-09  
**Repository:** `D:\Ournear Hub\Ournear Hire`  
**Purpose:** Determine what remains before **Corporate Library Specification Phase 5** (Product Shells) can be formally closed, and the smallest safe sequence to close it.

**Evidence baseline:** `docs/OURNEAR_PRODUCT_SHELL_ARCHITECTURE_REVIEW.md` (not re-audited in this gate).  
**Related:** `docs/OURNEAR_CORPORATE_ARCHITECTURE_CLOSURE_PR5AE.md`, `docs/OURNEAR_LOADING_ERROR_STATE_ARCHITECTURE_REVIEW.md`.

**Terminology:** **PR-5A–PR-5AE** = atom/molecule review series (complete). **Corporate Library Phase 5** = spec §37 “Product shells (contracts on existing layouts)” — **not** the PR-5 series.

**Phase 5 closure status (recorded):** **`CLOSE WITH EXPLICIT DEFERRALS`** — effective **2026-10-09** (§6.1 sign-off).

---

## 1. Executive decision (recorded)

| Question | Decision |
|----------|----------|
| Is Phase 5 **implementation** (renames, `*Shell` modules, layout moves) required for closure? | **No** — spec §37 and §723: closure = **documented contracts on existing layouts** (`OURNEAR_PRODUCT_SHELL_ARCHITECTURE_REVIEW.md` §1, §8). |
| Is Phase 5 **architecture/documentation** closed? | **Yes** — contracts published; gap disposition in §3; sign-off recorded §6.1. |
| **Final closure status** | **`CLOSE WITH EXPLICIT DEFERRALS`** — not unconditional `CLOSE` (§3.1 deferrals remain in force). |
| Phase 6 readiness | **Charter not authorized** — Phase 5 prerequisite met; Phase 6 UX Systems and **PR-6A** remain **separate gates** (§7). |

### 1.1 What Phase 5 closed means (and does not mean)

| In scope for Phase 5 closure | Explicitly **not** closed by Phase 5 |
|------------------------------|--------------------------------------|
| Shell **contracts** on V2 layouts (doc-only) | Layout renames, `*Shell.tsx` exports, file moves |
| Accepted **logical** shell mappings (§1.2) | Deferred spec shells (§1.3) |
| V2 behavior unchanged | Shell implementation, nav refactor PRs |
| | **Phase 6** UX Systems (Form/Data/Search wrappers) |
| | **PR-6A** V2 consumer adoption |

### 1.2 Accepted shell mappings (approved)

Evidence: `docs/OURNEAR_PRODUCT_SHELL_ARCHITECTURE_REVIEW.md` §2, §3.

| V2 implementation | Approved spec / logical shell | Notes |
|-------------------|------------------------------|--------|
| `Layouts/PublicLayout.tsx` | **PublicShell** | Public nav (`content/navigation.ts`), SEO/branding via shared props |
| `Layouts/AccountLayout.tsx` (wraps `PublicLayout`) | **CandidateShell** when `role === candidate` + `candidateNav` | Nested **PublicShell** chrome **accepted** (Q1) |
| Same layout | **EmployerShell** when employer account + `employerNav` / fallback nav | |
| Same layout | **RecruiterShell** (logical only) | Hiring workspace routes + grouped `employerNav` — **no separate layout file** (Q3) |
| `Layouts/AdminLayout.tsx` | **AdminShell** | `Sidebar.tsx` + permissions; `FlashMessage`; `adminAlerts` in TopBar |
| `Layouts/AdminAuthLayout.tsx` | **AuthShell** (admin staff auth) | Login, reset, admin password change |
| Account login/register pages on **PublicLayout** | **PublicShell + auth page template** | Not a dedicated AuthShell layout — **deferred** (Q2) |
| `Components/Profile/ProfileShell.tsx` | **ProfileShell** (composition region) | Not an Inertia root layout |

### 1.3 Explicit deferrals (must not be treated as implemented)

| Deferred item | Track |
|---------------|--------|
| Admin `Sidebar.tsx` → external nav config | Post–Phase 5 implementation PR |
| Unified Inertia `nav` prop (vs `candidateNav` / `employerNav`) | Adoption / Phase 6+ |
| `*Shell` re-exports, canonical shell folder, registry shell tier | Separate authorization; §723 |
| Dedicated **account AuthShell** layout | Optional UX PR |
| **SettingsShell** as distinct layout | Settings stay on AccountLayout + AccountNav |
| **CorporateWorkspaceShell** | Future product / P2 |
| **DocumentationShell** / Storybook host | **Phase 9** |
| Shell tier **freeze** as canonical library exports | PR-5AE “full unification” — not Phase 5 |
| UI Lab shell gallery | Optional — not required for §37 |

**Organism register cross-reference (2026-10-10):** **D-ORG-6 closed** in [OURNEAR_ORGANISM_TRACK_REGISTER.md](./OURNEAR_ORGANISM_TRACK_REGISTER.md) §24 — Spec §11 **Header/Footer**, **Sidebar/TopBar**, and **AccountNav** are **RESOLVED — PHASE 5 SHELL LAYER**, not Spec §37 Phase 3 **`Components/organisms/`** exports. This does **not** close **P5-16** implementation.

---

## 2. Requirement completion matrix

Legend: **Mandatory for Phase 5 closure** = required to satisfy spec §37 Phase 5 as **contracts on existing layouts**. **Later phase** = not blocking Phase 5 doc closure if deferred with evidence.

| ID | Requirement | Source | Status | Mandatory for Phase 5 closure? | Evidence / acceptance for closure |
|----|-------------|--------|--------|--------------------------------|-----------------------------------|
| P5-01 | **Product shell library defined** (names, nav ownership, responsive, permissions at architecture level) | Spec §14; Unified §9 | **COMPLETE** (in-scope subset) | **Yes** (documented subset) | §1.2 mappings + §1.3 deferrals; future shells not claimed implemented |
| P5-02 | **Formalize `PublicLayout` → PublicShell** (contract, not rename) | Spec §36, §37 | **COMPLETE** (architecture) | **Yes** | Product Shell Review §3.1 — props, nav, SEO, branding |
| P5-03 | **Formalize `AccountLayout` → Candidate/Employer/Recruiter logical shells** | Spec §14, §36 | **COMPLETE** (architecture) | **Yes** | Product Shell Review §3.2 — single layout, `candidateNav` / `employerNav` |
| P5-04 | **Formalize `AdminLayout` → AdminShell** | Spec §36, §37 | **COMPLETE** (architecture) | **Yes** | Product Shell Review §3.3 — Sidebar, permissions, flash, alerts |
| P5-05 | **Formalize `AdminAuthLayout` → AuthShell (staff auth)** | Spec §36 | **COMPLETE** (architecture) | **Yes** | Product Shell Review §3.4 |
| P5-06 | **AuthShell for account login/register** | Spec §14 AuthShell | **DEFERRED** (pattern documented) | **No** | **Accepted:** PublicShell + auth page template; not separate layout (sign-off Q2) |
| P5-07 | **ProfileShell preserved / contract** | Spec §36, §716 | **COMPLETE** (architecture) | **Yes** (for in-scope shells) | Product Shell Review §3.5 — region component, 4 consumers |
| P5-08 | **SettingsShell** as separate shell | Spec §14 | **DEFERRED** | **No** | Settings via AccountLayout + AccountNav — Product Shell Review §2 |
| P5-09 | **CorporateWorkspaceShell** | Spec §14; Unified §9 | **DEFERRED** | **No** | Not in repo — later phase / P2 product |
| P5-10 | **DocumentationShell** | Spec §14; §37 Phase 9 | **DEFERRED** | **No** | Phase 9; UI Lab uses AdminLayout — Product Shell Review §2 |
| P5-11 | **Shell context props** (`auth`, nav, breadcrumbs, flash, alerts, branding, seo) documented | Spec §14, §385 | **COMPLETE** (V2 contract) | **Yes** | Product Shell Review §5 — split keys **accepted as V2 contract** at sign-off |
| P5-12 | **Do not merge nav trees** | Spec §383 | **COMPLETE** | **Yes** | Product Shell Review §6 — public / account / admin separate |
| P5-13 | **No layout file moves / renames without authorization** | Spec §723 | **COMPLETE** | **Yes** | No moves in review; V2 unchanged |
| P5-14 | **Admin nav config split from `Sidebar.tsx`** | Spec §36 | **DEFERRED** | **No** | “Refactor later” — Product Shell Review §6, §7.3 Q4 |
| P5-15 | **Unified TypeScript `nav` shared prop** | Spec §385 ideal | **DEFERRED** | **No** | Adoption / Phase 6+ SDK; not §37 Phase 5 blocker |
| P5-16 | **Separate `*Shell.tsx` exports or registry entries** | Implied by some readers; **not** spec §37 Phase 5 wording | **PENDING** (implementation track) | **No** for architecture closure | Product Shell Review §1, §8 — separate authorization |
| P5-17 | **V2 behavior preserved** | Spec §37 rule; PR-5AE | **COMPLETE** | **Yes** | Reviews doc-only; no layout edits |
| P5-18 | **Organism tier / shell “freeze” in library governance** | PR-5AE §9 | **PENDING** | **No** for Phase 5; **Yes** for “full platform unification” | Shells documented, not frozen as canonical exports |
| P5-19 | **Phase 4 patterns/templates** | Spec §37 Phase 4 | **NOT APPLICABLE** | **No** | Precedes Phase 6 in sequence but not Phase 5 gate |
| P5-20 | **Phase 6 UX Systems (Form, Data, Search wrappers)** | Spec §37 Phase 6; §16–17 | **NOT APPLICABLE** | **No** | Explicitly later — PR-5AE §8 |

**Summary counts (architecture closure scope):**

| Status | Count (P5-01–P5-20) |
|--------|---------------------|
| COMPLETE | 10 |
| PARTIAL | 0 |
| PENDING | 2 |
| DEFERRED | 6 |
| NOT APPLICABLE | 2 |

---

## 3. Open questions — resolution (from Product Shell Review §7.3)

| # | Question | Resolution (approved 2026-10-09) | Category |
|---|----------|----------------------------------|----------|
| Q1 | Nested **PublicLayout** inside **AccountLayout** | **Approved:** V2 intentionally composes PublicShell + account chrome. | Bounded decision |
| Q2 | Account **AuthShell** parity vs admin | **Approved deferral:** PublicShell + auth page template (no dedicated account AuthShell layout). | Defer |
| Q3 | **RecruiterShell** vs shared **AccountLayout** | **Approved:** RecruiterShell = logical shell only (routes + `employerNav`). | Bounded decision |
| Q4 | **Admin nav** extraction from Sidebar | **Approved deferral** to post–Phase 5 implementation. | Defer |
| Q5 | Closure = docs only vs UI Lab/tests | **Approved:** contracts + gate + sign-off; UI Lab shell section optional. | Bounded decision |
| Q6 | **CorporateWorkspaceShell / DocumentationShell / SettingsShell** | **Deferred** — Phase 9 / future product (§1.3). | Defer |
| Q7 | Can Phase 5 close without implementation? | **Yes** — spec §37 = contracts on existing layouts; implementation separate (P5-16). | Documented decision |

### 3.1 Deferred items (explicit, not silent)

| Item | Deferred to | Rationale |
|------|-------------|-----------|
| Admin Sidebar → config module | Implementation phase / maintenance PR | Spec §36 |
| Unified `nav` Inertia prop | Adoption or UX Systems phase | V2 uses split keys |
| `*Shell` re-exports / canonical folder | Optional implementation; PR-6A+ if ever | §723 |
| Account AuthShell layout component | Optional UX parity PR | V2 works with PublicLayout |
| CorporateWorkspaceShell, DocumentationShell | Product P2 / Phase 9 | Not in codebase |
| SettingsShell as distinct layout | Optional; settings remain AccountNav | Spec naming vs V2 |

### 3.2 Blocking items (historical — resolved 2026-10-09)

| Blocker | Resolution |
|---------|------------|
| **B1 — Product/architecture sign-off** | **Cleared** — §6.1 |
| **B2 — Q1 acceptance** | **Cleared** — nested PublicLayout **approved** |

---

## 4. Minimum Phase 5 acceptance criteria (objective)

Phase 5 may be **formally closed** when **all** are satisfied:

1. **Spec alignment:** Closure record states Phase 5 deliverable = **contracts on existing layouts** (§37), not layout renames or duplicate shell components (§723).
2. **Published evidence:** `OURNEAR_PRODUCT_SHELL_ARCHITECTURE_REVIEW.md` remains the contract source of truth for Public, Account, Admin, AdminAuth, ProfileShell mappings.
3. **Gap disposition:** Every row in §2 with status PARTIAL/PENDING/DEFERRED has an **accept / defer / schedule** entry in §3 (no silent gaps).
4. **V2 preservation:** No Phase 5 closure batch required layout, nav, middleware, or route changes (verified by review stop conditions).
5. **Sign-off:** §6.1 completed by product + architecture (date + outcome).
6. **Explicit deferrals attached:** §3.1 list included in closure record (`CLOSE WITH EXPLICIT DEFERRALS`).
7. **Out of scope affirmed:** Phase 6 UX Systems, PR-6A, DataTable/FilterBar, Storybook — **not** part of Phase 5 closure.

**Not required for Phase 5 closure:**

- Renaming `*Layout.tsx` → `*Shell.tsx`
- New `resources/js/Components/shells/` package
- Admin nav file extraction
- UI Lab shell gallery (optional)

---

## 5. Remaining bounded tasks (dependency order)

| Step | Task | Type | Depends on | Authorization |
|------|------|------|------------|---------------|
| **1** | **Phase 5 closure sign-off** — approve §2 mapping, §3 deferrals, Q1/Q3/Q5 proposals | Governance (doc) | This gate doc | **Complete** — §6.1 |
| **2** | Update closure status in this file §6 → `CLOSE WITH EXPLICIT DEFERRALS` | Doc edit | Step 1 | **Complete** |
| **3** (optional) | Short **Phase 5 closure record** one-pager (or §6 amendment only) referencing sign-off date | Doc | Step 2 | **Not required** — §1 + §6 suffice |
| **4** (later, **not** Phase 5) | Admin nav config extraction spike | Implementation | Separate PR | Not authorized here |
| **5** (later) | Phase 6 UX Systems charter | Architecture | Phase 5 closed + product | Not authorized here |

**Smallest task to unlock closure:** **Step 1 only** (sign-off). No code.

---

## 6. Phase 5 closure decision (final)

| Decision | Status |
|----------|--------|
| **`CLOSE WITH EXPLICIT DEFERRALS`** | **Effective 2026-10-09** — mandatory §4 criteria satisfied; sign-off §6.1. |
| **`REMAIN OPEN`** | **Superseded** |
| **`CLOSE`** (unconditional) | **Not used** — §1.3 deferrals remain binding |

### 6.1 Sign-off record

| Role | Name | Date | Outcome |
|------|------|------|---------|
| Product | venkat | 2026-10-09 | **Approve** `CLOSE WITH EXPLICIT DEFERRALS`; **accept** nested PublicLayout (Q1); **accept** §1.3 deferrals and Q2–Q6 resolutions |
| Architecture | Corporate Library governance (authorized closure task) | 2026-10-09 | **Confirm** §2 matrix; Phase 5 = **contracts on existing layouts** only; V2 unchanged; Phase 6 / PR-6A **not** authorized by this sign-off |

**Authorization basis:** Product/architecture decision recorded via authorized task “Record Phase 5 Product Shell Sign-Off” (2026-10-09).

---

## 7. Phase 6 readiness status

| Criterion | Status | Evidence |
|-----------|--------|----------|
| **Phase 6 defined** | **Yes** (roadmap) | Spec §37 Phase 6: “UX systems (Form, Data, Search wrappers)” |
| **Phase 6 authorized** | **No** | No charter in repo; PR-5AE §8 UX wrappers “future” |
| **Phase 5 prerequisite** | **Met** | Phase 5 **`CLOSE WITH EXPLICIT DEFERRALS`** — §6.1 |
| **PR-6A / V2 adoption** | **Not Phase 6** | PR-5AE — adoption is separate product gate |
| **LoadingState/ErrorState** | **Closed (COMPOSE-ONLY)** | Loading/Error review — not Phase 6 blocker |
| **Recommended next step after Phase 5 close** | **Author Phase 6 charter** (read-only planning PR) | **Allowed as next planning task** — requires **separate authorization** to implement |

**Phase 6 readiness verdict:** **Not ready to begin implementation.** Phase 5 closure satisfied; Phase 6 **charter and authorization** still required. **PR-6A** remains a **separate** product gate (not Phase 6).

---

## 8. Verification

| Check | Result |
|-------|--------|
| Application code modified (sign-off task) | **No** |
| Layouts / nav / middleware modified | **No** |
| §4 mandatory acceptance criteria | **All met** — see §6.1 |
| Unmet criteria blocking closure | **None** |
| PR-5AE atom/molecule freeze preserved | **Yes** |
| Builds/tests run | **No** (doc-only) |

---

## 9. Stop condition

Corporate Library **Phase 5 (Product Shells)** is **closed with explicit deferrals** (§6).

- **Do not** interpret closure as permission to rename layouts, implement shell modules, or refactor admin nav without a **new implementation authorization**.
- **Do not** start **Phase 6 UX Systems implementation** or **PR-6A** from this sign-off alone.
- **Optional next planning (separate authorization):** Phase 6 UX Systems **charter** (read-only architecture).
