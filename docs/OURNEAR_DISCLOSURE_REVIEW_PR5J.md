# OURNEAR Disclosure / Collapsible review — PR-5J

**Status:** Review complete — **Disclosure NOT created** (architecture phase — **V2 not modified**)

## 1. Decision

**Disclosure was NOT created.**

No `Disclosure.tsx`, `Collapsible.tsx`, `Collapse.tsx`, UI Lab route, exports, or disclosure-specific tokens.

Expand/collapse in OurNear is already covered by:

1. **Frozen Accordion** (PR-3Z) — multi-item (or single-item) disclosure collections on native `<details>` / `<summary>`.
2. **Native HTML** — page-local `<details>` for responsive filter panels where product-specific CSS (`lg:open`, breakpoint visibility) is required.

## 2. Architectural classification

| Observed pattern | Classification |
|------------------|----------------|
| FAQ / multi-section expand lists | **Accordion (DS, frozen)** |
| Single expandable region (generic) | **Accordion `type="single"` with one `AccordionItem`**, or native `<details>` |
| Mobile job/applicant filter “Filters” block | **Page-local native `<details>`** — responsive + filter copy, not a shared V2 primitive |
| Jobs “More filters” | **Page-local native `<details>`** + Tailwind `lg:open` (desktop always expanded) |
| Admin sidebar section groups | **Navigation tree** — `aria-expanded` on section toggles, not content disclosure |
| Combobox / DropdownMenu / Popover / MultiSelect | **Other frozen molecules** — `aria-expanded` on triggers, not collapsible content regions |
| Read more / show more / expandable rows | **Not found** in V2 `resources/js` |
| V2 shared Collapsible/Disclosure component | **Does not exist** |

## 3. Evidence inspected (read-only)

| Search / source | Finding |
|---------------|---------|
| `Collapsible` in `resources/js` | **3 files** — all DS Accordion/lab/catalog references, **no V2 component** |
| `Disclosure` in `resources/js` | **3 files** — lab/catalog/classNames commentary only |
| `Collapse` / `Collapsible.tsx` | **0** in Ournear Hire app tree |
| `<details` | **5 files**: `Accordion.tsx` (DS), `JobsSearchPanel.tsx`, `EmployerApplicantSearch.tsx`, `EmployerPartnershipSearch.tsx`, `EmployerStaffingSearch.tsx` |
| `AccordionItem` / `AccordionTrigger` usage | **Only** `Accordion.tsx` + `AccordionLabSection.tsx` — **no V2 production consumers yet** |
| `aria-expanded` | **14 files** — Accordion, Sidebar, TopBar, Header, Combobox, DropdownMenu, Popover, MultiSelect, mobile nav, UI lab (not standalone disclosure primitive) |
| `show more` / `read more` / `show less` | **0** matches |
| `expandable` | **1 file** — Timeline lab boundary text only |
| `Admin/ui/*` | **No** Collapse/Disclosure helper alongside Modal/Drawer |
| PowerShell `Get-ChildItem -Recurse` + `Select-String` on `D:\Ournear Hub\Ournear Hire\resources\js` | Primary search method; ripgrep from workspace `References` unreliable |

## 4. Candidate patterns

### A. Already Accordion (frozen)

`Components/molecules/Accordion.tsx`:

- Native `<details>` / `<summary>` per item
- `type`: `single` \| `multiple`
- Controlled / uncontrolled open values
- `AccordionTrigger` sets `aria-expanded`, `aria-controls`
- Chevron + `accordion*` recipes

**Use for:** any reusable multi-section (or single-section) disclosure where a consistent DS visual is desired.

### B. Native details/summary (V2)

**Employer applicant/partnership/staffing search** (mobile only):

```tsx
<details className="rounded-md border border-line bg-surface/50 px-3 py-2">
  <summary className="cursor-pointer text-sm font-semibold text-ink">Filters…</summary>
  <div className="mt-3 pb-2">{filterFields}</div>
</details>
```

Desktop uses always-visible `md:grid` form — disclosure is **responsive product layout**, duplicated 3× with near-identical markup but **no shared import**.

**JobsSearchPanel:**

```tsx
<details className="group mt-3 lg:open">
  <summary className="… lg:hidden">More filters</summary>
  …
</details>
```

Uses **`lg:open`** so large viewports keep filters expanded without JS — **not** a generic molecule concern.

### C. Navigation expand (not Disclosure)

`Components/Admin/Sidebar.tsx` — section headers toggle nested links via local state + `aria-expanded`. This is **nav hierarchy**, not expandable content panels.

### D. Absent patterns

No V2 evidence for: expandable DataTable rows, expandable cards, controlled button+panel disclosure helper, animation framework, or “read more” truncation component.

## 5. Accordion comparison

| Need | Accordion sufficient? |
|------|---------------------|
| Multiple FAQ/settings sections | **Yes** — primary Accordion use case |
| One expandable block with DS styling | **Yes** — `type="single"` + single `AccordionItem` |
| Mobile filter block with page-specific borders/active counts | **Optional** — Accordion could style-align later; V2 today uses raw `<details>` |
| Sidebar nav sections | **No** — use nav semantics, not Accordion |

Creating **Disclosure** would largely duplicate Accordion’s item implementation (same native details, same trigger/content split) with a second API surface.

## 6. Native HTML comparison

Native `<details>` / `<summary>` is **already the architectural baseline** in both DS Accordion and V2 filter panels.

A thin `Disclosure` wrapper would add:

- Duplicate state/ARIA wiring Accordion already provides
- No V2 shared component to mirror (unlike Modal→Dialog, Drawer→Drawer)

**Recommendation:** Prefer native `<details>` for one-off responsive filters; prefer **Accordion** when adopting DS styling/controlled groups in future migrations.

## 7. Semantic contract (if Disclosure existed — rejected)

N/A — no molecule created.

## 8. Accessibility contract

V2 native details: browser-provided summary activation (Enter/Space on summary).

DS Accordion: explicit `aria-expanded`, `aria-controls`, `aria-disabled` on summary; content `id` pairing.

No evidence that a **second** wrapper improves on Accordion + native details for this codebase.

## 9. Keyboard contract

Native summary + Accordion summary behavior — no custom arrow-key navigation required.

## 10. Interaction contract

V2 filter disclosures: uncontrolled native toggle; desktop/mobile split via CSS display classes.

No stable V2 pattern for **controlled** single disclosure separate from Accordion’s controlled `value` API.

## 11. Visual contract

Filter `<details>`: page-local Tailwind (`rounded-md border bg-surface/50`).

Accordion: canonical `accordion*` recipes — **the** DS visual for disclosure collections.

No third stable visual system evidenced for Disclosure.

## 12. State ownership

Filter panels: caller owns filter state + Inertia routing; disclosure open state is **uncontrolled** (native).

Accordion: caller may control `value` / `onValueChange` when needed.

## 13. Final API

**Not created.**

Reference (frozen Accordion only):

```tsx
<Accordion type="single" collapsible defaultValue="section-a">
  <AccordionItem value="section-a">
    <AccordionTrigger>Title</AccordionTrigger>
    <AccordionContent>…</AccordionContent>
  </AccordionItem>
</Accordion>
```

## 14. UI Lab route

**Not created** (no `/molecules/disclosure`).

Existing: `/admin/design-system/molecules/accordion`.

## 15. Files created

- `docs/OURNEAR_DISCLOSURE_REVIEW_PR5J.md`

## 16. Files modified

**None**

## 17. Dependencies

**None**

## 18. TypeScript result

**Not run** — documentation-only PR; no implementation files.

## 19. Build result

**Not run** — documentation-only PR.

## 20. Deviations

N/A — no DS component.

## 21. V2 untouched confirmation

**Confirmed** — no V2 components, pages, routes, or callers modified.

## 22. Migration / adoption confirmation

**Not started.**

Future guidance (non-binding):

- Multi-section disclosure → `@/Components/molecules/Accordion`
- Single-section with DS chrome → one-item Accordion or native `<details>`
- Do **not** introduce parallel Disclosure API without new V2 evidence of a distinct contract

## Explicit non-goals (PR-5J)

- Duplicating Accordion as Disclosure/Collapsible
- Canonicalizing mobile filter `<details>` (product/responsive pattern)
- Canonicalizing admin sidebar section toggles
- Expandable table rows, read-more truncators, animation config APIs

## Relationship to frozen molecules (summary)

| Molecule | Role vs disclosure |
|----------|-------------------|
| **Accordion** | Canonical disclosure **collection** |
| **Tabs** | Peer sections with tablist — not expand/collapse |
| **Popover** | Floating contextual — not inline disclosure |
| **Dialog / Drawer** | Modal overlays — not inline disclosure |
| **Card** | Surface — not toggle behavior |
