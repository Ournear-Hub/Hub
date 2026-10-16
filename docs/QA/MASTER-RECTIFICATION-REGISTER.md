# Master Rectification Register

**Project:** OurNear Hire  
**Environment:** Local `http://127.0.0.1:8000` (2026-10-14 audit)  
**Status legend:** NOT TESTED · REPRODUCED · CONFIRMED WORKING · DEFECT CONFIRMED · FIX IMPLEMENTED · BROWSER VERIFIED · BLOCKED

---

## Summary (latest — OurNear local unblock 2026-10-15)

| Item | Status | Notes |
|------|--------|-------|
| OurNear missing from admin recruiter dropdown | **FIXED** | `RecruitingEmployerAccounts` replaces `role=employer`-only query |
| Talent Pool jobs empty for OurNear | **EXPLAINED + QA DATA** | Job #32 is `employer_public`; new internal draft job #41 for Talent Pool list |
| Local email verification without SMTP | **IMPLEMENTED** | `ournear:local-verify-account-email` + admin route; env-guarded |
| PHPUnit full suite | **PASS** | 1040 passed / 3 skipped / 0 failed |

## Summary (gap closure 2026-10-14 night)

| Area | Browser | Automated tests | Notes |
|------|---------|-----------------|-------|
| Talent Pool assignment type label (admin + recruiter) | **BROWSER VERIFIED** (prior pass) | **PASS** | Fixed UI mapping; regression test on label consistency |
| Staffing access OurNear request → admin approve | **BROWSER VERIFIED** | **PASS** | `2026-10-14-staffing-ournear-*.png`, admin verification tab; reject path PHPUnit-only |
| Full PHPUnit suite | — | **1035 pass / 3 skip / 0 fail** | Fixture fixes: employer `company_name`, unique register mobile, header/share tests |
| All four allocation types | PARTIAL browser | **PASS** | Talent Pool browser; others PHPUnit |
| Internal Talent Pool job / public exclusion | NOT RUN browser | **PASS** | `EmployerModulesStaffingTest` |
| `npx tsc --noEmit` + `npm run build` | — | **PASS** | Gap closure pass |

## Summary (prior — Modules / Staffing / Talent Pool pass)

| Area | Browser | Automated tests | Notes |
|------|---------|-----------------|-------|
| Local HTTP 500 → migrate fix | **BROWSER VERIFIED** (home 200) | PASS | Root cause: missing `listing_scope`; migrate on `ournear_hire_local` |
| Staffing access request / admin approve | **BROWSER VERIFIED** (OurNear) | PASS | See gap-closure row above |
| OurNear missing from staffing selector | **BROWSER VERIFIED** (context) | PASS | Admin companies vs requirement enquiries |
| Talent Pool job internal scope | NOT RUN browser | PASS | PHPUnit |
| Admin Candidate Allocation (Talent Pool assign) | **BROWSER VERIFIED** | PASS | Evidence PNGs 2026-10-14 |
| Migration `2026_10_14_210000_*` | **Applied local** | PASS | Columns verified via tinker |

## Summary (prior — Candidate Allocation pass)

| Area | Browser | Automated tests | Notes |
|------|---------|-----------------|-------|
| Admin Candidate Allocation UI | NOT TESTED | PASS | `/admin/talent-pool-assignments` — multi-select, modal assign, tracking; needs admin session + `php artisan migrate` |
| Recruiter Assigned Candidates + progress | **BROWSER VERIFIED** | PASS | Renamed from Assigned Executive; progress stages + submit-to-employer when linked application |
| Employer submission from assignment | NOT TESTED | Partial | Uses existing `EmployerSubmissionService`; PHPUnit paths in assignment tests |

## Summary (prior — Talent Pool / Staffing / header pass)

| Area | Browser | Automated tests | Notes |
|------|---------|-----------------|-------|
| Recruitment Support (no personal dashboard) | **BROWSER VERIFIED** | PASS | Root cause: `employerNav` missing on `/account/recruitment-support` for dual-role users → fixed via `AccountRecruiterWorkspacePaths` |
| Assigned Executive rename | **BROWSER VERIFIED** | PASS | Nav + page; executive profile URL when public share enabled |
| Staffing direct page (no submit CTA) | **BROWSER VERIFIED** | PASS | `/account/enquiries`; `/account/staffing/request` → redirect; submit feature removed |
| Duplicate routing row (recruiter chrome) | **BROWSER VERIFIED** (spot) | — | `AccountLayout` hides shell title bar on recruiter paths; removed `ProfileShell` tabs on employer home |
| Global search centered width | **BROWSER VERIFIED** (desktop) | — | Header grid + `max-w-md` search |
| Responsive matrix / Playwright | NOT TESTED | — | — |
| Top branding PNG | BLOCKED | — | Asset not supplied |

## Summary (prior audit pass)

| Area | Browser | Automated tests | Notes |
|------|---------|-----------------|-------|
| Global header search (companies/profiles) | BROWSER VERIFIED | PASS | Jobs removed from suggest API + dropdown UI |
| Job card layout (logo left) | FIX IMPLEMENTED | Partial | Grid layout; multi-viewport screenshots not captured |
| Own-company apply/save | CONFIRMED WORKING | PASS | PHPUnit |
| Jobs workspace selection | CONFIRMED WORKING | PASS | PHPUnit |

---

## Register

### §4 Job posting and master data

| ID | Requirement | Expected | Actual (browser) | Status | Root cause / fix | Tests |
|----|-------------|----------|------------------|--------|------------------|-------|
| MR-4.1 | Mining/Infrastructure/Construction categories + General | Categories load from Laravel on create/edit job | NOT TESTED (browser) | CONFIRMED WORKING (code + prior PHPUnit) | `MasterDataBootstrapper`; run bootstrap once on existing DB if categories missing | `MasterDataTest`, `JobMasterDataValidationTest` (historical PASS) |
| MR-4.2 | Remove Site from job forms | No Site field in UI | NOT TESTED (browser) | CONFIRMED WORKING (code) | `JobForm.tsx` omits Site; DB column retained | — |
| MR-4.3 | Qualification combobox + Add Qualification | In-dropdown footer create | NOT TESTED (browser) | FIX IMPLEMENTED | `Combobox` `listFooter` + `MasterDataDropdownFooter` in `JobForm.tsx` | `EmployerJobMasterDataController` |
| MR-4.4 | Skills multi-select + Add Skill | In-dropdown footer create | NOT TESTED (browser) | FIX IMPLEMENTED | `MultiSelect` `listFooter` for employer scope | — |
| MR-4.5 | Job classifications (Urgent, Immediate, Featured) | Dropdown + card labels; employer cannot self-Featured | NOT TESTED (browser) | PARTIAL | Employer: `listing_priority` select (standard/urgent/immediate); Featured admin-only; cards use `listing_priority_label` / urgent | PhaseE tests (historical) |

### §5 Job Card layout

| ID | Requirement | Expected | Actual | Status | Fix |
|----|-------------|----------|--------|--------|-----|
| MR-5.1 | No employer logo on job card; readable layout | Title, company, metadata, badges, actions; no image column | Homepage latest jobs (mobile) show text-only cards | **BROWSER VERIFIED (sample)** | `JobCard.tsx` — logo removed; flex layout |
| MR-5.1b | Personal Dashboard 2-column job grid | Exactly 2 cols at desktop widths | **NOT TESTED** (browser) | FIX IMPLEMENTED | `CandidateRecommendedJobs` grid `min-[480px]:grid-cols-2` (no xl:3) |
| MR-5.2 | Responsive 1440/1280/768/390/360 | No overflow/overlap | NOT TESTED all widths | NOT TESTED | Needs Playwright viewport screenshots |
| MR-5.3 | Preview limits on home/dashboard | Capped previews + view all | Homepage latest jobs limited (6 visible in audit) | CONFIRMED WORKING (browser) | Existing home limits |

### §6 Jobs workspace and navigation

| ID | Requirement | Expected | Actual | Status |
|----|-------------|----------|--------|--------|
| MR-6.1 | Internal cards → `/jobs?job=` | Workspace selection | Homepage **View job** links present | CONFIRMED WORKING (code + prior tests) |
| MR-6.2 | Selection keeps full list | List not filtered away | `/jobs` shows 9 of 19 with selection | BROWSER VERIFIED |
| MR-6.3 | Recent jobs View → applicants | Company applicants workspace | href `/account/jobs/{slug}/applicants`; page loads | **BROWSER VERIFIED** (direct nav) + PHPUnit | `AccountDashboardService::employerWorkspaceJobRow`, `EmployerRecentJobLinksTest` |
| MR-6.5 | Create Job on Jobs workspace | Prominent header control | `/account/jobs` shows **Create Job** + opens `/account/jobs/create` | **BROWSER VERIFIED** | `Employer/Jobs/Index.tsx` in-page header (recruiter shell hides layout action bar) |
| MR-6.4 | Own-company apply/save | Backend block + UI state | NOT re-tested in browser this pass | CONFIRMED WORKING (PHPUnit) |

### §7 Homepage and public search

| ID | Requirement | Expected | Actual | Status | Fix |
|----|-------------|----------|--------|--------|-----|
| MR-7.1 | Industry cards display-only | No navigation | Headings only, scroll buttons | BROWSER VERIFIED | `IndustryCard` without `href` on home |
| MR-7.2 | Popular searches from master data | Valid designation chips + filter | Links present (e.g. Civil Site Engineer) | CONFIRMED WORKING (PHPUnit) | — |
| MR-7.3 | Global search ≠ jobs search | All/Companies/Profiles; no jobs; no View All | **Search companies & profiles**; tabs; no job section | **BROWSER VERIFIED** | `GlobalHeaderSearch.tsx`, `PublicSocialSearchService::discoverySearch()` |
| MR-7.4 | Bounded search width | Comfortable width | `w-[min(12rem,26vw)]` | FIX IMPLEMENTED | GlobalHeaderSearch |

### §8 Company public profile

| ID | Requirement | Status | Notes |
|----|-------------|--------|-------|
| MR-8.1 | Remove clutter banner/duplicate headers | NOT TESTED | Needs browser on `/companies/{id}` |
| MR-8.2–8.4 | Identity, tabs, links | NOT TESTED | — |

### §9 Personal profile

| ID | Requirement | Status |
|----|-------------|--------|
| MR-9.1 | Profile image, public preview, owner chrome | NOT TESTED (this pass) |

### §10 Unified sharing

| ID | Requirement | Status |
|----|-------------|--------|
| MR-10.1–10.4 | WhatsApp, email, copy, canonical URLs | NOT TESTED (this pass); code exists (`PublicShareMenu`, `PublicShareUrlBuilder`) |

### §11 Company workspace and duplicate headers

| ID | Requirement | Status | Notes |
|----|-------------|--------|--------|
| MR-11.1 | Overview layout KPI → pipeline/actions → talent/staffing → recent jobs/applicants | BROWSER VERIFIED | Single **Company Workspace** h1 |
| MR-11.2 | No duplicate dashboard headers | PARTIAL | Recruitment Support + Staffing use `hideShellHeader`; full page inventory **NOT TESTED** |

### §12 Talent Pool and Staffing

| ID | Requirement | Status | Notes |
|----|-------------|--------|--------|
| MR-12.1 | Recruitment Support label + form title | **BROWSER VERIFIED** | Nav **Recruitment Support**; h1 **Recruitment Requirement Form**; company sidebar persists |
| MR-12.2 | Staffing opens directly; inline **Staffing Requirements** form | **BROWSER VERIFIED** | **`/account/enquiries`** — form visible; **Contact support** removed; `POST account.enquiries.store`; GET `/account/staffing/request` still redirects |
| MR-12.3 | Assigned Executive + company-scoped assignments | **BROWSER VERIFIED** | Root cause: `RecruiterTalentPoolAssignmentPolicy::viewAny` required `isEmployer()` while dual-role users pass `canAccessHiringWorkspace()` only → **403** on `/account/talent-pool`; fixed to `canAccessHiringWorkspace()` |
| MR-12.4 | Separate persistence | **BROWSER VERIFIED** (submit) | Staffing POST persists `employer_enquiries`; recruitment support unchanged |

### §13 Verification center and documents

| ID | Requirement | Status | Notes |
|----|-------------|--------|--------|
| MR-13.1 | Mobile/WhatsApp coming soon, not blocking | PARTIAL | Employer mobile **Not available yet** in VerificationCenter; Company profile section uses **Not verified** |
| MR-13.2 | WhatsApp sync company profile | NOT TESTED (browser) | Implemented on profile save (prior batch) |
| MR-13.3–13.5 | Document type upload + private storage + admin review | NOT TESTED | Employer uploads at `/account/company-documents`; full Verification Center lifecycle audit pending |

### §14 Social links

| ID | Requirement | Status |
|----|-------------|--------|
| MR-14.1 | Social fields read-only, excluded from save | CONFIRMED WORKING (code, prior work) |

### §15 Top bar and bottom bar

| ID | Requirement | Status | Fix |
|----|-------------|--------|-----|
| MR-15.1 | Branding PNG top-right | BLOCKED | Awaiting user asset |
| MR-15.2 | White bottom nav | **BROWSER VERIFIED** @390px | `PublicMobileBottomNav` → `bg-[#FFFFFF]` + inline `#FFFFFF`; computed `rgb(255,255,255)` |
| MR-15.3 | Global search centered, medium width | **BROWSER VERIFIED** | 5-column header grid + `max-w-xl` search column; evidence `docs/QA/evidence/2026-10-14-global-search-header-1440.png`, `…-768.png` |

### §16 Dashboard search

| ID | Requirement | Status |
|----|-------------|--------|
| MR-16.1 | Backend dashboard search, debounced | NOT TESTED | Search box visible on hiring workspace |

### §17–18 Profile links and candidate regressions

| ID | Requirement | Status |
|----|-------------|--------|
| MR-17.1 | Identity links separate from job nav | CONFIRMED WORKING (jobs workspace snapshot) |
| MR-18.1 | Candidate profile/resume/saved jobs | NOT TESTED (this pass) |

### §19 Automated evidence (this run)

| Command | Result |
|---------|--------|
| `php artisan test` (focused rectification batch) | **PASS** — 24 tests incl. `EmployerRecentJobLinksTest` |
| `npm run build` | **PASS** |
| `npx tsc --noEmit` | **PASS** |
| `GlobalDiscoverySearchTest` | Added; run with suite |

---

## Files changed (2026-10-14 urgent rectification pass)

| File | Reason |
|------|--------|
| `app/Policies/RecruiterTalentPoolAssignmentPolicy.php` | Allow hiring-workspace users (incl. dual-role) to open Assigned Executive |
| `app/Http/Controllers/Account/EmployerEnquiriesController.php` | Inline staffing form props + `store()` |
| `routes/account.php` | `POST /account/enquiries` |
| `resources/js/Pages/Account/Enquiries.tsx` | Embed form; remove Contact support |
| `resources/js/Components/Account/Staffing/StaffingRequirementsForm.tsx` | Reusable staffing form |
| `resources/js/Components/Public/Header.tsx` | Balanced grid for centered search |
| `resources/js/Components/Public/GlobalHeaderSearch.tsx` | Remove inner `max-w-md` cap |
| `tests/Feature/RecruiterWorkspaceEmployerNavTest.php` | Dual-role talent-pool + staffing form/submit |

| Command | Result |
|---------|--------|
| `php artisan test tests/Feature/RecruiterWorkspaceEmployerNavTest.php` | **PASS** (5 tests) |
| `php artisan test` (talent-pool + employer account batch) | **PASS** (13 tests) |
| `npx tsc --noEmit` | **PASS** |
| `npm run build` | **PASS** |
| Browser @ `127.0.0.1:8000` (`qa-employer-a@example.test`) | Assigned Executive **200**; Staffing form **visible**; header search **1440/768** screenshots |

---

## Files changed (2026-10-14 master pass)

| File | Reason |
|------|--------|
| `app/Services/PublicSocialSearchService.php` | `discoverySearch()` excludes jobs from header suggest |
| `app/Http/Controllers/PublicSocialSearchController.php` | Suggest uses discovery search |
| `resources/js/Components/Public/GlobalHeaderSearch.tsx` | Tabs All/Companies/Profiles; no View All; no job results; bounded width |
| `resources/js/Components/Public/JobCard.tsx` | 3-column grid; vertically centered logo/actions |
| `resources/js/Components/Public/PublicMobileBottomNav.tsx` | Solid white bottom bar |
| `resources/js/Pages/Account/Settings/Index.tsx` | Employers: mobile unverified ≠ “Action required” on security card |
| `tests/Feature/GlobalSearchSuggestTest.php` | Assert empty jobs in suggest |
| `tests/Feature/EmployerUnverifiedTrialPublishTest.php` | Activation includes `industry_id` |
| `tests/Feature/GlobalDiscoverySearchTest.php` | Regression: jobs excluded from suggest when company matches |

---

## End-to-end recruitment workflow (2026-10-14)

| WF | Topic | Status | Evidence |
|----|-------|--------|----------|
| E2E-1 | Admin assign → Assigned Executive | **BROWSER VERIFIED** (recruiter page) + PHPUnit | `EndToEndRecruitmentWorkflowTest`, `RecruiterTalentPoolAssignmentTest` |
| E2E-2 | Job publish + public visibility | **IMPLEMENTED — NOT VERIFIED** (browser) | E2E WF-B PHPUnit |
| E2E-3 | Multi-candidate / multi-job matrix | **BROWSER VERIFIED** (PHPUnit) | E2E WF-C/D |
| E2E-4 | In-app notifications | **BROWSER VERIFIED** (PHPUnit) | E2E WF-E, `EmployerSelfServiceRecruitmentTest` |
| E2E-5 | Offer/Joined employer self-service | **BLOCKED** (by design — admin ATS) | See `EmployerHiringStage25Test` |

Full report: **`docs/QA/END-TO-END-RECRUITMENT-WORKFLOW-REPORT.md`**

---

## Open blockers

1. **MR-15.1** — Final OurNear branding PNG not in workspace.  
2. **MR-5.2** — Full responsive job card screenshot matrix.  
3. **MR-11.2** — Systematic duplicate-header audit across all dashboard routes.  
4. **MR-13.3–13.5** — End-to-end confidential document verification in Verification Center (vs company-documents page).  
5. **MR-5.2 / MR-18** — Full 5-width job-card screenshot matrix + candidate Personal Dashboard 2-col browser pass.  
6. **MR-13.3–13.5** — Verification Center document lifecycle browser proof.  
7. **Live domain** `ournear.app` share reachability — not verified in this local audit.
