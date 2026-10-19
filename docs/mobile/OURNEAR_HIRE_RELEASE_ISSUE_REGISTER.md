# OurNear Hire Candidate Mobile â€” Release Issue Register

| Field | Value |
| --- | --- |
| Document ID | RELEASE-ISSUE-REGISTER |
| Workspace | `C:\Ournear Hub\ournear_mobile` |
| Entry point | `lib/preview/hire_candidate_preview_main.dart` |
| Last updated | 2026-10-19 (B7 Flutter verification follow-up) |
| Historical baseline reference | Earlier report: 140 tests pass, analyze clean (2 info lints), web + debug APK built; these are historical results, not the current test count |
| B7 cross-check (2026-10-18) | Hire-Web-B6: **`tests/Feature/Api/V1` 40 passed**; live public jobs list **200**. Flutter focused unit tests **19 passed** (auth, jobs, applications, hire API). **Staging/device E2E still open** â€” see `Hire-Web-B6/docs/implementation-batches/B7-Mobile-Integration.md` |

## How to read rows

| Status | Meaning |
| --- | --- |
| PASS | Requirement met with automated and/or manual evidence in this register |
| PARTIAL | Usable with documented limits |
| FAIL | Broken against requirement; fix required or in progress |
| BLOCKED | Missing authorized contract, URL, or environment; cannot honestly implement |

| Severity | Release impact |
| --- | --- |
| P0 | Blocks release (crash, auth/session integrity, data leak) |
| P1 | Core journey broken or misleading |
| P2 | Secondary journey or polish |
| P3 | Documentation or non-blocking gap |

---

## Register

| ID | Requirement / journey | Status | Sev | Root cause / dependency | Files | Acceptance criteria | Test / build evidence | Limitations |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| REL-MENU-01 | Menu only on Profile tab; order and labels | PASS | P1 | â€” | `candidate_shell_scaffold.dart`, `candidate_direct_profile_screen.dart`, `candidate_app_menu.dart` | No menu on Home/Jobs/Applications; Logout label exact; no Verification/Notifications menu items | `candidate_menu_placement_test.dart`, `candidate_home_menu_test.dart` | Info dialogs for legal/OurNear apps (no invented URLs) |
| REL-MENU-02 | App Version from project metadata | PASS | P3 | Static `AppVersion` mirrors `pubspec.yaml` | `lib/core/app_version.dart`, menu `menu-app-version` | Shows `1.0.0+1` aligned with pubspec | Menu list tile subtitle | Not read from `package_info` at runtime |
| REL-THEME-01 | Candidate dark mode isolated | PASS | P1 | â€” | `candidate_theme_controller.dart`, `ournear_theme.dart`, `hire_dark_tokens.dart` | Toggle persists in secure storage; unified app unchanged | `candidate_dark_theme_test.dart`, menu dark test | Shared widgets follow `Theme.of(context)` |
| REL-AUTH-01 | Login / account / logout | PASS | P1 | â€” | `auth_service.dart`, `login_screen.dart`, `AuthController` | Verified contracts; no token logging | `login_flow_test.dart`, `auth_service_test.dart`, stage7 logout; **2026-10-16** auth acceptance widget run (23 tests incl. tabs, validation, reduced motion) | **Staging login not tested** â€” no authorized API base/credentials in workspace |
| REL-AUTH-05 | Auth UI acceptance (tabs, labels, blocked signup) | PASS | P2 | â€” | `login_screen.dart`, `signup_form.dart`, `AuthTabSelector` | Tabs, keys, consent order, GAP-AUTH-REGISTER messaging | `candidate_auth_visual_test.dart`, `candidate_account_access_test.dart`, `login_flow_test.dart` | No website screenshot comparison |
| REL-AUTH-02 | Registration | PARTIAL | P1 | Requires compile-time `OURNEAR_API_BASE` | `signup_form.dart`, `AuthService.register` | POST register + token | Prior test suite; **new tests NOT RUN this pass** | Staging E2E not evidenced |
| REL-BRAND-01 | Admin-synced logo/name | PARTIAL | P2 | â€” | `hire_branding_service.dart` | GET `/api/v1/site/branding` + fallback | `hire_branding_service_test.dart` (**NOT RUN this pass**) | Logo refresh needs app restart (compile-time base) |
| REL-AUTH-03 | Session gate and browse-without-account | PASS | P2 | â€” | `candidate_session_gate.dart`, preview app | Recoverable bootstrap failure; browse jobs path | Auth/onboarding tests | â€” |
| REL-AUTH-04 | Email verification in Settings (not menu) | PARTIAL | P2 | â€” | `account_verification_screen.dart` | Resend via authorized API when configured | Settings tests (**NOT RUN this pass**) | In-app verify link still web/email client |
| REL-LEGAL-01 | Terms, privacy, help URLs | PARTIAL | P2 | Uses GET /api/v1/site/contact when API base set | `candidate_app_menu.dart`, `SiteContactService` | Opens official terms/privacy/about URLs; help shows published contact | Automated client wiring; live browser open not run | Requires OURNEAR_API_BASE |
| REL-JOBS-01 | Public job list and detail | PARTIAL | P1 | Catalogue filters wired in the Jobs tab | `hire_screen.dart`, `LiveJobsRepository` | Keyword plus industry, location, category, employment type, experience, and sort | `jobs_repository_test.dart` updated; **not executed in this pass** | Company and salary are not separate dropdowns |
| REL-JOBS-02 | Saved jobs list screen | PARTIAL | P2 | Requires auth + API base | `saved_jobs_screen.dart`, `fetchSavedJobs` | Lists job cards from GET collection | Prior tests; **NOT RUN this pass** | Live server unverified |
| REL-JOBS-03 | Save/unsave job bookmark | PARTIAL | P2 | Needs auth + API base | `job_card.dart`, `HireApiService.setJobSaved` | POST/DELETE per slug | `live_jobs_api_integration_test.dart` | Live server unverified |
| REL-APP-01 | Application history (live) | PARTIAL | P1 | Staging E2E not evidenced | `CandidateApplicationsScreen`, `LiveApplicationsRepository`, `ApplicationsController` | Authenticated tab uses injected repo + bearer (2026-10-16 RCA: orphan `LiveApplicationsRepository()` without token) | `applications_authenticated_session_test.dart`, `live_applications_repository_test.dart` | End-to-end vs Laravel not proven |
| REL-APP-02 | Application submit wizard | PARTIAL | P1 | POST apply only; no resume/questions body | `application_wizard_screen.dart` | Unconfigured shows nothing sent | application flow tests | GAP-APP-SUBMIT product gaps |
| REL-PROF-01 | Direct Profile tab | PARTIAL | P1 | Existing records can be selected, updated, and deleted; completion percent comes from the account payload | `edit_profile_section_screen.dart`, `auth_user.dart`, `professional_profile_body.dart` | Education, certification, and employment edit/delete by id; header shows `profile_completion` when present | Prior profile tests exist; **not executed in this pass** | Staging cross-check vs website not run |
| REL-DOC-01 | Resumes/documents | PARTIAL | P2 | Library delete and set-primary use existing resume routes | `documents_repository.dart`, `documents_screen.dart` | List, upload, set primary, delete | `live_documents_repository_test.dart` updated; **not executed in this pass** | Resume download URL not opened in-app |
| REL-DEL-01 | Delete account | PASS | P1 | No backend delete | `candidate_app_menu.dart` | Does not logout; states not deleted | `candidate_home_menu_test.dart` | No API call |
| REL-QA-01 | Manual browser/device QA | PARTIAL | P0 | Authorized live API/device workflow evidence remains outstanding | — | Flutter full suite: **176 passed, 0 failed**; design-system test: **4 passed**; existing release APK present; main manifest declares `INTERNET` | Automated tests and APK existence do not prove live login, apply, application history, or resume upload against Laravel on a device | Authorized live candidate workflow evidence still required |
| REL-DESIGN-01 | HireMe/Social-aligned candidate UI + shared design system | PARTIAL | P2 | Reference templates not in mobile repo; saved list/profile API gaps | `hire_notice_banner.dart`, `hire_brand_mark.dart`, `job_card.dart`, `login_screen.dart`, `saved_jobs_screen.dart`, design gap report | Shared banners/tiles; branded login; settings cards; honest saved/docs states | **158 tests** baseline; widget visual spot-check 2026-10-16; web+APK built 2026-10-16 | Side-by-side template/website screenshots still pending |

---

## GAP-AUTH-REGISTER â€” contract needed to unblock signup

The mobile workspace requires an authorized row (not Laravel inspection) specifying:

1. HTTP method and path (e.g. whether registration is distinct from login).
2. JSON request fields (username uniqueness rules, email, password, consent flags).
3. Success response (token issued or email verification required).
4. Error semantics (422 field errors, duplicate username).
5. Whether signup auto-authenticates the session.

Until supplied, `LiveAccountAccessRepository.register` must throw before HTTP.

---

## Phase H â€” Manual QA log (candidate preview)

Manual checks are recorded in the release report section â€œManual browser/device test evidenceâ€ for the current run. Automated tests do not replace this table when environment allows execution.

---

## Change log (register maintenance)

| Date | Change |
| --- | --- |
| 2026-10-15 | Initial register; API register synced with client implementations for applications and saved-job toggle; badge lint fix |
| 2026-10-15 | Applications auth-gating fix; tests aligned to signed-out copy; 145 tests pass; analyze 0 issues; web rebuild |
| 2026-10-16 | Implementation follow-up (code only): profile record edit/delete, completion percent, resume primary/delete, jobs catalogue filters. Tests, builds, staging, and device checks **not executed in this pass**. Laravel Stage 11 `record` already includes `$staffingSubmission`; no signature edit |


| 2026-10-19 | Updated B7 evidence: full Flutter suite 176 passed; design-system test 4 passed; existing release APK and main-manifest INTERNET permission confirmed. Live API/device E2E remains open. |

