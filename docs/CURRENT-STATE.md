# OurNear Hire — Current Development State

Read this first. Code is the source of truth. This file is a high-signal snapshot for developers and AI agents.

**Consolidated rectification (2026-10-16, code only — tests not re-run in the follow-up pass):** Employer ATS offer and joining stay on the existing placement workflow (`EmployerApplicationWorkflowService` + `PlacementService::createForEmployerApplication`, migration `2026_10_16_210000_*`). Account `/account/talent-pool/jobs*` redirects to hiring; Admin **`/admin/talent-pool-jobs`** is the Talent Pool job create path. Showcase cards can optionally link one **`employer_profiles`** row (`2026_10_16_220000_*`, unique, nullable) so Home / Talent Pool / Staffing placements share a canonical company; unlinked historical cards are not auto-merged. Offer/joining code was verified afterward: full suite **1156 passed / 3 skipped / 0 failed**, plus TypeScript and Vite **PASS**. The showcase-link migration and its new test were added after that suite and are **not** in those results. Migrations `210000` and `220000` were **not** applied in that run.

**PHPUnit skips (default `phpunit.xml`, 2026-10-16 Batch 7 closure):** **3 skipped** — (1) `ApplicationSubmissionConcurrencyTest::test_concurrent_apply_posts_create_at_most_one_application` — requires `APP_ENV=local` + live server + `qa:hiring-fixtures`; (2) `BrevoMailConfigurationTest::test_brevo_smtp_handoff_when_credentials_are_configured` — requires `MAIL_MAILER=smtp` + Brevo creds + `MAIL_TEST_TO` (CI uses `array`); (3) `LiveBrevoEmailVerificationQaTest::test_live_brevo_email_verification_qa_flow` — live SMTP QA only. **Full suite after employer offer/joining (2026-10-16, sqlite `:memory:`):** **1156 passed / 3 skipped / 0 failed** (17102 assertions, ~817s). **`npx tsc --noEmit`** and **`npm run build`** **PASS** on that same code. The later showcase-to-employer link (`2026_10_16_220000_*` and `test_showcase_card_reuses_one_employer_profile_across_placements`) was added after that run and is **not** included. Prior Batch 7 **1151** count is historical.

**Batch 7 — Staffing follow-ups, client messaging & notifications (2026-10-16):** Reuses **`recruitment_activities`** (`RecruitmentActivityType::StaffingFollowUpRecorded`, `RecruitmentOperationalVisibility` internal vs **`EmployerShared`**) via **`StaffingOperationalFollowUpService`**; client coordination via **`ConversationContextType::StaffingSubmission`** + **`StaffingCoordinationMessagingService`**; access via **`RecruiterOperationalAccess`** + existing policies (internal notes never in employer Inertia). Admin **`POST /admin/staffing-candidate-submissions/{submission}/follow-ups`**, **`POST …/messages`**; employer **`POST /account/staffing-submissions/{submission}/messages`** + shared follow-ups on show. Notifications: submit (**`notifyStaffingCandidateSubmitted`**), messages (**`notifyStaffingSubmissionMessage`**), confirmed joining (**`notifyStaffingJoiningConfirmed`** on **`StaffingCandidateSubmissionService::confirmJoining`**). UI: **`Admin/StaffingCandidateSubmissions/Show`**, **`Account/StaffingSubmissions/Show`** (timeline, follow-up form, conversation). Tests: **`StaffingOperationsBatch7Test` 7/7**; focused Batch 5–7 bundle **37/37** (452 assertions); **`npx tsc --noEmit`** + **`npm run build` PASS** (~2m). **Browser:** admin login + **`/admin/staffing-workspace`** **PASS** (local `127.0.0.1:8000`); submission detail follow-up/messaging journey **NOT RUN** — **0** rows in local MySQL **`staffing_candidate_submissions`** (no seeded submission to open). **Regression fixes (tests only):** `ProductionReadinessStage11Test` stub **`staffingSubmission`** param; `EndToEndRecruitmentWorkflowTest` internal-recruiter admin visibility; `EditorialWebsiteContentTest` tabs include showcase admin tabs.

**Batch 6 — Staffing operations end-to-end (2026-10-16):** **`staffing_candidate_submissions`** + status events + **`employer_enquiries.fulfilled_joinings_count`** (migration `2026_10_16_190000_*`). Admin **`GET /admin/staffing-workspace`**, **`GET /admin/staffing-candidate-submissions/{id}`**, **`POST /admin/talent-pool-assignments/{assignment}/submit-to-staffing-client`**. Client **`/account/enquiries`** (requirements + fulfilment on show), **`/account/staffing-submissions/{id}`** (review, stage decisions, joining confirmation with date — not inferred from selection alone). **`StaffingCandidateSubmissionService`**: duplicate-active guard, client-attributed transitions, idempotent fulfilment counting, requirement **Completed** when joinings ≥ `number_of_people`. Staffing allocations **no longer require** shortlisted ATS application. Admin allocation UI: **Submit to client** on staffing assignments. Distinct from Talent Pool company submissions and job applications. Tests: **`StaffingOperationsBatch6Test` 9/9** + Batch 3–5 regression **24/24**; **`npx tsc --noEmit`** + **`npm run build` PASS**; browser client/admin staffing smoke **PARTIAL** (Batch 7 — workspace only).

**Batch 5 — employer Hiring/ATS completion (2026-10-16):** **`/account/hiring`** workspace: primary **Create Job** hero when `jobs_count === 0`, quick links (Jobs, Applications, Company profile, OurNear submissions), renamed **Applicants by stage** (distinct from coordinated Talent Pool). **Next actions** point to **`/account/company`** (not personal profile) and **`/account/hiring/applications`** for applicant review. Application **timeline** includes **`actor_name`** on employer application detail (`CandidateApplicationViewService`). Existing employer job/applicant/self-service recruitment, compare, resume auth, unverified publish policy unchanged. Tests: **`EmployerHiringAtsBatch5Test` 6/6** + **`EmployerUnverifiedTrialPublishTest` 4/4** + Batch 3–4 talent pool **15/15**; **`npx tsc --noEmit`** + **`npm run build` PASS**; browser ATS smoke **NOT RUN**.

**Batch 4 — recruiter workspace, follow-ups & coordination (2026-10-16):** Admin **`GET /admin/talent-pool-workspace`** (scoped line **Recruiter** overview: companies, assignments, submissions, overdue follow-ups). **`GET /admin/talent-pool-company-submissions/{submission}`** — ownership/conflict display, employer-attributed status timeline, internal vs employer-shared follow-ups (`recruitment_activities` + `operational_meta` / `visibility`; migration `2026_10_16_180000_*`), employer messaging via **`ConversationContextType::TalentPoolSubmission`**. Employer **`POST /account/coordinated-talent-pool/{submission}/messages`** + shared follow-ups on show (internal notes never in Inertia). Notifications: **`TalentPoolCoordinationNotificationService`** (company assigned, submission created, employer response/message). Line recruiter submission **`view`** policy scoped even when role carries manage permissions. Tests: **`TalentPoolRecruiterWorkspaceTest` 6/6** + Batch 3 workflow **9/9**; **`npx tsc --noEmit`** + **`npm run build` PASS**; browser smoke **NOT RUN**.

**Batch 3 — Talent Pool company submission + employer review (2026-10-16):** **`talent_pool_company_submissions`** + **`talent_pool_submission_status_events`** (migration `2026_10_16_160000_*`; short MySQL FK names). **Ownership (`TalentPoolSubmissionOwnerResolver`):** explicit admin-selected internal recruiter → **`explicit_admin`**; else active **candidate** talent-pool assignment owner → **`candidate_assignment`** (wins over company relationship recruiter; if both differ → **`candidate_over_company_conflict`** with both IDs stored, assignments unchanged); else active **company relationship** recruiter → **`company_assignment`**; else **`unassigned`** (non-admin submit blocked). Admin **`POST /admin/talent-pool-company-submissions`** and **`POST …/talent-pool-assignments/{assignment}/submit-to-company`**; internal recruiter submit via assignment service → **`TalentPoolCompanySubmissionService`** (blocks duplicate **active** same candidate+company; links assignment `talent_pool_company_submission_id` + progress **`SubmittedToEmployer`**; **no** `Application` / staffing). Employer **`GET/PATCH /account/coordinated-talent-pool/{submission}`** + resume route; nav **OurNear submissions** (`EmployerAccountNav`). Admin Talent Pool UI: submit-to-company + recent submissions. **Visibility fix:** scoped internal recruiters see candidates with **active assignments** (`assigned_recruiter_user_id` on candidate query). Legacy **`submit-to-employer`** on assignments remains partnership/ATS path (`EmployerSubmission` + application). Tests: **`TalentPoolCompanySubmissionWorkflowTest` 9/9** + Batch 2 bundle **20/20 PASS** (210 assertions); **`php artisan migrate`** OK on local MySQL; **`npx tsc --noEmit`** + **`npm run build` PASS**; test stub **`ProductionReadinessStage11Test`** aligned with `RecruitmentActivityService` talent-pool param. **Batch 4 dependency:** full recruiter follow-up/comms UI on submissions (activities foundation only here). **Not exposed to employers:** submission `internal_notes`. **Not run:** full PHPUnit suite; browser employer coordinated-pool smoke.

**Batch 2 — admin ops + recruiter ownership (2026-10-16):** Admin sidebar regrouped into **Core Operations** (Dashboard, Companies, Candidates, Recruiters), **Recruitment Operations** (Hiring / ATS, **Talent Pool** at `/admin/talent-pool-assignments`, Staffing), **Platform Management** (Website Content, Media & Social, notifications/templates, Master Data, Settings). **OurNear internal recruiters** = admin staff (`OurNearInternalRecruiters`); employer accounts are not valid assignees for new Talent Pool or company-relationship assignments. **Company → recruiter:** `company_relationship_recruiter_assignments` + Admin **Companies** verification tab + Talent Pool company picker (history preserved on reassignment). **Candidate → recruiter:** existing `recruiter_talent_pool_assignments` (Talent Pool / General types do not create submissions or applications). Line **Recruiter** staff role workspace scoped to own assignments on admin Talent Pool. Admin routes: assignment status/archive for internal recruiters. Tests: **`AdminNavigationCatalogTest`**, **`RecruiterTalentPoolAssignmentTest`**, **`CompanyRelationshipRecruiterAssignmentTest`**, **`QaRecruitmentJourneyProofTest`** (focused batch **19/19 PASS**); **`npx tsc --noEmit` PASS**.

**Company showcase + homepage statistics (2026-10-16):** Admin → Website Content tabs **Homepage Statistics**, **Home Page Companies**, **Talent Pool Companies**, **Staffing Companies**; shared `showcase_companies` records with per-surface assignment; manual `homepage_statistics` (never auto-counted). Public: **OurNear Hire at a Glance**, **Our Companies** (home order: hero → glance → industries → companies → rest), **Talent Pool Companies**, **Staffing Companies**. Tests: **`ShowcaseCompaniesAndHomepageStatisticsTest`** (4). **`QaHiringFixtures`:** Employer A pagination jobs **15** extras (16 total) for Stage 1 pagination.

**Release-critical continuation (2026-10-16):** Admin branding — media-library assign (`logo_media_id` / `wordmark_media_id`), `BrandMark` public `logosOnly` (configured image or empty spacer; no text substitute); **`WebsiteBrandingSettingsTest`** extended; **`QaRecruitmentJourneyProofTest`** (fixture candidate → employer resume → admin → recruiter assignment). PHPUnit **1100 passed / 3 skipped / 0 failed** (758s); **`npx tsc --noEmit`** + **`npm run build`** PASS; Playwright Stage 1 **`test_stage1_public_website.py`**: **27 passed / 0 failed / 1 skipped** (pagination skip when &lt;10 Employer A jobs) after selector updates (local `127.0.0.1:8000`, `OURNEAR_ALLOW_RUN=local`).

**Unified identity, staffing intake, branding CMS (2026-10-16):** Public **`/staffing`** restores guest **Submit a staffing requirement** (`EmployerManpowerRequestForm` → **`POST /for-employers`** with `from_staffing=1`, reference in flash; no login/email-verify/pre-approval). **`BrandMark`** safe header/footer fallbacks + admin **Branding & website assets** labels/filenames; shared props **`header_logo_url`** / **`footer_logo_url`**. Admin user 360 adds **Personal details**, **Professional profiles**, and **Application access and roles** sections. API doc: unified identity + web-only staffing intake in **`docs/11-API-AND-ROUTES.md`**. Tests: **`PublicStaffingGuestSubmissionTest`**, updated **`PublicPlatformPagesTest`** / **`WebsiteBrandingSettingsTest`**.

**Public website finalization (2026-10-15 evening):** **`EmployerWorkspaceDestination`** + GET-only **`EnsureEmployer`** activation redirects; **`resolvePublicCmsHref`** on home hero/promo CMS links; **`GET account.settings.communication`** restored for employer comm prefs (was dead redirect); staffing public form removed; editorial CMS tabs + promo **`primary_href`** admin field; **`php artisan test`** **1093 passed / 3 skipped / 0 failed**; **`npx tsc --noEmit`** + **`npm run build`** PASS. Playwright stage1 **BLOCKED** (Chromium not installed in agent env). Browser E2E acceptance **NOT RUN** here.

**Public website workspace routing + CMS focus (2026-10-15):** **`EmployerWorkspaceDestination`** session + `?destination=` on **`GET/POST /account/hiring`** — activation redirects to preserved paths (job create, ATS, recruitment support, staffing enquiries). **`EnsureEmployer`** redirects candidates without workspace to hiring activation (no 403). Public CTAs centralized in **`employerHiringEntry.ts`** (login → intended, activation URL, `portal_navigation.company.has_company_profile`). **Staffing** public form removed; CTAs → **`/account/enquiries`** via company activation when needed. **`GET /account`** personal home no longer renders **`EmployerAccountDashboard`** (company workspace stays on **`/account/hiring`**); employer-role company chrome href **`/account/hiring`**. Admin **Website Content** tabs limited to **home, hire, talent-pool, staffing, about** (`WebsiteContentService::editorialPageTabs`). Tests: **`EmployerWorkspaceDestinationTest`**, updated portal/dashboard tests; **`npx tsc --noEmit`** + **`npm run build`** PASS; focused PHPUnit **31+** PASS. Full **914** suite + browser acceptance journeys **NOT RUN** in this pass.

**Candidate profile UI trim + saved-job bookmark (2026-10-15):** Functional area and experience notes removed from candidate professional profile edit/display and Hire resume header fields; completion scoring uses **industry** + **experience range** only (DB columns retained). Saved jobs use **bookmark-filled** icon with optimistic toggle + rollback on failure.

**B7 mobile integration (2026-10-18):** Candidate Flutter app at `C:\Ournear Hub\ournear_mobile` uses live repositories when **`OURNEAR_API_BASE`** is set (`HireCandidateApp`). Batch plan: **`docs/implementation-batches/B7-Mobile-Integration.md`**. **Fresh:** Api V1 PHPUnit **40 passed** on Hire-Web-B6; focused Flutter unit tests **19 passed** (auth, jobs, applications, hire API). **B7 not complete** — staging/device E2E + full Flutter suite closure pending.

**Candidate mobile API v1 (2026-10-15, test count updated 2026-10-18):** Flutter candidate app contract under **`/api/v1`** — Sanctum bearer (`account:mobile`), **`GET /account`** via `MobileCandidatePresenter`, profile/resume/saved-jobs/applications/verification/apply/messages/notifications routes in `routes/api.php`. Reuses web **`AccountProfileService`**, **`CandidateProfessionalProfileService`**, resume library, **`SavedJobService`**, **`ApplicationSubmissionService`**, **`VerifyAccountEmailNotification`**. Public jobs accept optional bearer; **`is_saved`** only when authenticated candidate. Docs: **`docs/11-API-AND-ROUTES.md`**. Tests: **`tests/Feature/Api/V1/*`** (**40** tests as of 2026-10-18). Production email-link verify **not** re-tested.

**Unified account contact + mobile API (2026-10-15):** Public and account Contact Us pages read **`SiteContactService`** → `website_settings` (no CMS/“to be published” placeholders). Admin **Website → Contact Us page** edits office address, enquiry email (`support_email`, fallback `office_email`), contact phone, WhatsApp. **`GET /api/v1/site/contact`** (public JSON, same four fields). Signup heading **Create Your OurNear Unified Account**. Email verification guest link fix preserved (`verification.verify` outside `auth:account`). Production inbox verify link **not** re-tested post-deploy.

**AUTH-01 email verification enforcement (2026-10-15):** Registration still fires `Registered` → `VerifyAccountEmailNotification` (unverified `email_verified_at`). **`EnsureAccountEmailVerified`** applies only to **`POST jobs.apply.store`** (plus `ApplicationSubmissionService` defense-in-depth). Unverified **candidates** may use dashboard, profile, Hire resume, library resume, saved jobs, and application history; **`GET jobs.apply`** shows verification UI with resend. Unverified **employers** / hiring workspace policy unchanged. Local **`MAIL_MAILER=log`** — real inbox delivery **not** exercised in this pass.

**Last documentation audit:** 2026-09-29 (complete knowledge-base pass; docs 24–25 added).
**Application code modified in that audit:** NO

**Independent QA continuation (2026-10-12):** REG-COM-002/004 defects repaired; scoped PHPUnit **PASS**; report `docs/OURNEAR_INDEPENDENT_QA_CONTINUATION_2026-10-12.md`.

**QA execution batch (2026-10-12):** Full PHPUnit **914 passed / 3 skipped / 0 failed** — `storage/logs/phpunit-qa-execution-20261012T100000Z.txt` (also `phpunit-full-20261012-post-auth-correction-v3.txt`). Report: `docs/OURNEAR_QA_EXECUTION_20261012T100000Z.md`. **Playwright:** `test_lr_register_account_home_browser` **2/2 PASS** after qa-agent harness sync for in-place auth UI.

**Profile field audit (2026-10-12):** `tests/profile_audit/test_profile_field_audit.py` **2/2 PASS**, **19** PFA save/reload cases **PASS** — run `20261012T055640Z-cbe20eb6` (`fill_account_login` harness fix). Full **58-field** browser inventory still **PASS_WITH_COVERAGE_GAPS**. Report: `docs/OURNEAR_PROFILE_FIELD_AUDIT_EXECUTION_20261012T110000Z.md`. Release gate **`insufficient_evidence`** unchanged (no staging; remaining NOT_TESTED profile fields / employer C2 browser).

**Public jobs / company profile (2026-10-12):** Public job cards and job detail expose `company_url` only when `EmployerProfile::allowsPublicCompanyPage()` (active employer user + `public_share.enabled`), aligned with `GET /companies/{employer}` / `UnifiedProfilePresenter::publicCompany()`. Public verification flags (`company_verified`, `ournear_verified`) are omitted unless the employer is OurNear verified.

**Rectification batch 3 — settings, public profile, contact (2026-10-13):** Settings four-tab nav + `#communication` deep link (hashchange scroll); orphan `Settings/Index.tsx` and `Settings/Communication.tsx` remain in bundle only (routes redirect). Public profile: `UnifiedProfileStage23FTest` + `ProductionUxStage12Test` **PASS** (privacy, no contact leak). Contact: `Contact.tsx` TS fix (`detailsBody`); layout sections verified in source; `PublicWebsiteTest` contact **PASS**. `npx tsc --noEmit` **PASS**; scoped PHPUnit **82/82 PASS** (settings, OTP inertia, profile, contact). Browser visual QA **not run** in batch 3.

**Rectification batch 6 — candidate dashboard, profile photo, nav (2026-10-13):** Canonical `ProfilePhotoUpload` (clickable avatar + hidden file input); dashboard identity card with verification actions; layout = metrics → Recent Applications + Saved Jobs cards → Recommended Jobs; removed erroneous `SettingsNav` from `/account/profile` (duplicate tab bar); employer header **Company Profile** → `/account/company` (was `/account/profile`); portal labels **Company Profile** + recruiter `/account/hiring`; resume print hides public header/footer via `resume-print-hide`. PHPUnit **33+** scoped PASS (dashboard, portal nav, photo upload); `npx tsc --noEmit` + `npm run build` **PASS**. Browser: `/account`, `/account/profile#personal` on local QA candidate.

**Rectification batch 7 — post-batch-6 acceptance (2026-10-13):** Browser QA on local `127.0.0.1:8000` (QA candidate): dashboard **PASS** (single H1, distinct Recent applications / Saved jobs / Recommended jobs, real metrics, **390×844** no horizontal overflow); OurNear ID hidden on dashboard, visible on Settings; profile **one** Profile sections nav (no Settings tabs); photo **remove** via UI **PASS**; server upload/persistence **PARTIAL** (Cursor browser blocks file picker — PHPUnit + service path **PASS**); profile hash deep links **FAIL→FIX**: `Profile.tsx` syncs hash from Inertia `pageUrl` + `popstate` (`#professional`, `#languages` browser **PASS** after fix); recruiter `/account/hiring` activation **PASS** (no bypass); resume print CSS **PARTIAL** — CDP `media: print` hides header/footer/mobile nav when `.ourneer-resume-sheet` present; native print dialog / PDF save **NOT RUN**. Scoped PHPUnit **62/62 PASS** (dashboard, photo, nav, portal, header, foundation, correction); `npx tsc --noEmit` + `npm run build` **PASS** (~37s). Full PHPUnit **914** **NOT RUN**. Release verdict **`insufficient_evidence`** unchanged.

**Dashboard QA execution (2026-10-13):** Focused PHPUnit **165/165 PASS** (dashboard, profile, resumes, saved jobs, applications, settings, contact, comms, public profile); `npx tsc --noEmit` + `npm run build` **PASS**; Playwright smoke login **PASS** after harness fix (`Username or email address` / **Login** button). Browser (QA candidate): dashboard save-job → saved count sync on reload. Full PHPUnit (~914) and staging **NOT RUN**. Report: `docs/OURNEAR_DASHBOARD_QA_EXECUTION_2026-10-13.md`. Release verdict **`NOT READY`** (insufficient staging/full manual matrix).

**Rectification batch 4 — applications, saved jobs, account nav (2026-10-13):** Saved Jobs failed-unsave UX (`onError` alert, loading state); candidate sidebar **OurNear ID** hidden on all candidate account routes except `/account/settings/*` (`AccountLayout`); nav hash sync adds `popstate`; `CorrectionPassTest` register link assertion aligned to current `Header.tsx`. Scoped PHPUnit **97/97 PASS** (applications, saved jobs, nav, auth, dashboard, header); `npx tsc --noEmit` + `npm run build` **PASS**. Browser QA (local `127.0.0.1:8000`, QA candidate): dashboard, applications list/detail, saved jobs save/remove/reload, profile `#personal`/`#professional`, settings ID visibility, applications **390px** (no horizontal overflow). Profile photo upload / date-picker / logout session **not exercised** in this batch.

**Rectification batch 1 — candidate dashboard & profile (2026-10-13):** Account registration requires **mobile** (`RegisterRequest`); dashboard registration test aligned via `registrationPayload()`. Candidate **OurNear ID** hidden in account sidebar (see batch 4 for layout-wide rule; Settings still shows ID). Dashboard/professional/personal flows: `AccountCandidateDashboardTest` **16/16 PASS**, `AccountCandidateProfessionalTest` **19/19 PASS**, photo upload/remove via `AccountUserFoundationTest` + `CorrectionPassTest` photo case **PASS**. Browser QA for this batch **partially covered** by batch 4 browser pass (see gaps above).

**Personal vs company identity separation (2026-10-13):** Company logos persist on **`employer_profiles.logo_path`** (`EmployerLogoService`, `GET /employers/{employer}/logo`, `POST/DELETE /account/company/logo`). Personal photos remain on **`users.photo_path`** (`/account/profile/photo`). Shared Inertia `auth.user.display_name` is always the **personal** name; company chrome uses `employer_identity` + `companyHeaderNav`. Public header: **`CompanyWorkspaceMenu`** (company) + personal account dropdown. Company workspace chip hidden until **`canAccessHiringWorkspace()`** (no email-only candidate chip). **Migration:** `2026_10_13_210000_add_logo_path_to_employer_profiles_table` (copies employer-role legacy `users.photo_path` into `employers/` storage on run — **not executed in this pass**). Scoped PHPUnit **18/18 PASS**; `npx tsc --noEmit` **PASS**. Full **914** / browser matrix **NOT RUN**.

**Company avatar → recruiter dashboard (2026-10-13):** Canonical UI = **`EmployerAccountDashboard`** on **`GET /account`** (employer role) and on **`GET /account/hiring`** when `employerProfile` exists; activation form when no profile. **Routing:** `AccountPortalNavigation::companyWorkspaceHref()` — employer → **`/account`**, dual-role → **`/account/hiring`**. Company chrome only when **`canAccessHiringWorkspace()`** (not email-verified candidate-only). **`RecruiterWorkspaceAvatarLink`** uses server href (no rewrite of `/account`). **`HandleInertiaRequests`:** `employerNav` on recruiter paths even **before** `employerProfile` exists (fixes candidate sidebar on activation). **Root causes:** (1) forced `/account/hiring` for all roles + client href rewrite, (2) wrong dashboard component on hiring route, (3) `employerNav` gated on `canAccessHiringWorkspace()` so pre-activation hiring pages showed candidate nav. **Local assets:** `npx tsc --noEmit` + `npm run build` **PASS** (2026-10-13). **Local browser (127.0.0.1:8000):** `qa-employer-a@example.test` company avatar **`/account`** + Dashboard/Own jobs **PASS**; `qa-candidate@example.test` activation + post-activate **`EmployerAccountDashboard`** on `/account/hiring` **PASS**; `/account` stays candidate dashboard **PASS**. **Production (`ournear.com`):** fix **pending deploy** + post-deploy verification.

**Development batch 8 — admin-managed Staffing illustrations (2026-10-13):** **COMPLETE (development)** — public `/staffing` retains both shaded illustration frames (hero + support column); configured images load from CMS `website_sections.media_id` → `MediaAsset` / public `image_url` via `WebsiteContentService::resolveImageUrl` (no hardcoded hero/support raster art in Staffing components). Admin path: **Admin Panel → Website Content** (`/admin/website-content?page=staffing`) → **Staffing Page Illustrations** banner → **Page Sections** → **Staffing Hero Illustration** (`hero`) or **Staffing Support Illustration** (`workforce_partner`); upload in **Media & Social** (`POST /admin/media-social/media`, `StoreMediaRequest`), assign/replace via dropdown, **Remove image** with confirm, optional `extra.image_alt`; save via **Save Changes** (`PUT /admin/website-content/staffing`, `canManageWebsite`). Public: `StaffingIllustrationFrame` (`object-contain`, `onError` clears broken image). Invalid `media_id` rolls back CMS update (DB transaction). **No migration** (existing `website_sections.media_id` + `media_assets`).

**Personal account spacing consistency (2026-10-14):** Shared `accountLayout` tokens + `AccountPageStack`; account shell uses `PageContainer` `gutter="account"` and tighter `mainPy` / sidebar grid; `cardDensity.compact` → 16px padding; dashboard/profile/applications/saved-jobs/resumes/contact/settings spacing aligned. Scoped PHPUnit dashboard + portal nav + responsive **PASS**; TS + build **PASS**.

**Personal dashboard layout batch (2026-10-14):** Candidate `/account` — **Personal Profile Summary** (avatar, name, @username, designation, headline, completion bar, View Profile); removed duplicate candidate **contextual** search panel (global `/search` remains in fixed header via `GlobalHeaderSearch` + mobile popover); account shell uses **`PageContainer` width `wide`** for fuller desktop width; employer home keeps contextual workspace search. **`AccountCandidateDashboardTest` 16/16**; portal nav + responsive stage 17 scoped **PASS**; `npx tsc --noEmit` + `npm run build` **PASS**; browser smoke on QA candidate dashboard **PASS** (identity, header search control, Notifications → Company → Personal).

**Company dashboard nav consolidation (2026-10-14):** Recruiter workspace header — company menu + notifications only (no personal control in workspace band); consolidated dark-teal sidebar card (`EmployerSidebarIdentityCard` + collapsible `AccountNav` groups); **OVERVIEW** / **HIRING ATS** (8 items incl. `/account/hiring/automation` info) / **TALENT POOL** (`/account/hiring/candidates` stage views + `/account/talent-pool` manpower assignments) / **STAFFING** / **SETTINGS** / **ACCOUNT**; sidebar identity — 128px logo, bold name/industry, optional website link; no completion/verification prompts in sidebar. Company logo `object-contain` fix in header avatar. Personal Dashboard unchanged.

**Company dashboard search + local admin (2026-10-14):** Recruiter workspace contextual search (`RecruiterWorkspaceHeader` + `DashboardContextualSearch`) uses a three-column grid with centered `max-w-[520px]` search; mobile stacks controls above full-width search. Browser evidence: `docs/QA/evidence/2026-10-14-company-dashboard-search-{1440,1280,768,390,360}.png`. Local admin: `admin@ournear.test` + Super Admin role; password synced via `php artisan db:seed --class=LocalDevelopmentSeeder` from `LOCAL_ADMIN_PASSWORD` in `.env` (login verified programmatically + `AuthenticationTest` / `AdminPanelTest`).

**End-to-end recruitment workflow QA (2026-10-14):** Added `tests/Feature/EndToEndRecruitmentWorkflowTest` (6 cases: admin→recruiter assignment, draft publish, S1–S6 application matrix, interview/reject/pending paths, in-app notifications, Assigned Executive vs `/account/hiring/candidates` separation). Regression bundle **39 PHPUnit tests PASS** with existing talent-pool, self-service recruitment, and hiring stage tests. Browser smoke: Employer A **`/account/hiring/applications`** (9 applications, pipeline chips) and **`/account/talent-pool`**. Report: `docs/QA/END-TO-END-RECRUITMENT-WORKFLOW-REPORT.md`. Verdict: **ACCEPTED WITH DOCUMENTED LIMITATIONS** (employer Offer/Joined via admin; admin assign UI not browser-walked; email not verified).

**Urgent rectification (2026-10-14 night):** **Assigned Executive** — dual-role employers hit **403** because `RecruiterTalentPoolAssignmentPolicy::viewAny` required `UserRole::Employer` while `EnsureEmployer` allows any `canAccessHiringWorkspace()` user; policy aligned to hiring workspace access. **Staffing** — inline **Staffing Requirements** form on `GET /account/enquiries` + `POST account.enquiries.store` (Contact support removed from index). **Global header search** — balanced 5-column grid, search column up to `max-w-xl`. Browser verified for `qa-employer-a@example.test`; evidence under `docs/QA/evidence/2026-10-14-global-search-header-*.png`.

**Master rectification UI pass (2026-10-14 evening):** Job cards — employer logo removed from shared `JobCard` (company logo remains on job detail / public company pages). Personal dashboard recommended jobs — **2-column** grid at `min-[480px]+` (not global). Company **Jobs** workspace — prominent **Create Job** in-page header on `/account/jobs` (recruiter chrome hides layout action bar). Recent jobs **View** — explicit `/account/jobs/{slug}/applicants` href + `EmployerRecentJobLinksTest`. Job form — Add qualification/skill via combobox/multiselect **dropdown footers** (no `prompt()`). Mobile bottom nav — solid `#FFFFFF` verified @390px. Focused PHPUnit **24 PASS**; `npx tsc --noEmit` + `npm run build` **PASS**. QA: `docs/QA/MASTER-RECTIFICATION-REGISTER.md`, `MASTER-BROWSER-ACCEPTANCE-REPORT.md`.

**Personal sidebar white redesign (2026-10-14):** All candidate personal routes — unified white sidebar card (`CandidateSidebarProfileCard`: circular photo, name, display `^username`, designation/headline/location/organization when present, View Profile; **no** completion bar in sidebar). Shared Inertia `candidateSidebarProfile` from `AccountDashboardService`. Nav active state: light brand tint + orange left accent. Main dashboard **Profile completion** unchanged in `CandidateProfileSummary`. Employer/company sidebar and public header **unchanged**. PHPUnit **31** scoped **PASS**; tsc + build **PASS**; local browser QA candidate **PASS**.

**Local environment restore (2026-10-14 evening):** Home **500** traced to missing `job_listings.listing_scope` (pending `2026_10_14_210000_*`). **`php artisan migrate`** on `ournear_hire_local` restored **200** for `/`, `/admin/login`, and module pages. Browser acceptance evidence under `docs/QA/evidence/2026-10-14-*`.

**Local OurNear visibility + verification unblock (2026-10-15):** Admin **Candidate Allocation** recruiter dropdown used `User::where('role', employer)` — OurNear account (`users.id=4`, `username=mukunda10`) is **candidate** role with linked `employer_profiles.id=2`, so it was excluded despite `canAccessHiringWorkspace()`. Fixed via **`RecruitingEmployerAccounts`** (any user with a named employer profile). **Talent Pool jobs index** filters `listing_scope` ∈ {`talent_pool_internal`, `talent_pool_and_public`} — existing job **#32** (`height`) is **`employer_public`**, so Talent Pool list was empty (not a verification bug). One internal draft job **`Local QA Talent Pool Role Oct 2026`** (`jobs.id=41`, `talent_pool_internal`) added for local QA; public **`/jobs?q=Local+QA+Talent+Pool`** shows no match (**browser verified**). **Local email verification:** `ournear:local-verify-account-email {email|username}` + admin `POST /admin/users/{user}/local-verify-email` (middleware `local.environment`, `users.manage`, audit `local_dev_action=verify_account_email`) — **blocked outside `local`/`testing`**. Applied to `mukunda10` for local QA only. **Mail diagnostic:** `MAIL_MAILER=log`, `QUEUE_CONNECTION=sync` — verification messages are written to `storage/logs/laravel.log` as `local.DEBUG` mail payloads, not delivered to SMTP. **PHPUnit:** **1040 passed / 3 skipped / 0 failed** (`storage/logs/phpunit-full-20261015-urgent.txt`); new **`LocalDevAccountEmailVerificationTest`**, dual-role recruiter list test. Evidence: `docs/QA/evidence/2026-10-15-talent-pool-jobs-ournear.png`.

**Module acceptance gap closure (2026-10-14 night):** **Assignment type labels** — admin detail + recruiter Assigned Candidates use `assignment_type_label` (Talent Pool no longer falls back to “General”). Regression: `RecruiterTalentPoolAssignmentTest::test_talent_pool_assignment_type_labels_are_consistent_in_admin_and_recruiter_views`. **PHPUnit full suite** **1035 passed / 3 skipped / 0 failed** after fixture fixes (`UserFactory` employer `company_name`, unique mobile in `registrationPayload()`, header/share-menu test alignment, `listing_priority` on employer job payloads). **Browser:** OurNear company (`mukunda10`, ONT-E-000002) Staffing request → admin Employers Verification **approve** → requirement form enabled; evidence `docs/QA/evidence/2026-10-14-staffing-ournear-*.png`, `2026-10-14-admin-ournear-staffing-pending.png`. Staffing **reject** path + all four allocation types in one browser session + Talent Pool job create in browser **NOT RUN** (reject/allocation-types/internal-job covered by **`EmployerModulesStaffingTest`** + **`RecruiterTalentPoolAssignmentTest`**). `npx tsc --noEmit` + `npm run build` **PASS**.

**Hire modules — Talent Pool, Staffing, company access (2026-10-14):** Separates Hiring vs Talent Pool vs Staffing. **Staffing access** on `employer_profiles` (`EmployerStaffingAccessStatus`, events table, `EmployerStaffingAccessService`); company request `POST account/company/staffing-access/request`; admin approve/reject/revoke on `EmployersController` + **Employers → Verification** UI. **Candidate Allocation** adds **Talent Pool** assignment type (no job/enquiry required). **Staffing Requirement** selector lists `employer_enquiries` (not companies) — explains OurNear in admin list but absent from requirement dropdown until a real requirement exists. **Talent Pool jobs** via `RecruiterTalentPoolJobsController` + `JobListingScope` (internal jobs excluded from `Job::scopePubliclyVisible`). Migration `2026_10_14_210000_add_employer_staffing_access_and_job_listing_scope.php`. Guide: `docs/QA/OURNEAR-HIRE-MODULES-AND-PERMISSIONS-GUIDE.md`. **`EmployerModulesStaffingTest` 6/6 PASS**; **`RecruiterTalentPoolAssignmentTest` 7/7 PASS**; tsc + build **PASS**. Browser matrix for new admin/Staffing flows **NOT RUN** in this pass; local migrate **operator run** if not on PHPUnit RefreshDatabase.

**Candidate Allocation (admin → recruiter, 2026-10-14):** Admin UI label **Candidate Allocation** at `GET/POST /admin/talent-pool-assignments` — searchable candidate table with multi-select, assign modal (job / staffing / general follow-up), and assignment tracking dashboard with history. Recruiter UI label **Assigned Candidates** at `GET /account/talent-pool` with **progress stages**, follow-up/interview fields, and **`POST …/submit-to-employer`** when a linked real application exists (`EmployerSubmissionService`). Tables: `recruiter_talent_pool_assignments` + status events; extension migration `2026_10_14_200000_extend_recruiter_talent_pool_assignments.php` (`assignment_type`, `progress_stage`, follow-up/submission fields). Still distinct from employer ATS `/account/hiring/candidates`. Run **`php artisan migrate`** on local DBs outside PHPUnit. Report: `docs/QA/CANDIDATE-ALLOCATION-WORKFLOW-REPORT.md`. **`RecruiterTalentPoolAssignmentTest` 7/7** + related E2E/nav tests **23 PASS**; tsc + build **PASS**. Recruiter page browser-verified; admin assign flow browser **NOT RUN** without admin session.

**Staffing landing design restoration (2026-10-13):** Public `/staffing` restored to the original marketing layout (gradient hero, six-step process, light workforce-partner band with four benefit cards, dark-teal responsibility + conversion sections) while keeping independent CMS images for `hero` (right) and `workforce_partner` (left large support column). Public **`StaffingIllustrationFrame`** (`object-contain`, shaded gradient shell, `onError` hides broken image); **`staffingCmsImages.ts`** + **`StaffingPageDefaultMedia`** unchanged. **No empty frames** when images disabled. **`StaffingIllustrationsCmsTest` 7/7 PASS**; `npx tsc --noEmit` + `npm run build` **PASS** (post-restore).

**Public home + shared chrome (2026-10-13):** Fixed public header (`Header.tsx`) with dual OurNear symbol + wordmark lockup, Jobs/Hiring/Talent Pool/Staffing nav (About footer-only), Login/Signup only, fixed offset + employer/user identity chips when `employer_identity` is present. Footer groups Explore/Company/Legal; contact placeholders suppressed when unset. Home: full-width hero carousel, premium employer cards, 33-industry master catalogue (`WebsiteContentDefaults::homepageIndustryCatalogue()`), ATS strip polish, compact Latest Jobs empty state. Tests: `PublicHomePageTest`, `PublicPrimaryNavigationTest` — **PASS** (local run); TS + `npm run build` **PASS**.

**Employer trial publishing (2026-10-13):** Unverified employers activate workspace with company name, phone, and email (no OTP gate on dashboard access). One **one-hour trial** publish tracked via `employer_profiles.trial_job_used_at` + `jobs.posting_tier=trial` / `trial_expires_at`; additional jobs require email + mobile verification (or Admin posting approval). Expiry: `jobs:expire-trials` scheduled every five minutes (`routes/console.php`); `Job::scopePubliclyVisible` excludes overdue trials immediately. Canonical policy: `EmployerPostingEligibilityService` + `EmployerJobPublicationPolicy`.

**Permanent dev rules (2026-10-13):** `docs/DEVELOPMENT-RULES.md` Rules A–F (architecture, local server, change safety, verification, failure handling, change management). Canonical startup: `scripts/start-local-web-server.ps1`.

**Local public site (2026-10-13):** `EnsureLocalFrontendDev` removes unreachable `public/hot` on every local request so Laravel falls back to `public/build` instead of blank Inertia pages when Vite is down; still 503 when build is broken and Vite is required.

**Local dev launcher (2026-10-12):** Canonical entry `scripts/start-local-web-server.ps1` — MySQL probe, Vite HTTP health (`/@vite/client`), `public/hot` sync, then `php -S 127.0.0.1:8000 -t public scripts/local-dev-server-router.php` (NTS: no worker forking). Reuses existing Vite/PHP processes when healthy.

**Public `/talent-pool` landing (2026-10-13):** Managed recruitment marketing (`Platform/TalentPool` + `TalentPoolLandingSections`); primary CTAs → `/contact?interested_in=Recruitment` (persisted `contact_enquiries`, same as partnership `support_href`). Partnership workspace remains `/account/submissions`. No public candidate directory on this route.

**Public `/hire` landing (2026-10-13):** Enterprise marketing page (`Platform/Hire` + `HireLandingSections`) — full-width dark-teal hero with workspace KPI panel, 1-month job posting / 1-year ATS offer copy, five-step How Hiring Works, labelled screening demonstration, six-stage ATS pipeline, horizontal notification journey, combined company profile + reports (CSS bar chart `#3977D5`, pipeline snapshot — not time series). Live metrics via `HireLandingPresentationService` + `EmployerApplicationPipeline` for authenticated hiring workspace employers; guests see demonstration placeholders. CMS hero image optional (`WebsiteContentService` hire `hero`). Post-a-Job CTAs: `resolvePostJobHref`.

**Auth registration in-place correction (2026-10-12):** `/login` → **`Account/Login`**, `/register` → compact **`Account/Register`** (shared `AccountAuthShell`); removed AuthEntry, `/auth/continue`, `/register/full`, compact auto-login. Public register = candidate-only + verification redirect. Report: `docs/OURNEAR_AUTH_REGISTRATION_IN_PLACE_CORRECTION_2026-10-12.md`.

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

**UI architecture adoption (FE-504, partial — 2026-10-12):** Waves 2–9 — BackNavLink, account/public header nav (`PublicHeaderAccountNav`), **FE-302** marketing `atoms/Card`, admin **DetailHeader** heading batches (detail pages + core/recruitment admin indexes). Register: `docs/OURNEAR_RELEASE_ISSUE_REGISTER.md` · report: `docs/OURNEAR_UI_ARCHITECTURE_ADOPTION_WAVE2_REPORT.md`. Vitest/build/browser **not run** (product owner deferred).

**IMPLEMENTED:** Dual-guard auth, public site + CMS, candidate and employer accounts, admin ATS through finance, screening match/comparison, CRM, channel partners, investors, campaigns/referrals, mobile OTP + WhatsApp OTP foundations, unified public profile, recruitment automation foundation, Stage 25 Admin/RBAC hardening, Stage 26 Recruiter Hiring Command Center, Hire display identity (`ON-HIR-*`), Admin bulk resume import, **internal external-resume foundation** (`internal_candidate_resumes` + `InternalCandidateResumeService`, private `candidate_files` disk, no User creation; Admin UI not yet), public destinations `/companies`, `/hire`, `/talent-pool`, and `/staffing`.

**IN PROGRESS / PARTIAL:** Final platform UX. Account employer Hiring UX completion (Stage 22): employer **application received** in-app notification via existing `DatabaseNotification` (`EmployerNotificationService` + `EmployerLifecycleNotification` + `EmployerNotificationEvent::ApplicationReceived`, hooked from `RecruitmentNotificationService::notifyApplicationSubmitted()` after `ApplicationSubmissionService` persist; recipient = job owner `job.employer.user`; fingerprint dedup `employer_application_received:application_id=…`; URL `account.employer.jobs.application`; candidate notifications unchanged). MySQL `ONLY_FULL_GROUP_BY` fix for talent-pool stage counts (`EmployerApplicationPipeline::summaryForEmployer` uses qualified `applications.status` on `toBase()` query). Tests: `EmployerHiringStage22Test` (6 cases); full suite **643** tests / **11774** assertions (2026-10-01). Browser QA on local `127.0.0.1:8000` (employer `stage21-qa@example.com`): hiring command center, grouped mobile Menu drawer, all primary hiring routes (jobs tabs, create job form, applications, talent pool including `?stage=shortlisted` after 500 fix, submissions, enquiries, company profile view, verification, notifications empty state); horizontal overflow checks at **390×844**, **412×915**, **1366×768**, **1440×900** on sampled hiring surfaces — not every sidebar link clicked individually at all four sizes; no live application-detail or post-apply bell verification (no applications on QA employer). Account employer Hiring UX completion (Stage 21): employers (and hiring-enabled users) use existing `DatabaseNotification` + `/account/notifications` (policy extended via `canAccessHiringWorkspace()`); `accountAlerts` + notification bell in `AccountLayout`; `EmployerAccountNav` includes Notifications; candidate-with-hiring keeps candidate nav on `/account` but gets grouped `employerNav` on hiring workspace routes; company profile defaults to **view** with “Edit company profile”; application contact email/mobile from immutable application snapshot on employer application detail + talent pool hints (no separate Request Access/Unlock backend—public talent directory remains separate); employer ATS status changes remain staff/admin (UI explains on application detail). Tests: `EmployerHiringStage21Test`. Browser QA on local `127.0.0.1:8000` (employer login, `/account/hiring`, notifications bell, grouped sidebar)—not all four viewports exhaustively checked. Account employer Hiring Command Center (Stage 20): grouped `EmployerAccountNav` (HIRING / TALENT POOL / SERVICES / ACCOUNT) via shared `employerNav`; `/account/hiring` shows real KPI command center whenever an `employerProfile` exists, with compact activation strip + inline setup when `HiringActivationService` is not fully active (protected posting still gated by `EmployerPostingEligibilityService`); employer-wide **Applications** at `/account/hiring/applications` and **Talent Pool** at `/account/hiring/candidates` (owned job applications only—maps stages to `ApplicationStatus`, “In review” = Reviewing; not public `/talent-pool`); jobs index tabs `all|active|draft` via `?listing=`; tests `EmployerHiringStage20Test`. Browser/responsive QA for Stage 20 hiring surfaces not run in the Stage 20 pass. Candidate account finalization (Stage 19): grouped `CandidateAccountNav` (no personal Talent Pool link; hiring link only when `canAccessHiringWorkspace()`), profile share sheet (copy/WhatsApp/email/native), public profile owner vs visitor (`viewer.is_owner`), registration terms acceptance + employer work-email hint, Hire Resume maps educations/projects/internship-split experience and drops `@username`, compact notifications, application list without redundant resume download. Technical stabilization (Stage 18): full PHPUnit suite aligned with current product rules (single active library resume, screening pipeline nine stages, no public WhatsApp apply CTA); public jobs list cards expose a real `/jobs/{slug}` link on titles in list mode (desktop split still uses `?job=` via `preventDefault`); resume re-upload updates title/visibility when provided. Responsive QA (Stage 17): shared `PageContainer` `min-w-0`, jobs list touch targets (`JobActions`/`SaveJobButton`), job title wrapping on cards; browser QA on local `127.0.0.1:8000` (public, jobs split/detail, candidate account/profile, overflow checks at 390/1366). Admin release readiness (Stage 16): `/admin` dashboard uses real `DashboardService` metrics with **Action required** (`pending_actions`) first, staff greeting, quick access links, then operational summary, pipeline, and recent applications/staffing enquiries; sidebar labels **Recruitment Partnership** (`/admin/submissions`) and **Staffing Enquiries** (`/admin/employer-enquiries`); architecture unchanged (dual guard, existing modules, four-line Hiring Command Center at `/admin/hiring`). Public `/jobs` uses `PublicPageHero`, `JobsSearchPanel` (keyword, location, expandable filters), real result counts, and on `lg+` a split list/detail workspace with independent scroll and `?job=` selection; mobile list navigates to `Jobs/Show`. Public apply flow: `JobActions` → `/jobs/{slug}/apply` (`Jobs/Apply.tsx`) for resume upload and submit via existing `ApplicationSubmissionService`; success confirmation on job detail via flash + `ApplicationConfirmation`. Candidate `/account/resumes` is a Resume Hub (Hire Resume preview via `ResumeBuilderService` + single My Resume upload/replace via `CandidateResumeLibraryService`); `/account/resumes/builder` shows `ResumeReadinessPanel` and print/PDF via browser. Candidate `/account/profile` uses `ProfileCompletionCenter` (module cards + next step from `User::profileCompletionItems()` / `profile_completion`) with one-module editing via existing `CandidateProfessionalSections` and basic/about forms. Public `/hire` routes signed-in users to `/account/hiring` (Stage 10 entry). Account `/account/hiring` shows onboarding until hiring services activate, then an employer Hiring Command Center reusing `AccountDashboardService::employerHiringCommandCenter()`. Staff recruiters use `/admin/hiring` (`Admin/Hiring/CommandCenter.tsx`) with the existing `RecruiterHiringCommandCenterService` payload—Stage 10 refines hierarchy only. Employer job applicants (`/account/jobs/{job}/applicants`, Stage 11–12) use server-side keyword/status/`stage` filters, authoritative `EmployerApplicationPipeline` stage counts, `EmployerApplicantPipeline` tabs, `RecruiterCandidateSummary` (primary **View application** → `/account/jobs/{job}/applications/{application}`), and public profile links with applicants/application back-context query params; employer application detail reuses `CandidateApplicationViewService::timeline()` and authorized resume snapshot routes (employer status changes remain staff/admin via existing `ApplicationPolicy`). Recruitment Partnership (`/account/submissions`, Stage 13) is a service workspace over existing `EmployerSubmission` records: job-grouped requirements via `EmployerRecruitmentPartnership`, summary metrics, server-side filters, requirement detail at `/account/submissions/requirements/{job}`, submission detail at `/account/submissions/{submission}`, `RecruiterCandidateSummary` in submission mode, support CTA to `/contact` (distinct from Direct Hiring / ATS and from staffing `/account/enquiries`). Account settings (Stage 15): `/account/settings` hub; **Verification & security** at `/account/settings/security` reuses email resend, `MobileVerificationService` OTP, optional WhatsApp number display (no required WhatsApp verification in UX), `HiringActivationService` gate for employers; six-digit OTP UI; notifications at `/account/notifications`; candidate communication preferences at `/account/settings/communication` (`CommunicationChannel` only). Staffing Services (`/account/enquiries`, Stage 14) is a workforce-request workspace over existing `EmployerEnquiry` records: `EmployerStaffingServices` summary counts, server-side keyword/status filters, request cards, detail at `/account/enquiries/{id}`, primary CTA to `/for-employers` (existing enquiry form) plus `/contact` support (distinct from Recruitment Partnership and ATS). Public `/talent-pool` supports keyword/location search over share-enabled profiles only (distinct from employer applicants and admin `admin/candidates`). Candidate `/account` (Stage 9) is a compact command center: welcome header, dominant next-action card (`resolveCandidateNextAction`), compact `ProfileCompletionCenter` + resume status, application status summary + recent list, saved-job previews, notifications unread count via shared `accountAlerts`, and email verification link—data from existing `AccountDashboardService::candidateDashboard()` (plus grouped application status counts). Public primary nav (`navigation.ts`): Jobs, Companies, Hire (`/hire`), Talent Pool, Staffing, About. Responsive public header (desktop links + mobile drawer) and candidate mobile bottom nav (Home, Jobs, Applications, Profile, More from `candidateNav`). Public homepage (`Home.tsx`) uses CMS hero + job search, `JobCard` listings, employer spotlight cards, Hire/Talent Pool/Staffing CTAs, and factual “Why OurNear” copy (no invented metrics). Public marketing pages share `PublicPageHero`, `PublicCompanyCard`, and Stage 1 section/card patterns; public company labels use `EmployerProfile::publicDisplayCompany()` (no “Unnamed employer” on public surfaces). Contact stays at `/contact` and the footer. Admin chrome reads OurNear Admin. The Admin sidebar groups existing routes and keeps its own scroll, with the active item scrolled into view. A full job-to-joining recruitment run on local data has not been completed.

**NOT BUILT IN THIS PASS:** A second user identity, a second notification backend, an unlimited resume library, or fake staffing operations such as a new attendance product. `/freelancers`, `/services`, and `/for-employers` remain available; they are no longer primary navigation.

**PARTIALLY IMPLEMENTED:** Company communication settings migration `2026_09_28_120000` may still be pending on some local DBs; WhatsApp Cloud send is gated on `WHATSAPP_PROVIDER`; recruitment automation orchestration exists but does not auto-hire.

**FOUNDATION ONLY:** `UserRole::Partner` and `UserRole::Investor` on `users.role` without dedicated account portals; SMS OTP driver default `unconfigured`; no `app/Jobs` classes.

**PLANNED:** Visual automation workflow builder; WhatsApp recruitment messaging (OTP must not be reused); Search & Discovery / public platform redesign.

**QA (2026-10-10):** **12-stage local program closed.** Stage 12 final gate: full PHPUnit **714 passed** / **2 skipped** (`storage/logs/phpunit-stage12-20261010.txt`), TS + build green, consolidated scorecard in `docs/OURNEAR_STAGE_12_FINAL_REGRESSION_RELEASE_READINESS_REPORT.md`. Stages 1–11 canonical runs preserved; **0** open product defects from Stages 1–12; profile audit **PASS_WITH_COVERAGE_GAPS**. Release gate **`insufficient_evidence`** — staging not verified; not launch-ready.

**Combined recruitment model (2026-10-10, local):** Employer self-service shortlist/reject/interview/message and candidate interview response + correction requests on shared application/interview records (`docs/OURNEAR_COMBINED_RECRUITMENT_MODEL_IMPLEMENTATION_REPORT.md`). Post-implementation regression: full PHPUnit **729 passed** / **2 skipped** / **13,018 assertions** (`storage/logs/phpunit-post-implementation-20261010-rerun.txt`); `changeStatus` uses `lockForUpdate` (`ApplicationPipelineConcurrencyTest`). Candidate in-app notification on employer application messages. Stage 11 refresh run **`20261010T170415Z-f4676464`** — STG11-MISS-002..005 browser **PASS**; lifecycle admin journey **FAIL** (admin QA env); E2E self-service **SKIPPED** (429). Report: `docs/OURNEAR_POST_IMPLEMENTATION_REGRESSION_QA_REPORT.md`. Release gate **`insufficient_evidence`** unchanged.

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

**Job apply access (2026-10-11):** Public job browse/detail stays guest-accessible. `/jobs/{slug}/apply` requires account auth; guests use `redirect()->guest(login)` so a **same-origin** apply path is preserved in `IntendedUrl` and restored after login (`/jobs` prefix allowed). External/`//` intended URLs are rejected. Guest Apply CTA links to the apply route (`JobActions.tsx`, `PublicSiteController`).

**Local dev stack (2026-10-12):** Canonical launcher `scripts/start-local-web-server.ps1` — `ensure-local-mysql84.ps1` (service + `verify-local-mysql-connection.php`), `ensure-local-vite-dev.ps1` (HTTP `/@vite/client` before `public/hot`; startup **aborts** if Vite is unhealthy), then foreground `php -S 127.0.0.1:8000 -t public scripts/local-dev-server-router.php` (router resolves `public/index.php` by path, not repo-root `index.php`). Helpers: `local-vite-lib.ps1`, `local-dev-lib.ps1`. Vite logs: `storage/logs/local-vite-dev.log`. `PHP_CLI_SERVER_WORKERS` only on **ZTS**; Windows **NTS** uses single-request mode (forking unsupported). Vite runs in a **background** Node process; PHP stays in the launcher terminal. Reuses healthy Vite/PHP PIDs when detected.

**REG-PROD-002 email delivery (2026-10-11):** Admin **Settings → Email delivery** (`SettingsEmailPanel`, `EmailSettingsController`, encrypted SMTP on `website_settings`). PHPUnit green; Playwright harness **PASS** for tab save/reload persistence (`test_reg_prod_002_email_settings_browser.py`, `20261011T163000Z-reg-prod-002-browser`). **Local DB prerequisite:** run migration `2026_10_11_140000_add_mail_delivery_settings_to_website_settings_table` on `ournear_hire_local` before admin save (otherwise PUT returns **503**). Live SMTP/inbox/DNS still operator-evidenced only — `qa-agent/artifacts/agent2/20261011T163000Z-reg-prod-002-local-acceptance/`.

**REG-COM-001 message templates (2026-10-11):** Admin `/admin/message-templates`; PHPUnit **10/10**; Playwright **8/8 PASS** (`20261011T174500Z-release-verification-continuation`). Root layout includes CSRF meta for admin JSON preview `fetch()`.

**REG-PROD-001 employer pipeline (2026-10-14):** **CLOSED — PASS** (local browser). G1 hiring-wide + per-job pipeline **9/9**; Playwright reject (fresh apply), resume owner/isolation, self-service shortlist/interview/message **PASS**; PHPUnit `EmployerJobApplicantPipelineTest` + `EmployerHiringApplicationsPipelineTest` + `EmployerSelfServiceRecruitmentTest` **17/17 PASS**. Evidence `qa-agent/artifacts/agent2/20261014T120000Z-reg-prod-001-browser/`; report `docs/OURNEAR_REG_PROD_001_BROWSER_EXECUTION_20261014.md`. qa-agent harness only (account home headings, applications list, resume region). Admin-only offer/join not on employer self-service UI.

**Employer application resume access (2026-10-12):** Snapshot preview/download via `ApplicationResumeDeliveryService` on `account.employer.applications.resume`; employer UI `has_resume` requires file on storage. Report: `docs/OURNEAR_EMPLOYER_APPLICATION_RESUME_ACCESS_REPORT.md`. Tests **added, not run**.

**REG-PROD-009 job master-data validation (2026-10-12):** Admin + employer job create/update use hardened `ValidatesJobMasterData` (FK hierarchy, active/inactive, strict skill/qualification ID arrays); updates preserve omitted lookup fields. `JobMasterDataValidationTest` **added, not run**. Report: `docs/OURNEAR_PHASE_E1_JOB_DETAILS_SAVED_JOBS_REPORT.md` §8.

**REG-BRW-004 employer applicant compare (2026-10-12):** Job-scoped compare on `GET /account/jobs/{job}/applicants?compare=` via `EmployerApplicantCompareService` (authorized application IDs only, advisory match via existing `ScreeningMatchService`). Admin C2 compare panel fix preserved. `EmployerApplicantCompareTest` **added, not run**. Report: `docs/OURNEAR_QA_C2_SCREENING_COMPARISON_REPORT.md` (Employer section).

**Candidate application lifecycle consistency (2026-10-12):** Employer applicant `pipeline_stage_label` now uses the same `ApplicationStatus::toCandidateStatusLabel()` as candidate application list/detail (fixes e.g. **Placed** shown as “Selected” on employer cards). Withdraw validation errors surface on candidate application pages. `CandidateApplicationLifecycleConsistencyTest` **added, not run**. Report: `docs/OURNEAR_STAGE_3_CANDIDATE_APPLICATION_IMPLEMENTATION_REPORT.md` §8.

**REG-COM-002 / REG-COM-003 communication preferences (2026-10-12):** Recruitment in-app uses `Push`; operational email uses `System` (`canSendRecruitmentInApp` / `canSendRecruitmentEmail`). Employer email opt-out no longer conflated with in-app. Account verification email exempt. Tests **added/updated, not run**. Report: Stage 6 §REG-COM-002 / §REG-COM-003.

**REG-COM-004 employer self-service preferences (2026-10-12):** Employers use the same `CommunicationPreferenceService` as candidates (`isAccountMember()` gate). Self-service UI lives on **Account Settings → Account** (`#communication`); `GET /account/settings/communication` redirects there; `PUT /account/settings/communication` unchanged. Admin CRM preference edit unchanged. `AccountEmployerCommunicationSelfServiceTest` **PASS** (2026-10-13).

**FE-504 (2026-10-12):** Waves 2–9 documented in wave report (through Submissions/Placements/Replacements/Employees/Work indexes). Tests authored, **not run**. **Full-site adoption not complete** — FE-201 blocked; CRM/Settings/Master Data indexes and adapter cleanup optional.

**REG-PROD-003 admin navigation (2026-10-12):** Eight-group sidebar driven by `AdminNavigationCatalog` shared as Inertia `adminNav`; scorecard templates + design system links; section Tabler icons. `AdminNavigationCatalogTest` + vitest **added, not run**. Report: `docs/OURNEAR_PHASE_F3_ADMIN_NAV_REPORT.md`.

**REG-CAN-PROF-001 candidate profile persistence (2026-10-12):** Partial basic PUT preserves omitted personal/candidate fields; hiring-enabled users no longer wipe **employer company** fields on personal-only saves; owners can open full profile preview at `/profiles/{username}` when public sharing is off. **Unified profile hub:** `CandidateUnifiedProfileNav` on `/account/profile` (Overview / Personal / Professional / Resumes / Preview / Privacy). Scoped PHPUnit **PASS** — `docs/OURNEAR_P0_FUNCTIONAL_COMPLETION_2026-10-12.md`.

**Account auth pages (2026-10-12):** `/login` → **`Account/Login`**, `/register` → compact **`Account/Register`** (shared shell; candidate-only; verification then login). See `docs/OURNEAR_AUTH_REGISTRATION_IN_PLACE_CORRECTION_2026-10-12.md`.

**Internal candidate resumes admin (2026-10-12):** `/admin/candidates/internal-resumes` — list, upload, permission-gated download (`InternalCandidateResumeController`); catalog item under Candidates. `AdminInternalCandidateResumeUiTest` **PASS**.

Public Hire display ID is `OurNearHireIdentity`: one immutable `users.person_number`, shown as `ON-HIR-CAN-###` / `ON-HIR-REC-###` / `ON-HIR-USER-###`. Existing `business_id` values (`ONT-*`) stay internal. **Account Settings (2026-10-13):** `/account/settings` → Account tab; tabs **Account | Security | Verification | Change Password** (`/account/settings/account`, `…/security`, `…/verification`, `…/password`). Verification entry `/account/settings/verification` (Laravel email verify + resend, mobile OTP via `verification_challenges`, optional WhatsApp). Password change on `/account/settings/password` (`PUT /account/settings/password`). Verified users change email through `users.pending_email` + signed `POST /account/settings/email/change` / `GET /account/settings/email/verify-change/{id}/{hash}`; unverified users update `email` directly then receive standard `VerifyEmail`.

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

PHPUnit Feature + Unit. Last **full** local run (2026-10-10, post CAN-01 / PUB-02 UI fix): `php artisan test` → **712 passed**, **2 skipped**, **0 failed**, **12,839 assertions**, ~1020s (SQLite `:memory:`). Log: `storage/logs/phpunit-full-20261010-session2.txt`. Test harness: per-test isolated `Storage::persistentFake` roots + re-bind after HTTP (`tests/TestCase.php`) to fix Windows/flaky resume downloads. Focused QA utility tests: `QaHiringFixturesTest` (4 tests).

---

## LOCAL QA DATA (Hiring)

**Command:** `php artisan qa:hiring-fixtures`
**Allowed environments:** `local`, `testing` only — **refuses** `production` (`APP_ENV=production`).
**Purpose:** Deterministic employer A/B + candidate + published/draft jobs + one real application (via `ApplicationSubmissionService::submitFromProfile`) and employer in-app notification (existing `EmployerNotificationService` lifecycle). Idempotent: re-run updates the same `@example.test` identities and fixed job slugs without duplicating QA users/jobs/applications.

| Role | Email | Username (candidate) |
| --- | --- | --- |
| Employer A | `qa-employer-a@example.test` | `qaemployerA` |
| Employer B | `qa-employer-b@example.test` | `qaemployerB` |
| Candidate | `qa-candidate@example.test` | `qacandidate` |

**Credentials:** The command prints the shared local QA password to the terminal only (`QaHiringFixturesCommand`); do not commit or paste it into docs, tickets, or chat logs.

**Job slugs:** `qa-fixture-published-a`, `qa-fixture-draft-a`, `qa-fixture-published-b`.
**Implementation:** `App\Support\QaHiringFixtures`, `App\Console\Commands\QaHiringFixturesCommand`. Does not touch non-QA records.

**Talent Pool / privacy (Stage 24):** Employer Talent Pool at `/account/hiring/candidates` lists **owned job applications** only (`EmployerVisibleCandidate::fromApplication`). Stages map to `ApplicationStatus` via `EmployerApplicationPipeline::talentPoolStages()` (Reviewing = “In review”; Selected = Offered; Placed = Joined; no separate Contacted status). Contact email/mobile on cards and application detail come from **`application_contact` snapshot** on the application record—not live `users.email` / `users.mobile`. No Request Access / Unlock Candidate product flow in codebase; employer path is application snapshot + optional public profile when share-enabled. Tests: `EmployerHiringStage24Test` (6 cases, uses `qa:hiring-fixtures`). Browser QA on QA accounts: run fixtures then sign in as Employer A for Talent Pool smoke.

**Direct Hiring / ATS employer workspace (Stage 25):** `/account/hiring/applications` lists employer-scoped applications with **ATS stage chips** (aligned with job applicants index); detail at `/account/jobs/{job}/applications/{application}` (`Account/Employer/Applications/Show`) includes **employer self-service** recruitment actions (shortlist, reject, schedule interview, messages, correction handling) for owned jobs, alongside existing admin ATS on the same records. Resume via `account.employer.applications.resume` (application snapshot file). Tests: `EmployerHiringStage25Test`, `EmployerSelfServiceRecruitmentTest` (`qa:hiring-fixtures`).

---

## Deployment State

Shared-hosting oriented (Hostinger mentioned in `.env.example` comments). Web root `public/`. Leftover tree `ournear_app_Hostinger_2026-09-19/` must **not** be deployed. Queue default `database`; cron for scheduler. **QA agent PR checks:** `.github/workflows/qa-agent-checks.yml` (registry + unit/guard tests; no remote staging). [NOT VERIFIED] production host details.

---

## Recently Completed Work

Stage 23E WhatsApp OTP, 23F unified profile, Stage 24 recruitment automation foundation, Stage 25 Admin/RBAC, Stage 26 Recruiter Hiring Command Center.

**QA defect fixes (2026-10-09):** `AtsStage::resolve()` now maps `ApplicationStatus::Reviewing` to the **Screening** ATS stage (was "Applied / New" when no screening row existed) and `ApplicationQuery` ATS-stage filters mirror it; public `/jobs` Company filter is a case-insensitive exact match on the public company label (employer public name, else job `project`) and its options use the same label (`JobListingQuery::publicCompanyLabel()`); `/jobs?employer=` (profile id, `ONT-E-…` business ID or company name; alias `employer_id`) now narrows server-side and unknown employers return no jobs; Jobs page remounts the search panel on filter change so Clear resets selects; `/account/company` and `/account/profile/company` redirect to `/account/profile`. Tests: `QaDefectRegressionTest` (5 cases).

**Human QA Agent (Phase G, 2026-10-09):** Normalized `artifacts/<run_id>/run-record.json`, `ournear-qa report` (HTML/JSON/JUnit, `--ci-exit`), release-readiness and execution-based coverage (`run_record.py`, `run_report.py`, `dashboard_html.py`); report `docs/OURNEAR_QA_AGENT_PHASE_G_REPORT.md`. Phase F planner and C5 evidence unchanged. Does not replace PHPUnit.

**Human QA Agent (staging allowlist + CI, 2026-10-09):** `OURNEAR_STAGING_HOSTS` exact allowlist, `OURNEAR_ALLOW_RUN=staging` opt-in, `ournear-qa preflight-staging`, staging suite guard (`smoke`/`registry` only); PR workflow `.github/workflows/qa-agent-checks.yml`; report `docs/OURNEAR_QA_AGENT_STAGING_ALLOWLIST_REPORT.md`. Live staging smoke not verified in implementation.

**Human QA Agent (P0 employer job publish, 2026-10-09):** `JRN-EMPLOYER-JOB-PUBLISH-01` browser journey (draft, publish, public list/detail, edit, close, employer B/candidate 403); `scripts/inspect-qa-employer-job-form-defaults.php`; report `docs/OURNEAR_QA_P0_EMPLOYER_JOB_PUBLISH_REPORT.md`. Does not replace `EmployerAccountWorkspaceTest`.

**Human QA Agent (P0 cross-role, 2026-10-09):** `JRN-CROSS-ROLE-01` closed via existing `cross_role.py` orchestration (candidate apply → employer A resume → admin review → B/candidate/employer A admin denial); registry status **implemented**; report `docs/OURNEAR_QA_P0_CROSS_ROLE_REPORT.md`.

**Human QA Agent (employer job pause/republish, 2026-10-09):** `JRN-EMPLOYER-JOB-PUBLISH-01` extended with pause, public hide, and republish; `JRN-EMPLOYER-RESUME-01` and `JRN-ADMIN-APPLICATION-01` registry **implemented**.

**Trial posting expiry (2026-10-09):** Product behavior via `Job::isPubliclyVisible()`; focused PHPUnit **1 passed**; report `docs/OURNEAR_QA_P0_TRIAL_EXPIRY_REPORT.md` — browser journey not added (no product defect).

**CAN-02 profile completion (2026-10-10):** Account profile/dashboard completion % aligned with `completion_items` via `calculateProfileCompletion()`; professional sections reload `profile` after save; `AccountCandidateProfessionalTest` **17 passed**. Registry `human: partial` remains (no dedicated browser journey).

**CAN-01 candidate dashboard (2026-10-10):** `/account` home profile payload now includes `professional` + `library_resume` (checklist parity with profile edit). Server `dashboard.next_action` from `CandidateProfileGuidance` (first incomplete scored item, then resume/jobs guidance); React uses payload instead of duplicating completion rules. `AccountCandidateDashboardTest` **16 passed** (362 assertions). Registry `human: partial` (smoke login only; use `MAN-CAN-01-DASHBOARD-GUIDANCE` manual case for human loop).

**Human QA operator workflow (2026-10-10):** `ournear-qa export-manual`, `validate-manual-results`, `import-manual-results` (attach to pytest `run_id`); companion `qa-agent/registry/manual_cases.yaml`; reports include separate human section (`qa-agent/README.md`). Verified: `tests/test_manual_workflow.py` **8 passed**; CLI demo export CAN-01 (**2 cases**).

**QA evidence session (2026-10-10):** Manual cases `MAN-CAN-01-DASHBOARD-GUIDANCE` + `MAN-PUB-02-JOBS-SEARCH` **PASS** (Playwright harness + screenshots) run `20261010T025807Z-37d441ab`; targeted P0 journeys **9 passed** run `20261010T030042Z-8d065de8` (no cross-role/apply re-run). PUB-02/EMP-02 PHPUnit filters green; no new product defects in filters/employer workspace.

**PUB-02 company filter UI (2026-10-10):** Initial fix: invalid `lg:open` on `<details>`. **Closure:** removed native `<details>` toggle; always-visible “More filters” section in `JobsSearchPanel.tsx` (mobile + desktop). Manual **`20261010T054124Z-a53f91f2`**: keyword, company select, mobile `#job-company`, **pagination + Clear** (`test_man_pub_02_jobs_company_pagination`). PHPUnit: `test_public_jobs_company_filter_persists_across_pagination`; fixtures: `QaHiringFixtures::PUBLISHED_A_PAGINATION_JOBS`.

**CAN-02 profile reload (2026-10-10 closure):** `MAN-CAN-02-PROFILE-RELOAD` **PASS** run **`20261010T041431Z-9b88be75`** (bio + designation persist across reload; screenshots 01–05). Harness: `test_man_can_02_profile_reload`.

**Profile field audit (2026-10-10):** End-to-end inventory + browser/PHPUnit verification in `docs/OURNEAR_PROFILE_FIELD_AUDIT_AND_REPAIR_REPORT.md`. **No product profile defects reproduced**; PHPUnit profile-related **50 passed** (851 assertions); Playwright `qa-agent/tests/profile_audit/` **2 passed** (19 field-group cases, single login per role). Verdict **PASS_WITH_COVERAGE_GAPS** (34 candidate + 13 employer controls still NOT_TESTED; STG2 bio re-login **BLOCKED** 429 in combined run `20261010T103411Z-1a41a58a`). Harness registry: `qa-agent/registry/profile_field_audit.yaml`.

**Regression pass (2026-10-10 session 2):** Full PHPUnit **712 passed** (see Testing section). Closure targeted: `QaDefectRegressionTest` **8 passed**; `QaHiringFixturesTest` **4 passed**. Frontend: `npx tsc --noEmit` + `npm run build` exit **0**.

**Web release readiness (2026-10-10):** Consolidated local P0 index `docs/OURNEAR_P0_LOCAL_EVIDENCE_RECORD.md`; snapshot `docs/OURNEAR_WEB_RELEASE_READINESS.md`. Verdict **`insufficient_evidence`** (staging not verified; no single full journey-matrix archive). Latest manual reports: `qa-agent/artifacts/reports/20261010T054124Z-a53f91f2/`, `.../20261010T041431Z-9b88be75/` (`ci_exit_code: 4`). Journeys **`20261010T030042Z-8d065de8`** (9 passed) reused without re-run.

**Resume fixture integrity (Batch 6, 2026-10-09):** QA/tests used a 59-byte PDF header stub (no `/Type /Page`). `App\Support\ReadablePdfFixture::minimalOnePage()` is the shared structurally valid single-page PDF for `TestCase::fakeResume()` and new `qa:hiring-fixtures` library seeds. Application snapshots remain a **copy** on the `resumes` disk at apply time and do not follow library replace (`ApplicationExperienceTest`). Local QA backup/restore: `scripts/backup-qa-resume-artifacts.php`, `scripts/restore-qa-resume-artifacts.php` under `storage/app/qa-backups/batch6/`.

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
