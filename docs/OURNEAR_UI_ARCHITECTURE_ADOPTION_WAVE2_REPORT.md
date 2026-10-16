# UI architecture adoption — wave 2 (FE-504)

**Date:** 2026-10-12  
**Status:** **PARTIAL (code)** — waves 2–9 (recruitment admin index headings); full 99-page UI adoption **not** claimed

## Scope vs master prompt

Historical audit (**99 pages**) and prior batches (**FE-101–FE-501**, **REG-PROD-003** admin nav) already delivered most atom migration on account/admin surfaces, DetailHeader on key application flows, Phase F1/F2 public homepage/service work, and server-driven admin sidebar grouping.

This wave addresses a **documented cross-cutting gap**: inconsistent parent navigation (plain text / unicode arrows / footer-only back links) without a canonical molecule.

**Explicitly not in this wave:**

- **FE-201 / FE-202** FilterBar barrel unification — **BLOCKED** (REG-GOV-001 / PR-5M).
- ~~Site-wide **UI/Card → atoms/Card** on public marketing pages~~ **FE-302 complete (2026-10-12)** — see §FE-302 below; FE-504 waves unchanged.
- Mass replacement of **Admin/ui** adapters or **Public/Button** alias removal.
- Retiring routes beyond **FE-501** (`Account/Switch.tsx` page removed; POST `/account/switch` retained).

## Implemented (code)

### Canonical molecule

| Asset | Path |
| --- | --- |
| **BackNavLink** / **BackNavButton** | `BackNavLink.tsx` — href parent nav; **BackNavButton** for in-page hub (profile modules). Tabler `arrow-left` via `OurNearIcon` |
| Barrel export | `Components/molecules/index.ts` |
| Vitest | `BackNavLink.test.tsx` — **NOT RUN — deferred by product owner** |

### Page groups updated (back navigation + chrome)

| Group | Pages / components |
| --- | --- |
| Candidate account | `Applications.tsx`, `Applications/Show.tsx`, `SavedJobs.tsx` |
| Employer workspace | `Employer/Applications/Show.tsx`, `Employer/Submissions/Index.tsx`, `Employer/Submissions/Show.tsx`, `Enquiries/Show.tsx` |
| Public jobs | `Jobs/Index.tsx` (empty state), `Jobs/Apply.tsx`, `Components/Public/JobDetail.tsx` |
| Hiring profile context | `Profiles/Show.tsx` |
| Admin | `Admin/Freelancers/Show.tsx`, `Admin/Analytics/Index.tsx` (`DetailHeader` h2), `Admin/Profile/Password.tsx`, `Admin/Auth/Login.tsx`, `Admin/Auth/ForgotPassword.tsx`, `Admin/Auth/ResetPassword.tsx` |
| Auth (account) | `Account/Login.tsx`, `Account/Register.tsx`, `Account/ForgotPassword.tsx`, `Account/ResetPassword.tsx` — **BackNavLink** + canonical **Button** |
| Settings / public forms | `Account/Settings/Security.tsx`, `Contact.tsx` — **Alert** for success flash |
| Employer jobs | `Account/Employer/Jobs/Form.tsx` — back to jobs list (**not** applicant pipeline scope) |
| Profile modules | `ProfileModuleEditorShell.tsx`, `ProfileModuleContinueBar.tsx`, `Account/Profile.tsx` (module back) |
| Public freelancers | `Freelancers/Received.tsx` |

### Redundant chrome removed

- Footer-only “Back to …” links on list/detail pages where back is now **above** primary content.
- Duplicate **DetailHeader** description on candidate application detail (layout already carries description).
- Unicode `←` prefixes replaced with registry icon.

### Wave 4 — account & employer sidebar icons (2026-10-12)

| Layer | Change |
| --- | --- |
| PHP | `CandidateAccountNav`, `EmployerAccountNav` — optional `icon` per item (registry names); labels, hrefs, groups, visibility unchanged |
| Inertia | Existing `candidateNav` / `employerNav` shared props carry `icon` |
| React | `AccountNav` + `CandidateMobileNav` “More” sheet — `AccountNavItemIcon` → `OurNearIcon`; labels retained; missing/unknown icons safe |
| Tests | `AccountNavigationIconsTest.php` (4 cases), `AccountNavItemIcon.test.tsx` (3 cases) — **NOT RUN — deferred by product owner** |

**Not in wave 4:** public header profile dropdown (addressed in wave 5), admin sidebar (REG-PROD-003 unchanged).

### Wave 5 — public header account dropdown (2026-10-12)

| Layer | Change |
| --- | --- |
| PHP | `PublicHeaderAccountNav` — curated employer header menu (legacy hrefs/labels); icons from `EmployerAccountNav` lookup; candidates use full `CandidateAccountNav`; admin header links unchanged |
| Inertia | Shared `headerAccountNav` on all account-authenticated requests |
| React | `Header.tsx` desktop profile menu + mobile account section consume `headerAccountNav` + `AccountNavItemIcon` |
| Tests | `PublicHeaderAccountNavTest.php` (5 cases) — **NOT RUN — deferred by product owner** |

**Preserved:** Employer header still uses `/account/profile` (not sidebar `/account/company`); labels “Job Management” / “Enquiries”; logout + menu a11y unchanged; full `employerNav` still shared for account sidebar.

### Wave 6 — admin detail duplicate headings (2026-10-12)

| Page | Issue | Fix |
| --- | --- | --- |
| `Pages/Admin/Users/Show.tsx` | Breadcrumb current item + inline “Users / {name}” duplicated profile `h1` | Breadcrumbs end at **Users** (linked); removed inline trail; **`h1` keeps `{user.name}`** |
| `Pages/Admin/Candidates/ResumeImport.tsx` | Breadcrumb “Bulk resume import” + matching `h1` | **`DetailHeader` `h2`** “Import and review resumes” (Analytics pattern) |

**Reviewed, unchanged:** `ApplicationWorkspacePage` (ATS excluded); `Hiring/CommandCenter`; `Freelancers/Show`, `Profile/Show`, `Profile/Password`; admin index pages with breadcrumb + identical `h1` (deferred).

| Tests | `adminDetailHeading.test.ts` (2 cases) — **NOT RUN — deferred by product owner** |

### Wave 7 — admin index heading consistency (2026-10-12)

| Page | Before | After |
| --- | --- | --- |
| `Jobs/Index` | Breadcrumb **Jobs** + `h1` Jobs | Breadcrumb **Jobs** + **`DetailHeader` h2** “Recruitment listings”; Create job in `actions` |
| `Users/Index` | Breadcrumb **Users** + `h1` Users | Breadcrumb **Users** + **`DetailHeader` h2** “Platform user directory”; Admin Users in `actions` |
| `Candidates/Index` | Breadcrumb **Candidates** + `h1` Candidates | Breadcrumb **Candidates** + **`DetailHeader` h2** “Candidate talent library” |
| `Employers/Index` | Breadcrumb **Employers** + `h1` Employers | Breadcrumb **Employers** + **`DetailHeader` h2** “Employer company records” |

**Unchanged:** Other admin indexes (CRM, Finance, etc.) — out of scope until duplicate confirmed.

| Tests | `adminDetailHeading.test.ts` (+4 index cases) — **NOT RUN — deferred by product owner** |

### Wave 8 — Tasks / Interviews / Submissions indexes (2026-10-12)

| Page | Before | After |
| --- | --- | --- |
| `Tasks/Index` | No breadcrumb; `h1` Tasks | Breadcrumb **Tasks** + **`DetailHeader` h2** “Staff follow-up worklist” |
| `Interviews/Index` | No breadcrumb; `h1` Interviews + scorecard link | Breadcrumb **Interviews** + **`DetailHeader` h2** “Application interview schedule”; scorecard link in `actions` |
| `Submissions/Index` | **Unchanged** | No breadcrumb; `h1` “Employer Submissions” ≠ nav label “Recruitment Partnership” — no breadcrumb/`h1` duplicate to fix without expanding scope |

| Tests | `adminDetailHeading.test.ts` (+2 index paths) — **NOT RUN — deferred by product owner** |

### Wave 9 — recruitment admin indexes (2026-10-12, master completion pass)

| Page | Breadcrumb | **`DetailHeader` h2** |
| --- | --- | --- |
| `Submissions/Index` | Admin → **Recruitment Partnership** (nav-aligned) | Shortlisted candidates for employer review |
| `Placements/Index` | Admin → Placements | Placement records from employer selections |
| `Replacements/Index` | Admin → Replacements | Employee replacement pipeline |
| `Employees/Index` | Admin → Employees | Joined employees from placements |
| `Work/Index` | Admin → My Work | Your assigned recruitment work |

Document `<Head title>` unchanged where it differed from breadcrumb (e.g. Employer Submissions, Joined / Employees).

| Tests | `adminDetailHeading.test.ts` (+5 paths, Submissions nav label) — **NOT RUN — deferred by product owner** |

### Wave 10 — Staffing Enquiries index (2026-10-12, secondary terminal)

| Page | Change |
| --- | --- |
| `EmployerEnquiries/Index` | Breadcrumb **Staffing Enquiries** (matches `AdminNavigationCatalog` / dashboard quick link); **`DetailHeader` h2** “Workforce and project requests”; removed legacy **Employer Enquiries** page `h1` (nav label mismatch) |

| Tests | `adminDetailHeading.test.ts` (+1 path, Staffing Enquiries nav label) — **NOT RUN — deferred by product owner** |

### FE-302 — public marketing Card consolidation (2026-10-12, post wave 3)

| Layer | Change |
| --- | --- |
| Public components | `ServiceCard`, `IndustryCard`, `ContactCard`, `PublicModuleCard`, `PublicCompanyCard`, `PublicProductProofGrid` — inline `rounded-xl border…` shells → `atoms/Card` (`padding`, `as`, preserved `className` for hover/rounded-2xl/canvas) |
| Pages | `About.tsx` (mission/pillar/commitment cards), `ForEmployers.tsx` (delivery steps + get-in-touch form shell), `Contact.tsx` (message form shell); prior batch: `Jobs/Apply.tsx`, `Platform/TalentPool.tsx` |
| Home | Consumes migrated `PublicModuleCard`, `PublicCompanyCard`, `PublicProductProofGrid` — no page-level shell change |

**Retained (incompatible or out of scope):**

| Surface | Reason |
| --- | --- |
| `JobCard` | Whole-card `role="button"`, keyboard selection, dense/list variants, accent inset — not a static `cardDensity` shell |
| `Home` hero pipeline panel | Dark translucent brand hero (`border-white/15`, `bg-white/5`), not white marketing card |
| `Admin/Hiring/CommandCenter` | Admin lane; still `UiCard` / `UI/Card` adapter |
| `Components/UI/Card.tsx` | Barrel adapter (`density` prop) for legacy consumers |

**Tests:** tsc / build / vitest / browser — **NOT RUN — deferred by product owner**

### Preserved

- **REG-PROD-003** `AdminNavigationCatalog` + Inertia `adminNav` + section Tabler icons (prior session).
- **REG-CAN-PROF-001**, **REG-SEC-003**, employer pipeline/compare, resume delivery, routes and permissions unchanged.
- Auth back links now use **BackNavLink** (account + admin).

## Obsolete routes

No new retirements. Only documented obsolete surface remains **FE-501** (orphan Switch page removed).

## Tests

| Suite | Status |
| --- | --- |
| `BackNavLink.test.tsx` | Authored — **NOT RUN — deferred by product owner** |
| `AccountNavigationIconsTest.php` | Authored — **NOT RUN — deferred by product owner** |
| `AccountNavItemIcon.test.tsx` | Authored — **NOT RUN — deferred by product owner** |
| `PublicHeaderAccountNavTest.php` | Authored — **NOT RUN — deferred by product owner** |
| `adminDetailHeading.test.ts` | Authored — **NOT RUN — deferred by product owner** |
| PHPUnit / Playwright / `tsc` / `build` | **NOT RUN — deferred by product owner** |

## Remaining code gaps (authorized next work)

1. **Remaining admin indexes** — optional `DetailHeader` + breadcrumbs (CRM, Finance, Master Data, Settings, Freelancers, Dashboard, Automations, etc.); **CommandCenter** and **ApplicationWorkspace** excluded. **Wave 10 done:** `EmployerEnquiries/Index`.
2. **Organism adoption** on remaining admin index pages (opportunistic).
3. **Mass Admin/ui adapter retirement** and **Public/Button** alias removal (opportunistic).
4. **Employer applicant pipeline UI** — REG-PROD-001 **complete (code)** including hiring-wide ATS; FE-504 does not duplicate pipeline work.
5. **FE-201** when PR-5M authorization is recorded.

## Issue register

**FE-504** — tracked in `docs/OURNEAR_RELEASE_ISSUE_REGISTER.md` (partial completion).
