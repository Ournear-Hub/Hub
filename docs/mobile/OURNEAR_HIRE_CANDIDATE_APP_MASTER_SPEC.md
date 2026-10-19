# OurNear Hire Candidate App — Master Specification

| Field | Value |
| --- | --- |
| Document ID | SPEC-CANDIDATE-MASTER |
| Status | Product authority for the candidate mobile app |
| Stage | 0 — baseline and specification |
| Date | 2026-10-14 |
| Workspace | `C:\Ournear Hub\ournear_mobile` |
| Product | OurNear Hire, candidate-facing jobs and careers application |

This document is the product authority. Architecture, screen inventory, API register, roadmap, and baseline report must follow it. If those documents disagree with this specification, this specification wins and the other document must be corrected.

Stage 0 records the target product and the verified starting point. It does not authorize a rebuild, a second backend, or employer features.

## 1. Product identity

| Item | Rule |
| --- | --- |
| Product name | OurNear Hire |
| Audience | Candidates |
| Purpose | Search jobs, save jobs, apply, track applications, manage a professional profile, resumes, messages, notifications, and account settings |
| Industries | Every industry the existing OurNear Hire platform exposes. Mining and construction are not the only industries and must not be hard-coded as such |
| Mobile UI | Flutter and Dart only |
| Target platforms | Android, and responsive mobile web with PWA installation where the browser supports it |
| Backend | Existing OurNear Hire Laravel application and its MySQL database. The mobile app is a client |
| Out of scope | Employer dashboards, recruiter pipelines, employer-specific navigation, and any second production candidate system |

Brand colours:

| Token | Hex | Use |
| --- | --- | --- |
| Primary dark teal | `#214449` | Primary brand colour for the candidate app |
| Accent orange | `#F15A24` | Accent, emphasis, and demo/warning highlights already used by the applications banner |
| White | `#FFFFFF` | Cards and navigation surfaces |
| Neutral surface | `#F5F7F8` | Hire theme scaffold and app bar (`OurNearColors.hireCanvas`). The unified shell canvas stays `#F8FAFC`. Cards stay white |

Typography and layout:

- Use a readable sans-serif. The current Hire theme uses Flutter’s default Material typeface. A custom font is not required unless a later stage adds one as an asset.
- Body text stays at accessible sizes. Current tokens start body copy at 13–14 and titles at 16–20. Do not drop body text below 13.
- Keep spacing, borders, buttons, status badges, and loading states consistent with `OurNearTokens` and the shared state widgets.
- Do not introduce an unrelated brand palette. The unified shell’s blue `#2563EB` (`OurNearColors.brand`) is legacy platform colour, not the candidate product colour.

## 2. Frozen stack and isolation

### 2.1 Mobile stack

- Flutter and Dart.
- Existing packages in `pubspec.yaml` stay until a later stage has a recorded reason to add, remove, or upgrade one.
- Android build via the existing `android/` project.
- Web build via the existing `web/` project, including the PWA manifest.
- State management already in the project is `provider` with `ChangeNotifier`. Do not replace it in passing.

### 2.2 Backend stack (unchanged, not modified by mobile work)

- Laravel 12, PHP 8.4, MySQL 8.4.
- Existing Laravel business rules, data models, APIs, and authentication.
- The website frontend stays as it is. Do not migrate it to Flutter or replace it.

### 2.3 Prohibited substitutions

Do not introduce React Native, a separate JavaScript frontend, a Node.js backend, Firebase, or Supabase as a production backend. Do not create a second production database.

### 2.4 Workspace isolation

| Location | Rule |
| --- | --- |
| Mobile project | All candidate app work happens in the mobile workspace. On this machine that path is `C:\Ournear Hub\ournear_mobile`. The Stage 0 brief also named `D:\Ournear Hub\ournear_mobile`; `D:\` is not present here |
| Website project | `D:\Ournear Hub\Ournear Hire` is out of bounds. Do not open, inspect, edit, test, configure, deploy, or query it to fill documentation gaps |
| Git | Do not create a local Git repository in the mobile workspace. Repository integration happens later through the established process. A `.gitignore` file already exists; that is not a Git repository |
| Employer code | `lib/app/hire_employer_app.dart` and `lib/preview/hire_employer_preview_main.dart` stay unless a change is essential to keep the candidate app compiling. Do not build employer features |

## 3. Scope

The programme eventually covers the feature groups below. Identifiers are stable. Later stages cite them instead of inventing new names.

### 3.1 In scope

| ID | Group | Required outcome |
| --- | --- | --- |
| F-AUTH-SESSION | Startup | Splash and restoration of an existing session |
| F-AUTH-ONBOARDING | Startup | Optional onboarding. Skip must be possible. No mandatory language picker |
| F-AUTH-LOGIN | Authentication | Login with the existing account |
| F-AUTH-REGISTER | Authentication | Registration |
| F-AUTH-RECOVERY | Authentication | Password recovery |
| F-AUTH-SETUP | Authentication | Profile setup and completion after registration or when the profile is incomplete |
| F-HOME | Discovery | Search entry, industry/category shortcuts, recommended jobs, latest jobs, saved-job access |
| F-JOBS-SEARCH | Discovery | Search by job title, skill, company, and location |
| F-JOBS-FILTERS | Discovery | Supported filters and sorting. Options come from platform data after integration |
| F-JOBS-CATALOGUE | Discovery | Industry and category catalogue from platform master data |
| F-JOBS-DETAIL | Discovery | Job cards and job details |
| F-JOBS-COMPANY | Discovery | Company details |
| F-JOBS-SAVED | Discovery | Saved jobs and availability status |
| F-APP-READINESS | Applications | Readiness checks and required profile-field validation before apply |
| F-APP-WIZARD | Applications | Wizard, review, resume selection, job-specific questions where the backend supports them, confirmation, duplicate-submission safeguard |
| F-APP-HISTORY | Applications | History, supported status filters, application details |
| F-APP-TIMELINE | Applications | Hiring-progress timeline, interview details, and only candidate actions the backend allows |
| F-PROFILE | Profile | Social-style overview, photo/avatar, headline, read-only professional profile, and separate edit forms for personal information, summary, experience, education, skills, qualifications, and certifications. Completion and validation |
| F-DOCS | Documents | Generated Hire Resume; one replaceable uploaded My Resume; preview; upload/replace; file validation; secure access; historical application resume snapshots where the backend supports them |
| F-MSG | Communication | Inbox, thread, composer, unread indicators, links to related jobs and applications |
| F-NOTIF | Communication | Notification list and read state, with links to related jobs and applications |
| F-PREFS | Settings | Job preferences and alerts, notification preferences, privacy and account security, help/support, terms, privacy policy, logout, and session clearing |
| F-NAV | Navigation | Four root tabs, top-bar messages and notifications, gesture rules in section 5 |

### 3.2 Out of scope

- Employer and recruiter workflows.
- Unified OurNear modules (Vibes, Drops, Memory) as candidate product features. Their existing shell code stays; it is not the candidate product.
- Invented status transitions, support-desk workflows, identity-verification flows, or master-data lists that the platform does not already own.
- iOS, desktop, and Windows/Linux targets as product deliverables. Template folders exist. Do not delete them in order to tidy the tree, and do not spend candidate stages on them.

## 4. Navigation

### 4.1 Root tabs

The candidate app has exactly four persistent bottom-navigation tabs, in this order:

| Order | Tab ID | Label | Contents |
| --- | --- | --- | --- |
| 1 | `home` | Home | F-HOME |
| 2 | `jobs` | Jobs | F-JOBS-SEARCH, F-JOBS-FILTERS, F-JOBS-DETAIL, F-JOBS-COMPANY, F-JOBS-SAVED |
| 3 | `applications` | Applications | F-APP-HISTORY, F-APP-TIMELINE, entry into F-APP-WIZARD from a job |
| 4 | `profile` | Profile | F-PROFILE, F-DOCS, F-PREFS |

`HireAppConfigs.candidate` declares these four IDs and labels. Home is `CandidateHomeScreen`. Jobs search uses the repository `keyword` parameter.

### 4.2 Messages and notifications

Messages (F-MSG) and notifications (F-NOTIF) open from the top app bar and from relevant contextual entry points. Neither is a fifth bottom tab.

`OurNearTopAppBar` already has optional message and notification actions. On the candidate Home screen and the Applications tab those actions currently show a “later phase” snack bar. That is a placeholder, not the feature.

### 4.3 Hiding the bottom bar

Hide or disable the persistent bottom bar on focused flows:

- authentication
- application steps
- message threads
- document previews

Pushed routes on the root navigator already cover the shell, so login, job detail, and application detail hide the bar by covering it. There is no separate in-tab hide control. Later focused flows must keep the bar hidden for the whole focused flow.

### 4.4 Horizontal swipe

On the four root pages only:

- Order is Home → Jobs → Applications → Profile.
- A left swipe moves to the next tab.
- A right swipe moves to the previous tab.
- Do not wrap at either end.
- Bottom-tab taps always remain available.

Do not intercept horizontal gestures in:

- text fields
- forms
- message threads
- PDF viewers
- image carousels
- filter chips
- nested horizontal scroll areas

Do not switch tabs from job details, company details, application workflows, or other detail routes.

Preserve scroll position and search/filter state where practical. `IndexedStack` in `RootTabShell` already keeps tab states alive. Later search and filter state must live in controllers that survive tab changes.

Respect Android back navigation, accessibility, and reduced-motion preferences.

Current code in `RootTabSwipeCoordinator` already implements one-tab, non-wrapping swipes, ignores edge starts, ignores an active nested horizontal scroll, ignores a focused `EditableText`, and ignores swipes when the navigator can pop. It does **not** read reduced-motion or accessibility flags. Closing that gap is a later navigation task. Stage 0 does not reimplement gestures.

### 4.5 Back navigation

Android system back closes a pushed route before it changes the selected root tab. Root tabs do not wrap, and back from the first tab follows the platform default (leave the activity) unless a later stage documents a different, accessibility-safe behaviour.

## 5. Interaction rules

- Loading, empty, error, offline, and success states use the shared platform state widgets where a screen loads remote or repository data.
- Demo or mock data that can be mistaken for the candidate’s account must show the existing demo-banner pattern (`ApplicationsDemoBanner` or an equivalent that says the rows are not from the account and are not stored on the server).
- Buttons that need a missing API show an explicit unavailable state. They must not pretend a write succeeded.
- Duplicate application submission is blocked in the client only as a safeguard. The backend remains the authority if a duplicate still arrives.
- Job-specific questions render only when the backend supplies them.
- Interview actions render only when the backend marks them as candidate-allowed.
- Availability of a saved job is shown from platform data. Do not invent an “open” or “closed” flag when the payload has no such field. `PublicJob.isClosed` and `PublicJobDetail.acceptsApplications` are the current parsed fields; the Jobs UI does not display them yet.
- Forms validate required fields before submit. Server validation messages win when the API returns them.
- Logout clears the in-memory bearer token and the secure-storage entry even if the network logout call fails. `AuthService.logout` already does this.

## 6. Accessibility

- Interactive controls meet the 44px minimum touch target already encoded as `OurNearTokens.minTouchTarget` for filled, outlined, text, and icon buttons.
- State widgets expose a semantic label or container where they already do (`PlatformEmptyState`, `ApplicationsDemoBanner`, `StatusBadge`). New screens follow that pattern.
- Colour is not the only status signal. Status badges include text.
- Support text scaling. Do not clip primary labels at large text sizes.
- Respect `MediaQuery.disableAnimations` for non-essential motion. On the Hire shell, reduced motion replaces decorative page transitions with an immediate transition and sets the root navigation indicator duration to zero. Root tab taps and one-step swipes still change tabs. Edge, focused-field, nested-scroll, and pop-route guards still apply.
- Respect Android back as described in section 4.5.
- Focus order follows visual order. Focused text fields must not trigger a tab change.

No screen-reader audit was run in Stage 0. Accessibility gaps found later are defects against this section.

## 7. Profile and privacy rules

- After integration, the existing Laravel candidate profile is the only profile. The mobile app must not create a second candidate profile model as another source of truth.
- `AuthUser` (name, username, role, email verified, must change password) is an account session summary, not the professional profile.
- `CandidateApplication` and `MockApplicationsRepository.demoApplications` are client demo records. They are not the canonical profile or the canonical application history.
- Industry, category, and other master-data options are loaded from the platform after integration. Until that API is wired, show an empty or unavailable catalogue. Do not hard-code a short industry list.
- Do not add mandatory language selection to onboarding or registration.
- Do not show private contact details or uploaded resume files by default. Follow the platform’s privacy and authorization rules. A document preview requires an authorized session and a short-lived or authorized URL. Do not cache resume bytes in plain preferences.
- Public job browsing may stay available without an account, matching the current Jobs screen. Applying, messages, notifications, documents, and profile edits require an authenticated candidate session.

## 8. Mock-first strategy

Frontend stages use deterministic mock repositories. Integration stages swap in Laravel repositories that implement the same interfaces.

Rules:

- Widgets depend on repository interfaces, not on hard-coded lists inside the widget build method.
- Mocks and live implementations share the interface.
- Mocks return stable IDs and copy so widget tests can assert them.
- A mock result is labelled `ApplicationsDataSource.mockDemo` or the equivalent source flag for that feature.
- A live result is labelled as live and is never mixed into the same list as demo rows without an explicit empty-then-replace refresh.
- Missing Laravel APIs are integration gaps (see the API register). Stages do not silently invent endpoints, status enums, or success responses.

Current alignment:

- Applications follow this pattern: `ApplicationsRepository`, `MockApplicationsRepository`, `ApplicationsController`.
- Jobs do not. `HireScreen` constructs `HireApiService` and calls it directly. A jobs repository is a later stage, not a Stage 0 rewrite.
- Authentication uses `AuthService` directly. A repository split is optional and only justified if a stage needs a mock login that does not pretend to be Laravel.

## 9. API integration boundary

- Runtime base URL is `OURNEAR_API_BASE` (`ApiConfig.apiBase`), empty by default.
- Optional browser fallback origin is `OURNEAR_WEB_BASE`, empty by default. It opens the website in an external browser. It is not the JSON API.
- The mobile client sends JSON `Accept` headers. Authenticated calls add `Authorization: Bearer`.
- Passwords are sent only to the login request and are not written to storage.
- The bearer token is stored with `flutter_secure_storage` under `ournear_mobile_bearer_token`.
- Evidence standard: an endpoint is “present in the mobile client” only when `lib/` builds that URL. Client unit tests with a fake HTTP client do not prove the Laravel route exists. Stage 0 did not inspect the website project and did not call a live server.
- Unknown server capabilities stay in the API register as gaps.

## 10. Security rules

- No production Firebase or Supabase.
- No tokens, passwords, or resume bytes in widget text, snack bars, or debug banners.
- Release builds must declare Android `INTERNET` on the main manifest before they can call the API. Today `INTERNET` is only on the debug and profile manifests. That is a recorded defect, not a Stage 0 code change.
- Release signing still uses the debug keystore (`android/app/build.gradle.kts`). That is acceptable for local debug and unacceptable as a store upload. A later release stage must replace it. Stage 0 does not create a keystore.
- Web secure storage is not hardware-backed storage. Treat the PWA token store as a platform limitation until a later security pass reviews `flutter_secure_storage_web`.
- Logout and HTTP 401 both clear the local session. Keep that behaviour.
- Do not broaden public job responses with candidate-private fields.

## 11. Entry points that exist today

| Entry | File | What it launches | Product status |
| --- | --- | --- | --- |
| Default | `lib/main.dart` | `OurNearApp` → unified 5-tab shell (Home, Vibes, Drops, Hire, Profile) | Existing platform shell. Not the candidate product |
| Candidate preview | `lib/preview/hire_candidate_preview_main.dart` | `HireCandidateApp` → four candidate tabs | Candidate shell. Home is the job-discovery screen |
| Employer preview | `lib/preview/hire_employer_preview_main.dart` | `HireEmployerApp` | Preserved. Out of scope |

Candidate Android and web runs for later stages use the candidate entry, or a successor of it. They must not be pointed at the employer entry. Changing `lib/main.dart` away from the unified shell is a Stage 2 decision with an explicit regression check of `test/widget_test.dart` and `test/app_navigation_test.dart`. Stage 0 does not change it.

## 12. Acceptance gates

The candidate product is accepted only when every gate below is true. Stage gates in the roadmap are smaller and come first.

| Gate | Condition |
| --- | --- |
| G-SCOPE | Only candidate features from section 3.1 are in the candidate entry. Employer UI is not linked from it |
| G-STACK | Implementation remains Flutter/Dart against the existing Laravel API. No prohibited backend |
| G-NAV | Four tabs in order, messages and notifications off the tab bar, swipe rules in section 4.4, bottom bar hidden on focused flows |
| G-BRAND | Teal `#214449`, orange `#F15A24`, white `#FFFFFF`, surface `#F5F7F8`, sans-serif, shared components |
| G-PROFILE | One canonical Laravel profile. No hard-coded industry list. No mandatory language step. Private contact and resumes hidden by default |
| G-MOCK | UI talks to repository interfaces. Demo data is labelled. Live repositories replace mocks without widget rewrites |
| G-API | Every networked call is either evidenced in the API register or added to that register in the same stage. No invented success |
| G-A11Y | Section 6 holds on the four root tabs and on auth, apply, message, and document flows |
| G-TEST | `flutter analyze` and `flutter test` pass on the candidate entry and on preserved shells that the stage still compiles |
| G-RELEASE | Android release manifest has network permission, release signing is not the debug keystore, web manifest names OurNear Hire and uses the brand colours |

## 13. Stage 0 gate

Stage 0 is documentation and baseline only. Its completion criteria are in `OURNEAR_HIRE_MOBILE_BASELINE_REPORT.md`. Stage 0 does not satisfy G-NAV through G-RELEASE for the product. Those remain open.
