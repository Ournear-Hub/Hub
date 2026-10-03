# OurNear Hire — Current Development State

Read this first. Code is the source of truth. This file is a high-signal snapshot for developers and AI agents.

**Last documentation audit:** 2026-09-29 (complete knowledge-base pass; docs 24–25 added).
**Application code modified in that audit:** NO

---

## Project Identity

| Field | Value |
| --- | --- |
| Product | OurNear (platform) · OurNear Hire (hiring module) · OurNear Jobs (job board) |
| Code root | Repository root (`app/`, `resources/js/`, `routes/`) |
| Release label | V1.1 line in working tree |
| Git branch (typical) | `main` |
| Canonical user model | `App\Models\User` only |

[CODE IS SOURCE OF TRUTH — EXISTING DOCUMENTATION CONFLICT]: `V1.1_DEVELOPMENT_STATE.md` still describes “latest completed phase A.9” and “next V1.2 Candidate Portal”. The live tree already contains candidate/employer account, ATS operations, CRM, partners, investors, campaigns, WhatsApp OTP, unified profile, and Stage 24 recruitment automation.

---

## Current Version

- **Laravel:** `^12.0` (`composer.json`)
- **PHP:** `^8.2` (project uses PHP 8.4 locally in recent work; [NOT VERIFIED] exact runtime on every machine)
- **Inertia Laravel:** `^3.0` with `@inertiajs/react` `^3.7`
- **React:** `^19.2`
- **TypeScript:** `~5.8.3`
- **Vite:** `^7.0.7`
- **Tailwind:** `^4.0` via `@tailwindcss/vite`
- **PHPUnit:** `^11.5`

---

## Current Development Status

**IMPLEMENTED:** Dual-guard auth, public site + CMS, candidate and employer accounts, admin ATS through finance, screening match/comparison, CRM, channel partners, investors, campaigns/referrals, mobile OTP + WhatsApp OTP foundations, unified public profile, recruitment automation foundation, Stage 25 Admin/RBAC hardening, Stage 26 Recruiter Hiring Command Center, Hire display identity (`ON-HIR-*`), Admin bulk resume import, **internal external-resume foundation** (`internal_candidate_resumes` + `InternalCandidateResumeService`, private `candidate_files` disk, no User creation; Admin UI not yet), public destinations `/companies`, `/hire`, `/talent-pool`, and `/staffing`.

**IN PROGRESS / PARTIAL:** Final platform UX. Account employer Hiring UX completion (Stage 22): employer **application received** in-app notification via existing `DatabaseNotification` (`EmployerNotificationService` + `EmployerLifecycleNotification` + `EmployerNotificationEvent::ApplicationReceived`, hooked from `RecruitmentNotificationService::notifyApplicationSubmitted()` after `ApplicationSubmissionService` persist; recipient = job owner `job.employer.user`; fingerprint dedup `employer_application_received:application_id=…`; URL `account.employer.jobs.application`; candidate notifications unchanged). MySQL `ONLY_FULL_GROUP_BY` fix for talent-pool stage counts (`EmployerApplicationPipeline::summaryForEmployer` uses qualified `applications.status` on `toBase()` query). Tests: `EmployerHiringStage22Test` (6 cases); full suite **643** tests / **11774** assertions (2026-10-01). Browser QA on local `127.0.0.1:8000` (employer `stage21-qa@example.com`): hiring command center, grouped mobile Menu drawer, all primary hiring routes (jobs tabs, create job form, applications, talent pool including `?stage=shortlisted` after 500 fix, submissions, enquiries, company profile view, verification, notifications empty state); horizontal overflow checks at **390×844**, **412×915**, **1366×768**, **1440×900** on sampled hiring surfaces — not every sidebar link clicked individually at all four sizes; no live application-detail or post-apply bell verification (no applications on QA employer). Account employer Hiring UX completion (Stage 21): employers (and hiring-enabled users) use existing `DatabaseNotification` + `/account/notifications` (policy extended via `canAccessHiringWorkspace()`); `accountAlerts` + notification bell in `AccountLayout`; `EmployerAccountNav` includes Notifications; candidate-with-hiring keeps candidate nav on `/account` but gets grouped `employerNav` on hiring workspace routes; company profile defaults to **view** with “Edit company profile”; application contact email/mobile from immutable application snapshot on employer application detail + talent pool hints (no separate Request Access/Unlock backend—public talent directory remains separate); employer ATS status changes remain staff/admin (UI explains on application detail). Tests: `EmployerHiringStage21Test`. Browser QA on local `127.0.0.1:8000` (employer login, `/account/hiring`, notifications bell, grouped sidebar)—not all four viewports exhaustively checked. Account employer Hiring Command Center (Stage 20): grouped `EmployerAccountNav` (HIRING / TALENT POOL / SERVICES / ACCOUNT) via shared `employerNav`; `/account/hiring` shows real KPI command center whenever an `employerProfile` exists, with compact activation strip + inline setup when `HiringActivationService` is not fully active (protected posting still gated by `EmployerPostingEligibilityService`); employer-wide **Applications** at `/account/hiring/applications` and **Talent Pool** at `/account/hiring/candidates` (owned job applications only—maps stages to `ApplicationStatus`, “In review” = Reviewing; not public `/talent-pool`); jobs index tabs `all|active|draft` via `?listing=`; tests `EmployerHiringStage20Test`. Browser/responsive QA for Stage 20 hiring surfaces not run in the Stage 20 pass. Candidate account finalization (Stage 19): grouped `CandidateAccountNav` (no personal Talent Pool link; hiring link only when `canAccessHiringWorkspace()`), profile share sheet (copy/WhatsApp/email/native), public profile owner vs visitor (`viewer.is_owner`), registration terms acceptance + employer work-email hint, Hire Resume maps educations/projects/internship-split experience and drops `@username`, compact notifications, application list without redundant resume download. Technical stabilization (Stage 18): full PHPUnit suite aligned with current product rules (single active library resume, screening pipeline nine stages, no public WhatsApp apply CTA); public jobs list cards expose a real `/jobs/{slug}` link on titles in list mode (desktop split still uses `?job=` via `preventDefault`); resume re-upload updates title/visibility when provided. Responsive QA (Stage 17): shared `PageContainer` `min-w-0`, jobs list touch targets (`JobActions`/`SaveJobButton`), job title wrapping on cards; browser QA on local `127.0.0.1:8000` (public, jobs split/detail, candidate account/profile, overflow checks at 390/1366). Admin release readiness (Stage 16): `/admin` dashboard uses real `DashboardService` metrics with **Action required** (`pending_actions`) first, staff greeting, quick access links, then operational summary, pipeline, and recent applications/staffing enquiries; sidebar labels **Recruitment Partnership** (`/admin/submissions`) and **Staffing Enquiries** (`/admin/employer-enquiries`); architecture unchanged (dual guard, existing modules, four-line Hiring Command Center at `/admin/hiring`). Public `/jobs` uses `PublicPageHero`, `JobsSearchPanel` (keyword, location, expandable filters), real result counts, and on `lg+` a split list/detail workspace with independent scroll and `?job=` selection; mobile list navigates to `Jobs/Show`. Public apply flow: `JobActions` → `/jobs/{slug}/apply` (`Jobs/Apply.tsx`) for resume upload and submit via existing `ApplicationSubmissionService`; success confirmation on job detail via flash + `ApplicationConfirmation`. Candidate `/account/resumes` is a Resume Hub (Hire Resume preview via `ResumeBuilderService` + single My Resume upload/replace via `CandidateResumeLibraryService`); `/account/resumes/builder` shows `ResumeReadinessPanel` and print/PDF via browser. Candidate `/account/profile` uses `ProfileCompletionCenter` (module cards + next step from `User::profileCompletionItems()` / `profile_completion`) with one-module editing via existing `CandidateProfessionalSections` and basic/about forms. Public `/hire` routes signed-in users to `/account/hiring` (Stage 10 entry). Account `/account/hiring` shows onboarding until hiring services activate, then an employer Hiring Command Center reusing `AccountDashboardService::employerHiringCommandCenter()`. Staff recruiters use `/admin/hiring` (`Admin/Hiring/CommandCenter.tsx`) with the existing `RecruiterHiringCommandCenterService` payload—Stage 10 refines hierarchy only. Employer job applicants (`/account/jobs/{job}/applicants`, Stage 11–12) use server-side keyword/status/`stage` filters, authoritative `EmployerApplicationPipeline` stage counts, `EmployerApplicantPipeline` tabs, `RecruiterCandidateSummary` (primary **View application** → `/account/jobs/{job}/applications/{application}`), and public profile links with applicants/application back-context query params; employer application detail reuses `CandidateApplicationViewService::timeline()` and authorized resume snapshot routes (employer status changes remain staff/admin via existing `ApplicationPolicy`). Recruitment Partnership (`/account/submissions`, Stage 13) is a service workspace over existing `EmployerSubmission` records: job-grouped requirements via `EmployerRecruitmentPartnership`, summary metrics, server-side filters, requirement detail at `/account/submissions/requirements/{job}`, submission detail at `/account/submissions/{submission}`, `RecruiterCandidateSummary` in submission mode, support CTA to `/contact` (distinct from Direct Hiring / ATS and from staffing `/account/enquiries`). Account settings (Stage 15): `/account/settings` hub; **Verification & security** at `/account/settings/security` reuses email resend, `MobileVerificationService` OTP, optional WhatsApp number display (no required WhatsApp verification in UX), `HiringActivationService` gate for employers; six-digit OTP UI; notifications at `/account/notifications`; candidate communication preferences at `/account/settings/communication` (`CommunicationChannel` only). Staffing Services (`/account/enquiries`, Stage 14) is a workforce-request workspace over existing `EmployerEnquiry` records: `EmployerStaffingServices` summary counts, server-side keyword/status filters, request cards, detail at `/account/enquiries/{id}`, primary CTA to `/for-employers` (existing enquiry form) plus `/contact` support (distinct from Recruitment Partnership and ATS). Public `/talent-pool` supports keyword/location search over share-enabled profiles only (distinct from employer applicants and admin `admin/candidates`). Candidate `/account` (Stage 9) is a compact command center: welcome header, dominant next-action card (`resolveCandidateNextAction`), compact `ProfileCompletionCenter` + resume status, application status summary + recent list, saved-job previews, notifications unread count via shared `accountAlerts`, and email verification link—data from existing `AccountDashboardService::candidateDashboard()` (plus grouped application status counts). Public primary nav (`navigation.ts`): Jobs, Companies, Hire (`/hire`), Talent Pool, Staffing, About. Responsive public header (desktop links + mobile drawer) and candidate mobile bottom nav (Home, Jobs, Applications, Profile, More from `candidateNav`). Public homepage (`Home.tsx`) uses CMS hero + job search, `JobCard` listings, employer spotlight cards, Hire/Talent Pool/Staffing CTAs, and factual “Why OurNear” copy (no invented metrics). Public marketing pages share `PublicPageHero`, `PublicCompanyCard`, and Stage 1 section/card patterns; public company labels use `EmployerProfile::publicDisplayCompany()` (no “Unnamed employer” on public surfaces). Contact stays at `/contact` and the footer. Admin chrome reads OurNear Admin. The Admin sidebar groups existing routes and keeps its own scroll, with the active item scrolled into view. A full job-to-joining recruitment run on local data has not been completed.

**NOT BUILT IN THIS PASS:** A second user identity, a second notification backend, an unlimited resume library, or fake staffing operations such as a new attendance product. `/freelancers`, `/services`, and `/for-employers` remain available; they are no longer primary navigation.

**PARTIALLY IMPLEMENTED:** Company communication settings migration `2026_09_28_120000` may still be pending on some local DBs; WhatsApp Cloud send is gated on `WHATSAPP_PROVIDER`; recruitment automation orchestration exists but does not auto-hire.

**FOUNDATION ONLY:** `UserRole::Partner` and `UserRole::Investor` on `users.role` without dedicated account portals; SMS OTP driver default `unconfigured`; no `app/Jobs` classes.

**PLANNED:** Visual automation workflow builder; WhatsApp recruitment messaging (OTP must not be reused); Search & Discovery / public platform redesign.

---

## Technology Stack

See [01-SYSTEM-ARCHITECTURE.md](01-SYSTEM-ARCHITECTURE.md). Single Laravel app, Inertia SSR-less SPA pages, MySQL in local `.env.example`, SQLite in-memory for PHPUnit.

---

## Current Architecture

```text
Browser (Inertia/React)
  → routes/web.php + account.php + admin.php
  → UseAuthChannel (admin vs account guard)
  → middleware aliases (admin, account.candidate, …)
  → Controllers + Form Requests + Policies
  → Services (no repository layer)
  → Eloquent models
  → MySQL / SQLite
```

No Laravel domain Events/Listeners except `MessageSending` mail identity. No `app/Jobs`. Scheduler: hourly `recruitment:send-reminders`.

---

## Authentication State

**IMPLEMENTED.** Dual session guards `admin` and `account`, same `users` provider. Default guard `account`. See [02-AUTHENTICATION-ARCHITECTURE.md](02-AUTHENTICATION-ARCHITECTURE.md).

**Do not** add a second User model or merge guards.

---

## Authorization State

**IMPLEMENTED.** `users.role` = `admin` | `candidate` | `employer` | `partner` | `investor`. Staff RBAC via `roles` / `permissions` / `PermissionName` (`{module}.{view|manage}`). 34 permissions including `automation.view` / `automation.manage`. Catalog: `app/Support/AccessCatalog.php`. Bootstrap: `AuthorizationBootstrapper` (catalog permission **floor** via `syncWithoutDetaching`; Super Admin still full `sync`; `roles.manage` exclusive to Super Admin). Privilege escalation blocked on role assignment and permission grants. Operational analytics requires more than website/notifications access (`User::canViewOperationalAnalytics()`).

---

## Candidate State

**IMPLEMENTED.** Registration, username, profile, professional profile, skills, employment, certifications, languages, education, projects, resume library, documents, saved jobs, applications, notifications, communication preferences, relationship reviews, mobile/WhatsApp OTP settings, public share flags.

Public Hire display ID is `OurNearHireIdentity`: one immutable `users.person_number`, shown as `ON-HIR-CAN-###` / `ON-HIR-REC-###` / `ON-HIR-USER-###`. Existing `business_id` values (`ONT-*`) stay internal. Account verification entry is `/account/settings/security` (Laravel email verify + resend, mobile OTP via `verification_challenges`, optional WhatsApp). Verified users change email through `users.pending_email` + signed `POST /account/settings/email/change` / `GET /account/settings/email/verify-change/{id}/{hash}`; unverified users update `email` directly then receive standard `VerifyEmail`.

---

## Employer/Recruiter State

**IMPLEMENTED (split):**

- **Account employer:** company jobs, applicants (restricted), submissions index, documents, reviews. Isolation by `employer_profiles`.
- **Admin/staff recruiter:** full ATS. Not a separate Recruiter user type. Staff roles include Recruiter / Recruitment Manager.

No multi-company membership for one employer user. Recruiter Hiring Command Center: **IMPLEMENTED** at `/admin/hiring` (orchestration over existing ATS/partnership/staffing/Jobs; not a fifth engine).

---

## Jobs State

**IMPLEMENTED.** `job_listings` via `App\Models\Job`. Publishing: `JobPublishingStatus` draft/published/paused/closed/archived. Employer posting eligibility + admin lifecycle. Public listing when published.

---

## Applications State

**IMPLEMENTED.** Status enum (exact): `New`, `Reviewing`, `Shortlisted`, `Interview`, `Selected`, `Placed`, `Rejected`, `Withdrawn`. Pipeline: `ApplicationPipelineService`. Screening match: `ScreeningMatchService` (explainable factors, disclaimer). Shortlist is human-controlled.

---

## Resume/Documents State

**IMPLEMENTED.** Private disks: `resumes`, `candidate_files`, `employer_files`, `freelancer_files`. Application `resume_path` is a snapshot, not the live library file.

---

## Notifications State

**IMPLEMENTED:** database notifications (admin + candidate), recruitment mail, admin internal messages.
**FOUNDATION:** WhatsApp Cloud API + OTP templates; SMS OTP unconfigured.
**NOT** recruitment WhatsApp campaigns.

---

## Integrations State

See [14-EXTERNAL-INTEGRATIONS.md](14-EXTERNAL-INTEGRATIONS.md). Email (SMTP/log), Meta WhatsApp Cloud, optional Turnstile, optional analytics IDs. No payment gateway; finance is internal invoices/payments.

---

## Frontend State

Inertia React pages under `resources/js/Pages`. Admin layout + Account layout + Public layout. Types under `resources/js/types`. No Redux; Inertia props + `useForm`.

---

## Database State

MySQL 8 intended for app; tests use SQLite `:memory:`. Many tables from staged migrations. **Local risk:** some 27 Sep migrations may be pending while later 28 Sep files already ran (`2026_09_27_220000`–`270000`, `2026_09_28_120000`). Stage 24 tables: `recruitment_automations`, `recruitment_automation_executions`. Stage 26 did not run those pending files. Command Center interview counts fall back to `outcome` when `lifecycle_status` is absent.

---

## Testing State

PHPUnit Feature + Unit. Last **cited** full run (Stage 22 hiring pass, 2026-10-01): **643** passed / **11,774** assertions. Focused QA utility tests: `QaHiringFixturesTest` (4 tests). **[NOT VERIFIED]** full suite on every machine after Stage 23B.

---

## LOCAL QA DATA (Hiring)

**Command:** `php artisan qa:hiring-fixtures`
**Allowed environments:** `local`, `testing` only — **refuses** `production` (`APP_ENV=production`).
**Purpose:** Deterministic employer A/B + candidate + published/draft jobs + one real application (via `ApplicationSubmissionService::submitFromProfile`) and employer in-app notification (existing `EmployerNotificationService` lifecycle). Idempotent: re-run updates the same `@example.test` identities and fixed job slugs without duplicating QA users/jobs/applications.

| Role | Email | Password (local QA) |
| --- | --- | --- |
| Employer A | `qa-employer-a@example.test` | `Password1!` (printed by command) |
| Employer B | `qa-employer-b@example.test` | same |
| Candidate | `qa-candidate@example.test` | same |

**Job slugs:** `qa-fixture-published-a`, `qa-fixture-draft-a`, `qa-fixture-published-b`.
**Implementation:** `App\Support\QaHiringFixtures`, `App\Console\Commands\QaHiringFixturesCommand`. Does not touch non-QA records.

**Talent Pool / privacy (Stage 24):** Employer Talent Pool at `/account/hiring/candidates` lists **owned job applications** only (`EmployerVisibleCandidate::fromApplication`). Stages map to `ApplicationStatus` via `EmployerApplicationPipeline::talentPoolStages()` (Reviewing = “In review”; Selected = Offered; Placed = Joined; no separate Contacted status). Contact email/mobile on cards and application detail come from **`application_contact` snapshot** on the application record—not live `users.email` / `users.mobile`. No Request Access / Unlock Candidate product flow in codebase; employer path is application snapshot + optional public profile when share-enabled. Tests: `EmployerHiringStage24Test` (6 cases, uses `qa:hiring-fixtures`). Browser QA on QA accounts: run fixtures then sign in as Employer A for Talent Pool smoke.

**Direct Hiring / ATS employer workspace (Stage 25):** `/account/hiring/applications` lists employer-scoped applications; detail at `/account/jobs/{job}/applications/{application}` (`Account/Employer/Applications/Show`) shows read-only pipeline copy (status changes via staff/admin only—no employer status POST routes). Resume via `account.employer.applications.resume` (application snapshot file). Employer notification `account.notifications.read?redirect=1` redirects to the application URL from Stage 22 payload. Tests: `EmployerHiringStage25Test` (8 cases, `qa:hiring-fixtures`). Responsive/browser ATS pass: manual login recommended when automation is flaky.

---

## Deployment State

Shared-hosting oriented (Hostinger mentioned in `.env.example` comments). Web root `public/`. Leftover tree `ournear_app_Hostinger_2026-09-19/` must **not** be deployed. Queue default `database`; cron for scheduler. No in-repo GitHub Actions workflow found at audit time. [NOT VERIFIED] production host details.

---

## Recently Completed Work

Stage 23E WhatsApp OTP, 23F unified profile, Stage 24 recruitment automation foundation, Stage 25 Admin/RBAC, Stage 26 Recruiter Hiring Command Center.

---

## Current Work

No application coding in the documentation-only audit. Product-wise: Command Center and public platform destinations exist in code; a full local job-to-joining recruitment run is [NOT VERIFIED].

---

## Next Planned Work

Typical follow-ons: WhatsApp recruitment messaging (separate from OTP), apply remaining pending **27 Sep / 23B** migrations **intentionally** (do not bundle), Search & Discovery / public platform redesign, visual automation builder.

---

## Known Problems

See [17-KNOWN-ISSUES-TECHNICAL-DEBT.md](17-KNOWN-ISSUES-TECHNICAL-DEBT.md). Highlights: stale `V1.1_DEVELOPMENT_STATE.md`; leftover Hostinger copy; local migration order gaps (27 Sep pending while later 28 Sep applied); no queue worker classes; automation is synchronous after commit.

---

## Important Architectural Constraints

1. Dual guards; one `User` model.
2. Employer data isolation.
3. Candidate privacy / private files.
4. Do not rebuild screening/matching.
5. Do not auto shortlist/select/offer via automation.
6. Do not reuse WhatsApp OTP for recruitment chat.
7. Do not run unrelated pending migrations as a bundle.
8. Do not commit secrets or push unless asked.

---

## High-Risk Areas

- `config/auth.php`, `UseAuthChannel`, account/admin login controllers
- `ApplicationPipelineService`, `ScreeningMatchService`
- File download controllers and disks
- WhatsApp webhook CSRF exception
- `AuthorizationBootstrapper`
- Production `.env` (never document values)

---

## Files/Directories Requiring Care

- `app/Models/User.php`
- `bootstrap/app.php`
- `routes/*.php`
- `app/Services/ApplicationPipelineService.php`
- `app/Services/ScreeningMatchService.php`
- `app/Services/RecruitmentAutomation*.php`
- `ournear_app_Hostinger_2026-09-19/` (legacy dump)

---

## Last Documentation Audit

2026-09-29 documentation-only completeness pass. Added [24-HIRING-COMMAND-CENTER.md](24-HIRING-COMMAND-CENTER.md) and [25-HIRE-IDENTITY-AND-RESUME-IMPORT.md](25-HIRE-IDENTITY-AND-RESUME-IMPORT.md). Index: [DOCUMENTATION-INDEX.md](DOCUMENTATION-INDEX.md). Agent entry: [AI-READING-GUIDE.md](AI-READING-GUIDE.md), repo `AGENTS.md`.
