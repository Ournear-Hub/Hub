# Backend staging completion report (2026-10-16)

Laravel project used: `C:\Ournear Hub\Ournear Hire` (same codebase as `D:\Ournear Hub\Ournear Hire`).

## Workstream A — Registration (GAP-AUTH-REGISTER)

| Item | Value |
| --- | --- |
| Method / route | `POST /api/v1/auth/register` |
| Auth | Public |
| Rate limit | `throttle:account-register` |
| Body | `name`, `username`, `email`, `mobile`, `password`, `password_confirmation`, `accept_terms` (accepted), `accept_privacy` (accepted), optional `device_name` |
| Validation | Reuses `RegisterRequest` rules via `MobileRegisterRequest` |
| Success `201` | `token`, `token_type`, `expires_at`, `verification_email_sent: true`, `user` (same shape as login) |
| Errors | `422` Laravel `errors` map (duplicate username/email, password policy, mobile, consent) |
| Post-register | Verification email via `Registered` event; Sanctum mobile token issued (same as login); candidate may call `POST /api/v1/auth/login` |
| Laravel tests | `tests/Feature/Api/V1/MobileAuthApiTest.php` — **10 passed** (includes register, duplicates, login-after-register, forgot-password) |
| Mobile | `AuthService.register`, `AuthController.register`, `SignupForm` wired when `OURNEAR_API_BASE` set |
| Live probe | `POST http://127.0.0.1:8000/api/v1/auth/register` → **201** (agent session) |

**Files (Laravel):** `MobileAuthController.php`, `MobileRegisterRequest.php`, `routes/api.php`, `RegistrationSource.php`, `LegalConsentService.php`, `MobileAuthApiTest.php`

**Files (mobile):** `auth_service.dart`, `auth_controller.dart`, `account_access_repository.dart`, `signup_form.dart`, tests

## Workstream B — Branding (GAP-BRAND-SYNC)

| Item | Value |
| --- | --- |
| Method / route | `GET /api/v1/site/branding` |
| Auth | Public |
| Cache | `Cache-Control: public, max-age=300` |
| Response | `{ "data": { "app_name": string, "logo_url": string|null } }` — sourced from `WebsiteSettingService::publicBranding()` (admin website settings) |
| Missing values | `app_name` falls back to `OurNear Hire`; `logo_url` may be null (mobile keeps palette fallback) |
| Laravel tests | `tests/Feature/Api/V1/PublicSiteBrandingApiTest.php` — **1 passed** |
| Mobile | `HireApiService.fetchPublicBranding`, `HireBrandingService.load` |
| Live probe | `GET http://127.0.0.1:8000/api/v1/site/branding` → **200** |

**Files (Laravel):** `PublicSiteBrandingController.php`, `routes/api.php`, `PublicSiteBrandingApiTest.php`

**Files (mobile):** `hire_api_service.dart`, `hire_branding_service.dart`, `hire_candidate_app.dart`, tests

## Workstream C — Other candidate APIs

### Saved jobs list (GAP-JOBS-SAVED list)

| Item | Value |
| --- | --- |
| Backend | **Already existed:** `GET /api/v1/account/saved-jobs` (Bearer, candidate middleware) |
| Mobile | **Implemented:** `HireApiService.fetchSavedJobs`, `LiveJobsRepository.fetchSavedJobs`, `SavedJobsScreen` lists rows |
| Laravel tests | Covered in `MobileCandidateApiTest::test_saved_jobs_list_save_unsave_and_duplicate_handling` |

### Password recovery (GAP-AUTH-RECOVERY)

| Item | Value |
| --- | --- |
| Method / route | `POST /api/v1/auth/forgot-password` |
| Body | `{ "email": string }` |
| Response `200` | Generic message (no account enumeration) |
| Mobile | `AuthService.requestPasswordRecovery`, `PasswordRecoveryScreen` success copy |

### Profile (final mobile pass)

| Item | Value |
| --- | --- |
| Read | `GET /api/v1/account/profile/personal` + `GET /api/v1/account/profile/professional` → `CandidateProfile` via `ProfileApiMapper` |
| Write | Section saves: personal location, summary (bio), job preferences, skills (POST skill), qualifications (PATCH), experience (POST employment history) |
| Blocked writes | **Education** and **certifications** — no authorized mobile write routes (validation message; data still loads from GET) |
| Mobile | `LiveProfileRepository`, `HireApiService` profile methods, profile tab refresh after edit |
| Tests added | `test/features/hire/profile/live_profile_repository_test.dart` — **NOT RUN — deferred by product owner** |

### Documents / resumes (final mobile pass)

| Item | Value |
| --- | --- |
| List / builder | `GET /api/v1/account/resumes`, `GET /api/v1/account/resumes/builder` |
| Upload | `POST /api/v1/account/resumes` multipart (`file`, optional `title`, `is_primary`) — PDF/DOC/DOCX, 5 MB per Laravel rules |
| Mobile | `LiveDocumentsRepository`, `file_picker` on documents screen |
| Tests added | `test/features/hire/profile/live_documents_repository_test.dart` — **NOT RUN — deferred by product owner** |

### Email verification (final mobile pass)

| Item | Value |
| --- | --- |
| Status | `GET /api/v1/account/verification/email` (via account flags + resend flow) |
| Resend | `POST /api/v1/account/verification/email/resend` |
| Mobile | `AccountVerificationScreen`, wizard + apply error copy, `AuthController.refreshAccount` |

### Applications

| Capability | Backend | Mobile client |
| --- | --- | --- |
| Applications list/detail/submit/resume | Present | Integrated; apply POST body remains `{}` (server uses profile primary resume) |
| Job questions in wizard | Not in mobile apply contract | **Blocked** — wizard documents omission |

**Defect closure (2026-10-16) — Applications tab asked for sign-in while already authenticated**

| Item | Detail |
| --- | --- |
| Root cause | `CandidateApplicationsScreen` constructed `ApplicationsController(LiveApplicationsRepository())` without the app `Provider` `HireApiService` / bearer token, so list requests ran unauthenticated and surfaced a sign-in wall. |
| Fix | Lazy-init controller in `didChangeDependencies` via `context.read<ApplicationsRepository>()`; wait for `AuthController.bootstrapSettled` before gating; sign-in opens login tab (not registration); `SignupForm` pops after successful register when pushed from sign-in. |
| Regression tests | `test/features/hire/applications/applications_authenticated_session_test.dart` |

**Profile editing (2026-10-16)**

| Item | Detail |
| --- | --- |
| Personal | Edit form saves `location` + `professional_headline` via existing personal GET/PATCH |
| Professional sections | Unchanged: summary, skills, qualifications, experience, job preferences via documented routes |
| Blocked | Education/certification **writes** (view-only mobile forms); profile photo **upload** (read `photo_url` when present on personal GET) |
| Repository injection | `CandidateRepositoryResolver` — all candidate screens use `HireApiService` bearer token |
| Resume upload fix | Multipart upload uses injected HTTP client (`_client.send`) so auth and tests work |
| Regression tests | `live_profile_repository_test.dart` — personal patch case added |

## Workstream D — Tests

| Suite | Result |
| --- | --- |
| Laravel `MobileAuthApiTest` + `PublicSiteBrandingApiTest` | **10 passed**, 0 failed |
| Flutter full suite (2026-10-16 E2E pass) | **`flutter test` — 172 passed**, 0 failed |
| `flutter analyze` (2026-10-16) | **0 issues** |
| Candidate web build | **`flutter build web -t lib/preview/hire_candidate_preview_main.dart -o build/web_candidate` — success** |

No production deploy, commit, or push performed.

## Remaining release gates (external / not implementable in mobile-only pass)

1. **Profile education & certification writes** — **Implemented 2026-10-16:** Laravel `POST/PATCH/DELETE` …/educations and …/certifications; mobile `POST` add via edit forms. Staging E2E not run.
2. **Profile photo upload** — **Implemented 2026-10-16:** multipart `PATCH /api/v1/account/profile/personal` with `photo`; mobile `ProfilePhotoFlow` + repository tests. Device/staging E2E not run.
2. **Apply wizard job questions** — Blocked; `POST /jobs/{slug}/apply` accepts empty body only.
3. **Master data / filters**, **messaging**, **notifications**, **legal content URLs** — Still section 4 gaps in API register.
4. **Staging / device E2E** — Requires HTTPS `OURNEAR_API_BASE`, physical or emulator QA, then APK build (out of scope for this pass).
5. **Automated test execution** — New/updated tests marked **NOT RUN — deferred by product owner** for this pass.

## Mobile integration checklist (new/changed)

- Set `OURNEAR_API_BASE` (compile-time) to staging Laravel HTTPS origin.
- **Register:** `POST /api/v1/auth/register` with full signup form fields; store token; show verification notice.
- **Login / account / logout:** unchanged.
- **Branding:** `GET /api/v1/site/branding` on app start (`HireBrandingController.load`).
- **Saved jobs list:** `GET /api/v1/account/saved-jobs` with bearer; paginate client-side (cap 50 pages).
- **Recovery:** `POST /api/v1/auth/forgot-password`; show generic success message only.

## Implementation follow-up (code only, 2026-10-16)

Earlier rows that say education writes, photo upload, filters, messaging, or notifications are blocked are superseded by the code already in the workspaces plus this follow-up. This pass did **not execute** PHPUnit, Flutter tests, builds, staging probes, or a device.

| Item | Code status |
| --- | --- |
| Profile records | Education, certification, and employment rows can be selected, updated, and deleted by id |
| Completion | Account `profile_completion` is parsed on `AuthUser` and shown on the profile header |
| Resumes | `POST /api/v1/account/resumes/{id}/primary` and `DELETE /api/v1/account/resumes/{id}` are called from the documents screen |
| Jobs filters | `GET /api/v1/jobs/filters` feeds industry, location, category, employment type, and experience. Sort sends `oldest` or `title` |
| Messaging / notifications / legal URLs | Already present from the previous implementation. Not rebuilt in this pass |
| Stage 11 signature | `Stage11FailingCommissionActivityService::record` already accepts and forwards `?StaffingCandidateSubmission $staffingSubmission`. No edit |

Apply still posts `{}`. The Laravel apply action uses the candidate primary resume. Job-question fields were not added.

Automated tests, builds, staging checks, and physical-device checks: **not executed in this pass**.
