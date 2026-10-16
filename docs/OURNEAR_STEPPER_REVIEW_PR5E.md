# OURNEAR Stepper review — PR-5E

**Status:** Review complete — **Stepper NOT created**. **V2 not modified.**

## 1. Decision

**NOT CREATED** — no canonical `Stepper` molecule, UI Lab section, exports, or className recipes.

## 2. Architectural classification

No new component. Sequential workflow UI in V2 is **domain-specific**, **page-local**, or better represented by existing frozen primitives (**Progress**, **Tabs**, **Timeline**, **Link** navigation).

## 3. Evidence inspected (read-only)

| Area | Files / patterns | Finding |
|------|------------------|---------|
| Named Stepper / wizard | `resources/js` grep | **No** `Stepper.tsx`, **no** `wizard`, **no** shared multi-step controller. |
| Admin shared | `Components/Admin/RecruitmentChain.tsx` | Vertical **ordered list** of recruitment records (label, value, optional Inertia `href`). **7** admin consumers (Employees, Finance, Messages, Notifications, Placements, Replacements, Submissions drawers/tabs). |
| Admin timeline | `Components/Admin/ui/Timeline.tsx` | Chronological **event list** (`border-l` + dots). Used in ApplicationDrawer and related workspaces. |
| Profile completion | `Components/Profile/ProfileCompletionCenter.tsx` | **`role="progressbar"`** + percent + module **checklist cards** + “Next step” callout. Buttons/links open modules — not step-indicator UI. |
| Resume readiness | `Components/Resume/ResumeReadinessPanel.tsx` | Missing-item **link list** + CTA — checklist, not stepper. |
| Hiring onboarding | `Components/Account/Hiring/HiringEntryOnboarding.tsx` | Stacked **cards**, verification **checklist**, single **form** — no step rail. |
| Job apply | `Pages/Jobs/Apply.tsx` | **SectionHeader** eyebrows `Step 1` / `Step 2` — linear **page sections**, no shared step component, no interactive step nav. |
| Marketing “steps” | `Pages/Platform/Hire.tsx`, `Staffing.tsx`, `TalentPool.tsx` | Static **3-column cards** (“How it works”) — content, not workflow control. |
| Application history | `Pages/Account/Applications/Show.tsx`, employer show pages | Inline **timeline** `<ol>` — event history, not workflow stages. |
| Registration / forms | `Pages/Account/Register.tsx`, freelancer forms | Single-page or long forms — **no** stepper UI. |
| ARIA | Repo search | **No** `aria-current="step"`, **no** `aria-posinset` / `aria-setsize` on step UIs. **`role="progressbar"`** on profile completion bar and canonical **Progress** atom — completion, not steps. |
| Frozen DS | Progress, Tabs, SegmentedControl, Breadcrumb, Pagination | Already cover progress %, in-page sections, mode toggles, hierarchy, and page links. |

## 4. Candidate V2 patterns (classification)

| Pattern | Classification | Why not Stepper |
|---------|----------------|-----------------|
| **RecruitmentChain** | Domain **chain-of-records** display (vertical ol, ↓ separators, optional links) | No `current` / `complete` / `upcoming` model; not interactive step navigation; data is backend-assembled recruitment graph, not wizard stages. |
| **Timeline** (admin + account) | **Timeline** — past events | Chronological history, not forward workflow control. |
| **ProfileCompletionCenter** | **Progress indicator** + **task list** | Bar + module cards; navigation via Link/button to profile sections — not a step rail. |
| **ResumeReadinessPanel** | **Checklist / gaps list** | Links to profile sections. |
| **HiringEntryOnboarding** | **Onboarding content** (cards + checklist) | No repeated step-indicator visual or API. |
| **Apply page “Step 1/2”** | **Copy / section labels** only | One scrollable page; no shared component; no step state machine in UI. |
| **Platform marketing grids** | **Static marketing layout** | Not application workflow. |
| **Admin Tabs** (frozen elsewhere) | **Tabs** — peer sections | Already canonical for drawer/page section switching (e.g. submission “chain” tab). |

## 5. Why Stepper is not justified

1. **Repetition:** There is **no** second implementation of a generic step-indicator / wizard rail. One domain component (**RecruitmentChain**) does not generalize to `status: complete | current | upcoming` without inventing semantics V2 does not use.

2. **Semantic stability:** V2 does not agree on a single ordered-step model (chain vs timeline vs progress % vs section eyebrows vs tabs).

3. **Interaction:** Nowhere is there **interactive step-to-step navigation** with a stable keyboard contract. Links go to **other routes/records**; Tabs switch **panels**; module cards open **profile areas**.

4. **Visual stability:** No shared horizontal/vertical stepper chrome (numbered circles, connectors, states) repeated across surfaces.

5. **Primitive overlap:** Creating Stepper would **duplicate or blur**:
   - **Progress** — completion ratio
   - **Tabs** — in-page section switching (PR-5D)
   - **Timeline** — history
   - **Link / LinkButton** — route navigation to next task
   - **RecruitmentChain** — should remain domain UI until a future migration phase, not a DS molecule clone

6. **Business logic boundary:** Real “steps” (screening stages, KYC gates, application pipeline) are **server-driven** and rendered as **status badges**, **timelines**, and **tabs** — not as a reusable presentational Stepper.

## 6. Final API

**N/A** — component not created.

## 7–11. Contracts

**N/A** — semantic, accessibility, keyboard, interaction, and visual contracts for Stepper were **not** established because the abstraction was rejected.

## 12. State ownership

V2 keeps ownership in pages/controllers (tab query params, `useState` tab in drawers, profile module builders, chain arrays from API). No DS change.

## 13. Relationship to Tabs (PR-5D, frozen)

**Tabs** = `tablist` / `tab` / `tabpanel` for peer sections (e.g. submission drawer including “chain” content). **Not** a sequential wizard. Stepper would not replace Tabs.

## 14. Relationship to SegmentedControl (frozen)

**SegmentedControl** = `radiogroup` for compact exclusive **mode/value** without panels. Unrelated to workflow stage rails.

## 15. Relationship to Progress (frozen atom)

**Progress** = determinate **`role="progressbar"`**. Profile completion uses this pattern (and ad-hoc bars). Step completion **percentages** belong here, not a Stepper molecule.

## 16. Relationship to Breadcrumb (frozen)

**Breadcrumb** = hierarchy landmark. **RecruitmentChain** is closer to a vertical **record trail** with optional links — still domain-specific, not a generic Stepper.

## 17. Relationship to Timeline

**Timeline** is the correct abstraction for **application/event history** on account show pages and admin drawers. Do not recast timelines as Steppers.

## 18. UI Lab route

**None** — Stepper not created.

## 19. Files created

- `docs/OURNEAR_STEPPER_REVIEW_PR5E.md` (this document)

## 20. Files modified

**None** (no `Stepper.tsx`, no exports, no classNames, no UI Lab wiring).

## 21. Dependencies

**None**

## 22. TypeScript result

**Not run** — no TypeScript sources changed. (Prior PR-5D build remains valid.)

## 23. Build result

**Not run** — no bundler inputs changed.

## 24. Deviations from V2

**None** — V2 untouched.

## 25. V2 modification confirmation

**Confirmed:** No V2 components, pages, routes, or workflows were modified.

## 26. Migration / adoption confirmation

**Confirmed:** Migration and adoption of a Stepper **not started**. Future work should pick the correct primitive per pattern (Progress, Tabs, Timeline, Link, or domain components like RecruitmentChain) rather than assuming a Stepper.

## Explicit non-goals (PR-5E)

- Generic Stepper molecule without V2 evidence
- Renaming RecruitmentChain to Stepper in the design system
- Tablist semantics for workflow (forbidden — Tabs PR-5D)
- Coupling Stepper to Inertia, routing, validation, or pipeline business rules
