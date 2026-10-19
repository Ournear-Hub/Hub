# OurNear Hire Candidate Mobile — Release Evidence Report

| Field | Value |
| --- | --- |
| Document ID | RELEASE-REPORT |
| Workspace | `C:\Ournear Hub\ournear_mobile` |
| Preview entry | `lib/preview/hire_candidate_preview_main.dart` |
| Report date | 2026-10-16 |
| Protected workspace | `D:\Ournear Hub\Ournear Hire` — not accessed |

## 1. Executive verdict

**NOT READY** for production release against the full master-spec scope.

**INSUFFICIENT EVIDENCE** for live-server persistence and for complete manual device QA.

The **candidate preview build is regression-stable** in automation (tests, analyze, web/APK artifacts) and suitable for **staged QA** against a configured `OURNEAR_API_BASE`.

## 2. Baseline vs this run

| Evidence | Baseline (prior report) | Rerun this stabilization cycle |
| --- | --- | --- |
| Flutter tests | 140 passed (historical) | **163 passed** (`flutter test`, exit 0 — 2026-10-16 registration/branding/APK pass) |
| Flutter analyze | 0 errors, 2 info lints (historical) | **0 issues** (`flutter analyze`, exit 0 — same session) |
| Web preview build | Success (historical) | **Success** (`flutter build web -t lib/preview/hire_candidate_preview_main.dart -o build/web_candidate`, exit 0 — 2026-10-16) |
| Debug APK | Success (historical) | **Success** (`flutter build apk --debug -t lib/preview/hire_candidate_preview_main.dart` → `build/app/outputs/flutter-apk/app-debug.apk`, exit 0 — 2026-10-16) |
| Design gap report | Phase 1 only (historical) | **`OURNEAR_HIRE_DESIGN_GAP_REPORT.md`** updated Phase 2 shared components |
| Manual browser/device QA | Not performed | **PARTIAL** — static web served; page title loads; Flutter canvas not fully automated in IDE browser; **device install not executed in agent environment** |

`lib/main.dart` and the unified five-tab employer shell were not modified in the implementation follow-up. Later candidate and Laravel API work did change both workspaces. The table above is historical and was **not re-run in this pass**.

## Implementation follow-up (code only)

This pass finished remaining candidate code and documentation. It did **not execute** `flutter analyze`, `flutter test`, `flutter build`, PHPUnit, staging checks, or a physical device. No APK path is claimed.

| Area | Implementation status |
| --- | --- |
| Profile edit/delete | Education, certification, and employment entries can be selected, saved, and deleted by record id |
| Profile completion | Shown from account `profile_completion` when that field is present |
| Resumes | Library list, set primary, and delete use existing account resume routes |
| Jobs search | Keyword plus filters from `GET /api/v1/jobs/filters`, with sort `oldest` or `title` |
| Messaging, notifications, legal links, photo, auth, applications | Left in place from the previous implementation |
| Laravel Stage 11 | Test subclass signature already matches `RecruitmentActivityService::record`. No change |

Still unresolved in product code: password change and account deletion APIs, live application status filters, per-application resume snapshot, a separate company resource, in-app resume file download, and apply questions. Apply continues to post `{}` because the server submits from the primary resume.

## 3. Requirement summary (PASS / PARTIAL / FAIL / BLOCKED)

| Area | Status | Notes |
| --- | --- | --- |
| Four-tab candidate shell (Home, Jobs, Applications, Profile) | PASS | Smoke + navigation tests |
| Menu Profile-only, order, Logout label | PASS | Widget tests |
| Dark mode (candidate-only) | PASS | Theme tests; `CandidateThemeController` |
| Login / account / logout | PASS | Branded unified auth UI; client integrated; **staging login not tested** (no authorized env) |
| Design system consistency | PARTIAL | Shared Hire widgets + settings/saved/docs polish; references not in-repo |
| Registration | BLOCKED | GAP-AUTH-REGISTER |
| Public jobs list + detail | PASS (client) | HireMe-style cards + detail sections (Phase 1 UI); requires API base |
| Candidate profile presentation | PARTIAL | Social-style `ProfileSectionCard` sections; API read/write still blocked |
| Saved jobs list | PARTIAL | Toggle POST/DELETE wired; **no GET list** in client |
| Application history / submit | PARTIAL (client) | APIs in `HireApiService`; wizard body gaps; E2E unverified |
| Professional profile | PARTIAL | UI complete; GAP-PROFILE-READ/WRITE |
| Documents / resumes | BLOCKED | GAP-RESUME-* |
| Legal / help / app links | BLOCKED | GAP-CONTENT; info dialogs only |
| Delete account | PASS | No false success; no session substitute |
| Verification UX | PARTIAL | **Settings → Verification** shows `AuthUser` flags; no resend API |
| App version | PASS | `AppVersion.label` ↔ `pubspec.yaml` 1.0.0+1 |
| Manual QA | PARTIAL | See §9 |

## 4. Issue register

Authoritative row-level register: `OURNEAR_HIRE_RELEASE_ISSUE_REGISTER.md`.

## 5. Material file changes (this cycle)

| File | Reason |
| --- | --- |
| `docs/mobile/OURNEAR_HIRE_RELEASE_ISSUE_REGISTER.md` | Traceable release issues |
| `docs/mobile/OURNEAR_HIRE_RELEASE_REPORT.md` | This report |
| `docs/mobile/OURNEAR_HIRE_API_INTEGRATION_REGISTER.md` | API-APP-* and saved-job toggle rows |
| `lib/features/hire/applications/candidate_applications_screen.dart` | Auth-gated load; signed-out copy |
| `lib/features/hire/applications/widgets/application_status_badge.dart` | Lint fix |
| `lib/features/hire/settings/account_verification_screen.dart` | Settings → Verification (email / password flags) |
| `lib/features/hire/settings/candidate_settings_screen.dart` | Verification tile; `AppVersion.label` in app info |
| `test/features/hire/settings/account_verification_settings_test.dart` | Verification settings regression |
| Multiple `test/**` | Align expectations with signed-out Applications copy |
| `docs/mobile/OURNEAR_HIRE_DESIGN_GAP_REPORT.md` | Phase 1 template comparison and closure evidence |
| `lib/features/hire/jobs/job_card.dart` | Company avatar, badges, theme tokens, card key |
| `lib/features/hire/jobs/widgets/job_company_avatar.dart` | Logo/initial placeholder |
| `lib/features/hire/jobs/widgets/job_detail_header.dart` | Detail summary card |
| `lib/features/hire/hire_job_detail_screen.dart` | Header + section cards; apply CTA |
| `lib/features/hire/profile/widgets/profile_section_card.dart` | Reusable profile/detail sections |
| `lib/features/hire/profile/professional_profile_body.dart` | Section card layout |
| `lib/features/hire/home/candidate_home_screen.dart` | Theme-aligned search submit |
| `test/features/hire/jobs/job_card_presentation_test.dart` | Job card hierarchy + dark theme |
| `test/features/hire/jobs/candidate_job_discovery_test.dart` | Scroll to Apply on job detail |
| `lib/features/hire/widgets/hire_*.dart` | Shared notice, brand, welcome, profile avatar, settings tiles |
| `lib/core/widgets/ournear_password_field.dart` | Login password field |
| `lib/features/auth/login_screen.dart`, `registration_screen.dart` | Branded auth surfaces |
| `lib/features/hire/jobs/saved_jobs_screen.dart` | Honest saved-jobs unavailable UX |
| `lib/features/hire/settings/candidate_settings_screen.dart` | Settings nav tiles |
| `lib/features/hire/applications/widgets/application_list_tile.dart` | Company avatar row |
| `test/features/hire/widgets/hire_design_system_test.dart` | Design system regression |

## 6. Tests

| Command | Result |
| --- | --- |
| `flutter test` | **162 passed**, 0 failed, 0 skipped |

Notable suites: auth, job discovery, applications (live + mock), menu/dark theme, release smoke (360×640, 1280×800), unified app widget test (unchanged entry).

## 7. Analysis

| Command | Result |
| --- | --- |
| `flutter analyze` | **No issues found** |

## 8. Builds

| Command | Exit | Artifact |
| --- | --- | --- |
| `flutter build web -t lib/preview/hire_candidate_preview_main.dart -o build/web_candidate` | 0 | `build/web_candidate/` |
| `flutter build apk --debug -t lib/preview/hire_candidate_preview_main.dart` | 0 | `build/app/outputs/flutter-apk/app-debug.apk` |

## 9. Manual browser / device evidence

| Journey | Method | Result |
| --- | --- | --- |
| Web preview load | `python -m http.server 8765` in `build/web_candidate`; navigate `http://127.0.0.1:8765` | **PASS load** — document title “OurNear Hire” (2026-10-15 and **2026-10-16** re-check) |
| First-launch onboarding frame | IDE browser screenshot after load | **PARTIAL** — onboarding “Discover jobs” + `OURNEAR_API_BASE` copy visible; artifact `docs/mobile/qa-evidence/2026-10-16-web-onboarding-load.png` |
| Tap-through (Skip, Sign in, tabs, auth forms) | IDE browser on Flutter `<flutter-view>` canvas | **NOT RELIABLE** — a11y tree exposes only root + “Enable accessibility”; coordinate/pointer taps did not advance onboarding (2026-10-16) |
| Android APK install + journeys | Physical device / emulator | **NOT EXECUTED** — `app-debug.apk` built (2026-10-16); no emulator/install in agent environment |

### 9.1 Authentication acceptance (2026-10-16)

| Check | Evidence type | Result |
| --- | --- | --- |
| Login / Sign up tab switch | Widget | **PASS** — `candidate_auth_visual_test.dart` |
| Login labels (`Username or email`, password field) | Widget + reduced motion | **PASS** — `login_flow_test.dart`, `reduced_motion_test.dart` (keys `login-field`, `login-password`) |
| Password visibility | Widget | **PASS** — `OurNearPasswordField` in login/signup tests |
| Validation order (fields before consent on signup) | Widget | **PASS** — `candidate_account_access_test.dart` |
| Blocked signup (no HTTP, GAP message) | Widget + unit | **PASS** — `LiveAccountAccessRepository` + registration widget test |
| Login loading / duplicate submit / error | Widget | **PASS** — `login_flow_test.dart` |
| Recovery unavailable | Widget | **PASS** — `candidate_account_access_test.dart` |
| Reduced-motion login route | Widget | **PASS** — Profile → Sign in shows login keys; instant page transitions |
| Live staging login | Runtime | **NOT TESTED** — no authorized staging URL, `--dart-define=OURNEAR_API_BASE`, or credentials in workspace; shipped `build/web_candidate` embeds empty API base (throws “not configured” in `main.dart.js`) |
| Website screenshot parity | Visual | **UNVERIFIED** — no reference PNG/JPG in mobile repo; templates documented on `D:\` only |

**Recommended manual checklist for venkat:** browse without account → Jobs (not configured message) → Applications (sign in prompt) → Profile → menu → dark mode → Settings → Verification (signed in) → login/logout with test API → job detail → apply readiness.

## 10. API gaps (remain unavailable or unverified)

| ID | Mobile client | Live server |
| --- | --- | --- |
| GAP-AUTH-REGISTER | Blocked before HTTP | — |
| GAP-AUTH-RECOVERY | Blocked | — |
| GAP-AUTH-VERIFY | Flags in UI; no resend URL | — |
| GAP-PROFILE-READ/WRITE | Blocked | — |
| GAP-JOBS-SAVED (list) | No GET list method | Toggle only in client |
| GAP-RESUME-* | Blocked | — |
| GAP-MSG / GAP-NOTIF | Blocked | — |
| GAP-CONTENT | Info-only | — |

Integrated client operations (see API register §2): login, account, logout, jobs list/detail, account applications list/detail/apply POST, saved-job POST/DELETE per slug.

## 11. Security / account isolation (automated evidence)

- Logout clears session even when POST fails (stage7 test).
- HTTP 401 on account clears token (auth_service_test).
- Applications tab clears on sign-out (`ApplicationsController.clearForSignOut`).
- Delete account dialog does not invoke logout (`candidate_home_menu_test.dart`).
- No credentials or bearer tokens in test fixtures beyond fakes.

## 12. Limitations, risks, next action

**Limitations:** Signup blocked (`GAP-AUTH-REGISTER`); profile/documents/messages/notifications not on live API; saved jobs list not wired; legal URLs not connected; Laravel E2E not proven; **manual device QA and scriptable web tap-through incomplete**.

**Release risks:** Shipping without device QA and without configured API base gives false confidence.

**Next actions:**

1. Manual tap-through on **device/emulator** (APK) or Flutter integration driver — web canvas blocked IDE automation (2026-10-16).
2. Rebuild preview with authorized `--dart-define=OURNEAR_API_BASE=…` and run login against staging with supplied test credentials.
3. Copy authorized website auth screenshots into `docs/mobile/qa-evidence/` for pixel parity.
4. Supply **GAP-AUTH-REGISTER** contract before enabling signup HTTP.
5. Supply authorized **GET saved-jobs list** contract before list screen work.
6. After staging login + device QA pass, reassess verdict → **READY (preview scope)** vs full master-spec.

## 13. Boundary compliance

Only `C:\Ournear Hub\ournear_mobile` was modified. No access to `D:\Ournear Hub\Ournear Hire`. No deploy, commit, or push.

## 14. Mobile completion execution (2026-10-16)

### Functionality implemented this run

| Area | Change |
| --- | --- |
| Applications history | `LiveApplicationsRepository.fetchApplications` now loads **all authorized pages** (`page` query through `meta.last_page`, cap 50) so the Applications tab is not limited to the first page |
| Saved jobs messaging | Removed misleading reference to an undocumented GET path; uses standard `savedJobsUnavailable` copy |
| Staging configuration | Added `docs/mobile/CANDIDATE_STAGING_CONFIGURATION.md` with `--dart-define=OURNEAR_API_BASE` / `OURNEAR_WEB_BASE` run and build commands |

### Already integrated (verified, not re-built)

Auth login/account/logout/session gate; jobs list/detail/keyword search/pagination; save toggle POST/DELETE; application list/detail/submit/resume download UI; blocked signup/recovery/profile/documents/saved list — per `OURNEAR_HIRE_API_INTEGRATION_REGISTER.md` section 2.

### API contracts reused (sources)

All from **`OURNEAR_HIRE_API_INTEGRATION_REGISTER.md`** and `HireApiService`: `POST /api/v1/auth/login`, `GET /api/v1/account`, `POST /api/v1/auth/logout`, `GET /api/v1/jobs`, `GET /api/v1/jobs/{slug}`, `GET /api/v1/account/applications` (+ pagination), `GET /api/v1/account/applications/{id}`, `POST /api/v1/jobs/{slug}/apply`, `GET .../applications/{id}/resume`, `POST|DELETE /api/v1/account/saved-jobs/{slug}`.

### Files changed

| File | Purpose |
| --- | --- |
| `lib/features/hire/applications/data/applications_repository.dart` | Multi-page application history fetch |
| `lib/features/hire/jobs/data/jobs_repository.dart` | Honest saved-jobs unavailable message |
| `test/features/hire/applications/live_applications_repository_test.dart` | Pagination, detail, submit confirm, resume download tests |
| `docs/mobile/CANDIDATE_STAGING_CONFIGURATION.md` | Authorized staging run/build instructions |
| `docs/mobile/OURNEAR_HIRE_API_INTEGRATION_REGISTER.md` | Pagination + inventory corrections |
| `docs/mobile/OURNEAR_HIRE_RELEASE_REPORT.md` | This section |

### Fresh evidence (this run)

| Check | Result |
| --- | --- |
| `flutter test` | **162 passed** |
| `flutter analyze` | **0 issues** |
| `flutter build web -t lib/preview/hire_candidate_preview_main.dart -o build/web_candidate` | **Success** |
| Debug APK | **Success** → `build/app/outputs/flutter-apk/app-debug.apk` (exit 0, post-completion web build) |

### Staging / live Laravel

**No live HTTP verification** — no authorized staging URL or test credentials stored in the mobile workspace. Default artifacts still compile with empty `OURNEAR_API_BASE`. Use `CANDIDATE_STAGING_CONFIGURATION.md` on a machine with an approved backend.

### Remaining contract blockers

`GAP-AUTH-REGISTER`, `GAP-AUTH-RECOVERY`, `GAP-PROFILE-READ/WRITE`, `GAP-RESUME-*`, **saved-jobs GET list**, `GAP-MASTER-DATA`, `GAP-MSG`, `GAP-NOTIF`, `GAP-CONTENT`, wizard resume/questions in apply POST.

### Manual QA

**Incomplete** — no device/emulator journey with configured API base in this run. Prior partial web onboarding screenshot remains in `docs/mobile/qa-evidence/`.

### Preview API configuration (2026-10-16)

**Root cause:** Chrome preview on port **8080** was running without compile-time `OURNEAR_API_BASE`. Laravel JSON API responds on **8000**, not 8080.

**Fix (one-time restart):** `flutter run -t lib/preview/hire_candidate_preview_main.dart -d chrome --web-port=8080 --dart-define-from-file=config/candidate_dev_api.json` — see `CANDIDATE_STAGING_CONFIGURATION.md`. Current session cannot pick up defines without relaunch.

### Smallest dependencies to finish the app

1. Authorized **staging URL + test account** (outside repo) for live E2E sign-off.
2. **GET saved-jobs list** contract row in the API register before Saved Jobs can show real rows.
3. **Profile + documents + registration + recovery** contracts before those screens can call HTTP.
4. Device or integration-driver manual QA once (1) is available.

### Release verdict (unchanged scope)

**NOT READY** (full master-spec). **READY for staged QA** of all section-2 client contracts once `OURNEAR_API_BASE` and credentials are supplied on the test device.
