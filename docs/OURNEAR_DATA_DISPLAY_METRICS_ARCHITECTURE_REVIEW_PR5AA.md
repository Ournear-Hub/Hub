# OurNear Data Display & Metrics Architecture Review (PR-5AA)

## Objective and scope

PR-5AA evaluates whether the frozen OurNear Design System (DS) needs new canonical **atoms or molecules** for data display and metrics: statistic/KPI tiles, label–value rows, description lists, compact record summaries, and trend/delta indicators.

**In scope:** Read-only inspection of Hire foundations (`resources/js/Components/atoms`, `molecules`, UI Lab catalog), Admin/Account/Public presentation patterns, and existing domain components (e.g. Admin `StatCard`).

**Out of scope:** V2 adoption or migration; changes to existing Admin, Public, Account, Employer, or Candidate pages; new dependencies; PR-6A; production/deployment configuration.

**Decision summary:** **No new DS component.** Existing **Card**, **Text**, **Heading**, **Label**, **Badge**, **StatusBadge**, **Chip**, **Stack**, **Cluster**, **Divider**, **Skeleton**, **Progress**, **EmptyState**, and **Timeline** are sufficient for presentation composition. Admin **`StatCard`** and Analytics page-local card helpers remain **domain-owned** until a future adoption PR explicitly promotes or replaces them.

---

## Files and patterns inspected

### Frozen DS exports

| Area | Path | Notes |
|------|------|--------|
| Atoms index | `resources/js/Components/atoms/index.ts` | Card, Text, Heading, Label, Badge, StatusBadge, Chip, Divider, Skeleton, Progress, Stack, Cluster, etc. |
| Molecules index | `resources/js/Components/molecules/index.ts` | EmptyState, Timeline, Alert, Tabs, Dialog, Drawer, … — **no** StatCard, MetricCard, DescriptionList, KeyValue |
| UI Lab catalog | `resources/js/ui-lab/catalog.ts` | Documents frozen atoms/molecules; **no** dedicated metrics/data-display molecule |
| UI Lab InfoPanel | `resources/js/ui-lab/components/InfoPanel.tsx` | Example `<dl>` metadata (lab-only) |

### Domain / surface components

| File | Role |
|------|------|
| `resources/js/Components/Admin/StatCard.tsx` | Admin KPI tile: label, value, optional href, hint, emptyHint |
| `resources/js/Pages/Admin/Dashboard.tsx` | Platform/recruitment stat grids via `StatCard` |
| `resources/js/Pages/Admin/Analytics/Index.tsx` | `MetricCard` → `StatCard`; local `ConversionCard`, `TimeCard`; large KPI grids |
| `resources/js/Pages/Admin/Hiring/CommandCenter.tsx` | `MetricGrid` → `StatCard` + `HiringMetricCard` type |
| `resources/js/Pages/Admin/EmployerEnquiries/Index.tsx` | Summary row of `StatCard` |
| `resources/js/Components/Account/Hiring/EmployerHiringCommandCenter.tsx` | **Public** `Components/UI/Card` + inline label/value counts (not `StatCard`) |
| `resources/js/Pages/Account/Home.tsx` | Dashboard sections; inline spans and cards, not shared metric primitive |
| `resources/js/lib/recorded.ts` | Domain copy helper for empty metadata (`Not recorded yet`) |
| `resources/js/Components/Admin/RecruitmentChain.tsx` | Step chain with label/value; domain workflow UI |

### Search targets (ripgrep on `resources/js`)

- **StatCard / MetricCard:** Only Admin paths above; Analytics `MetricCard` is **page-local**, not exported.
- **DescriptionList / KeyValue / PropertyList / SummaryPanel / RecordSummary:** **No** Hire implementations under those names.
- **`<dl` usage:** ~30 TSX files (drawers, show pages, JobDetail, Account settings, Finance index, UI Lab, resume, etc.) with **heterogeneous** layout (grid columns, spacing, mixed actions/links in values).
- **Trend/delta:** No shared “vs last period” or percentage-change indicator component; Analytics uses **period vs snapshot** copy in `hint` (`kindLabel`), not directional deltas.

### Surface stacks (architectural note)

- **DS atoms:** `Components/atoms/Card.tsx` — structural padding presets, no metric semantics.
- **Public UI:** `Components/UI/Card.tsx` — same density tokens, optional `as` prop; used on Account employer metrics.
- **Admin UI barrel:** `Components/Admin/ui` — re-exports DS-aligned pieces for Admin; **StatCard is separate**, not in DS index.

---

## Evidence from existing implementations

### Admin statistic tiles (`StatCard`)

```tsx
// Components/Admin/StatCard.tsx — presentation-only, Admin-scoped
// label (muted sm), value (2xl semibold), optional emptyHint, hint, optional Inertia Link wrapper
// Surface: rounded-xl border border-line bg-white p-4 sm:p-5 (mirrors Card atom visually)
```

**Consumers:** Dashboard, Analytics (direct + via `MetricCard`), Hiring Command Center, Employer Enquiries Index.

**Data ownership:** Server aggregates (`DashboardStats`, analytics `Metric`, `HiringMetricCard[]`, enquiry summaries). No fetching inside the component.

### Analytics extensions (page-owned)

- **`MetricCard`:** Maps analytics `Metric` (`value`, `kind`, `definition`) to `StatCard` with `hint={kindLabel(metric.kind)}`.
- **`ConversionCard`:** Rate %, formula, numerator/denominator labels — **different semantic contract** than `StatCard`.
- **`TimeCard`:** Average days + eligible count + empty reason — **again distinct**.

These variants share visual rhythm (bordered white tile, 2xl value) but **not** a single stable API across Account/Public/Admin.

### Account employer metrics (composition, different stack)

Employer Hiring Command Center uses **four** `Card density="compact"` tiles with `text-xs` label and `text-2xl` value — same *visual idea* as `StatCard`, different component import and spacing (`mt-1` vs `mt-2`, `text-xs` vs `text-sm` label).

### Label–value / metadata rows

- Widespread **semantic `<dl>`** / label span + value span patterns in show pages, drawers, and profile views.
- Empty-state copy often via **`recorded()`** or local `recordedValue()` — **domain**, not DS.
- Layout varies: 1-column stack, 2–4 column CSS grids, inline “Label: value” list items (Account Home resume block).

### Record summaries

Job cards, staffing cards, recruitment requirement cards, etc. are **entity-specific** compositions (title, status badges, actions). No evidence of a generic “record summary” primitive reused with identical props across domains.

---

## Candidate-by-candidate decisions

### 1. Metric / statistic display (StatCard, MetricCard, KPI grid)

| Criterion | Assessment |
|-----------|------------|
| Existing implementations | Admin `StatCard` (4 areas); Analytics wrappers; Account uses UI `Card` inline |
| Semantic purpose | Present a **scalar count or snapshot** with optional navigation |
| Reuse & stability | **Unstable across surfaces:** Admin vs Account import different Card stacks; Analytics adds conversion/time shapes |
| Frozen DS relationship | Fully composable: `Card` + `Text`/`Heading` + optional DS `Link`/`LinkButton` |
| State / data ownership | Page/controller; correct |
| A11y / responsive | `StatCard` uses `<article>` or link; grids use responsive `grid-cols-*`; no gap requiring new primitive |
| Composition sufficient? | **Yes** for DS; Admin `StatCard` is optional consolidation **within Admin surface**, not DS canon |
| **Decision** | **Do not add** DS `MetricCard` / `StatCard`. Promoting Admin `StatCard` without migration is duplicate surface API. Future adoption PR may align Admin tile to `Card`+typography tokens or move a renamed molecule **with** consumer migration. |

### 2. Label–value / description list (KeyValue, DescriptionList, PropertyList)

| Criterion | Assessment |
|-----------|------------|
| Existing implementations | ~30 files with `<dl>` or span pairs; no shared component |
| Semantic purpose | Read-only field metadata |
| Reuse & stability | **Low:** column counts, dd content (badges, links, buttons), and density differ per screen |
| Frozen DS relationship | `Label` + `Text` + `Stack`/`Cluster` + native `<dl>` |
| A11y | Native `<dt>`/`<dd>` associations preferred; DS should not fight semantic HTML |
| Composition sufficient? | **Yes** — a DS DescriptionList would either be too generic (pass-through) or too opinionated (break show pages) |
| **Decision** | **Do not add.** Keep page-owned `<dl>`/grid patterns; use `Text`/`Label` when touching DS-aligned areas. |

### 3. Compact record-summary display (SummaryPanel, SummaryCard, RecordSummary)

| Criterion | Assessment |
|-----------|------------|
| Evidence | Domain cards per entity; EmptyState for empty queues |
| **Decision** | **Do not add** — summaries are entity-specific; `Card` + `Heading` + badges/chips suffice. |

### 4. Trend, delta, comparison indicators

| Criterion | Assessment |
|-----------|------------|
| Evidence | Analytics `kind` hints (“Selected period” / “Current snapshot”); RecruitmentChain uses “↓” for **step flow**, not KPI trend |
| **Decision** | **Do not add** — no repeated delta/%-change contract; if needed later, likely **Badge**/`Text` tone + icon, still page-owned calculation. |

---

## Existing canonical components that cover requirements

| Need | DS / foundation |
|------|------------------|
| Bordered metric container | `Card` (atom); Public `UI/Card` on Account |
| Label / caption | `Label`, `Text` (`variant="caption"`, `tone="muted"`) |
| Primary value | `Heading` (level) or `Text` with typography classes |
| Status on metadata | `StatusBadge`, `Badge`, `Chip` |
| Loading | `Skeleton` |
| Progress metrics | `Progress` |
| Empty metric grid | `EmptyState` (molecule) |
| Historical sequences | `Timeline` (not KPI) |
| Layout | `Stack`, `Cluster`, `Divider` |

---

## Component API and semantic contract (new component)

**Not applicable** — no new component created in PR-5AA.

*Reference (domain, non-DS):* Admin `StatCard` props remain `label`, `value`, `href?`, `hint?`, `emptyHint?` for Admin-only use.

---

## Accessibility and responsive requirements

- **Metrics:** Scalar tiles should expose readable text (label + value); when interactive, use link with visible label (Admin `StatCard` pattern). DS composition should use semantic headings only when the value is a section title, not for every KPI number.
- **Description lists:** Prefer `<dl>`/`<dt>`/`<dd>` for static metadata; ensure contrast via `text-muted` / `Text` tones.
- **Responsive:** Existing grids (`grid-cols-1 sm:grid-cols-2 xl:grid-cols-3`) are page responsibilities; `Stack`/`Cluster` do not require extension.

No DS changes required for PR-5AA.

---

## Data and state ownership

| Pattern | Owner |
|---------|--------|
| KPI values, hrefs, permissions | Laravel controllers → Inertia props |
| Period selection, analytics definitions | Analytics page + backend |
| Empty copy (`recorded`, empty hints) | Domain helpers / page copy |
| Formatting (rates, days) | Page-local functions (`formatRate`, `TimeCard`) |

Presentation primitives must **not** embed calculations, fetching, or permission gates.

---

## UI Lab changes

**None.** No new catalog entry or section added (documentation-only gate).

---

## Dependencies

**No changes.**

---

## Test and build results

**Not run** — documentation-only PR-5AA (no application code changes).

---

## V2 isolation and adoption status

| Item | Status |
|------|--------|
| V2 inspection | Read-only; patterns noted in Admin Analytics and command centers |
| V2 modification | **None** |
| V2 adoption / migration | **Not started** (explicit stop) |
| Admin `StatCard` | Remains **V1 Admin domain** component, not DS canon |

---

## Relationship to prior PR-5 gates

Consistent with PR-5U–PR-5Z: add canonical components only when a **stable, cross-surface semantic contract** is evidenced. Here, metric presentation is **similar visually** but **split across Admin StatCard, Analytics card variants, and Account UI Card composition**, with metadata rows too varied for a single DescriptionList molecule.

---

## Stop condition

PR-5AA complete. **Do not start PR-6A.** **Do not begin V2 adoption or migration.**
