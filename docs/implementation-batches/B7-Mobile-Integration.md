# B7 — Mobile Integration

Status: **In progress — candidate app integrated against `/api/v1`; batch not complete.** Staging, device E2E, and full Flutter suite closure remain open. Employer mobile is out of B7 scope.

## Scope

Connect the OurNear Hire **candidate** Flutter app to the Laravel `/api/v1` JSON layer (Sanctum bearer, `account:mobile`). Laravel + MySQL remain source of truth. No Firebase/Supabase production backend.

| Workspace | Path |
| --- | --- |
| Laravel (authoritative API) | `C:\Ournear Hub\Hire-Web-B6` |
| Flutter candidate app | `C:\Ournear Hub\ournear_mobile` |
| Mobile specs & registers | `ournear_mobile/docs/mobile/` |

Run candidate shell:

```bash
cd "C:\Ournear Hub\ournear_mobile"
flutter run -t lib/preview/hire_candidate_preview_main.dart --dart-define=OURNEAR_API_BASE=http://127.0.0.1:8000
```

Android emulator toward host Laravel: `OURNEAR_API_BASE=http://10.0.2.2:8000`.

## Tasks

- [x] B7-01: Map `/api/v1` contracts (`docs/11-API-AND-ROUTES.md`, `routes/api.php`) to mobile client (`HireApiService`, `AuthService`, live repositories).
- [x] B7-02: Auth — login, logout, secure token storage, session restore, registration and forgot-password when `OURNEAR_API_BASE` is set.
- [x] B7-03: Public jobs — list, detail, filters endpoint, optional bearer `is_saved`.
- [x] B7-04: Candidate account — `GET /account`, personal/professional profile PATCH, skills and employment CRUD where exposed.
- [x] B7-05: Applications — list, detail, `POST /jobs/{slug}/apply` via `LiveApplicationsRepository` (no mock in production wiring).
- [x] B7-06: Saved jobs — list, save, unsave when authenticated.
- [x] B7-07: Resumes — library upload/replace/primary/delete; **Hire Resume** preview text from `GET /account/resumes/builder` separate from **My Resume** uploads (`documents_screen.dart` copy).
- [x] B7-08: Notifications and application-scoped messages — live repositories wired in `HireCandidateApp`.
- [ ] B7-09: Staging / physical-device E2E against authorized API base and credentials.
- [ ] B7-10: Full Flutter test suite green + release build evidence on target devices.
- [ ] B7-11: Close mobile release register `REL-QA-01` and remaining **PARTIAL** rows with live-server proof.

## Acceptance criteria

1. Candidate app uses **live** repositories when `OURNEAR_API_BASE` is configured; no silent mock jobs in the candidate shell.
2. Auth session uses Sanctum bearer stored in secure storage; logout revokes token when reachable.
3. Job discovery and detail consume `GET /api/v1/jobs` and `GET /api/v1/jobs/{slug}`.
4. Apply, applications list, profile, saved jobs, resume library, and builder preview work end-to-end against Laravel when API base + candidate credentials are valid.
5. Uploaded resumes and generated Hire Resume remain **distinct** in UI and data paths.
6. Backend PHPUnit `tests/Feature/Api/V1` passes on the Hire-Web-B6 checkout.
7. Documented blockers for missing contracts (employer mobile, staffing intake JSON, in-app resume builder editor) without backend workarounds.

## Implementation evidence (2026-10-18)

### Backend (Hire-Web-B6, fresh this session)

| Check | Result |
| --- | --- |
| `php artisan test tests/Feature/Api/V1` | **40 passed**, 265 assertions (~8.6s) |
| Live `GET http://127.0.0.1:8000/api/v1/jobs?per_page=1` | **200**, `meta.total` = **35** public jobs |

Historical note: `docs/CURRENT-STATE.md` cites **31** Api V1 tests from 2026-10-15; current suite count is **40** (includes branding, messaging, register, filters).

### Flutter client (ournear_mobile, fresh this session)

Focused unit tests (not full suite):

```text
flutter test test/core/auth/auth_service_test.dart \
  test/features/hire/jobs/jobs_repository_test.dart \
  test/features/hire/applications/live_applications_repository_test.dart \
  test/core/api/hire_api_service_test.dart
```

**19 passed**, 0 failed.

Historical full suite (`test_run2.log`, not re-run this session): **151 passed, 2 failed** (`hire_design_system_test.dart` HireBrandMark).

### Wiring (code — pre-existing, verified by inspection)

- `HireCandidateApp` provides `LiveJobsRepository`, `LiveApplicationsRepository`, `LiveProfileRepository`, `LiveDocumentsRepository`, `LiveMessagesRepository`, `LiveNotificationsRepository`, `LiveAccountAccessRepository`.
- `AuthService`: `POST /api/v1/auth/login`, `POST /api/v1/auth/register`, forgot-password, `GET /api/v1/account`, logout.
- `HireApiService`: jobs, apply, applications, saved jobs, profile, resumes, builder preview, notifications, messages inbox.

## API endpoints integrated (client → Laravel)

| Area | Methods | Client | Live E2E this session |
| --- | --- | --- | --- |
| Public jobs | `GET /jobs`, `GET /jobs/filters`, `GET /jobs/{slug}` | `HireApiService.fetchJobs`, `fetchJobDetail` | List probe only (35 jobs) |
| Auth | `POST /auth/login`, `register`, `forgot-password`, `POST /auth/logout`, `GET /account` | `AuthService`, `AuthController` | Not run (no credentials in agent session) |
| Profile | `GET/PATCH` personal & professional; skills/employment/education/certification routes | `LiveProfileRepository` | Not run |
| Applications | `GET /account/applications`, `GET …/{id}`, `POST /jobs/{slug}/apply` | `LiveApplicationsRepository` | Unit-tested with fake HTTP only |
| Saved jobs | `GET/POST/DELETE /account/saved-jobs/{slug}` | `HireApiService.setJobSaved`, `fetchSavedJobs` | Not run |
| Resumes | Library CRUD + `GET /account/resumes/builder` preview | `LiveDocumentsRepository` | Not run |
| Notifications | `GET /account/notifications`, mark read | `LiveNotificationsRepository` | Not run |
| Messages | `GET /account/messages/inbox`, application thread | `LiveMessagesRepository` | Not run |
| Site | `GET /site/contact`, `GET /site/branding` | branding/contact helpers | Not run |

## Features: complete vs partial vs blocked

| Feature | Status | Notes |
| --- | --- | --- |
| Job list & detail (guest) | **Complete** (client + backend tests) | Requires `OURNEAR_API_BASE` |
| Login / logout / session | **Complete** (client unit tests) | Staging login not evidenced |
| Registration | **Complete** (client → `POST /auth/register`) | Register issues token per backend |
| Apply for job | **Complete** (client seam + backend route) | Wizard is minimal POST; no extra questionnaire body |
| Application history | **Complete** (client) | Staging E2E not evidenced |
| Profile edit | **Complete** (client routes) | Cross-check vs website not run |
| Uploaded resume library | **Complete** (client) | Distinct from Hire Resume preview |
| Hire Resume preview in app | **Partial** | Preview text from builder API; no in-app builder editor (web builder remains primary) |
| Saved jobs UI | **Partial** | API wired; live bookmark E2E not evidenced this session |
| Notifications / messages | **Partial** | API exists; live inbox not evidenced |
| Employer mobile | **Out of scope** | `HireEmployerApp` preview only |
| Staffing intake JSON | **Blocked** | No mobile route per `docs/11-API-AND-ROUTES.md` |
| Delete account | **Blocked** | No backend delete API; menu shows honest message |

## Pending work & blockers

1. **REL-QA-01 / B7-09:** Manual device or staging run with real credentials and `--dart-define=OURNEAR_API_BASE=…`.
2. **B7-10:** Re-run full `flutter test` and fix **2** historical failures in `hire_design_system_test.dart`.
3. **Live E2E:** Login → apply → applications list → upload resume on physical device or emulator against Hire-Web-B6 port 8000.
4. **Documentation drift:** `ournear_mobile/docs/mobile/OURNEAR_HIRE_API_INTEGRATION_REGISTER.md` still states “Laravel project was not inspected” (2026-10-14); update when mobile docs are next edited.
5. **Employer / unified shell:** Not part of B7 candidate batch.

## Decision

**B7 is not complete.** Core candidate integrations are implemented and backend Api V1 tests pass on Hire-Web-B6, but batch acceptance requires staging/device E2E and a green full Flutter suite. **Do not** mark B7 complete or mobile release-ready until `REL-QA-01` and open **PARTIAL** rows in `ournear_mobile/docs/mobile/OURNEAR_HIRE_RELEASE_ISSUE_REGISTER.md` are closed with evidence.

## Related docs

- `docs/11-API-AND-ROUTES.md`
- `docs/CURRENT-STATE.md` (Candidate mobile API v1)
- `ournear_mobile/docs/mobile/OURNEAR_HIRE_CANDIDATE_APP_MASTER_SPEC.md`
- `ournear_mobile/docs/mobile/OURNEAR_HIRE_MOBILE_IMPLEMENTATION_ROADMAP.md`
- `ournear_mobile/docs/mobile/OURNEAR_HIRE_API_INTEGRATION_REGISTER.md`
- `ournear_mobile/docs/mobile/OURNEAR_HIRE_RELEASE_ISSUE_REGISTER.md`
