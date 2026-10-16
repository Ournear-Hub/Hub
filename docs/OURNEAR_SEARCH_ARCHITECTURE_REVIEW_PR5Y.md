# OURNEAR Search architecture review — PR-5Y

**Status:** Review complete — **no search components beyond frozen SearchField** (architecture phase — **V2 not modified**)

## 1. Decision

**SEARCHFIELD SYSTEM SUFFICIENT — NO NEW COMPONENT**

Frozen **SearchField** (PR-3J) remains the canonical **search input** molecule. V2 search is overwhelmingly **server-side query/filter workflows** composed from **SearchField** (or adoption equivalents), **Select**, **Input**, **FilterBar**, **Pagination**, **EmptyState**, and **DataTable** / domain result cards — not a separate **SearchSuggestions**, **SearchResults**, **SearchPanel organism**, or **GlobalSearch** system.

## 2. Architectural classification

| Pattern | V2 evidence | DS mapping |
|---------|-------------|------------|
| **Search text input** | Admin **SearchBar**, public keyword fields | **SearchField** (icon + `type="search"`) or **Input** / **FormField** |
| **Filter form shell** | **FilterBar** (~27 admin pages) | Page shell (PR-5O) — not search molecule |
| **Apply → Inertia GET** | Admin lists, jobs index | **URL/query state** + server pagination |
| **Search + many Selects** | Users, Candidates, CRM, etc. | **FilterBar** composition |
| **Public job discovery** | **JobsSearchPanel** + split-pane results | **Domain** page composition |
| **Employer/account keyword filter** | Applicant/staffing search components | **Domain** forms (raw `input` + `router.get`) |
| **Saved filter presets** | Candidates **saved_searches** | **Domain** CRM feature — not DS |
| **Inline typeahead / suggestions** | **None** for query search | N/A — **Combobox** only for option pickers (unused in V2 pages) |
| **Global omnibar** | **TopBar** disabled `type="search"` | Placeholder only (PR-5L) — no command palette |
| **Live/debounced search** | **None** evidenced | N/A |

## 3. Evidence inspected (read-only)

**Root:** `D:\Ournear Hub\Ournear Hire\resources\js`, `package.json`.

**Reviewed:** `Components/molecules/SearchField.tsx`, `Components/Admin/ui/SearchBar.tsx`, `FilterBar.tsx`, `Public/JobsSearchPanel.tsx`, `Pages/Jobs/Index.tsx`, `Pages/Admin/Users/Index.tsx`, `Pages/Admin/Candidates/Index.tsx` (saved searches), `Account/Recruiter/EmployerApplicantSearch.tsx`, `Account/Staffing/EmployerStaffingSearch.tsx`, `Admin/TopBar.tsx`, `ui-lab/sections/SearchFieldLabSection.tsx`, `molecules/FormField.tsx`.

| Term | Hire app |
|------|----------|
| `SearchField` in Pages/Components (excl. molecule, FormField, UI Lab) | **0** |
| Admin pages using **SearchBar** | **~24** |
| Admin pages using **FilterBar** | **~27** |
| `SearchSuggestions`, `GlobalSearch`, `Typeahead`, `SearchResults` components | **0** |
| `debounce` / `useDebounce` for search | **0** |
| Search/autocomplete npm libraries | **0** |
| `CommandPalette` / `cmdk` | **0** (PR-5L) |
| **saved_searches** UI | **1** — Admin Candidates only |

## 4. SearchField findings

**Frozen contract (`Components/molecules/SearchField.tsx`):**

- Composes **Input** with **`type="search"`**, leading **OurNearIcon** (`search`), `pl-9` padding.
- Forwards Input props (`surface`, `error`, `aria-*`, controlled/uncontrolled).
- **Explicitly does not** own query state, debounce, requests, or results (comment + PR-3J intent).

**Consumers:**

| Consumer | Role |
|----------|------|
| **UI Lab** (`SearchFieldLabSection`) | Surfaces, disabled, FormField wrapper |
| **FormField** molecule | Optional labeled search control |
| **Design System Show** | Lab route |

**Production V2:** **No direct SearchField imports on pages.** Adoption gap: Admin **SearchBar** and public keyword fields could map to **SearchField** without new architecture.

## 5. Search form findings

**Dominant admin pattern:**

```text
FilterBar onSubmit → FormData → router.get('/admin/...', params)
```

- **SearchBar** field: `name="search"` (or page-specific), `defaultValue` from server `filters`, `aria-label` via placeholder.
- Submit via **Apply** button — **not** instant search on keystroke.

**Public jobs:**

- **JobsSearchPanel** form: keyword **FormField** (plain text input, not SearchField icon), location **select**, **Search** button → `router.get('/jobs', { keyword, ... })`.

**Account employer:**

- **EmployerApplicantSearch**, **EmployerStaffingSearch**, **EmployerPartnershipSearch**: controlled/local `q` + status **select**, submit → Inertia GET; mobile collapsible filter UI — **domain components**, not reusable SearchPanel.

## 6. Search + filter compositions

Repeated **FilterBar grid**:

- **SearchBar** + multiple **Select** / **Input** (dates, numeric) + **Apply** / **Reset**.
- Segment toggles (e.g. Users) sit **outside** FilterBar — **SegmentedControl**-like buttons but page-local (PR-3Y applies to frozen molecule, not these chips).

**Jobs index:** **JobsSearchPanel** (keyword + location + primary filters + expandable secondary filters) + result count + **EmptyState** + **JobCard** list / split pane + **Pagination**.

**Classification:** Page-level **filter composition** — do **not** canonicalize as **SearchPanel organism** (one-off layouts differ: admin 4-col grid vs public card form vs employer 2-field strip).

## 7. Suggestions / autocomplete findings

**No** inline search suggestion dropdown, recent-query popover, or typeahead result list tied to server search.

- **Combobox** / **MultiSelect** use `aria-autocomplete="list"` for **option selection** from a static list — **UI Lab only** in V2 pages (PR-5X).
- HTML **`autoComplete`** on unrelated fields (email, OTP) — not search suggestions.

**Do not** create **SearchSuggestions** or **Autocomplete** — no repeated V2 contract distinct from **SearchField** + **Combobox**.

## 8. Search results findings

Results are **domain lists**, not a generic **SearchResults** molecule:

| Surface | Results UI |
|---------|------------|
| Admin indexes | **DataTable** + **Pagination** + **EmptyState** |
| Public jobs | **JobCard** list, desktop split pane, **Pagination** |
| Job detail pane | **JobDetail** (selection, not search widget) |

Empty copy is page-owned (e.g. “No matching roles”, “Try a wider search”). **EmptyState** (frozen) covers zero-hit UX — no second results primitive.

## 9. Global search findings

**Admin TopBar:** `type="search"` input **disabled**, placeholder “Search candidates, jobs, employers”, title “Global search will be available in a later V1.1 phase” — **chrome placeholder only**, no handler, no results.

**No** global search provider, shortcut, or cross-entity result UI.

## 10. Command palette distinction

**PR-5L:** No CommandPalette / Cmd+K. PR-5Y inspection found **no contradictory evidence**. Disabled TopBar field is **not** a command launcher.

## 11. Loading / empty / error findings

- **Loading:** Standard **Inertia** navigation / page swap — no dedicated search-spinner component on input.
- **Empty:** **EmptyState** on jobs; admin tables show empty tables or **EmptyState** in drawers.
- **Error:** Form validation / flash — not search-specific.
- **Debounce / cancel:** **Not used** for search inputs (no `AbortController` on typeahead).

## 12. Accessibility findings

- **SearchBar:** `aria-label={placeholder}`; native `type="search"`.
- **SearchField:** Decorative search icon (`aria-hidden`); caller supplies **`aria-label`** / **FormField** label (UI Lab documents).
- **Jobs keyword:** **FormField** with visible label “Search jobs”.
- **Result regions:** Jobs split pane uses `aria-label="Job results"` on scroll container — page-local landmark, not DS search results.
- **No** `role="search"` landmark wrapper evidenced as repeated pattern.

## 13. Keyboard findings

- **Enter** submits filter forms (default form behavior).
- **No** arrow-key navigation through suggestion lists for search queries.
- **Escape** does not clear search fields globally (Combobox/DropdownMenu Escape is unrelated).

## 14. Responsive findings

- **Jobs:** Sticky filter bar on large screens; stacked cards on mobile; split pane desktop-only.
- **Employer applicant search:** Filter fields in responsive grid / collapsible sections.
- **FilterBar:** `md:grid-cols-4` wrapping — layout only.

No separate mobile **SearchPanel** component contract.

## 15. State ownership findings

| State | Owner |
|-------|--------|
| Filter field values (admin) | Mostly **uncontrolled** `defaultValue` from server `filters`; re-render on navigation |
| Employer `q` / status | **Local useState** synced on submit to URL |
| Query persistence | **URL query string** via Inertia `router.get` |
| Saved searches | **Server** records; Candidates page applies filter object on chip click |
| SearchField value (when used) | **Caller** controlled or uncontrolled |

**No** SearchContext, debounced client index, or client-side full-text engine.

## 16. Dependency findings

`package.json`: no Algolia, Fuse.js, downshift-for-search, react-instantsearch, etc.

## 17. Domain-vs-design-system classification

| Item | DS? |
|------|-----|
| Search input chrome | **SearchField** |
| Admin list filtering | **FilterBar** + **SearchBar**/**Select** (adopt SearchField) |
| Job discovery UX | **Domain** (JobsSearchPanel + JobCard layout) |
| Talent pool saved searches | **Domain** (recruiting workflow) |
| Applicant/staffing keyword forms | **Domain** |
| Global search (future) | Product decision — compose **SearchField** + page/route, not GlobalSearch molecule preemptively |

## 18. Relationship to frozen SearchField

- **Single** canonical search **input** — do not add SearchInput duplicate.
- V2 **SearchBar** = Admin **Input** `type="search"` without icon — adoption should prefer **SearchField** `surface="admin"` for visual parity with DS.
- SearchField must **not** grow to own debounce, API, or results (would blur into Combobox or domain page).

## 19. Relationship to Combobox and Select

- **Select:** categorical filters in same form as search text.
- **Combobox:** pick one option from a list with typing — use when admin needs searchable enum, **not** for full-page server search (PR-5X).
- Job keyword field is **free text** → **SearchField** / **Input**, not Combobox.

## 20. Candidate patterns

| V2 pattern | Recommendation |
|------------|----------------|
| Admin SearchBar in FilterBar | **SearchField** `surface="admin"` |
| Public job keyword FormField | **SearchField** or **FormField** + SearchField child |
| FilterBar + Apply | Keep page composition |
| DataTable results | Keep + **Pagination** |
| Saved searches (Candidates) | Stay domain |
| Disabled TopBar search | Future: wire to route or command product — out of DS until spec exists |

## 21. Why created / rejected

**Rejected — SearchSuggestions, SearchResults, SearchPanel organism, GlobalSearch, Typeahead, SearchProvider:**

- Zero inline suggestion UI; results are tables/cards; panels differ per product area.
- Would duplicate **SearchField**, **EmptyState**, **Pagination**, **Combobox** boundaries.

**Rejected — modifying SearchField:** frozen per stop conditions.

**Accepted — documentation only.**

## 22–28. Contracts (if created)

**N/A** — no new components.

## 29. UI Lab route (if created)

**N/A** — existing **`molecules-search-field`** unchanged.

## 30. Documentation path

`docs/OURNEAR_SEARCH_ARCHITECTURE_REVIEW_PR5Y.md`

## 31. Files created

- `D:\Ournear Hub\Ournear Hire\docs\OURNEAR_SEARCH_ARCHITECTURE_REVIEW_PR5Y.md`

## 32. Files modified

**None**

## 33. V2 inspection status

**Complete (read-only)**

## 34. V2 modification status

**None**

## 35. Dependency changes

**None**

## 36. TypeScript result

**Not run** (documentation-only)

## 37. Build result

**Not run** (documentation-only)

## 38. Deviations

None.

## 39. V2 migration / adoption status

**Not started.** Documented adoption only:

- Replace **SearchBar** / plain keyword inputs with **SearchField** where a search control is intended.
- Keep **FilterBar**, **JobsSearchPanel**, and employer search layouts as domain code.
- If global search ships, use **SearchField** + dedicated results **page** — not a new DS results molecule unless a **third** identical inline suggestion UX appears.

## 40. Stop condition

**PR-5Y complete. Stop. Do not start PR-5Z. Do not begin V2 adoption/migration.**
