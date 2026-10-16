# OURNEAR Progressive loading review — PR-5Q (Load More / Infinite Scroll)

**Status:** Review complete — **no LoadMore / InfiniteScroll / ProgressiveList component created** (architecture phase — **V2 not modified**)

## 1. Decision

**PROGRESSIVE LOADING DOCUMENTED — NO COMPONENT**

No `LoadMore.tsx`, `InfiniteScroll.tsx`, progressive-list organism, UI Lab routes, hooks, or data-fetching utilities in this PR.

## 2. Primary architectural question (A–E)

| Option | Assessment |
|--------|------------|
| **A — LoadMore molecule** | **Not evidenced.** **0** V2 matches for LoadMore / “Load more” / `hasMore` / append-page patterns. |
| **B — InfiniteScroll molecule** | **Not evidenced.** **0** `IntersectionObserver`, scroll sentinels, or auto-fetch-on-scroll implementations for lists. |
| **C — ProgressiveList organism** | **Rejected.** No coordinated progressive-list system in V2. |
| **D — Product pattern** | **Pagination + Inertia** is the dominant list navigation pattern; dashboard **preview truncation** (`slice(0, n)`) + **“View all”** links — not append loading. |
| **E — No component (this PR)** | **Selected.** |

**Final classification:** **Server-side page navigation via frozen Pagination**; no distinct progressive-loading primitive in V2.

## 3. Evidence inspected (read-only)

**Search method:** PowerShell `Get-ChildItem -Recurse` + `Select-String` on `D:\Ournear Hub\Ournear Hire\resources\js`.

| Term | Count (app TS/TSX) |
|------|---------------------|
| `LoadMore`, `Load More`, `InfiniteScroll`, `Infinite Scroll` | **0** |
| `IntersectionObserver`, `sentinelRef`, `onIntersect`, `sentinel` | **0** |
| `hasMore`, `has_more`, `hasNextPage`, `loadNextPage`, `fetchNext`, `appendPage` | **0** |
| `next_cursor`, `previous_cursor`, `cursorPagination`, `per_page`, `pageSize` (list loading) | **0** meaningful frontend cursor-pagination API |
| `@tanstack`, `useInfiniteQuery`, `react-query` | **0** |
| `<Pagination` / `Pagination links=` | **~52–95** references (legacy + DS import paths) |
| `window.scroll` / scroll `addEventListener` for list fetch | **0** |

| Source | Finding |
|--------|---------|
| `Components/molecules/Pagination.tsx` | Frozen DS: Laravel **`links`** array, Inertia **Link** navigation, `aria-label="Pagination"`, prev/next aria labels. **Unchanged in PR-5Q.** |
| `Components/Pagination.tsx` | Legacy V2 paginator nav — same **links** model, used across admin/account/public. |
| `Pages/Jobs/Index.tsx` | Public job list: **scrollable column** of current page results + **Pagination** in list footer (desktop split view) and mobile stack — **page replacement**, not infinite append. |
| `Pages/Admin/*/Index.tsx` | **DataTable** + **`Pagination links={*.links}`** — standard admin indexes (PR-5M pattern). |
| `Pages/Admin/Hiring/CommandCenter.tsx`, `EmployerHiringCommandCenter.tsx` | **`activity.slice(0, 5)`**, **`recent_applicants.slice(0, 5)`** — dashboard **preview cap** with links to full modules — **not** Load More. |
| `Pages/Home.tsx`, `Companies/Show.tsx` | **“View all”** / see-more **navigation links** to paginated routes — not in-place append. |
| `Components/Admin/ui/DataTable.tsx` | `cursor-pointer` on rows — **CSS cursor**, not cursor pagination. |

## 4. Candidate patterns

| Pattern | Where | Reuse | State owner | Verdict |
|---------|--------|------:|-------------|---------|
| Laravel link pagination | Admin/account/public lists | **~50+** pages | Server paginator + Inertia | **Pagination (frozen)** |
| DS Pagination molecule | Design system + some pages | Growing | Caller `links` prop | **Frozen — canonical** |
| Scrollable page slice | `Pages/Jobs/Index.tsx` | 1 | Current page `jobs.data` only | **Layout scroll** — still paginated |
| Dashboard preview lists | Command centers, home teasers | Few | Static props + `slice` | **Preview trim** — link to full list |
| “View all” navigation | Home, hiring dashboards | Few | Inertia routes | **Navigation** — not LoadMore |

## 5. Pagination relationship

V2 list UX is overwhelmingly **(A) numbered / prev-next pagination** via Laravel’s **`links`** metadata:

- User navigates to **another page** (full or partial Inertia visit).
- **Current page rows replace** prior page data in props — **not** client-side append of prior pages.
- Frozen **`Pagination`** molecule documents this contract (`PaginationLink[]`, hide when `links.length <= 3`).

**Load More** and **Infinite Scroll** would imply **retained prior records + fetch next segment** — **not** the default V2 model.

Do **not** extend Pagination into infinite mode; do **not** alias LoadMore as Pagination.

## 6. Load More findings

- **No** repeated “Load more” button component.
- **No** `hasMore` / `loading` / `onLoadMore` shared pattern.
- **No** client append of next page into existing array in shared utilities.
- Occasional **“View all”** / module links go to **separate paginated pages**.

## 7. Infinite Scroll findings

- **No** `IntersectionObserver` usage in application TSX.
- **No** scroll sentinel elements triggering fetch.
- **No** automatic next-page fetch on scroll boundary.
- Scrollable regions (e.g. job list column) scroll **within the current page’s data** only; **Pagination** changes the page.

## 8. Cursor findings

- **No** frontend cursor pagination (`next_cursor` query params, cursor state objects, append-by-cursor).
- Matches on **`cursor`** in codebase are predominantly **`cursor-pointer`** styling or text-input caret — **not** pagination cursors.

## 9. Lazy-loading findings (out of scope vs in scope)

| Type | V2 | PR-5Q scope |
|------|-----|-------------|
| Image `loading="lazy"` | Not surveyed as primary evidence | **Excluded** |
| Route/code splitting | Not evidenced as list progressive loading | **Excluded** |
| **Data list progressive load** | **Not evidenced** | **This PR** |

## 10. State ownership (V2)

| Concern | Owner |
|---------|--------|
| Page number / URL query | **Server** (Laravel paginator) + **Inertia** navigation |
| List records for view | **Inertia props** (`data` array for current page) |
| Loading between pages | **Inertia** visit state (page-level; not a shared list loader component) |
| Empty list | **EmptyState** / page copy |
| Preview truncation | **Page** (`slice(0, n)` on props) |

A future **LoadMore** / **InfiniteScroll** molecule would accept **`loading`**, **`hasMore`**, **`onLoadMore`** from caller only — **no fetch inside DS** — but V2 provides **no** template to derive that API from today.

## 11. Accessibility findings

- **Pagination:** `nav` + `aria-label="Pagination"`; current page `aria-current="page"` (DS molecule); prev/next supplemental labels.
- **No** live-region “loading more” or “end of list” patterns for infinite scroll — **not applicable** (pattern absent).
- Load-more would likely be a **Button** with **`aria-busy`** when loading — hypothetical only.

## 12. Responsive findings

- Pagination appears on **mobile and desktop** list layouts (e.g. jobs list mobile stack + desktop split pane footer).
- **No** mobile-only infinite scroll or desktop-only load-more split.

## 13. Visual contract (pagination — frozen reference)

- DS: `paginationNav`, item base/current/disabled/link recipes in `classNames.ts`.
- Legacy: `flex flex-wrap gap-1`, brand active state.
- **No** load-more button placement or infinite-scroll spinner strip in V2.

## 14. Relationship to frozen components

| Component | Role |
|-----------|------|
| **Pagination** | Explicit **page** navigation — **primary V2 list navigation** |
| **Button** | Would compose LoadMore if ever needed |
| **Spinner / Skeleton** | Loading indicators — caller-owned for visits |
| **EmptyState** | Zero results on current query/page |
| **LoadMore / InfiniteScroll** | **Not created** — no V2 contract |

## 15. Why rejected

1. **Zero** shared LoadMore or InfiniteScroll implementation.
2. **Zero** intersection-based or scroll-fetch list loading.
3. **Zero** cursor-pagination frontend contract.
4. **Pagination + Inertia** already satisfies list navigation across admin, account, and public jobs.
5. Dashboard **`slice` + “View all”** is **navigation/preview**, not progressive data loading.
6. Introducing LoadMore/InfiniteScroll would be **speculative** and overlap Pagination’s documented role.

## 16. Future adoption boundary

Revisit **LoadMore** molecule only if V2 adds a **repeated** pattern with:

- explicit control to fetch **next segment** while **keeping** prior items visible,
- caller-owned **`hasMore`** + **`loading`** + handler,
- stable visual placement (e.g. centered button below list).

Revisit **InfiniteScroll** only if V2 adds **IntersectionObserver** (or equivalent) **list** loading with the same caller-owned fetch boundary.

Until then:

- Continue **Pagination** for page-based lists.
- Use **Button** + page logic for one-off domain buttons — do not premature canonicalize.
- **Do not** add infinite query hooks, cursor managers, or virtualization in the design system without V2 proof.

**UI Lab:** N/A unless a component is created in a future PR.

## 17. V2 safety

- V2 files **not modified**.
- Frozen **Pagination**, **Button**, **Spinner**, **Skeleton**, **EmptyState** **unchanged**.
- No backend, API, or dependency changes.

## 18. Stop condition

PR-5Q complete. Do not start PR-5R. Do not begin V2 progressive-loading migration.
