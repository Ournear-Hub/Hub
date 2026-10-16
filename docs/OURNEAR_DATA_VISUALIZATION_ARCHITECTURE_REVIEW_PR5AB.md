# OurNear Data Visualization & Charts Architecture Review (PR-5AB)

## Objective and scope

PR-5AB determines whether the frozen OurNear Design System needs reusable **chart or visualization** components (wrappers, legends, tooltips, containers, shared data contracts) or whether current **domain-owned reporting** and **existing primitives** are sufficient.

**In scope:** Read-only inspection of `package.json` / lockfile, Admin Analytics and dashboards, account/profile reporting UI, frozen DS atoms/molecules, UI Lab, and related PR-5 documentation (including PR-5AA data-display metrics).

**Out of scope:** V2 adoption or migration; refactors of Admin/Public/Account pages; speculative chart library installation; PR-6A; deployment configuration.

**Decision summary:** **No charting dependency and no new DS visualization component.** Hire V1 reporting is **scalar metrics, tables, and a small amount of inline CSS bar comparison**—not a multi-chart product surface. Use frozen **Progress** (completion), **EmptyState**, **Skeleton**, **Tooltip**, **Card**, **Text**, and **DataTable** (Admin surface) where applicable; keep comparative bars and analytics aggregation **domain-owned** until a future gate defines chart requirements and library policy.

---

## Files and patterns inspected

| Area | Paths / notes |
|------|----------------|
| Dependencies | `package.json`, `package-lock.json` (Hire root) |
| Admin reporting | `Pages/Admin/Dashboard.tsx`, `Pages/Admin/Analytics/Index.tsx`, `Pages/Admin/Hiring/CommandCenter.tsx` |
| Account / profile | `Components/Profile/ProfileCompletionCenter.tsx`, `Components/Profile/MissingInformationList.tsx`, `Components/Account/PasswordStrength.tsx`, `Pages/Account/Home.tsx` |
| Frozen DS | `Components/atoms/Progress.tsx`, `Components/molecules/EmptyState.tsx`, `Components/molecules/Tooltip.tsx`, `Components/atoms/Card.tsx` |
| UI Lab | `ui-lab/sections/ProgressLabSection.tsx`, `Pages/Admin/DesignSystem/Show.tsx` |
| Prior gate | `docs/OURNEAR_DATA_DISPLAY_METRICS_ARCHITECTURE_REVIEW_PR5AA.md` |
| Backend | Ripgrep on `app/` for chart/visualization strings — **no** chart rendering stack (only unrelated “graph” API config e.g. WhatsApp Graph) |

### Search methodology

Ripgrep across `resources/js` for: `chart`, `recharts`, `chart.js`, `victory`, `nivo`, `d3`, `visx`, `plotly`, `echarts`, `highcharts`, `sparkline`, `funnel`, `time-series`, `canvas` (data viz), and `graph` / `visualization` in TSX.

---

## Confirmed visualization libraries and dependencies

| Source | Result |
|--------|--------|
| `package.json` `dependencies` | `@inertiajs/react`, `@tabler/icons-react`, `react`, `react-dom` only |
| `package.json` `devDependencies` | Vite, Tailwind 4, TypeScript, Laravel Vite plugin — **no chart packages** |
| `package-lock.json` | No matches for common chart library names |

**Conclusion:** There is **no installed client-side charting library** in OurNear Hire. Tabler icon names include chart glyphs in the upstream icon package; **no chart icon-driven UI** was found as a visualization implementation.

---

## Evidence of existing implementations

### 1. Admin Analytics (`Pages/Admin/Analytics/Index.tsx`)

- **Primary modality:** Date-filtered **aggregates** rendered as:
  - KPI tiles via Admin `StatCard` / page-local `MetricCard`, `ConversionCard`, `TimeCard` (PR-5AA).
  - **Admin `DataTable`** for named count breakdowns (`NamedTable`).
  - **`RatingBars`** — page-local function: horizontal bars normalized to max count per star rating; numeric count always shown in text.
- **Explicit product copy:** Page states aggregates are date-bounded, not a single-cohort hiring funnel; sections omitted by permission.
- **No** line, area, pie, donut, sparkline, or interactive chart tooltips.

### 2. Admin Dashboard (`Pages/Admin/Dashboard.tsx`)

- **StatCard grids** for platform/recruitment counts.
- **Recruitment pipeline:** Inline `<ul>` with label, **numeric value**, and decorative bar (`style={{ width: \`${width}%\` }}`), bar track `aria-hidden="true"`.
- **Recent lists** as text rows, not charts.

### 3. Hiring command centers

- Admin and Account command centers use **counts and queues** (StatCard / UI Card), not charts.

### 4. Profile and account “meters” (not analytics charts)

| Implementation | Purpose | A11y notes |
|----------------|---------|------------|
| `ProfileCompletionCenter` | Profile % complete | Full layout: `role="progressbar"` + `aria-valuenow`; compact: bar `aria-hidden`, % in text |
| `MissingInformationList` | Completion width bar | Inline CSS bar |
| `PasswordStrength` | Client-side password scoring | Bar `aria-hidden`; checklist + text label |
| DS **`Progress`** atom | Canonical **completion** meter (`role="progressbar"`) | UI Lab documents distinction from Spinner/Skeleton |

### 5. Repeated inline comparative bar pattern

Two domain implementations share **visual vocabulary** ( `h-2`, `rounded-full`, `bg-surface` track, `bg-brand` fill, width from normalized value):

1. Dashboard recruitment pipeline stages.
2. Analytics `RatingBars`.

Both expose **primary data as text/numbers**; bars are supplementary and often `aria-hidden`.

**Not evidenced:** Additional bar/line/pie implementations, shared export, or stable cross-domain chart API.

---

## Candidate-by-candidate decisions

### Generic chart wrapper

| Criterion | Assessment |
|-----------|------------|
| Evidence | **None** — no chart library, no wrapper abstraction |
| **Decision** | **Do not create.** Adding a wrapper without a rendering engine would be hollow; adding a library is out of scope and unjustified by current UI. |

### Chart-specific legend or tooltip primitive

| Criterion | Assessment |
|-----------|------------|
| Evidence | No chart legends; DS **Tooltip** covers contextual help for controls, not data points |
| **Decision** | **Do not create.** Do not fork Tooltip for speculative chart hover. |

### Reusable chart container

| Criterion | Assessment |
|-----------|------------|
| Evidence | Analytics/Dashboard use **Card**-like bordered sections + `overflow-x-auto` where needed |
| **Decision** | **Do not create.** **Card** (atom) + page layout sufficient. |

### Visualization-specific empty/loading state

| Criterion | Assessment |
|-----------|------------|
| Evidence | Analytics uses **EmptyState** in `RatingBars` and tables; pages use text fallbacks |
| **Decision** | **Do not create.** **EmptyState** and **Skeleton** already cover reporting empty/loading at presentation layer. |

### Common visualization data contract (DS-wide)

| Criterion | Assessment |
|-----------|------------|
| Evidence | Metrics typed per page (`Metric`, `Conversion`, `DashboardPipelineStage`, etc.) from Laravel |
| **Decision** | **Do not create.** Aggregations, permissions, and period semantics belong in **domain/backend**; DS must not own analytics schema. |

### Optional: extract shared “comparative bar row” molecule

| Criterion | Assessment |
|-----------|------------|
| Evidence | **Two** page-local usages with different row shapes (pipeline stage vs star rating) |
| Stability | Bars are decorative; semantics differ; not a chart axis/legend model |
| **Decision** | **Do not create** in PR-5AB — insufficient repetition and no chart roadmap. Future adoption may align bars with **Progress** only where `value`/`max` semantics fit (completion), not normalized ranking bars. |

---

## Domain versus Design System boundaries

| Concern | Owner |
|---------|--------|
| SQL aggregation, period filters, permission-gated sections | Laravel controllers / services → Inertia props |
| KPI labels, hrefs, empty reasons, conversion formulas | Admin Analytics & dashboard pages |
| Comparative bar normalization (`max`, star distribution) | Page-local functions (`RatingBars`, dashboard IIFE) |
| Profile completion % | Account profile domain + optional DS **Progress** for accessible completion |
| Chart type selection, axes, time series, cohort analysis | **Not present** — would be a **product + dependency gate**, not DS-first |
| Presentation tokens (brand fill, surface track, typography) | Frozen tokens / **Progress** / **Text** |

DS components must **not** embed fetching, authorization, business KPI definitions, or chart library configuration.

---

## Accessibility and responsive requirements

**Observed patterns**

- **Good:** Numeric values displayed beside pipeline stages and rating rows; users not required to infer values from bar length alone.
- **Progress:** DS **Progress** exposes `role="progressbar"` and label text; preferred for **0–max completion** semantics.
- **Gaps (domain, not fixed in PR-5AB):** Some bars use `aria-hidden` without transferring value to a progressbar (compact profile, password strength, rating bars). Acceptable only if visible text is always present; rating bars should keep star label + count (current behavior).
- **Color:** Password strength adds textual checklist; bars alone are not sole indicator where checklist exists.
- **Responsive:** Dashboard pipeline section uses `overflow-x-auto` and `min-w-[280px]`; tables use Admin DataTable patterns.

**DS guidance for future chart work (documentation only):** Any future chart feature needs non-visual access (table alternate, summary text, or accessible chart library defaults), contrast-safe series colors, and no color-only encoding.

---

## Data and state ownership

- **Server-owned:** All Admin Analytics and dashboard numbers; date range via Inertia router GET.
- **Client-owned:** Analytics filter form local state (`from`/`to`); password strength scoring (client-only).
- **No client-side chart datasets** or streaming series in current codebase.

---

## Dependency and licensing considerations

- **Current bundle:** No chart library → no incremental chart licensing or bundle-size decision required.
- **If a future PR adds charting:** Requires explicit architectural gate (library choice, SSR/Inertia compatibility, a11y, license, bundle budget). **Not part of PR-5AB.**

---

## Component API and UI Lab

**Not applicable** — no new component.

Existing UI Lab **Progress** section remains the canonical demonstration for **completion meters**, explicitly distinct from charts and from Spinner/Skeleton.

---

## Files created and modified

| Action | Path |
|--------|------|
| Created | `docs/OURNEAR_DATA_VISUALIZATION_ARCHITECTURE_REVIEW_PR5AB.md` |
| Modified | *(none — application code unchanged)* |

---

## Verification

| Check | Result |
|-------|--------|
| TypeScript (`npm run typecheck`) | **Not run** — documentation-only |
| Production build (`npm run build`) | **Not run** — documentation-only |

---

## V2 isolation and migration status

| Item | Status |
|------|--------|
| V2 inspection | Read-only (e.g. Progress Lab references V2 profile vocabulary; V2 files not modified) |
| V2 modification | **None** |
| V2 adoption / migration | **Not started** |

---

## Relationship to PR-5AA

PR-5AA concluded **no DS metric/stat molecule**; reporting stays StatCard/KPI grids and tables. PR-5AB extends that: **no chart layer** exists to wrap; inline bars are **domain presentation**, not evidence for a corporate chart component.

---

## Stop condition

PR-5AB complete. **Do not start PR-6A.** **Do not begin V2 adoption or migration.**
