# OurNear Hire Mobile — Architecture

| Field | Value |
| --- | --- |
| Document ID | ARCH-CANDIDATE |
| Authority | `OURNEAR_HIRE_CANDIDATE_APP_MASTER_SPEC.md` |
| Stage | 0 description of the current tree and the target shape |
| Date | 2026-10-14 |
| Workspace | `C:\Ournear Hub\ournear_mobile` |

This document says how the candidate app is structured today and how later stages extend it. It does not authorize a rewrite of working code.

## 1. Project shape

Package name: `ournear_mobile`. Version: `1.0.0+1`. SDK constraint: `^3.10.1`. Publish: `none`.

`lib/` is the application. `test/` is the automated suite. `android/` and `web/` are the product platform hosts. `ios/`, `linux/`, `macos/`, and `windows/` are Flutter template hosts and are not candidate deliverables. `tool/` exists and is empty. `docs/mobile/` is this Stage 0 pack.

There is no `.git` directory. Do not create one. A Flutter `.gitignore` is already in the project root.

## 2. Layers

Target separation, which later stages must keep:

| Layer | Responsibility | Current home |
| --- | --- | --- |
| Presentation | Screens, widgets, routes, design tokens | `lib/features/**`, `lib/core/widgets/**`, `lib/core/theme/**`, `lib/core/layout/**`, `lib/core/navigation/**` |
| State | Loading, forms, filters, validation, UI state | `AuthController`, `ApplicationsController`, local `State` in `HireScreen` and `HireJobDetailScreen` |
| Repository interfaces | Jobs, authentication, profiles, resumes, applications, saved jobs, messages, notifications | Only `ApplicationsRepository` exists |
| Data implementations | Deterministic mocks now; Laravel API repositories at integration | `MockApplicationsRepository`. Jobs and auth call HTTP services directly |
| Platform | Android and web host configuration | `android/`, `web/` |

Existing working structure wins over a new framework. Do not introduce Riverpod, Bloc, GetIt, or go_router unless a later stage records why `provider` and `MaterialPageRoute` cannot meet a gate.

### 2.1 Presentation

Shared Hire building blocks exported from `lib/core/design_system/ournear_design_system.dart`:

- Layout: `OurNearPageLayout`, `OurNearResponsiveFrame` (max width 720)
- Theme: `OurNearColors`, `OurNearTheme`, `OurNearTokens`
- Controls: `OurNearCard`, `OurNearPrimaryButton`, `OurNearSecondaryButton`, `OurNearTextField`, `OurNearIconButton`, `OurNearPageHeader`, `OurNearTopAppBar`
- Feedback: `showOurNearSnackBar` in `lib/core/widgets/ournear_feedback.dart`
- States: `PlatformEmptyState`, `PlatformErrorState`, `PlatformLoadingState`, `PlatformOfflineState`, `PlatformSuccessState`, `StatusBadge`

`ModuleStatusChip` is used by the unified Home screen and is not part of that export.

Hire theme is `OurNearTheme.hireLight()`: seed and primary `#214449`, secondary `#F15A24`, Material 3, scaffold and app bar `#F5F7F8` (`OurNearColors.hireCanvas`). Cards and the navigation bar stay white. The unified theme keeps canvas `#F8FAFC`.

The unified shell uses `OurNearTheme.light()` with primary `#2563EB`. Candidate work uses `hireLight()` only.

### 2.2 State

`provider` `ChangeNotifierProvider` supplies `AuthController` at the candidate, employer, and unified app roots. Each root creates its own `AuthController(AuthService())` and calls `bootstrap()`.

`CandidateApplicationsScreen` creates `ApplicationsController` locally and provides it to its subtree. The controller is not app-global.

`HireScreen` keeps jobs, paging, error, and integration status in a `StatefulWidget`. There is no jobs controller. Tab switches keep that state because `RootTabShell` uses `IndexedStack`.

No form-validation package is installed. Login validates with `Form` and `TextFormField`.

### 2.3 Repositories

`ApplicationsRepository.fetchApplications()` returns `ApplicationsQueryResult` (`applications` plus `ApplicationsDataSource`). Failures are `ApplicationsRepositoryException` with kind `offline`, `unavailable`, or `unknown`.

`MockApplicationsRepository` is deterministic (three demo rows, or empty, or simulated offline/error). The candidate Applications tab uses it by default. The screen accepts a repository override for tests.

Jobs, saved jobs, profiles, resumes, messages, and notifications have no repository yet. Add an interface before adding a mock or a live client. Do not put mock lists inside widgets.

### 2.4 HTTP services

| Type | File | Role |
| --- | --- | --- |
| `ApiConfig` | `lib/core/api/api_config.dart` | `OURNEAR_API_BASE`, `OURNEAR_WEB_BASE`, optional `/jobs` and `/hire` web URIs |
| `HireApiService` | `lib/core/api/hire_api_service.dart` | Public job list and detail. No bearer token |
| `HireApiException` | `lib/core/api/hire_api_exception.dart` | Message plus optional status code |
| `AuthService` | `lib/core/auth/auth_service.dart` | Login, account, logout, secure-storage session |
| `AuthUser` | `lib/core/auth/auth_user.dart` | Account summary parsed from login and `/api/v1/account` |
| `PublicJob`, `PublicJobDetail`, `PaginatedPublicJobs` | `lib/core/api/models/public_job.dart` | JSON models for the public jobs payload |

`HireApiService.checkIntegration()` calls `fetchJobs(page: 1, perPage: 1)`. It does not call a separate health URL.

## 3. Entry and routing

There is no named route table and no `go_router`. Navigation is imperative `MaterialPageRoute`.

```
lib/main.dart
  OurNearApp
    AuthController.bootstrap()
    _BootstrapGate (spinner while AuthStatus.unknown)
    AppShell (5 tabs) via RootTabShell

lib/preview/hire_candidate_preview_main.dart
  HireCandidateApp
    AuthController.bootstrap()
    HireAppScaffold(HireAppConfigs.candidate)
      RootTabShell (home, jobs, applications, profile)

lib/preview/hire_employer_preview_main.dart
  HireEmployerApp
    HireAppScaffold(HireAppConfigs.employer)
      RootTabShell (home, my_jobs, applicants, profile)
```

Pushed routes that exist:

| From | To | File |
| --- | --- | --- |
| Jobs list | Job detail | `HireJobDetailScreen` |
| Applications list | Application detail | `CandidateApplicationDetailScreen` |
| Profile, when signed out | Login | `LoginScreen` |
| Unified Home module cards | Another unified tab | `AppShell.goToTab` |
| Applications empty state | Jobs tab | `RootTabShellState.goToId('jobs')` |

Detail routes are root-navigator routes, so they cover the bottom bar. The swipe coordinator also refuses a tab change when `Navigator.canPop()` is true.

Feature flags in `HireFeatureFlag`:

| Flag | Candidate config | Employer config | Read by a widget |
| --- | --- | --- | --- |
| `publicJobBrowse` | Yes | No | No. The jobs screen always runs |
| `authenticatedApplications` | No | No | No |
| `employerJobManagement` | No | Yes | No |
| `employerApplicantReview` | No | Yes | No |
| `inAppMessaging` | No | No | No |
| `pushNotifications` | No | No | No |

Flags are not a runtime gate. Do not treat an unset flag as proof that a feature is impossible. Do not turn employer flags on in the candidate config.

## 4. Navigation implementation

`ShellDestination` is an id, label, icon pair, and `WidgetBuilder`.

`RootTabShell`:

- Holds the selected index.
- Builds every destination once inside `IndexedStack` (state preserved).
- Wraps the body in `OurNearResponsiveFrame` and `SafeArea`.
- Optional horizontal drag uses `RootTabSwipeCoordinator`.
- `NavigationBar` stays tappable.
- A nested horizontal `ScrollUpdateNotification` marks the gesture so the coordinator returns no tab change.

`RootTabSwipeCoordinator`:

- Velocity threshold: 300 (`OurNearTokens.swipeVelocityThreshold`).
- Ignores drags that start within 24px of either screen edge.
- Finger moving left selects the next tab. Finger moving right selects the previous tab.
- `clampIndex` does not wrap.
- Ignores the gesture when an `EditableText` is focused or a route can pop.
- Does not treat reduced motion as a reason to cancel the swipe. Decorative page transitions are removed by `OurNearMotion` on the Hire `MaterialApp`. `RootTabShell` sets the navigation indicator duration to zero when `MediaQuery.disableAnimationsOf` is true.

`enableRootSwipe` defaults to true for every shell, including the unified five-tab app and the employer app. The candidate config satisfies the four-tab product order. Later stages must not disable swipe on the candidate shell to “simplify” it.

Candidate Home and Applications use `OurNearTopAppBar` message and notification buttons. Jobs and Profile use a plain `AppBar` and do not show those actions yet. The master spec requires those entry points on the candidate root experience. Adding them is later presentation work.

## 5. Feature modules as they stand

### 5.1 Candidate shell (`HireAppConfigs.candidate`)

| Tab | Widget | Behaviour |
| --- | --- | --- |
| Home | `CandidateHomeScreen` | OurNear Hire app bar. Search opens Jobs. Message and notification actions open secondary screens |
| Jobs | `HireScreen(appBarTitle: 'Jobs', showDiscoveryControls: true)` | Keyword search through `JobsRepository`. See section 5.2 |
| Applications | `CandidateApplicationsScreen` | Live applications repository. Message and notification actions open the same secondary screens. See section 5.3 |
| Profile | `CandidateDirectProfileScreen` | Direct professional profile with Edit Profile → guided setup. Account settings and secondary destinations live in the end drawer menu |

### 5.2 Jobs

`CandidateHomeScreen` and `HireScreen` call `JobsRepository`. The candidate app provides `LiveJobsRepository`, which wraps `HireApiService`. An unset `OURNEAR_API_BASE` stays on the not-configured state. The shell does not substitute mock jobs.

| State | What the user sees |
| --- | --- |
| Loading | Loading message. Home also shows static placeholder cards |
| API base missing | Not-configured message that names `OURNEAR_API_BASE`. No fabricated jobs |
| Offline or HTTP error | Offline or error state with retry |
| Empty 2xx | “No published jobs”, or “No published jobs match this keyword” when a keyword is active |
| Populated | Cards for title, company, location, experience, employment type, salary, and closed when those values exist. Load more when `currentPage < lastPage`. The candidate Jobs tab shows the server `total` |
| Search | Candidate Jobs submits one `keyword`. Clear restores the unfiltered list. Home search opens the Jobs tab with that keyword |
| Filters and sort | The candidate Jobs tab says they are not available. No extra query parameters are sent |
| Industries | Unavailable unless the repository returns shortcuts. A returned shortcut is sent as `keyword`, not as an industry filter |
| Saved jobs | A screen that says saving and sync are not available. No saved-jobs URL is called |
| Pull to refresh | Reloads from page 1 and keeps the current keyword |
| Web base set | “Browse jobs on web” via `url_launcher` |
| Web base unset | Hint text for `OURNEAR_WEB_BASE` |

`HireJobDetailScreen` loads `PublicJobDetail` through the repository. It shows the parsed description, responsibilities, requirements, what we offer, industry, category, and application status when present. Apply Now is hidden when `is_closed` is true or `accepts_applications` is false. Otherwise it opens application readiness. Company details show only the company fields already on the job.

The same `HireScreen` is the Hire tab of the unified shell, with the default title “Hire” and `showDiscoveryControls` false, so that tab does not show the candidate search field.

### 5.3 Applications

`HireCandidateApp` provides `LiveApplicationsRepository`. That class implements `ApplicationsRepository` and throws unavailable for list, detail, and submit. It does not call HTTP and it does not return the demo rows. The Applications tab therefore shows that history is not connected.

`MockApplicationsRepository` remains for tests and explicit injection. Its rows use `ApplicationsDataSource.mockDemo`, and the list shows `ApplicationsDemoBanner`. A mock submit adds a `demo-app-local-*` row, sets `confirmedByServer` to false, and refuses a second submit for the same job slug. That record is not a server application.

Controller states: `initial`, `loading`, `populated`, `empty`, `error`, `offline`, `sessionExpired`.

Status labels are client-only: Submitted, Under review, Shortlisted, Not selected. They are not verified Laravel enums. Filter chips appear only on the complete demo list and say they are not server statuses. The live screen has no filter chips.

Detail shows the supplied timeline. Demo timelines contain only the submitted timestamp and, when present, the updated timestamp. Interviews, withdrawal, and recruiter decisions are omitted.

Readiness shows the job, the published availability fields, and the profile and resume gaps. A closed job, or one with `accepts_applications` false, cannot continue. The wizard has account, resume, and review steps. The account step uses the signed-in `AuthUser` and does not collect a stand-in profile. The resume step states that no resume source is connected. Job questions are omitted because the job model has no question list. Review submit calls the repository. The live result says nothing was sent. A mock result shows the demo banner and “Demo record only.”

### 5.4 Profile and auth

The candidate Profile tab is `CandidateDirectProfileScreen` (`ProfessionalProfileBody`). It separates signed-in account identity (`AuthController`) from professional fields (`ProfileRepository`). `CandidateProfileScreen` remains for legacy tests only. Candidate navigation wraps four tabs in `CandidateShellScaffold` with `CandidateAppMenu` (end drawer). The menu icon and drawer are enabled only when the Profile tab is active (`CandidateShellScope.menuEnabled`). Candidate-only dark mode uses `CandidateThemeController` and `OurNearTheme.hireDark()` with `HireDarkTokens`; the unified OurNear shell theme is unchanged. Login uses the existing `AuthService` (`POST /api/v1/auth/login`); mobile signup has no verified client contract (see API register GAP-AUTH-REGISTER). The candidate app provides `LiveProfileRepository` and `LiveDocumentsRepository`. Both throw unavailable and do not call HTTP. A mock repository can keep an in-memory profile or one resume file name for tests. Those screens show a demo banner and do not say the data was saved on OurNear.

`ProfileScreen` remains the employer and unified account card. The unified and employer shells still show Memory when `showMemoryShortcut` is true. The candidate screen does not show Memory.

There is no profile completion percentage. The overview says completion cannot be calculated. Edit forms are separate for personal information, summary, experience, education, skills, qualifications, certifications, and job preferences. Save says “Saved on OurNear.” only when the repository result has `persisted` true. The live repository never sets that flag.

`GuidedProfileSetupScreen` is an optional entry flow over the same eight `ProfileSection` forms. `ProfileSetupProgress` labels sections as not started, in progress (opened this session without content), or reviewed locally (non-empty fields in the current repository snapshot). The summary line reads “Local setup progress: X of 8 sections reviewed locally.” It does not claim server persistence. Signed-out users see sign-in guidance; sections are disabled until signed in. `ProfileSetupEntryRequest` opens setup once when the candidate chooses it from onboarding page 2 and then visits the Profile tab.

Hire Resume and My Resume are separate sections. Preview shows supplied text only and does not open a file URL. Application readiness keeps the statement that no professional profile is loaded for the live repository. If a mock profile is injected, readiness shows that demo headline and says it will not be attached.

`ProfileScreen`, used by the unified and employer shells:

- Signed out: card plus Sign in, which pushes `LoginScreen`.
- Signed in: placeholder avatar, name, `@username`, role, Sign out.
- Settings rows: Notifications (snack bar). Memory (snack bar) when `showMemoryShortcut` is true, which is the default.

Candidate entry uses `CandidateSessionGate` while `AuthController.bootstrap()` runs or when account verification fails with a stored token (network or non-401 server errors). The gate offers retry and browsing jobs without treating the failure as a fresh sign-out. A 401 on `GET /api/v1/account` still clears the session.

`LoginScreen` posts email or username plus password through `AuthController.login`. Success pops the route. Failure shows `auth.errorMessage`. It pushes `RegistrationScreen` and `PasswordRecoveryScreen` for account access that is not yet connected on the server.

For the candidate variant only, `_HireBootstrapGate` in `hire_app_scaffold.dart` shows `CandidateOnboardingScreen` after bootstrap when the user is unauthenticated and `OnboardingPreferences.isComplete()` is false. Skip, get started, or finishing the last page calls `OnboardingPreferences.markComplete()` and then shows the four-tab shell. Authenticated users never see onboarding during ordinary navigation. The flag is local secure storage, not server data.

`RegistrationScreen` and `PasswordRecoveryScreen` validate input locally and call `AccountAccessRepository`. `LiveAccountAccessRepository` throws `AccountAccessException` with fixed unavailable copy and does not use `HireApiService`. No registration or recovery HTTP call exists in Stage 9.

`AuthController.bootstrap()`:

- `AuthStatus.unknown` while restoring.
- No stored token: `unauthenticated`.
- Stored token: `GET /api/v1/account`. Success: `authenticated`. `HireApiException`: `unauthenticated` and a null user.

The bootstrap spinner is a centered `CircularProgressIndicator` on an empty `Scaffold`. It is not a branded splash.

### 5.5 Unified shell and employer shell

Unified tabs: Home (`HomeScreen` module launcher), Vibes (empty state), Drops (empty state), Hire (`HireScreen`), Profile (`ProfileScreen`).

Employer tabs: Home, My Jobs, and Applicants are placeholders. Their message and notification buttons still show a later-phase snack bar. Profile is the shared screen. Leave this shell in place. Do not add candidate features to it.

### 5.6 Messages, notifications, and settings

`HireCandidateApp` provides `LiveMessagesRepository` and `LiveNotificationsRepository`. Both throw unavailable and do not call HTTP. The candidate shell does not substitute mock conversations or alerts.

Messages and notifications open from the Home and Applications app bars. Notifications also open from the candidate Profile row. None of these is a bottom tab. A pushed route covers the bottom bar. A horizontal drag on a thread does not change tabs.

The live inbox and notification list show the unavailable message. That message is not the empty-inbox sentence. An empty sentence is used only when a repository returns a successful live list with no rows. Mock lists show a demo banner and do not mean an employer or recruiter made contact. Send stays disabled. Mark read and dismiss throw, leave the unread flag as it was, and say nothing was changed. There is no push client and no device-token request.

Settings open from the candidate Profile tab. The account block shows the signed-in name and username from `AuthUser`. Job preferences open the existing profile form. A save there says it was saved on OurNear only when `ProfileSnapshot.persisted` is true. Job alerts reuse the unavailable preferences screen. Notification preferences, help, terms, and the privacy policy state that they are not connected. No support vendor is named. Password change and account deletion are described as unavailable and have no action button. Sign out on the privacy screen calls `AuthController.logout()`. After sign-out the account name is cleared.

The unified Profile screen still uses a snack bar for Notifications and still shows Memory.

### 5.7 Stage 7 contract boundary

Stage 7 did not add an HTTP path. The client calls remain `GET /api/v1/jobs`, `GET /api/v1/jobs/{slug}`, `POST /api/v1/auth/login`, `GET /api/v1/account`, and `POST /api/v1/auth/logout`.

Job detail is loaded through `LiveJobsRepository.fetchJobDetail`. HTTP 404 becomes not found. Other job HTTP failures stay unavailable. Logout posts to the verified path and clears the local token when that POST returns an error or throws.

Profile, documents, applications, messages, and notifications stay on the live repositories that throw before any request. Their screens do not treat that failure as an empty success or as a saved record. The inventory is section 8 of the API register.

### 5.8 Stage 8 release candidate

Stage 8 did not add a screen, a URL, or a package. The candidate preview entry still runs `HireCandidateApp`. The default `flutter build web` and `flutter build apk --debug` commands compile `lib/main.dart`, which still runs `OurNearApp`. A candidate-targeted artifact requires `-t lib/preview/hire_candidate_preview_main.dart`.

`OURNEAR_API_BASE` stays empty unless a build passes `--dart-define`. The Android application id stays `com.ournear.ournear_mobile`. The launcher label stays `ournear_mobile`. Release signing in Gradle still points at the debug keystore. Web title, manifest name, standalone display, and `#214449` theme color are unchanged. The limitations list is `docs/mobile/OURNEAR_HIRE_STAGE_8_RELEASE_CANDIDATE.md`.

## 6. Design tokens later UI must use

Spacing: 2, 4, 6, 8, 10, 12, 14, 16, 20, 24, 32. Page padding is 16 plus the keyboard inset. List gap is 10.

Radii: 6, 8, 10, 12. Cards use 12 and a 1px `#E2E8F0` border, elevation 0.

Icons: 18, 22, 40. Navigation bar height 64. Minimum control height 44.

Colours that already match the master spec: `hireTeal` `#214449`, `hireOrange` `#F15A24`, `white` `#FFFFFF`, and Hire scaffold `hireCanvas` `#F5F7F8`.

`canvas` `#F8FAFC` remains the unified shell background. `surface` `#F1F5F9` remains a separate token and is not the Hire page background.

Semantic success, warning, error, and info colours support status UI. They are not a second brand.

## 7. Platform configuration

### 7.1 Android

| Item | Current value |
| --- | --- |
| Application ID / namespace | `com.ournear.ournear_mobile` |
| Label | `ournear_mobile` |
| Activity | `MainActivity` extends `FlutterActivity`, `singleTop`, exported |
| SDK numbers | `flutter.compileSdkVersion`, `flutter.minSdkVersion`, `flutter.targetSdkVersion` (numeric values were not resolved in Stage 0) |
| Java / Kotlin target | 17 |
| Android Gradle Plugin | 8.11.1 |
| Kotlin Android plugin | 2.2.20 |
| Gradle wrapper | 8.14 |
| Release signing | debug keystore |
| `INTERNET` | Declared once in `android/app/src/main/AndroidManifest.xml`. Debug and profile manifests also declare it for the Flutter tool. The manifest merger keeps a single permission |
| `android/local.properties` | `sdk.dir=C:\Users\Lenovo\AppData\Local\Android\sdk`, `flutter.sdk=C:\src\flutter`. Neither path exists on this machine |

The default Android launch uses `lib/main.dart` (unified shell) because that is Flutter’s default target. The candidate preview is a `-t` target, not a separate application id.

### 7.2 Web and PWA

`web/index.html` loads `flutter_bootstrap.js`. Title, description, Apple web app title, and `theme-color` are OurNear Hire / `#214449`.

`web/manifest.json`:

- `name` and `short_name`: `OurNear Hire`
- `display`: `standalone` (installable display mode where the browser supports it)
- `background_color` and `theme_color`: `#214449`
- `description`: `OurNear Hire jobs and careers.`
- `orientation`: `portrait-primary`
- Icons unchanged: `web/icons/Icon-192.png`, `Icon-512.png`, `Icon-maskable-192.png`, `Icon-maskable-512.png`

`build/web/` contains a previous web build. `build/web/version.json` records app name `ournear_mobile`, version `1.0.0`, build number `1`. That artifact was not produced in Stage 0 and does not prove this machine can build web.

## 8. Dependencies

Direct packages from `pubspec.yaml`, locked in `pubspec.lock`:

| Package | Constraint | Locked | Use in `lib/` | Stage 0 note |
| --- | --- | --- | --- | --- |
| `flutter` | SDK | — | App | Required |
| `cupertino_icons` | `^1.0.8` | 1.0.9 | No import and no `CupertinoIcons` in `lib/` or `test/` | Unused. Do not remove in Stage 0 |
| `flutter_tabler_icons` | `^1.43.0` | 1.43.0 | Icons across shells | In use |
| `http` | `^1.2.2` | 1.6.0 | Jobs and auth | In use. No certificate pinning |
| `url_launcher` | `^6.3.1` | 6.3.2 | Jobs web fallback only | Suitable for that fallback |
| `flutter_secure_storage` | `^9.2.4` | 9.2.4 | Bearer token | Suitable on Android. The web backing store is a security limitation |
| `provider` | `^6.1.2` | 6.1.5+1 | Auth and applications | In use. Keep |
| `flutter_lints` | `^6.0.0` | 6.0.0 | `analysis_options.yaml` includes `package:flutter_lints/flutter.yaml` | Dev only |

`flutter pub outdated` was not run. No package was classified as obsolete from a registry check. None of these packages is Firebase or Supabase.

## 9. Tests

Fifteen test files declare 38 `test` / `testWidgets` cases. `test/helpers/fake_http_client.dart` is a helper. Names and counts are in the baseline report. Stage 0 did not execute them.

Source coverage that exists, and was not re-run:

- Public job JSON parsing, and `fetchJobs` success, empty, and HTTP 500.
- Auth login success, login HTTP 422, account HTTP 401 clearing the token, controller bootstrap, login failure, logout.
- Login screen success and failure.
- Unified shell tab taps and Home to Hire.
- Candidate tab order and employer tab order.
- Candidate shell swipe across four tabs without wrap, plus coordinator edge and nested-route cases.
- Applications controller, mock repository, list, empty, offline, and detail navigation.
- A sample of design-system and platform-state widgets.

Not covered: `fetchJobDetail`, the `keyword` query, `checkIntegration`, reduced motion, registration, profile editing, saved jobs, messages, notifications, and PWA manifest contents.

## 10. Rules for later stages

1. Read the master spec first. Add a repository interface before a new data source.
2. Keep `HireEmployerApp` compiling. Do not add employer screens.
3. Do not point candidate UI at hard-coded industry lists or unlabelled demo jobs.
4. Do not change `lib/main.dart` until the stage that owns the candidate entry also updates the unified-shell tests.
5. Do not remove `cupertino_icons` or upgrade packages as drive-by cleanup.
6. Do not call the website project to discover endpoints. Extend the API register only from mobile client code plus a contract that stage is allowed to use.
7. Preserve Android back, tab state, and the non-wrapping swipe. Add reduced-motion handling rather than replacing the coordinator.
8. New candidate screens use `OurNearTheme.hireLight()`, shared buttons, badges, and state widgets.
9. Demo rows stay behind a visible demo banner until a live repository returns an empty or real list.
10. A stage is unfinished if `flutter analyze` and `flutter test` were not run, unless the toolchain limitation in the baseline report is still true and the stage records that.
