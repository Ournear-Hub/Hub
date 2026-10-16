# OURNEAR Product Shell architecture review — Corporate Library Phase 5

**Status:** Review complete — **no shell implementation, renames, or V2 layout changes** (architecture phase — **V2 not modified**)

**Date:** 2026-10-09  
**Repository:** `D:\Ournear Hub\Ournear Hire`  
**Authorization:** Read-only Phase 5 shell contract documentation (this file only). Phase 5 **closure** and Phase 6 work require separate readiness decisions.

**Baseline preserved:** PR-3–PR-5 / PR-5AA–PR-5AE atom/molecule freeze (`docs/OURNEAR_CORPORATE_ARCHITECTURE_CLOSURE_PR5AE.md`); LoadingState/ErrorState **COMPOSE-ONLY** (`docs/OURNEAR_LOADING_ERROR_STATE_ARCHITECTURE_REVIEW.md`).

---

## 1. Executive summary

V2 product chrome is implemented as **four Inertia layout components** plus a **profile region component** (`ProfileShell`), not as named `*Shell` exports. Evidence supports **evidence-based shell mapping** to the Corporate Library Specification §14, with **one physical layout serving multiple logical shells** on the account surface.

| V2 implementation | Proposed spec shell mapping | Confidence |
|-------------------|----------------------------|------------|
| `Layouts/PublicLayout.tsx` | **PublicShell** | **High** — marketing/public chrome, public nav, SEO |
| `Layouts/AccountLayout.tsx` (wraps `PublicLayout`) | **CandidateShell** *or* **EmployerShell** *or* **RecruiterShell** (role + route + `employerNav` gating) | **High** for shared chrome; **logical split** is server nav, not separate layouts |
| `Layouts/AdminLayout.tsx` | **AdminShell** | **High** |
| `Layouts/AdminAuthLayout.tsx` | **AuthShell** (admin staff auth only) | **Medium** — account login/register uses **PublicLayout**, not a dedicated auth layout |
| `Components/Profile/ProfileShell.tsx` | **ProfileShell** (region/template, not app chrome) | **High** — used inside pages, not as Inertia root layout |

**Phase 5 deliverable in spec §37:** “Product shells (contracts on existing layouts).” This review **documents contracts**; it does **not** complete Phase 5 implementation (no formal shell module, no registry, no renames).

---

## 2. Layout inventory and shell mapping

**Consumer counts:** PowerShell scan of `resources/js/**/*.tsx` (excluding `Layouts/` definition files), files containing opening JSX tag `<LayoutName` or `<ProfileShell`.

| Path | Role (observed) | Consumer files (approx.) | Spec shell mapping |
|------|-----------------|--------------------------|-------------------|
| `resources/js/Layouts/PublicLayout.tsx` | Public site chrome: `Head`/SEO, `Header`, `main`, `Footer` | **23** | **PublicShell** |
| `resources/js/Layouts/AccountLayout.tsx` | Account chrome on top of **PublicLayout**: title bar, breadcrumbs, sidebar `AccountNav`, optional `CandidateMobileNav`, notifications bell | **28** | **CandidateShell** / **EmployerShell** / **RecruiterShell** (see §3.2) |
| `resources/js/Layouts/AdminLayout.tsx` | Admin app shell: `Sidebar`, `TopBar`, `FlashMessage`, breadcrumbs, main scroll region | **41** | **AdminShell** |
| `resources/js/Layouts/AdminAuthLayout.tsx` | Centered card auth for **admin** guard | **4** (`Pages/Admin/Auth/*`, `Pages/Admin/Profile/Password.tsx`) | **AuthShell** (admin variant) |
| `resources/js/Components/Profile/ProfileShell.tsx` | Profile identity header + optional tabs + `children` | **4** pages (see §3.5) | **ProfileShell** (composition) |

**Nested shell (observed):** `AccountLayout` **always renders `PublicLayout`** as outer wrapper (`AccountLayout.tsx` lines 38, 132). Account pages therefore inherit **public header/footer and public SEO** unless pages override `Head` locally.

**Not found in repository (spec §14):**

| Spec shell | Status |
|------------|--------|
| **CorporateWorkspaceShell** | **Not implemented** — no layout or nav |
| **SettingsShell** | **Not a separate layout** — settings routes use **AccountLayout** + **AccountNav** links |
| **DocumentationShell** | **Deferred Phase 9** — UI Lab uses **AdminLayout** on design-system pages (out of scope for V2 product shells) |
| **AuthShell** (account) | **Partial** — account auth pages use **PublicLayout** + page sections (e.g. `Pages/Account/Login.tsx`), not `AdminAuthLayout` |

---

## 3. Per-shell contracts (evidence-based)

Legend: **Observed** = current code; **Spec target** = Corporate Library §14 / §385; **Gap** = not unified today.

### 3.1 PublicShell ← `PublicLayout`

| Concern | Observed contract | Source |
|---------|-------------------|--------|
| **Layout props** | `title?`, `description?`, `children` (`LayoutProps`) | `types/layout.ts`, `PublicLayout.tsx` |
| **Auth** | Reads `usePage().props.auth` indirectly via `Header` (optional account user on public site) | `Header.tsx` |
| **Navigation** | **Public** primary nav from `content/navigation.ts` via `Header` / `PublicMobileMenu` | Spec: keep config file (§36) |
| **Breadcrumbs** | **Not** in layout — page-owned | Gap vs spec generic `breadcrumbs` on shell |
| **Flash** | **Not** in layout — pages render flash inline | Gap |
| **Alerts** | `accountAlerts` / `adminAlerts` **not** used in PublicLayout | N/A on anonymous public pages |
| **Branding** | `page.branding` → favicon in `Head` | `HandleInertiaRequests` → `WebsiteSettingService::publicBranding()` |
| **SEO** | `seo`, `pageSeo`, optional `cms` page props; layout builds `Head` title/description/canonical/OG/Twitter | `PublicLayout.tsx`, shared `pageSeo` lazy prop |
| **Responsive** | `Header` desktop links + mobile drawer (`PublicMobileMenu`) | Spec §14 “Header drawer” |
| **Main slot** | `{children}` in `<main className="flex-1">` | Template slot equivalent |

**Slots (informal):** single `children` main region; no formal aside/footer actions API.

---

### 3.2 CandidateShell / EmployerShell / RecruiterShell ← `AccountLayout`

One layout component; **logical shell** chosen by **auth role**, **route**, and **shared nav props**.

| Concern | Observed contract | Source |
|---------|-------------------|--------|
| **Layout props** | `title` (required), `description?`, `action?`, `breadcrumbs?`, `children` | `AccountLayout.tsx` |
| **Auth** | `page.auth.user` (account guard payload on non-admin routes) | `HandleInertiaRequests::authProps` |
| **Navigation** | **`AccountNav`** reads `candidateNav` **or** `employerNav` from shared props; fallback static employer links if `employerNav` empty | `AccountNav.tsx`, middleware share |
| **Candidate nav source** | `candidateNav` lazy array from `CandidateAccountNav::items($user)` when `user->isCandidate()` | `HandleInertiaRequests.php` |
| **Employer / hiring nav source** | `employerNav` from `EmployerAccountNav::items()` when `canAccessHiringWorkspace()` and (employer role **or** hiring workspace path) | `HandleInertiaRequests.php`, `isAccountHiringWorkspaceRequest()` |
| **RecruiterShell (spec)** | **Same layout and `employerNav`** on hiring workspace routes; not a separate TS layout | Spec §14 “RecruiterShell” vs V2: route family only |
| **Breadcrumbs** | Optional prop → `Breadcrumbs` (hidden on small screens in header band) | Page passes items |
| **Flash** | **Not** in AccountLayout — inherited public header does not show flash; pages handle | Gap |
| **Alerts** | **`accountAlerts.unread`** when non-null → notification bell (candidates + hiring-enabled users) | Middleware: candidate or `canAccessHiringWorkspace()` |
| **Branding / SEO** | Inherited from nested **PublicLayout** (`title` prop forwarded) | Double `Head title` possible (page + layout) |
| **Responsive** | Candidate: **`CandidateMobileNav`** bottom bar + sidebar `lg+`; Employer: collapsible **Menu** button `lg:hidden` for sidebar | Spec §14 bottom nav + sidebar |

**Permission-gated nav (account):** Nav items are **filtered server-side** when building `candidateNav` / `employerNav` (not re-filtered in `AccountNav` by permission strings). **Observed** client-side: role pick (`candidate` vs employer item list) and href active matching only.

---

### 3.3 AdminShell ← `AdminLayout`

| Concern | Observed contract | Source |
|---------|-------------------|--------|
| **Layout props** | `title?`, `breadcrumbs?`, `children` (`LayoutProps`) | `AdminLayout.tsx` |
| **Auth** | `auth.user` on admin surface = **admin guard** user with `permissions[]`, `can_view_hiring`, `can_view_analytics` | `userPayload()` |
| **Navigation** | **`Sidebar.tsx`** — static `sections` array in component + `dashboardItem`, conditional hiring/analytics links | Not yet split to config file (spec §36 “Refactor later”) |
| **Permission-gated nav** | **Observed:** `visibleItems()` filters by `auth.user.permissions`; `hasPermission()` allows `.view` or matching `.manage`; hiring/analytics links use **boolean flags** on user | `Sidebar.tsx` |
| **Breadcrumbs** | Optional layout prop | Pages pass items |
| **Flash** | **`FlashMessage`** reads shared `flash` (success/error/warning/status) | Admin-only inline banners |
| **Alerts** | **`adminAlerts`** in **TopBar** (unread + recent list) when user has `notifications.view` | Middleware permission gate |
| **Branding** | Sidebar `BrandMark` admin surface; footer “OurNear Admin” | Local chrome |
| **SEO** | Minimal — `Head title={title}` only | Admin not SEO-focused |
| **Responsive** | Sidebar overlay + `TopBar` menu button on small viewports; `h-screen` app frame | Spec §14 overlay sidebar |

**Slots:** `children` inside `AdminContainer` after flash + optional breadcrumbs.

---

### 3.4 AuthShell ← `AdminAuthLayout` (admin-only variant)

| Concern | Observed contract | Source |
|---------|-------------------|--------|
| **Props** | `title?`, `children` | `AdminAuthLayout.tsx` |
| **Auth context** | Unauthenticated admin guest pages | No shared `auth` UI in layout |
| **Navigation** | Brand link to `/admin/login` only | Minimal |
| **Flash** | Pages render `flash.status` / errors inline (e.g. admin login) | Not centralized in layout |
| **Branding** | `BrandMark inverted`, tagline in header | Static |
| **Responsive** | Centered card `max-w-md` | Spec “centered card” |

**Gap:** Spec **AuthShell** also covers account login/register; those use **PublicLayout** (`Pages/Account/Login.tsx`, Register, etc.) — **two auth chrome patterns** in V2.

---

### 3.5 ProfileShell ← `Components/Profile/ProfileShell.tsx`

| Concern | Observed contract | Source |
|---------|-------------------|--------|
| **Type** | **Page region component**, not Inertia root layout | Spec §716 “Preserve / extend” |
| **Props** | Identity fields (`name`, `username`, `photoUrl`, ids, verifications, …), `actions?`, `tabs?`, `headerVariant`, `children` | `ProfileShell.tsx` |
| **Navigation** | Optional **tabs** array (profile section links) — page-built | Contextual, not from shared `nav` prop |
| **Auth / permissions** | **Page/controller** supplies data; shell does not read `usePage` | Domain props |
| **Consumers (observed)** | `Pages/Account/Home.tsx`, `Pages/Account/Profile.tsx`, `Pages/Companies/Show.tsx`, `Pages/Profiles/Show.tsx` | 4 files |

**Relationship:** Often composed **inside** `AccountLayout` or `PublicLayout` pages — ProfileShell is **not** a replacement for CandidateShell/PublicShell.

---

## 4. Authorization and permission boundaries

| Surface | Mechanism | Observed behavior |
|---------|-----------|-------------------|
| **Admin sidebar items** | `permission` string on each `AdminNavItem`; filtered client-side against `auth.user.permissions` | Hidden if no permission; nested children pruned |
| **Admin alerts** | Server: `PermissionName::NotificationsView` or `adminAlerts` is **null** | TopBar hides bell when null |
| **Admin hiring / analytics shortcuts** | Server flags on user payload: `can_view_hiring`, `can_view_analytics` | Sidebar injects links outside section tree |
| **Account notifications bell** | Server: `accountAlerts` only for candidate or hiring workspace access | Layout checks `accountAlerts != null` |
| **Account nav items** | Built in PHP (`CandidateAccountNav`, `EmployerAccountNav`) | **Trust server list** — no client permission catalog |
| **Route middleware** | Laravel guards (`admin`, `account`) — **not documented in this file** | Enforces access before Inertia render |

**Recommendation (architecture, not implemented):** Spec §14 “Permissions” column for shells should **document server-side nav construction + guard** as authoritative; client filtering is **supplemental** (admin only).

---

## 5. Shared Inertia data (HandleInertiaRequests)

**File:** `app/Http/Middleware/HandleInertiaRequests.php`  
**Types:** `resources/js/types/page.ts` (`SharedPageProps`)

| Prop | When present | Source (observed) |
|------|--------------|-------------------|
| **`auth.user`** | Active guard user for current surface (admin **or** account, not both on same request) | `authProps()` — admin routes use admin guard only |
| **`auth.admin` / `auth.account`** | Split payloads on admin vs account routes | Same |
| **`flash.*`** | Session flash keys (success, error, warning, status, structured confirmations, OTP retry) | Laravel session |
| **`adminAlerts`** | Admin user with `notifications.view` | Eloquent notifications |
| **`accountAlerts`** | Account candidate or hiring workspace user | Unread count only |
| **`candidateNav`** | Account candidate | `CandidateAccountNav::items()` |
| **`employerNav`** | Employer or hiring-workspace routes for eligible users | `EmployerAccountNav::items()` + path gate |
| **`seo`, `branding`, `pageSeo`** | Public/marketing surfaces (lazy) | Website settings / `PublicSeoService` |
| **`publicContent`, `socialLinks`, `analytics`, `turnstileSiteKey`, `locale`** | Cross-cutting | Services / config |

**Spec §385 target props:** `auth`, `nav`, `breadcrumbs`, `flash`, `alerts`, `branding`, `seo`.

| Spec prop | V2 equivalent | Gap |
|-----------|---------------|-----|
| `nav` | **`candidateNav` / `employerNav` / Sidebar static / `primaryNav`** | **No unified `nav` key** |
| `breadcrumbs` | **Page/layout props**, not shared | OK for Inertia pattern |
| `alerts` | **`adminAlerts` / `accountAlerts`** | Split naming |

**Not shared globally:** `cms` — **page prop** when controllers pass it (PublicLayout reads `page.cms` if present).

---

## 6. Gap analysis vs Corporate Library Specification §14 and §36

| Spec expectation | V2 evidence | Gap severity |
|------------------|-------------|--------------|
| Formalize `PublicLayout` → **PublicShell** | Implemented; SEO/branding wired | **Low** — naming/docs only |
| Formalize `AccountLayout` → Candidate/Employer shell | **Single layout**; nav split via shared props | **Medium** — logical shells documented, not separate modules |
| **RecruiterShell** distinct | Same as employer/hiring nav on route prefix | **Low** — product naming vs one layout |
| **AuthShell** for login/register | **Admin:** `AdminAuthLayout`; **Account:** `PublicLayout` | **Medium** — two patterns |
| **SettingsShell** | Settings under AccountNav | **Low** — defer separate shell or document as route group |
| Shell **context props** unified | Split alert/nav keys | **Medium** for future SDK/adoption |
| **Sidebar.tsx** config split | Nav embedded in component | **Medium** — spec §36 “Refactor later” |
| **Do not merge nav trees** | Public vs account vs admin remain separate | **Met** |
| **No file moves until authorized** | Layouts unchanged | **Met** (PR-5AE) |
| ProfileShell preserved | Component exists, 4 consumers | **Met** |

---

## 7. Decisions, recommendations, and open product questions

### 7.1 Existing decisions (do not reopen)

- PR-5AE: atom/molecule freeze; shells **not** frozen; no PR-6A from library batch alone.
- LoadingState/ErrorState: **COMPOSE-ONLY** — no shell loading primitives.
- Spec §723: **no layout renames/moves** without implementation phase authorization.

### 7.2 Evidence-based recommendations (not authorization)

1. **Treat Phase 5 “complete” for architecture** when this contract doc + product sign-off on shell mapping table are accepted — **not** when files are renamed to `*Shell.tsx`.
2. **Document account auth** as **PublicShell + auth page template** unless product mandates a dedicated `AuthShell` layout for account.
3. **Keep `ProfileShell` as a composition component** inside templates, not an app root layout.
4. **Future adoption:** optional TypeScript `ShellContract` type mirroring shared props — **separate implementation PR**.

### 7.3 Product decisions still required

| # | Question |
|---|----------|
| 1 | Is **nested PublicLayout inside AccountLayout** intentional long-term (public header on all account pages)? |
| 2 | Should **RecruiterShell** remain a **route/nav grouping** only, or eventually a distinct layout variant? |
| 3 | Should **account login/register** migrate to a dedicated **AuthShell** layout for parity with admin auth? |
| 4 | When to **extract admin nav** from `Sidebar.tsx` to config (spec §36) — Phase 5 doc-only vs later PR? |
| 5 | **Phase 5 closure gate:** architecture docs only vs required **shell contract tests/UI Lab** section? |

---

## 8. Acceptance criteria for Corporate Library Phase 5 completion

Phase 5 (spec §37) may be declared **architecture-complete** when **all** are true:

1. **Shell mapping approved** — table in §2 agreed by product/architecture (including account auth pattern and RecruiterShell naming).
2. **Contract document published** — this review (or successor) matches production `HandleInertiaRequests` and layout files **without undocumented props**.
3. **Gap list disposition** — each §6 gap marked **accept**, **defer**, or **schedule** with owner (no silent “complete”).
4. **Explicit exclusion honored** — no V2 layout renames, no nav tree merges, no PR-6A in Phase 5 closure batch.
5. **Optional (product choice):** Admin nav config extraction plan authored — **not required** for doc-only Phase 5 if gap #4 in §6 is accepted as deferred.

Phase 5 **implementation-complete** (separate authorization) would additionally require: shell modules or aliases, adoption plan, and regression strategy — **out of scope for this review**.

---

## 9. Exclusions (honored)

- No layout renames, file moves, or shell component implementation.
- No changes to V2 consumers, routes, middleware, shared props, or permissions.
- No PR-6A / V2 adoption; no DataTable, FilterBar, LoadingState/ErrorState molecules, Storybook/Phase 9.
- No reopening frozen atom/molecule library decisions.

---

## 10. Files touched in this review

| File | Action |
|------|--------|
| `docs/OURNEAR_PRODUCT_SHELL_ARCHITECTURE_REVIEW.md` | **Created** |
| `resources/js/**`, `app/**` | **Not modified** |

---

## 11. Stop condition

Product Shell architecture review (Phase 5 **documentation**) is **complete**.

- **Do not** rename layouts, extract nav config, or implement `*Shell` exports from this document alone.
- **Do not** declare Corporate Library **Phase 5 closed** without product sign-off on §8.
- **Do not** start **Phase 6** (UX system wrappers per spec §37) or **PR-6A** adoption without a separate readiness charter.

**Suggested next step (requires authorization):** Phase 5 closure sign-off meeting **or** bounded **admin nav config extraction** architecture spike — product choice per §7.3.
