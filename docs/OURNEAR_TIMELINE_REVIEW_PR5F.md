# OURNEAR Timeline review — PR-5F

**Status:** Implemented (architecture phase — **V2 not modified**)

## 1. Decision

**Timeline was created** as a canonical **molecule**.

Singular name: **`Timeline`** (`Components/molecules/Timeline.tsx`). V2 **`Components/Admin/ui/Timeline.tsx`** remains unchanged.

## 2. Architectural classification

**Molecule** — **display-only**, vertically ordered **chronological event history** (rail + marker + title + meta). Distinct from **ActivityFeed** (card stack), **DataTable** (tabular audit), and **Progress** (completion bar).

## 3. Evidence inspected (read-only)

| Source | Finding |
|--------|---------|
| `Components/Admin/ui/Timeline.tsx` | Shared admin component: `{ id, title, meta? }`, `ol` + left border + brand dot, empty message. |
| Admin consumers | **ApplicationDrawer** (screening + tab), **Submissions**, **Placements**, **Replacements**, **Employees**, **Finance** (entity + payment tabs) — **~10+** `<Timeline` usages; callers map domain activities to `items`. |
| `Components/Admin/ui/ActivityFeed.tsx` | **Different** visual: `ul` of surface **cards**, same loose `{ id, title, meta? }` — used in ApplicationDrawer, Candidates, Employers, EmployerEnquiries, MasterData. **Not merged** into Timeline. |
| Account show pages | **Inline** `ol` timelines on Applications/Show, Employer Applications/Show, Employer Submissions/Show — similar semantics (label + timestamp), **variant** styling (`border-l-2` per-item vs admin rail+dot). **Not migrated**. |
| Activity logs admin page | **DataTable** + drawer — tabular audit, not timeline molecule. |
| Search notes | PowerShell `Get-ChildItem -Recurse` + `Select-String` used; `**` globs alone can miss matches. |

## 4. Candidate patterns (classification)

| Pattern | Classification |
|---------|----------------|
| Admin **Timeline** | **TRUE timeline primitive** — repeated contract + visual |
| Admin **ActivityFeed** | **Activity card list** — related data, different presentation |
| Account inline **ol** | **Timeline-like** — page-local duplicate markup |
| **RecruitmentChain** | Domain **chain** (PR-5E), not event history |
| **ProfileCompletionCenter** | **Progress** + module checklist |
| Activity logs **DataTable** | **Table** / audit browser |

## 5. Repetition analysis

Admin workspaces consistently render **activity/history tabs** with the same Timeline component and mapping idiom (`title` + actor/date in `meta`). Sufficient repetition for a canonical molecule aligned with **Admin/ui/Timeline**, not a speculative API.

## 6. Semantic analysis

- Ordered list (`ol`) — sequence meaningful.
- Each item: **title** (event label) + optional **meta** (timestamp, actor, secondary line).
- **Display-only** — no step navigation, no expansion.

## 7. Accessibility analysis

- Native **`ol` / `li`** — no tablist/progressbar roles.
- Optional **`aria-label`** on the list for landmark naming.
- Rail **marker** decorative (`aria-hidden`).
- Callers should provide meaningful **title** text; `meta` may include formatted dates (string/ReactNode).

## 8. Interaction analysis

**Non-interactive.** No links/buttons inside the molecule. Callers compose navigation outside.

## 9. Visual analysis

Recipes match V2 admin Timeline: `border-l border-line`, `pl-4`, brand **dot**, `text-sm` title, `text-xs` muted meta, configurable **empty** copy.

## 10. State ownership

Caller supplies **`items`** and optional **`empty`**. No fetching, filtering, or pagination inside the component.

## 11. Relationship to Stepper (PR-5E)

**Stepper NOT created.** Timeline shows **past events**, not forward workflow stages.

## 12. Relationship to Progress

**Progress** = numeric completion. Timeline = discrete **events**, not a percentage bar.

## 13. Relationship to Tabs (PR-5D)

Tabs often **host** Timeline in a “Timeline” tab panel; Tabs switch sections, Timeline renders history inside.

## 14. Relationship to Accordion

Unrelated — disclosure vs chronological list.

## 15. Relationship to Card / List / Table

- **Card**: optional wrapper around Timeline at page level.
- **List**: ActivityFeed uses generic list cards — different molecule.
- **Table**: audit logs remain tabular.

## 16. Final API

```tsx
type TimelineItem = {
  id: string | number;
  title: string;
  meta?: ReactNode;
};

<Timeline
  items={TimelineItem[]}
  empty?: string          // default "No timeline events yet."
  aria-label?: string
  className?: string
/>
```

## 17. UI Lab route

`/admin/design-system/molecules/timeline`

## 18. Files created

- `resources/js/Components/molecules/Timeline.tsx`
- `resources/js/ui-lab/sections/TimelineLabSection.tsx`
- `docs/OURNEAR_TIMELINE_REVIEW_PR5F.md`

## 19. Files modified

- `resources/js/design-system/classNames.ts` — `timeline*` recipes
- `resources/js/Components/molecules/index.ts`
- `resources/js/ui-lab/catalog.ts`
- `resources/js/ui-lab/metadata.ts`
- `resources/js/Pages/Admin/DesignSystem/Show.tsx`
- `app/Http/Controllers/Admin/DesignSystemController.php`
- `routes/admin.php`

## 20. Dependencies

**None**

## 21. TypeScript result

Run in PR-5F completion (`npm run typecheck`).

## 22. Build result

Run in PR-5F completion (`npm run build`).

## 23. Deviations from V2 (molecule only)

- Optional **`aria-label`** on `ol`
- **`meta`** typed as `ReactNode` (V2 used string only)
- Marker explicitly **`aria-hidden`**

V2 **Admin/ui/Timeline** and **ActivityFeed** unchanged.

## 24. V2 untouched confirmation

**Confirmed** — no V2 components, pages, or callers modified.

## 25. Migration / adoption confirmation

**Not started.** Future adoption may import `@/Components/molecules/Timeline` in admin/account when visual alignment is intentional. **ActivityFeed** remains a separate future review if ever canonicalized.

## Explicit non-goals

- Merging ActivityFeed into Timeline
- Horizontal timeline, icons per event, expandable rows
- Inertia/API/workflow logic
- Replacing DataTable activity logs
- Migrating account inline timelines in PR-5F
