# OurNear Hire — API Integration Register

| Field | Value |
| --- | --- |
| Document ID | API-CANDIDATE-REGISTER |
| Authority | `OURNEAR_HIRE_CANDIDATE_APP_MASTER_SPEC.md` |
| Date | 2026-10-18 (backend cross-check) |
| Evidence limit | Client rows from mobile `lib/` + `test/`. **Backend (2026-10-18):** Hire-Web-B6 `routes/api.php` + `docs/11-API-AND-ROUTES.md` inspected; `php artisan test tests/Feature/Api/V1` **40 passed**; live `GET /api/v1/jobs?per_page=1` **200** (`meta.total` 35). Authenticated mobile E2E (login/apply/resume upload) **not run** in this pass |

## 1. How to read a row

| Evidence level | Meaning |
| --- | --- |
| Client implemented | `lib/` builds this URL and parses a response |
| Client unit-tested | A fake HTTP client in `test/` exercises that path. Stage 0 did not re-run the suite |
| Live server | Unknown. Stage 0 does not claim the Laravel route exists |
| Absent from client | No mobile code calls it. The server might still have it. That is an integration gap, not a finding that Laravel lacks the route |

Configuration:

| Define | Code | Default | Used for |
| --- | --- | --- | --- |
| `OURNEAR_API_BASE` | `ApiConfig.apiBase` | empty | JSON origin. Comments in code cite `http://10.0.2.2:8000` for the Android emulator and a host such as `http://127.0.0.1:8000` for a local Laravel |
| `OURNEAR_WEB_BASE` | `ApiConfig.webBase` | empty | External browser URLs only |

If `OURNEAR_API_BASE` is empty, job and auth calls throw `HireApiException` (`OURNEAR_API_BASE is not configured.`) or return `HireIntegrationState.apiNotConfigured`. They do not fall back to mock jobs.

Timeouts in code: 12 seconds for jobs, login, and account; 8 seconds for logout. Logout ignores network failure and still clears the local token.

## 2. Client calls that exist

### API-JOBS-LIST

| Item | Value |
| --- | --- |
| Method and path | `GET {OURNEAR_API_BASE}/api/v1/jobs` |
| Query | `page`, `per_page`, optional `keyword`, `industry`, `location`, `category`, `employment_type`, `experience`, `sort` (`oldest` or `title`) |
| Headers | `Accept: application/json` only. No bearer token |
| Caller | `HireApiService.fetchJobs` |
| Also used by | `HireApiService.checkIntegration` with `page=1` and `per_page=1` |
| UI caller | `LiveJobsRepository.fetchJobs`, used by `CandidateHomeScreen` and `HireScreen`. The candidate Jobs tab sends `keyword` when the user submits or clears a search. The unified Hire tab does not show the search field and calls the list without `keyword` |
| Success parse | JSON object with `data[]` mapped to `PublicJob`, plus `meta.current_page`, `meta.last_page`, `meta.total`, `links.next` |
| Card fields parsed | `title`, `slug`, `project`, `company_name`, `company_logo_url`, `industry`, `category`, `location`, `employment_type`, `experience`, `salary`, `posted_label`, `closing_label`, `featured`, `urgent`, `is_closed` |
| Errors | HTTP 422 means invalid parameters. Any other non-2xx becomes a failure message with the status code. Transport errors become `apiUnreachable` from `checkIntegration` |
| Client tests | `test/core/api/hire_api_service_test.dart` (success, empty, HTTP 500). `test/features/hire/jobs/jobs_repository_test.dart` (keyword query, empty page, HTTP 500, unconfigured with no HTTP call). Keyword is asserted on the repository path. HTTP 422 is covered by `test/core/api/stage7_contract_boundary_test.dart` |
| Live server | Unverified |
| Product gap | The Jobs tab sends keyword plus catalogue filters returned by `GET /api/v1/jobs/filters`. Company and salary remain job-card fields, not separate mobile query controls |

### API-JOBS-DETAIL

| Item | Value |
| --- | --- |
| Method and path | `GET {OURNEAR_API_BASE}/api/v1/jobs/{slug}` |
| Headers | `Accept: application/json` only |
| Caller | `HireApiService.fetchJobDetail`, through `LiveJobsRepository.fetchJobDetail` |
| UI caller | `HireJobDetailScreen` |
| Success parse | `data` object mapped to `PublicJobDetail`: card fields plus `description`, `responsibilities`, `requirements`, `what_we_offer`, `accepts_applications` |
| Errors | HTTP 404 maps to “This job is not publicly available.” Other non-2xx responses map to a failure with the status code. The repository maps a missing base to not configured, transport failures to offline, and HTTP 404 to not found |
| Client tests | `test/core/api/stage7_contract_boundary_test.dart` covers a successful detail, HTTP 404, and HTTP 500 through `LiveJobsRepository` |
| Live server | Unverified |
| Product gap | The detail screen shows the parsed fields when they are present. No company resource is requested. Apply Now opens application readiness; **`LiveApplicationsRepository.submitApplication`** posts to `POST /api/v1/jobs/{slug}/apply` (unit-tested; live E2E not evidenced). Closed jobs, and jobs with `accepts_applications: false`, do not show Apply Now |

### API-AUTH-LOGIN

| Item | Value |
| --- | --- |
| Method and path | `POST {OURNEAR_API_BASE}/api/v1/auth/login` |
| Headers | `Accept: application/json`, `Content-Type: application/json` |
| Body | `login` (trimmed), `password`, `device_name` fixed to `ournear-mobile` |
| Caller | `AuthService.login` |
| UI caller | `LoginScreen` through `AuthController.login` |
| Success parse | `token` string is required. `user` maps to `AuthUser` (`name`, `username`, `role`, `email_verified`, `must_change_password`, optional `profile_completion`) |
| Errors | HTTP 422 reads `errors.login[0]` when that value is a non-empty list. Otherwise the client uses a fixed credentials sentence. Other non-2xx responses become “Login failed” |
| Storage | Token is written to memory and to `flutter_secure_storage` key `ournear_mobile_bearer_token`. The password is not stored |
| Client tests | `test/core/auth/auth_service_test.dart`, `test/core/auth/auth_controller_test.dart`, `test/features/auth/login_flow_test.dart` |
| Live server | Unverified |

### API-ACCOUNT

| Item | Value |
| --- | --- |
| Method and path | `GET {OURNEAR_API_BASE}/api/v1/account` |
| Headers | `Accept: application/json`, `Authorization: Bearer {token}` |
| Caller | `AuthService.fetchAccount`, during bootstrap when a token exists |
| Success parse | `data` maps to `AuthUser`, including `profile_completion` when the account payload includes it |
| Errors | HTTP 401 clears the local session and throws “Session expired. Sign in again.” Other non-2xx responses become “Could not load account” |
| Client tests | The 401 path is in `auth_service_test.dart`. The controller bootstrap test covers a successful account load through a fake client |
| Live server | Unverified |
| Product note | Account summary. `profile_completion` is shown on the profile header when present. Professional sections still come from the profile endpoints |

### API-AUTH-REGISTER

| Item | Value |
| --- | --- |
| Method and path | `POST {OURNEAR_API_BASE}/api/v1/auth/register` |
| Headers | `Accept: application/json`, `Content-Type: application/json` |
| Body | `username`, `name`, `mobile`, `email`, `password`, `password_confirmation`, `accept_terms`, `accept_privacy`, `device_name` (`ournear-mobile`) |
| Caller | `AuthService.register` → `AuthController.register` / `LiveAccountAccessRepository.register` |
| UI caller | `SignupForm` when `OURNEAR_API_BASE` is configured |
| Success parse | `201`: `token` required; `user` → `AuthUser`; `verification_email_sent` boolean |
| Errors | HTTP 422 → first Laravel validation message |
| Client tests | `auth_service_test.dart`, `candidate_account_access_test.dart` |
| Live server | Verified agent probe 201 on local `127.0.0.1:8000` (2026-10-16) |

### API-AUTH-FORGOT-PASSWORD

| Item | Value |
| --- | --- |
| Method and path | `POST {OURNEAR_API_BASE}/api/v1/auth/forgot-password` |
| Body | `{ "email": string }` |
| Caller | `AuthService.requestPasswordRecovery` |
| UI caller | `PasswordRecoveryScreen` |
| Success | HTTP 200, generic message (no enumeration) |
| Client tests | `candidate_account_access_test.dart` (unconfigured path) |
| Live server | Laravel feature test only in this session |

### API-SITE-BRANDING

| Item | Value |
| --- | --- |
| Method and path | `GET {OURNEAR_API_BASE}/api/v1/site/branding` |
| Headers | `Accept: application/json` only |
| Caller | `HireApiService.fetchPublicBranding` → `HireBrandingService.load` |
| Success parse | `data.app_name`, `data.logo_url` (nullable absolute URL) |
| Fallback | Static “OurNear Hire” when API unset or request fails |
| Client tests | `hire_branding_service_test.dart` |
| Live server | Verified agent probe 200 on local `127.0.0.1:8000` (2026-10-16) |

### API-PROFILE-READ

| Item | Value |
| --- | --- |
| Method and path | `GET /api/v1/account/profile/personal` and `GET /api/v1/account/profile/professional` |
| Headers | Bearer required |
| Caller | `HireApiService.fetchProfilePersonal/Professional` → `LiveProfileRepository.fetchProfile` → `ProfileApiMapper` |
| Success | Merged `CandidateProfile` for UI sections |
| Errors | 401 session; 422 validation on writes |
| Client tests | `live_profile_repository_test.dart` (5 tests, 2026-10-16) |
| Notes | Personal GET `photo_url` / `profile_photo_url` for avatar display |
| Repository access | Use `CandidateRepositoryResolver` or `Provider` repositories — never bare `Live*Repository()` without `HireApiService` |

### API-PROFILE-WRITE

| Item | Value |
| --- | --- |
| Methods | `PATCH` personal (JSON or multipart `photo` + required `name`, `username`, `preferred_language`); `PATCH` professional; `POST` …/skills; `POST` …/employment-histories; `POST` …/educations; `POST` …/certifications |
| Laravel additive (2026-10-16) | `PATCH`/`DELETE` …/educations/{id}`; `PATCH`/`DELETE` …/certifications/{id}` — ownership enforced |
| Photo upload (mobile) | `HireApiService.patchProfilePersonalWithPhoto` → `LiveProfileRepository.uploadProfilePhoto`; UI `ProfilePhotoFlow` on Profile tab |
| Caller | `LiveProfileRepository.save` / `uploadProfilePhoto` per section |
| Persisted UI | `ProfileSnapshot.persisted == true` after server round-trip |
| Client tests | `live_profile_repository_test.dart`; Laravel `MobileCandidateApiTest` (education, certification, photo) |
| Live server | Unverified in agent environment (no staging E2E this pass) |

### API-RESUME-LIBRARY

| Item | Value |
| --- | --- |
| Methods | `GET /api/v1/account/resumes`, `GET …/builder`, `POST …/resumes` (multipart), `POST …/resumes/{id}/primary`, `DELETE …/resumes/{id}` |
| Caller | `LiveDocumentsRepository` |
| Upload | `file_picker` + PDF/DOC/DOCX bytes; max 5 MB per Laravel |
| Client tests | `live_documents_repository_test.dart` includes upload plus delete/primary. **Not executed in this pass** |

### API-VERIFY-EMAIL

| Item | Value |
| --- | --- |
| Methods | `GET /api/v1/account/verification/email`, `POST …/verification/email/resend` |
| Caller | `AccountVerificationScreen`, `HireApiService` |
| Client tests | None added (**NOT RUN policy**) |

### API-JOBS-SAVED-LIST

| Item | Value |
| --- | --- |
| Method and path | `GET {OURNEAR_API_BASE}/api/v1/account/saved-jobs?page={n}` |
| Headers | Bearer required |
| Caller | `HireApiService.fetchSavedJobs` → `LiveJobsRepository.fetchSavedJobs` |
| UI caller | `SavedJobsScreen` |
| Success parse | Same job card JSON as public list (`data[]`, `meta.*`) |
| Client tests | `live_jobs_api_integration_test.dart` |
| Live server | Laravel `MobileCandidateApiTest` (pre-existing); mobile HTTP unverified E2E |

### API-AUTH-LOGOUT

| Item | Value |
| --- | --- |
| Method and path | `POST {OURNEAR_API_BASE}/api/v1/auth/logout` |
| Headers | `Accept: application/json`, `Authorization: Bearer {token}` |
| Body | None. The client does not send a JSON body |
| Caller | `AuthService.logout`, then `clearSession` even if the POST throws |
| UI caller | Profile “Sign out” |
| Client tests | `test/core/api/stage7_contract_boundary_test.dart` asserts `POST /api/v1/auth/logout`, an empty body, the bearer header, and session clearing after HTTP 500 and after a thrown request |
| Live server | Unverified |

### API-APP-LIST

| Item | Value |
| --- | --- |
| Method and path | `GET {OURNEAR_API_BASE}/api/v1/account/applications` |
| Query | `page` (default 1) |
| Headers | `Accept: application/json`, `Authorization: Bearer {token}` |
| Caller | `HireApiService.fetchApplications` → `LiveApplicationsRepository.fetchApplications` |
| UI caller | `CandidateApplicationsScreen` via `ApplicationsController` when `OURNEAR_API_BASE` is configured and the user is signed in. Controller must use `Provider<ApplicationsRepository>` (injected `LiveApplicationsRepository` with `HireApiService`); do not construct a bare `LiveApplicationsRepository()` on the screen |
| Success parse | `data[]` mapped through `CandidateApplication.fromApiJson` (`id`, `business_id`, `status`, `applied_at`, nested `job` title/slug/company/location, `has_resume`). `meta.current_page`, `meta.last_page`, `meta.total`. `LiveApplicationsRepository.fetchApplications` requests pages `1..last_page` (cap 50) and merges rows |
| Errors | HTTP 401 clears session via `AuthService`. Other non-2xx become repository unavailable/offline |
| Unconfigured | `LiveApplicationsRepository` throws `historyUnavailable` before HTTP when `OURNEAR_API_BASE` is empty |
| Client tests | `test/features/hire/applications/live_applications_repository_test.dart` |
| Live server | Unverified |

### API-APP-DETAIL

| Item | Value |
| --- | --- |
| Method and path | `GET {OURNEAR_API_BASE}/api/v1/account/applications/{id}` |
| Headers | Bearer required |
| Caller | `HireApiService.fetchApplicationDetail` → `LiveApplicationsRepository.fetchApplication` |
| UI caller | `CandidateApplicationDetailScreen.fetch` when a repository is supplied |
| Success parse | `data` object via `CandidateApplication.fromApiJson` |
| Errors | HTTP 404 → not found. HTTP 401 → session expired |
| Client tests | Covered indirectly through repository `_guard` mapping; no dedicated fake-HTTP detail test yet |
| Live server | Unverified |

### API-APP-SUBMIT

| Item | Value |
| --- | --- |
| Method and path | `POST {OURNEAR_API_BASE}/api/v1/jobs/{slug}/apply` |
| Headers | `Accept: application/json`, `Content-Type: application/json`, bearer token |
| Body | Empty JSON object `{}` |
| Caller | `HireApiService.submitJobApplication` → `LiveApplicationsRepository.submitApplication` |
| UI caller | Application wizard submit when live repository is configured |
| Success parse | `data.application_id`, `data.status`, optional `data.business_id` → `JobApplicationResult` |
| Errors | HTTP 403 with `error_code: email_verification_required` → verification required. HTTP 422 → validation message. HTTP 401 → session expired |
| Unconfigured | Throws `submitUnavailable` before HTTP |
| Client tests | `live_applications_repository_test.dart` (verification path). Wizard live-unavailable tests when API base unset |
| Live server | Unverified |
| Product gap | Wizard resume attachment and job questions are not sent in the POST body |

### API-APP-RESUME-DOWNLOAD

| Item | Value |
| --- | --- |
| Method and path | `GET {OURNEAR_API_BASE}/api/v1/account/applications/{applicationId}/resume` |
| Headers | Bearer required |
| Caller | `HireApiService.downloadApplicationResume` → `LiveApplicationsRepository.downloadResume` |
| UI caller | `CandidateApplicationDetailScreen` when `has_resume` and live repository |
| Client tests | None |
| Live server | Unverified |

### API-JOBS-SAVED-TOGGLE

| Item | Value |
| --- | --- |
| Method and path | `POST` or `DELETE {OURNEAR_API_BASE}/api/v1/account/saved-jobs/{slug}` |
| Headers | Bearer required |
| Caller | `HireApiService.setJobSaved` → `LiveJobsRepository.setJobSaved` |
| UI caller | Job card bookmark control when signed in and API configured |
| Success parse | `data.is_saved` boolean |
| Client tests | `test/features/hire/jobs/live_jobs_api_integration_test.dart` |
| Live server | Unverified |
| Product gap | List is `GET /api/v1/account/saved-jobs`. This toggle row is only the save/unsave pair |

## 3. Browser fallback URLs

These are used only when `OURNEAR_WEB_BASE` is non-empty. `url_launcher` opens them outside the app. They are not the JSON API.

| ID | URL | Built by | UI |
| --- | --- | --- | --- |
| WEB-JOBS | `{OURNEAR_WEB_BASE}/jobs` | `ApiConfig.jobsIndexUri` | Jobs screen button “Browse jobs on web” |
| WEB-HIRE | `{OURNEAR_WEB_BASE}/hire` | `ApiConfig.hireLandingUri` | No widget calls it |

Live website behaviour is unverified. Stage 0 did not open the website project.

## 4. Integration gaps

The candidate product needs these capabilities. The mobile tree has no URL, model, or repository method for them. Do not invent the path, verb, or body in a feature stage. Add a row in section 2 only when the mobile client actually calls a contract.

| ID | Capability | Master-spec feature | Client status |
| --- | --- | --- | --- |
| GAP-AUTH-REGISTER | Registration | F-AUTH-REGISTER | **Closed (2026-10-16).** API-AUTH-REGISTER. Requires `OURNEAR_API_BASE` at compile time |
| GAP-AUTH-RECOVERY | Password recovery | F-AUTH-RECOVERY | **Closed (2026-10-16).** API-AUTH-FORGOT-PASSWORD when API base configured |
| GAP-AUTH-VERIFY | Email verification or must-change-password follow-up | F-AUTH-SETUP | **Partial (2026-10-16).** Resend via API-VERIFY-EMAIL; must-change-password still display-only |
| GAP-PROFILE-READ | Professional profile read | F-PROFILE | **Closed (2026-10-16).** API-PROFILE-READ when configured + signed in |
| GAP-PROFILE-WRITE | Separate edit forms and completion validation | F-PROFILE | **Partial (implementation follow-up).** Education, certification, and employment can be selected, updated, and deleted by record id. Profile header shows account `profile_completion` when the account payload includes it. Automated tests for this follow-up were **not executed in this pass** |
| GAP-PROFILE-PHOTO | Avatar upload | F-PROFILE | **Closed (automated, 2026-10-16).** Pick, preview, multipart PATCH, reload `photo_url`. Staging/device not run |
| GAP-JOBS-SEARCH | Search beyond the single `keyword` parameter | F-JOBS-SEARCH | **Partial (implementation follow-up).** Jobs tab sends keyword, industry, location, category, employment type, experience, and sort (`oldest`/`title`) from `GET /api/v1/jobs/filters`. Company and salary filters are not separate controls. **Not executed in this pass** |
| GAP-JOBS-SAVED | Save, unsave, list saved jobs, availability | F-JOBS-SAVED | **Closed (2026-10-16).** Toggle + API-JOBS-SAVED-LIST when signed in and configured |
| GAP-COMPANY | Company details | F-JOBS-COMPANY | Absent. Job payloads include `company_name` and `company_logo_url` only. The company screen shows those published fields and does not request another resource |
| GAP-APP-LIST | Candidate application history | F-APP-HISTORY | **Partial (client).** API-APP-LIST wired when `OURNEAR_API_BASE` is set and user is signed in. Unconfigured builds throw `historyUnavailable`. Live server unverified. Demo rows are not injected in the candidate shell |
| GAP-APP-DETAIL | Application detail, timeline, interview, allowed actions | F-APP-TIMELINE | **Partial (client).** API-APP-DETAIL wired when configured. Timeline/interview/actions beyond parsed fields remain unavailable |
| GAP-APP-STATUS | Status enum and supported filters | F-APP-HISTORY | Open. The client enum is not a Laravel enum. Demo chips filter the complete local list and are labelled as such. The live screen sends no status query |
| GAP-APP-SUBMIT | Readiness, wizard, questions, resume choice, confirmation, duplicate protection | F-APP-WIZARD, F-APP-READINESS | **Partial (client).** API-APP-SUBMIT POST when configured. Wizard still omits resume/questions in body. Unconfigured throws `submitUnavailable`. Mock path labelled demo. No idempotency key documented |
| GAP-RESUME-GENERATED | Generated Hire Resume | F-DOCS | **Partial (2026-10-16).** Builder preview text via API-RESUME-LIBRARY when configured |
| GAP-RESUME-UPLOAD | One replaceable My Resume, validation, authorized download | F-DOCS | **Partial (implementation follow-up).** Multipart upload, library list, set primary, and delete are wired. In-app file download is still not opened from the documents screen. **Not executed in this pass** |
| GAP-RESUME-SNAPSHOT | Historical resume snapshot per application | F-DOCS | Open. Application review still says the resume is not attached |
| GAP-MASTER-DATA | Industries, categories, and filter or sort options | F-JOBS-CATALOGUE, F-JOBS-FILTERS | **Partial (implementation follow-up).** `GET /api/v1/jobs/filters` feeds industry, location, category, employment type, and experience dropdowns. Sort uses the website values `oldest` and `title`. **Not executed in this pass** |
| GAP-MSG | Inbox, thread, composer, unread | F-MSG | **Partial (2026-10-16).** Application-scoped inbox/thread/send via `/api/v1/account/messages/inbox` and `/account/applications/{id}/messages`. Not a general candidate inbox beyond applications |
| GAP-NOTIF | Notification list and read state | F-NOTIF | **Partial (2026-10-16).** `GET /api/v1/account/notifications` and mark-read. Dismiss marks read. No push |
| GAP-CONTENT | Help and support, terms, privacy policy | F-PREFS | **Partial (2026-10-16).** Menu opens `GET /api/v1/site/contact` URLs (`terms_url`, `privacy_url`, `about_url`) and support contact fields. No invented legal text |
| GAP-PREFS | Job alerts, notification preferences, privacy, account security | F-PREFS | Open. Settings navigate to the existing job-preferences form. Password change and account deletion have no API action |
| GAP-BRAND | Public mobile branding (logo, app name from admin) | F-PREFS | **Closed (2026-10-16).** API-SITE-BRANDING |
| GAP-ONBOARDING | Optional onboarding content | F-AUTH-ONBOARDING | Partial. Three-page optional flow for the candidate shell. Completion is stored locally in secure storage under `ournear_hire_candidate_onboarding_complete`. It is not server data |

## 5. Mock data that is not an API

| Source | IDs | Rule |
| --- | --- | --- |
| `MockApplicationsRepository.demoApplications` | `demo-app-001`, `demo-app-002`, `demo-app-003` | Local. `ApplicationsDemoBanner` shows when `source == mockDemo`. The candidate shell uses `LiveApplicationsRepository`, not this mock. A mock submit id looks like `demo-app-local-1` and is not a server row |
| Status labels | Submitted, Under review, Shortlisted, Not selected | Presentation enum only |
| `MockJobsRepository` | Rows supplied by the test or caller | Local. Used by widget tests. The candidate shell constructs `LiveJobsRepository`, not this mock. If a mock page is shown, the UI says the rows are not from the OurNear server |
| `MockMessagesRepository` | `demo-msg-001` | Local. Shown only when that repository is injected. The banner says the rows are not from OurNear |
| `MockNotificationsRepository` | `demo-notif-001` | Local. Shown only when that repository is injected. Mark read does not clear unread |

## 6. Authorization matrix (client behaviour only)

| Call | Signed-out behaviour in the client |
| --- | --- |
| Job list and detail | Allowed. No token is attached |
| Login | Creates the token |
| Account and logout | Require a stored token. HTTP 401 on account clears it |
| Section 4 gaps | Not called |

`AuthUser.role` is displayed and is not used to switch the candidate shell into the employer shell. Keep it that way. A candidate build must not navigate to `HireEmployerApp` because a role string says employer. That routing does not exist today, because the candidate entry never constructs the employer app.

## 7. How later stages update this register

When a stage adds a client call:

1. Add or update a row in section 2 in the same change.
2. State the method, path, headers, body, parsed fields, and error mapping from the mobile source.
3. Say whether a fake-HTTP test exists.
4. Leave “Live server” unverified until a stage is explicitly allowed to call Laravel and records the result.
5. Do not close a gap by guessing a URL.

## 8. Stage 7 contract inventory

Stage 7 inspected the mobile client and this register. It did not inspect the Laravel project. No row moved from section 4 into section 2. No new HTTP call was added.

Status words: `INTEGRATED` means the mobile client calls a verified contract and tests cover it. `PARTIAL` means only the listed portion is verified. `BLOCKED` means the mobile tree has no contract, so the live repository stays unavailable. `NOT_STARTED` means no candidate screen calls it.

| Feature | Screen | Repository | Status | Contract | Persistence | Tests | Missing contract |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Public job list | Home, Jobs | `LiveJobsRepository.fetchJobs` | INTEGRATED | `GET /api/v1/jobs` with `page`, `per_page`, optional `keyword`. No bearer token | Read only | `hire_api_service_test.dart`, `jobs_repository_test.dart`, Stage 7 HTTP 422 | Separate skill, company, and location query fields |
| Public job detail | Job detail | `LiveJobsRepository.fetchJobDetail` | INTEGRATED | `GET /api/v1/jobs/{slug}` | Read only | `stage7_contract_boundary_test.dart` | None for the parsed fields |
| Search beyond keyword | Jobs | Same list call | PARTIAL | One `keyword` only | Read only | Keyword assertion in `jobs_repository_test.dart` | GAP-JOBS-SEARCH |
| Saved jobs | Saved jobs | `fetchSavedJobs` | BLOCKED | None | None | Repository throws before HTTP | GAP-JOBS-SAVED |
| Industry catalogue and filters | Home, Jobs | `fetchIndustryShortcuts` | BLOCKED | None | None | Repository throws before HTTP | GAP-MASTER-DATA |
| Company resource | Company details | Job payload fields only | PARTIAL | `company_name` and `company_logo_url` on the job | Read only | Detail mapping in the Stage 7 test | GAP-COMPANY |
| Login | Login | `AuthService.login` | INTEGRATED | `POST /api/v1/auth/login` | Token stored. Password is not stored | `auth_service_test.dart` | None for the parsed `AuthUser` |
| Account | Session restore | `AuthService.fetchAccount` | INTEGRATED | `GET /api/v1/account` with bearer token. HTTP 401 clears the session | Account identity only | `auth_service_test.dart` | Professional profile fields are not in this mapping |
| Logout | Profile, Settings | `AuthService.logout` | INTEGRATED | `POST /api/v1/auth/logout` with bearer token and an empty body. Local session clears even when the POST fails | Session cleared | `stage7_contract_boundary_test.dart` | None for session clearing |
| Registration | None | None | NOT_STARTED | None | None | None | GAP-AUTH-REGISTER |
| Password recovery | None | None | NOT_STARTED | None | None | None | GAP-AUTH-RECOVERY |
| Email verification follow-up | None | `AuthUser` flags only | BLOCKED | Flags are parsed. No follow-up URL | None | Login parse covers the flags | GAP-AUTH-VERIFY |
| Professional profile read and write | Profile, edit forms | `LiveProfileRepository` | BLOCKED | None. Account is not used as the profile | Live save says nothing was saved | `stage7_contract_boundary_test.dart` | GAP-PROFILE-READ, GAP-PROFILE-WRITE |
| Profile photo | Profile overview | None | BLOCKED | None | None | Overview copy | GAP-PROFILE-PHOTO |
| Hire Resume, My Resume, snapshot | Documents, wizard | `LiveDocumentsRepository` | BLOCKED | None | Upload says no file was uploaded | Stage 7 boundary test | GAP-RESUME-GENERATED, GAP-RESUME-UPLOAD, GAP-RESUME-SNAPSHOT |
| Application list, detail, submit | Applications, wizard, detail | `LiveApplicationsRepository` | PARTIAL (client) | List/detail/submit/resume download per section 2. Status filter query not sent. Wizard body omits resume/questions | `confirmedByServer` when server returns a positive `application_id` | `live_applications_repository_test.dart`, application flow tests, Stage 7 boundary | GAP-APP-STATUS filter; GAP-APP-SUBMIT wizard body; live server unverified |
| Messages | Inbox, thread | `LiveMessagesRepository` | BLOCKED | None | Send stays disabled | Communication tests and the Stage 7 boundary test | GAP-MSG |
| Notifications | Notifications | `LiveNotificationsRepository` | BLOCKED | None | Mark read and dismiss say nothing was changed | Communication tests and the Stage 7 boundary test | GAP-NOTIF |
| Job alerts and notification preferences | Settings | Profile form for job preferences only | BLOCKED | Job-preference text uses the blocked profile save. No alerts contract | Not saved on OurNear | Settings test in `candidate_communication_test.dart` | GAP-PREFS |
| Help, terms, privacy policy | Settings | None | BLOCKED | None | None | Settings test | GAP-CONTENT |
| Onboarding | None | None | NOT_STARTED | None | None | None | GAP-ONBOARDING |

A live failure must not fall back to demo rows. The candidate shell injects the live repositories. Demo ids such as `demo-app-001`, `demo-msg-001`, and `demo-notif-001` appear only when a test injects the matching mock.

Stage 8 added no section 2 row and no section 4 path. The five calls above remain the client contract.

## Design passes (2026-10-15)

No new HTTP paths, payloads, or response fields were added in Phase 1–2 UI work. Shared widgets (`HireNoticeBanner`, auth branding, settings tiles) and screen polish consume existing models and blocked repositories only. **GAP-AUTH-REGISTER**, **GAP-JOBS-SAVED** (GET list), **GAP-PROFILE-***, **GAP-RESUME-***, **GAP-MSG**, and **GAP-CONTENT** remain unchanged. See `OURNEAR_HIRE_DESIGN_GAP_REPORT.md`.

## Implementation follow-up (code only)

Section 2 and section 4 above are the current client contract. Section 8 remains the historical Stage 7 inventory and is not the current status.

This follow-up did **not execute** `flutter analyze`, `flutter test`, `flutter build`, Laravel PHPUnit, staging checks, or a physical device. Do not treat earlier numeric results in this file as results of this pass.

Still open: GAP-PREFS, GAP-APP-STATUS live filters, GAP-RESUME-SNAPSHOT, GAP-COMPANY as a separate resource, in-app resume file download, and apply body beyond `{}` (server `submitFromProfile` uses the primary resume).
