# OURNEAR ActivityFeed review — PR-5G

**Status:** Review complete — **ActivityFeed NOT created**. **V2 not modified.**

## 1. Decision

**NOT CREATED** — no canonical `ActivityFeed` molecule, UI Lab section, exports, or activity-feed-specific recipes.

## 2. Architectural classification

No new component. V2 **`Components/Admin/ui/ActivityFeed.tsx`** remains the production implementation. Chronological/history presentation is governed by the frozen **Timeline** molecule (PR-5F) where a canonical primitive applies.

## 3. Evidence inspected (read-only)

| Source | Finding |
|--------|---------|
| `Components/Admin/ui/ActivityFeed.tsx` | **28 lines.** Props: `items: { id, title, meta? }[]`, `empty?: string`. Renders **`ul` > `li`** with `rounded-md bg-surface px-3 py-2`, title + optional meta. **No** avatar, actor slot, icon, link, or expandable row. |
| Consumers (**6** usages) | **ApplicationDrawer** (internal notes), **Candidates/Index** (activity tab), **Employers/Index** (activity tab), **EmployerEnquiries/Index** (internal notes), **MasterData/Index** (recent changes drawer). All import `@/Components/Admin/ui`. |
| Item mapping at call sites | Same idiom as Timeline: **`title`** = event/note/action label; **`meta`** = concatenated context (e.g. `author · timestamp`, activity actor line). Pre-shaped arrays from server (`activities`, `recent_changes`) or mapped notes. |
| Duplicate markup search | `rounded-md bg-surface px-3 py-2` pattern appears **only** in ActivityFeed.tsx (no scattered copy-paste of feed cards elsewhere in `resources/js`). |
| Account / public | **No** ActivityFeed component; notifications use separate pages/components (not audited as feed primitive). |
| `Components/molecules/Timeline.tsx` (frozen PR-5F) | Same item shape `{ id, title, meta? }`, same empty pattern, display-only. **Different visual:** `ol` + left rail + brand marker vs **card stack**. |
| Search method | `Get-ChildItem -Recurse` + `Select-String` on `D:\Ournear Hub\Ournear Hire\resources\js`. PowerShell `**` globs not used as sole evidence. |

## 4. Candidate patterns (classification)

| Pattern | Classification |
|---------|----------------|
| Admin **ActivityFeed** | Repeated **card-stack list** of title/meta records |
| Admin **Timeline** (V2 + DS) | Repeated **rail timeline** of title/meta records |
| **Notifications** (account/admin) | Separate product surfaces — not feed primitive |
| **Activity logs** (`ActivityLogs/Index`) | **DataTable** + drawer — tabular audit |
| **Toast** (DS molecule) | Ephemeral alerts — not activity history |
| **RelatedList** (admin drawers) | Navigable related rows with href — not ActivityFeed |

## 5. Repetition analysis

ActivityFeed **is** reused (one shared V2 component, six call sites). Repetition alone is **insufficient**: the **item contract and caller mapping** are the same as Timeline; only list element (`ul` vs `ol`) and surface styling differ.

## 6. Item-shape analysis

**Actual V2 fields (only):**

| Field | Role |
|-------|------|
| `id` | React key |
| `title` | Primary line (note body, activity label, change summary) |
| `meta` | Secondary line (author · time, actor · date, status · time) |

**Not present in V2 ActivityFeed:** `actor`, `avatar`, `timestamp` (separate), `icon`, `content` as rich nodes, links, badges inside items.

Speculative API shapes (actor/avatar/content nodes) are **not supported by evidence**.

## 7. Semantic analysis

- Native **`ul` / `li`** — unordered list in markup (chronology implied by caller order, not `ol`).
- **Display-only** stream of text lines in surface boxes.
- **Not** a notification system, **not** a live region, **not** `role="feed"` / `log` in V2.

## 8. Accessibility analysis

V2 uses plain lists/text — no special ARIA. Empty state is a **`p`**. No live-region or status semantics. Sufficient for static feed; no DS extension required for PR-5G.

## 9. Interaction analysis

**Non-interactive** list items. Navigation (e.g. RelatedList with `href`) uses **different** admin components. Caller owns forms beside feeds (notes submission in ApplicationDrawer / EmployerEnquiries).

## 10. Visual analysis

Stable **within V2 ActivityFeed**: `space-y-2`, surface card per row, `text-sm` title, `text-xs text-muted` meta. Distinct from Timeline **rail + marker** (PR-5F). Distinction is **presentational only**, not a separate content/semantic model.

## 11. State ownership

Caller supplies **`items`** and **`empty`**. No pagination, fetch, or filter inside V2 component — unchanged.

## 12. Relationship to Timeline (PR-5F, frozen)

| | Timeline (canonical) | V2 ActivityFeed |
|---|---------------------|-----------------|
| Item API | `{ id, title, meta? }` | **Identical** |
| Semantics | Ordered history (`ol`) | Card list (`ul`) |
| Visual | Rail + marker | Surface cards |
| Interaction | Display-only | Display-only |
| Rich actor/avatar | **No** | **No** |

Creating a second DS molecule would **duplicate the same conceptual UI** (activity/history lines) with only CSS variation. PR-5G criterion: *prefer NOT CREATING ActivityFeed* when contract is not materially distinct.

**Guidance (architecture only, not implemented):** Future adoption may either keep V2 ActivityFeed, or map the same `items` to **Timeline** when rail presentation is acceptable, or introduce a **`variant`** on Timeline in a **future** PR if product requires both looks under one API — **Timeline is frozen; no change in PR-5G**.

## 13. Relationship to Notification

Account/admin **Notifications** pages are separate list/table experiences. ActivityFeed is **not** an in-app notification center, badge stream, or Toast queue.

## 14. Relationship to Card

Each feed row uses **surface styling** similar to a compact card, but V2 does **not** compose the DS **Card** atom — inline Tailwind on `li`. A canonical ActivityFeed would largely reimplement a **list of mini-surfaces**, overlapping Timeline + surface tokens without new semantics.

## 15. Relationship to DataTable / Audit Log

**Activity logs** and similar admin indexes use **DataTable** + filters + pagination. ActivityFeed is for **short embedded streams** in drawers/tabs — different scale and pattern; not a table molecule substitute.

## 16. Final API

**N/A** — not created.

## 17. UI Lab route

**None**

## 18. Files created

- `docs/OURNEAR_ACTIVITY_FEED_REVIEW_PR5G.md`

## 19. Files modified

**None** (no molecule, lab, exports, or classNames).

## 20. Dependencies

**None**

## 21. TypeScript result

**Not run** — no TypeScript sources changed.

## 22. Build result

**Not run** — no bundler inputs changed.

## 23. Deviations

**None**

## 24. V2 untouched confirmation

**Confirmed** — `Components/Admin/ui/ActivityFeed.tsx` and all consumers unchanged.

## 25. Migration / adoption confirmation

**Not started.** V2 ActivityFeed remains in place. Canonical **Timeline** exists for rail-style history; **ActivityFeed** was not duplicated in the design system in PR-5G.

## Explicit non-goals

- Second molecule duplicating Timeline item API
- Merging ActivityFeed into Timeline (Timeline frozen)
- Notification/Toast/Audit/DataTable abstractions
- Avatar/actor-rich feed API without V2 evidence
- V2 migration or Admin/ui replacement
