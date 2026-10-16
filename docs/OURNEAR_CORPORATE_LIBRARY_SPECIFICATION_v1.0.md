# OURNEAR CORPORATE LIBRARY SPECIFICATION v1.0

**Document type:** Reusable web library contract (specification only)  
**Architecture source:** [OURNEAR_UNIFIED_PLATFORM_ARCHITECTURE_v1.1.md](./OURNEAR_UNIFIED_PLATFORM_ARCHITECTURE_v1.1.md)  
**Codebase anchor:** `D:\Ournear Hub\Ournear Hire` (V2 — preserve working behavior)  
**Status:** Library freeze candidate v1.0

---

## 1. Purpose

Define the **governed, reusable OurNear web development library** so Public, Account, Hiring, Corporate, Admin, and future surfaces share one design foundation, one component taxonomy, and one set of UX/Product Systems—**without** duplicating buttons, tables, forms, navigation, or domain logic in UI.

This document is the **implementation contract** for all future UI work. It does not authorize code changes by itself.

---

## 2. Scope

**In scope**

- Web: React 19 + Inertia 3 + TypeScript + Tailwind 4 (Vite)  
- All experience surfaces listed in Architecture v1.1  
- UX/Product Systems used across domains (forms, data, search, files, notifications, etc.)  
- Semantic parity rules for future Flutter (not shared React components)  
- Migration path from existing V2 folders (`Components/UI`, `Components/Public`, `Components/Admin/ui`, `Layouts`, `Pages`)

**Out of scope (this document)**

- Laravel domain implementation, migrations, API endpoints  
- Running or expanding codebase audits  
- Reference repo re-audits  
- Big-bang file moves or rewrites  

---

## 3. Source of Truth

| Source | Role |
|--------|------|
| **Architecture v1.1** | Layers, domains, surfaces, shells, systems, dependency rules |
| **V2 code** | What exists today (dual UI kits, DataTable, layouts, `useForm`) |
| **Module docs** (`docs/0x-*.md`) | Current behavior per domain |
| **Reference audits (7)** | Pattern input only (MDS/SDS forms & grids, Tabler icons, Moonbase structure)—not copied UI |
| **This specification** | Library naming, contracts, governance, mapping |

When code and spec diverge during migration, **code behavior wins** until an intentional, phased implementation updates the library.

---

## 4. Library Architecture

```text
OurNear Design Foundation
        ↓
Tokens
        ↓
Icons / OurNearIcon
        ↓
Atoms
        ↓
Molecules
        ↓
Components
        ↓
Organisms
        ↓
Patterns
        ↓
Templates
        ↓
Product Shells
        ↓
UX / Product Systems
        ↓
Features
        ↓
Pages
```

| Layer | Purpose | May depend on | Must not depend on | Reuse | Owner | Lifecycle |
|-------|---------|---------------|--------------------|-------|-------|-----------|
| **Design Foundation** | Brand, principles, a11y baseline | — | Features | Global | Design + Platform | Stable |
| **Tokens** | Visual constants | Foundation | Pages | Global | Design System | Versioned |
| **Icons** | OurNearIcon registry | Tokens | Tabler direct in pages | Global | Platform UI | Registry-driven |
| **Atoms** | Single-purpose controls | Foundation, Icons | Domain, Inertia page props logic | Global | UI Platform | Registry |
| **Molecules** | Composed controls | Atoms | Eloquent, routes | Global | UI Platform | Registry |
| **Components** | Reusable widgets (Card, Dialog, DataTable shell) | Atoms, Molecules | Business rules | Global / surface variants | UI Platform | Registry |
| **Organisms** | Large UI regions (Header, SideNav) | Components | Laravel | Global / domain-flavored | UI Platform + Feature | Registry |
| **Patterns** | Interaction recipes (List, Dashboard) | Components, Systems | Page-specific data fetching | Global | UX Platform | Documented |
| **Templates** | Domain-neutral page skeletons | Patterns, Components | ATS/candidate rules | Global | UX Platform | Documented |
| **Product Shells** | Nav + chrome + context | Templates, navigation config | Domain services | Per surface | UX Platform | Stable contracts |
| **UX / Product Systems** | Cross-cutting behavior (Form, Data) | UI + platform types | Unrelated domains | Global | Platform | Versioned APIs |
| **Features** | Domain UI (JobForm, ProfileCompletion) | Systems + UI | Other features’ pages | Domain / product | Feature teams | Feature lifecycle |
| **Pages** | Inertia route targets | Features, Shells, Templates | — as library | None (compose only) | Feature teams | Route-bound |

**Lifecycle (components):** Proposed → Approved → Active → Deprecated → Removed (see §32).

**Reuse rule:** If it appears on more than one page in more than one domain with the same structure, it belongs **below** Pages.

---

## 5. Design Foundation

### Brand (permanent)

| Token | Value | Usage |
|-------|-------|--------|
| Primary brand | `#214449` | Brand surfaces, secondary actions (admin primary) |
| Accent | `#F15A24` | Primary CTAs (public/account marketing emphasis) |
| White | `#FFFFFF` | Surfaces, cards |
| Typography | **Instrument Sans** | `--font-sans` in `resources/css/app.css` |

Do not replace this system without product approval.

### Principles

- Server authoritative data and permissions  
- Surface-aware **variants**, not duplicate component types  
- Accessible by default (keyboard, focus, labels)  
- Responsive by **pattern**, not only breakpoint utilities  
- Preserve V2 UX until migrated variant-by-variant  

---

## 6. Token Architecture

Three tiers. **Naming:** `--our-{tier}-{category}-{name}` in CSS where new tokens are added; Tailwind theme maps to semantic names (`brand`, `canvas`, …) as today.

### Tier 1 — Global (primitives)

**Established in V2 (`app.css` @theme):**

| Category | Values |
|----------|--------|
| Colors | `brand` #214449, `accent` #f15a24, `canvas` #f9fafa, `surface` #f4f5f5, `line` #e5e7e7, `ink` #0f172a, `muted` #64748b, `disabled` #94a3a8, `success` #22c55e, `warning` #f59e0b, `danger` #ef4444 |
| Radius | sm/md 0.375rem, lg 0.5rem, xl 0.75rem |
| Shadow | sm, md (ink-tinted) |
| Font | Instrument Sans stack |

**To document in implementation (not invented values yet):** spacing scale (Tailwind default), motion durations, z-index scale (modal currently `z-[70]` — normalize to token names), breakpoints (Tailwind `sm|md|lg|xl`).

### Tier 2 — Semantic

Map to Tailwind utilities used today:

- **canvas** — page background  
- **surface** — subtle fill, table header, hover  
- **elevated surface** — cards (white + border + shadow-sm)  
- **line** — borders  
- **ink / muted / disabled** — text hierarchy  
- **success / warning / danger** — status  
- **information** — alias of `brand`-tinted info (existing `info` in `statusTone`)  
- **focus** — `focusRing` in `design-system/classNames.ts`  

**State semantics:** hover/active via opacity/Tailwind; disabled via `disabled:`; error via `controlError` + `errorText`.

### Tier 3 — Component

Recipes (extend from `classNames.ts`):

- **button** — variant maps (primary, secondary, ghost, destructive, link)  
- **input** — `controlBase`, `controlError`  
- **card** — `cardDensity`: compact | standard | feature  
- **table** — bordered container, header `bg-surface`  
- **dialog** — overlay + panel radius xl  
- **badge** — `statusTone`  
- **navigation** — public underline nav vs admin sidebar item  
- **sidebar / tabs / dropdown** — defined during component implementation  

### Light / dark

**Light:** current production. **Dark:** readiness only—semantic tokens must be swappable later; no dark implementation required in Phase 1.

### Accessibility

Contrast minimum WCAG AA for text on canvas/surface using **ink/muted** on **canvas/white**; focus ring mandatory on interactive atoms.

---

## 7. Icon Architecture

```text
Tabler Icons (MIT, npm @tabler/icons-react — install only when implementing)
      ↓
OurNear Icon Registry (JSON/TS manifest: name, tabler id, category, aliases)
      ↓
OurNearIcon (single React component: name, size, stroke, aria-label)
      ↓
All product surfaces
```

### Rules

- Application **pages and features** import **`OurNearIcon` only**, not `@tabler/icons-react` directly.  
- Registry lists **approved** icons; additions require registry PR + design review.  
- **Aliases:** e.g. `close` → `IconX`.  
- **Sizes:** `xs | sm | md | lg` mapped to 16/20/24/32px.  
- **Stroke:** outline default; filled only if registry entry specifies.  
- **A11y:** decorative → `aria-hidden`; meaningful → `title` or `aria-label`. IconButton **requires** accessible name.  
- **brand-*** Tabler icons ≠ OurNear brand mark; marketing logo uses `BrandMark` only.  
- **Flutter:** subset exported from same registry YAML for semantic parity.

### Categories

navigation, actions, status, communication, jobs, recruitment, documents, users, organizations, analytics, social, settings, security, system

### Current V2

`resources/js/Components/UI/icons.tsx` — migrate entries into registry; deprecate direct SVG duplication over time.

---

## 8. Atom Library

Each atom: **purpose**, **variants**, **states**, **a11y**, **responsive**, **prohibited use**.

### Typography

| Atom | Purpose | Variants | States | Notes |
|------|---------|----------|--------|-------|
| **Text** | Body copy | body, secondary | — | Use `typography.body` / `secondary` |
| **Heading** | Titles | display, pageTitle, sectionTitle, cardTitle | — | Semantic `h1–h6` |
| **Label** | Form labels | default, required | — | `labelBase`; `htmlFor` required |
| **Caption** | Meta | caption, eyebrow | — | |
| **Link** | Navigation text | default, muted | hover, focus | Inertia Link wrapper |

**Prohibited:** headings for non-heading styling only (use Text).

### Actions

| Atom | Purpose | Variants | States |
|------|---------|----------|--------|
| **Button** | Primary actions | primary, secondary, ghost, destructive, link | idle, hover, focus, active, loading, disabled |
| **IconButton** | Icon-only | ghost, subtle | + requires `label` |
| **LinkButton** | Link styled as button | same as Button | uses `href` |

**Surface variant (not separate components):** `surface="public" | account | admin | corporate"` adjusts primary mapping (accent vs brand) per Architecture v1.1 product tokens.

**V2 refs:** `Components/Public/Button.tsx`, `Components/Admin/ui/Button.tsx` → **one Button atom**.

### Inputs

Input, Textarea, Select, Checkbox, Radio, Switch, SearchInput, DateInput, TimeInput, **OTPInput** (from `OtpSixInput`).

States: idle, focus, error, disabled, readonly. Admin: `Input`, `Select`, `Textarea`, `Label`. Public: `controlBase`.

### Identity

Avatar, Presence (future), Badge, StatusIndicator (tone-based dot).

### Feedback

Spinner (`SpinnerIcon`), Progress (future), Skeleton (future P1), Divider, Tooltip (future P2).

### Accessibility

**Focus utilities** (`focusRing`), **VisuallyHidden**, **SkipLink** (required in shells P1).

---

## 9. Molecule Library

| Molecule | Composition | Prohibited |
|----------|-------------|------------|
| **FormField** | Label + control + help + error | Domain validation rules inside |
| **SearchField** | SearchInput + optional button | Full job search page |
| **PasswordField** | Input + visibility toggle | |
| **DateField** | Label + DateInput | |
| **FilterChip** | Badge + dismiss | |
| **Pagination** | `Components/Pagination.tsx` | **KEEP**, standardize styling |
| **Breadcrumb** | `Navigation/Breadcrumbs.tsx` | |
| **Tabs** | Admin `Tabs` | |
| **Stat / Metric** | Label + value + optional href | |
| **NotificationItem** | Row for inbox lists | |
| **UserMenu** | Avatar + dropdown | |
| **Dropdown** | Trigger + menu panel | |
| **FileField** | Label + file input + hint | Upload logic in Form System |
| **RoleSelector** | Select + permissions hint | P2 org |
| **OrganizationSelector** | Select + org context | P2 |
| **EmptyState** | Title + description + action slot | **Consolidate** UI + Admin duplicates |
| **LoadingState** | Spinner + label | `UI/LoadingState.tsx` |
| **ErrorState** | Message + retry | `UI/ErrorState.tsx` |

Molecules **must not** fetch data or call `router` except Pagination/Link molecules.

---

## 10. Component Library

| Component | When to use | Responsive |
|-----------|-------------|------------|
| **Card** | Grouped content | Density prop |
| **Dialog / Modal** | Focused task, confirm | Bottom sheet mobile (`Admin/ui/Modal` pattern) |
| **Drawer** | Master-detail, filters | Full-width mobile |
| **DataTable** | Admin lists, server-driven rows | Horizontal scroll; card fallback via Data System |
| **DataGrid** | Enterprise column tooling | P2 |
| **FilterBar** | Facet filters | Collapse to drawer mobile |
| **SearchBar** | Query input + submit | Full width mobile |
| **FileUploader** | Drag/drop zone | Stack mobile |
| **FilePreview** | PDF/image preview | P1 |
| **Timeline** | Activity history | Vertical stack |
| **ActivityFeed** | Compact feed | |
| **Alert** | Inline banner | |
| **Toast** | transient feedback | P1 (flash today) |
| **Stepper / Wizard** | Multi-step | Vertical mobile |
| **FormSection / FormActions** | Form layout | Sticky actions mobile optional |
| **ConfirmDialog** | Destructive confirm | `Admin/ui/ConfirmDialog` |

---

## 11. Organism Library

Large structures; **may accept props and slots** but **no domain API calls** (pages pass data).

| Organism | Role | vs Feature |
|----------|------|------------|
| **Header / Footer** | Public chrome | Feature = job search panel |
| **Sidebar / TopBar** | Admin chrome | |
| **AccountNav** | Account sidebar | Nav items from server props |
| **ProfileCompletionCenter** | Profile progress UI | Rules from props |
| **DocumentGroupList** | Grouped doc list | Upload in page + Form System |
| **RecruitmentChain** | Message context display | |
| **JobForm** | Job fields block | **Feature** if tied to JobFormLookups + submit |
| **CandidatePipeline** | Stage columns | **Feature** when ATS-specific |
| **ApplicationTimeline** | Application events | |
| **ChatPanel** | Thread + composer | Communication System |
| **NotificationCenter** | Inbox layout | |
| **OrganizationSwitcher** | Org context | P2 |
| **DashboardHeader / DashboardGrid** | Dashboard chrome | Widgets from Dashboard System |
| **FilterWorkspace** | Filters + results layout | |
| **DetailHeader** | Entity title + actions | |

**Rule:** If it encodes **one domain’s business rules** (e.g. job posting eligibility), classify as **Feature** wrapping organisms.

---

## 12. Pattern Library

| Pattern | Structure | Owner |
|---------|-----------|-------|
| **List** | Search → Filters → Results → Pagination | Data + Search Systems |
| **Detail** | Header → Summary → Sections → Actions → Activity | Template + organisms |
| **Create/Edit** | Header → Form → Validation → Actions | Form System |
| **Dashboard** | Header → Filters → Metrics → Widgets → Data | Dashboard System |
| **Pipeline** | Stages → Items → Actions → Detail | Feature + Data |
| **Profile** | Identity → Sections → Completion → Actions | Profile Template |
| **Settings** | Nav → Sections → Save → Feedback | Settings Template |
| **Approval** | Request → Review → Decision → Audit | Workflow System |
| **Empty/Error** | State → Explanation → Action | State model |
| **Import** | Upload → Map → Validate → Preview → Import → Result | File System + Feature |
| **Communication** | Conversation list → Thread → Composer | Communication System |

Patterns are **documented compositions**, not necessarily one React export.

---

## 13. Template Library

Domain-neutral layouts only—**no** candidate/employer/ATS logic, **no** Laravel.

DashboardTemplate, ListTemplate, DetailTemplate, CreateTemplate, EditTemplate, SettingsTemplate, ProfileTemplate, PipelineTemplate, SearchTemplate, ReportTemplate, ErrorTemplate, EmptyTemplate.

Templates receive **slots**: `header`, `filters`, `main`, `aside`, `footerActions`.

---

## 14. Product Shell Library

| Shell | Purpose | Navigation | Responsive | Permissions | Templates |
|-------|---------|------------|------------|-------------|-----------|
| **PublicShell** | Marketing + jobs | `content/navigation.ts` | Header drawer | Public | Search, List, Detail |
| **AuthShell** | Login/register | Minimal | Centered card | Guest | Create |
| **CandidateShell** | Candidate account | `candidateNav` (server) | Bottom nav + sidebar lg | Account candidate | Dashboard, Profile, Settings |
| **EmployerShell** | Employer account | `employerNav` | Collapsible menu | Account employer | Dashboard, List |
| **RecruiterShell** | Hiring workspace | Grouped employer/hiring nav | Same as employer | Hiring permissions | Dashboard, Pipeline, List |
| **CorporateWorkspaceShell** | Multi-org | Org-scoped nav | Drawer sidebar | Org RBAC | Dashboard, Settings |
| **AdminShell** | Staff console | `Sidebar.tsx` + permissions | Overlay sidebar | `PermissionName` | List, Detail, Dashboard |
| **ProfileShell** | Profile-focused | Contextual | Stack mobile | Profile policy | Profile |
| **SettingsShell** | Security/prefs | Settings subnav | Stack | Self | Settings |
| **DocumentationShell** | Storybook/docs | Docs IA | — | Staff/dev | — |

**V2 mapping:** `PublicLayout`, `AccountLayout`, `AdminLayout`, `AdminAuthLayout` — **formalize as shells**; do not merge nav trees.

**Context props (shell contract):** `auth`, `nav`, `breadcrumbs`, `flash`, `alerts`, `branding`, `seo` (from `HandleInertiaRequests` + page).

---

## 15. UX / Product Systems (overview)

Systems sit **above** raw components; **below** features. They define **APIs and behavior**, not routes.

| System | Responsibility |
|--------|----------------|
| Form | Field registration, validation display, Inertia/API |
| Data | Table/grid, server pagination, selection, export |
| Search | Query, filters, saved search, permission-aware |
| File/Document | Upload, metadata, preview, lifecycle |
| Notification | Event → channel → delivery |
| Communication | Conversations, messages (≠ notification) |
| Workflow | Triggers, approvals, history |
| Permission | Scope helpers for UI (server still enforces) |
| Dashboard | Widgets, metrics, layout |
| Analytics | Events, metrics, reports |

---

## 16. Form System

### Contract

```typescript
// Conceptual — implementation later
FormContext {
  values, errors, processing, isDirty,
  setField, submit, reset,
  mode: 'inertia' | 'api'
}
```

### Requirements

- Controlled fields; **Inertia `useForm`** remains first-class for web Phase 1–2  
- Server validation authoritative; display field + form errors  
- Client validation optional (format only)  
- Dirty / unsaved: warn on navigate (P2)  
- File uploads: `forceFormData`, progress (P2)  
- Multi-step: Stepper + per-step validation  
- Autosave: domain-specific (P2)  
- A11y: labels, `aria-invalid`, error linked by id  
- **API v1:** same field error shape for mobile  

**V2:** Account documents `useForm`; admin forms in pages + `Admin/ui` controls → wrap with FormField molecule over time.

---

## 17. Data System

### DataTable v1 (current foundation)

`Components/Admin/ui/DataTable.tsx` — columns, rows, empty, row click, selected key.

**Extend:** loading skeleton rows, sort header UI (server sort params), row selection, bulk bar slot.

### DataGrid v2 (future)

Column visibility, reorder, grouping, saved views, virtual scroll.

### Server authoritative

Filtering, sorting, pagination, row-level authorization — **always** Laravel.

### States

loading, empty, error, permission denied; responsive **card list** fallback pattern for mobile.

**Related:** `Pagination.tsx`, `FilterBar`, `SearchBar`.

---

## 18. Search System

- **Global search** — P2 (permission-aware index)  
- **Contextual search** — admin SearchBar + URL query (standardize)  
- **Autocomplete** — P2  
- **Filters** — FilterBar + facet props from controller  
- **Saved searches / views** — P2 with DataGrid  
- **Ranking** — server-side  
- **Never** rely on client-only filtering for authorized datasets  

---

## 19. File / Document System

```text
File (blob on disk)
  → Document (metadata record)
  → Version (optional)
```

Attributes: type, owner, organization (future), entity link, visibility, permission, verification, expiry, retention, status.

**UI:** FileUploader, FilePreview, DocumentGroupList (existing), Form System for upload.

**V2:** `Pages/Account/Documents/Index.tsx`, storage disks in `config/filesystems.php`, authorized downloads.

---

## 20. Notification System

```text
Domain Event
  → Notification Rule
  → Channel Router
  → in-app | email | push | SMS | WhatsApp (future recruitment; not OTP)
  → Delivery → Retry / Failure → Audit
```

Templates, preferences, priority, quiet hours, delivery status — extend DatabaseNotification patterns.

**UI:** NotificationItem, NotificationCenter organism, bell + `accountAlerts` / `adminAlerts`.

---

## 21. Communication System

**Notification ≠ Message ≠ Conversation**

| Concept | Meaning |
|---------|---------|
| **Notification** | System/user alert, often short, actionable link |
| **Message** | Content in a thread |
| **Conversation** | Container for messages + participants |

Channels: in-app chat (admin Messages today), email, future external.

**V2:** `Pages/Admin/Messages/Index.tsx`, `types/communication`.

---

## 22. Workflow System

Trigger → Condition → Rule → Action → Approval → Outcome → History.

**Policy:** Automation **must not** auto shortlist/select/offer/reject (DEVELOPMENT-RULES).

**V2:** `RecruitmentAutomationProcessor`, automations admin, tasks — extend UI with Workflow System contracts.

---

## 23. Permission System

Organization → Membership → Role → Permission → Scope → Resource → Action.

Scopes: organization, business_unit, department, team, assigned_only, self.

**UI:** `hasPermission` for nav; **server:** policies + middleware. Permission System provides **hooks/types only**, not security.

**V2:** `PermissionName`, `AccessCatalog`, `Sidebar` filtering.

---

## 24. Dashboard System

DashboardHeader, MetricCard (`StatCard`), Widget, WidgetGrid, filters, saved views, role/org visibility.

**V2:** Admin Dashboard, Hiring Command Center, Account Home — compose via Dashboard System; do not duplicate grid logic per domain.

---

## 25. Analytics System

Events (server-emitted), metrics, aggregations, dashboards, reports — **no** business-critical metrics computed only in React.

**V2:** `DashboardService`, analytics admin page, `AnalyticsService` public payload.

---

## 26. Feature Architecture

**Feature** = domain-specific UI module composing library + systems + **props from controllers**.

Examples: Job Search, Job Application, Candidate Screening UI, Employer Job Creation, Resume Management, Document Verification, Interview Scheduling, Staffing Requirement, Notification Center UI, Chat UI, Profile Completion.

**May use:** atoms → templates, systems, domain TypeScript types.

**Must not:** become a dump of unrelated components; **must not** import other features’ pages.

**Location (future target):** `resources/js/features/{name}/` — **MOVE LATER**; today `Components/Admin/JobForm`, `Components/Profile/*`, etc.

---

## 27. Page Architecture

**Page** = Inertia entry tied to one primary route; orchestrates features + shell + template.

Examples: `Pages/Jobs/Index.tsx`, `Pages/Account/Home.tsx`, `Pages/Admin/Jobs/Index.tsx`.

**Must not** be imported as reusable library.

**Resolver:** `resources/js/app.tsx` → `./Pages/${name}.tsx` (unchanged until explicit migration).

---

## 28. Surface Variants

Prop **`surface`**: `public | account | admin | corporate` (optional `dense`).

Applies to: **Button**, **Card**, **EmptyState**, **DataTable**, **Dialog**, **Input** (spacing/density), **MetricCard**.

**Do not create** `PublicButton`, `AdminButton`, etc., unless structure truly differs (e.g. Admin-only dense table toolbar)—default is **one component, variant props**.

| Surface | Primary button token | Notes |
|---------|----------------------|-------|
| public | accent | Marketing CTAs |
| account | accent or brand | Contextual |
| admin | brand | Staff console |
| corporate | brand | Enterprise (future) |

---

## 29. Responsive System

| Breakpoint | Strategy |
|------------|----------|
| **Desktop (lg+)** | Persistent sidebar; split panes; full tables |
| **Tablet (md)** | Collapsible nav; scroll tables |
| **Mobile (sm)** | Drawer nav; bottom nav (candidate); table → **card list**; filters → **filter drawer**; pipeline → horizontal scroll or stage picker |

Do not shrink desktop layouts only—use **pattern-level** mobile layouts (List Pattern mobile variant).

---

## 30. Accessibility

Minimum contract for **Active** registry components:

- Keyboard operable; visible focus (`focusRing`)  
- Dialog: focus trap, Escape, return focus  
- Forms: labels, errors announced  
- Tables: `<th>` scope  
- SkipLink in shells  
- IconButton: accessible name  
- Contrast: semantic text on canvas  
- `prefers-reduced-motion`: reduce animations (when motion tokens added)  

---

## 31. Component State Model

Applicable states (subset per component):

idle, hover, focus, active, loading, skeleton, empty, success, error, disabled, readonly, processing, permission denied, not found, offline, stale.

Document required states per component in registry.

---

## 32. Component Registry

Fields: name, category, purpose, variants, states, dependencies, a11y, responsive, owner, status, version, examples, deprecated/replacement.

Lifecycle: **Proposed → Approved → Active → Deprecated → Removed**

**Host:** DocumentationShell / Storybook — Phase 9.

---

## 33. Asset Governance

| Asset | Source | License | Rules |
|-------|--------|---------|-------|
| Icons | Tabler Icons | MIT | Via registry only |
| Font | Instrument Sans | Project license | Self-host / Google as today |
| Logo | OurNear brand | Proprietary | `BrandMark` only |
| Illustrations | Undraw/etc. if used | Per asset file | Track in manifest |
| Reference repos | Various | Not shipped | Patterns only |

Record: source, license, attribution, allowed usage, modification, production status.

---

## 34. Dependency Rules

```text
Foundation → Tokens → Icons → Atoms → Molecules → Components → Organisms
Patterns / Templates → use Components + Systems
Shells → Templates + navigation
UX Systems → UI + typed platform contracts (no Eloquent)
Features → Systems + UI + types
Pages → Features + Shells
```

**Prohibited:** circular imports; UI → Models; Domain → React; pages as shared imports; frontend-only authorization.

---

## 35. Mobile Parity

| Shared | Not shared |
|--------|------------|
| Token semantics, icon names, state names, API DTOs | React components |
| Accessibility principles | Inertia forms |

Flutter implements parallel widgets under same registry YAML.

---

## 36. Current → Target Mapping

| Existing (V2 path) | Library target | Action |
|--------------------|----------------|--------|
| `resources/css/app.css` @theme | Global + semantic tokens | Standardize |
| `design-system/classNames.ts` | Foundation + component tokens | Standardize |
| `Components/UI/icons.tsx` | OurNearIcon registry | Standardize |
| `Components/Public/Button.tsx` + `Admin/ui/Button.tsx` | Atom Button + surface | Standardize |
| `Components/UI/Card.tsx` + `Admin/ui/Card.tsx` | Component Card | Standardize |
| `Components/UI/EmptyState.tsx` + `Admin/ui/EmptyState.tsx` | Molecule EmptyState | Standardize |
| `Components/UI/StatusBadge.tsx` + `Admin/ui/StatusBadge.tsx` | Atom Badge + domain tone maps | Standardize shell; keep admin status map in feature layer |
| `Components/UI/LoadingState`, `ErrorState`, `Alert` | Molecules | Keep |
| `Components/UI/IconButton.tsx` | Atom | Keep |
| `Components/Pagination.tsx` | Molecule Pagination | Keep |
| `Navigation/Breadcrumbs.tsx` | Molecule Breadcrumb | Keep |
| `Admin/ui/DataTable.tsx` | Data System v1 | Extend |
| `Admin/ui/FilterBar`, `SearchBar` | Search + Data Systems | Standardize |
| `Admin/ui/Modal`, `Drawer`, `ConfirmDialog` | Components | Extend a11y |
| `Admin/ui/Input`, `Select`, `Textarea`, `Label`, `Tabs`, `Timeline`, `ActivityFeed` | Atoms/Components | Standardize |
| `Layouts/PublicLayout.tsx` | PublicShell | Formalize |
| `Layouts/AccountLayout.tsx` | CandidateShell / EmployerShell | Formalize |
| `Layouts/AdminLayout.tsx` | AdminShell | Formalize |
| `Layouts/AdminAuthLayout.tsx` | AuthShell | Formalize |
| `content/navigation.ts` | Public nav config | Keep |
| `Components/Admin/Sidebar.tsx` | AdminShell nav | Refactor later (config split) |
| `Account/AccountNav.tsx` | Candidate/Employer shell nav | Keep |
| Inertia `useForm` | Form System | Standardize wrapper |
| `Components/Profile/ProfileShell.tsx` | ProfileShell + Profile Template | Preserve / extend |
| `Components/Profile/ProfileCompletionCenter.tsx` | Organism / Feature | Preserve |
| `Components/Documents/DocumentGroupList.tsx` | Organism | Preserve |
| `Components/Admin/JobForm.tsx` | Feature | Preserve |
| `Components/Admin/StatCard.tsx` | Dashboard System MetricCard | Extend |
| `HandleInertiaRequests` shared props | Shell + System inputs | Extend |

**No file moves** until implementation phases authorize them.

---

## 37. Implementation Strategy

| Phase | Deliverable |
|-------|-------------|
| **1** | Foundation + tokens doc + icon registry schema |
| **2** | Atoms + molecules (Button, FormField, EmptyState unify) |
| **3** | Components + organisms (Modal a11y, Card, DataTable extend) |
| **4** | Patterns + templates (document List/Dashboard) |
| **5** | Product shells (contracts on existing layouts) |
| **6** | UX systems (Form, Data, Search wrappers) |
| **7** | Feature standardization (gradual imports from features/) |
| **8** | Page cleanup (thin pages) |
| **9** | Registry + Storybook/DocumentationShell |

**Rule:** V2 keeps working after each phase; feature flags optional for new primitives.

---

## 38. Governance

Before creating UI, answer:

1. Does it exist?  
2. Can we extend?  
3. Atom, molecule, component, organism, pattern, template, shell, system, feature, or page?  
4. Domain-specific? → Feature  
5. Belongs in a UX System?  
6. New **variant** instead of new component?  
7. Reusable across surfaces?  
8. Duplication risk?  

Architecture review checklist (from v1.1) applies to features; library team owns registry.

---

## 39. Decision Matrix

| Need | Layer |
|------|-------|
| Text / Heading | Atom |
| Button / Input | Atom |
| Labeled field with errors | Molecule FormField |
| Search input + button | Molecule SearchField |
| Paginated links | Molecule Pagination |
| Data table shell | Component DataTable |
| Table + filters + server page | Data System + List Pattern |
| Job posting form | Feature |
| Screening match scores | Domain service — not UI |
| Pipeline kanban | Organism + Feature (ATS) |
| Dashboard layout | Pattern + Dashboard System |
| Admin jobs list page | Page |
| Candidate sidebar | CandidateShell |
| Notification routing | Notification System (backend) + inbox Feature |
| Chat thread UI | Communication System + Feature |
| Profile sections | Profile Template + Feature |
| Org permission check | Policy (server) + Permission System types (UI) |
| Status badge for application stage | Feature mapper + Atom Badge |
| New random modal copy | Extend Dialog — do not fork |

---

## 40. Final Library Summary

The OurNear Corporate Library is a **layered, governed stack** rooted in V2 brand and tokens, unified through **surface variants** rather than duplicate kits, with **UX/Product Systems** owning cross-cutting behavior and **Features/Pages** owning domain orchestration. Implementation is **phased and non-destructive**; Tabler feeds **OurNearIcon**; server remains authoritative for data, search, and permissions.

---

## Corporate Library Freeze Gate

**Status: READY WITH CONDITIONS**

The specification is complete enough to begin **controlled Phase 1 implementation** (foundation, tokens, icon registry schema, first atom standardizations).

**Conditions (precise, non-blocking for Phase 1 start):**

1. **Phase 1 implementation PR scope** must be explicitly bounded (e.g. tokens + Button + EmptyState + OurNearIcon wrapper only)—no simultaneous shell moves.  
2. **Product sign-off** on surface primary mapping (accent vs brand per surface) during Phase 2 Button rollout—documented in §28; default follows v1.1.  
3. **Registry host** (Storybook vs internal docs site) chosen before Phase 9—not required before Phase 1–2.

**Not blockers:** API v1, DataGrid v2, CorporateWorkspaceShell UI, dark mode, org selector molecules—these follow architecture phases.

---

## Next Recommended Step

**Controlled Phase 1 implementation plan** (engineering task breakdown: icon registry file format, token CSS export, first `OurNearIcon` + unified `Button`/`EmptyState` behind existing import paths or aliases)—**only after explicit approval to write code**.

---

*End of OURNEAR CORPORATE LIBRARY SPECIFICATION v1.0*
