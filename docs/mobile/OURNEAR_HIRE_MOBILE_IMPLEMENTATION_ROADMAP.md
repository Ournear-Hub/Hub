# OurNear Hire — Mobile Implementation Roadmap

| Field | Value |
| --- | --- |
| Document ID | ROADMAP-CANDIDATE |
| Authority | `OURNEAR_HIRE_CANDIDATE_APP_MASTER_SPEC.md` |
| Date | 2026-10-14 |
| Sequencing | Stages run in order. A stage starts only after the previous stage’s gate is met |

Stage 0 is the specification and baseline. It does not implement product features. Later stages implement the candidate app in slices. They do not rebuild working jobs, auth, applications, or navigation code just to match a new folder layout.

Employer work stays out of every stage. `HireEmployerApp` must still compile.

## Stage 0 — Baseline, isolation, and specification

| Item | Detail |
| --- | --- |
| ID | STAGE-0 |
| Status | Complete when the baseline report records PASS |
| Objective | Inspect the mobile workspace, separate existing behaviour from the target product, run the available checks, and write this pack |
| Deliverables | The six files in `docs/mobile/` |
| Tests | `flutter --version`, `flutter pub get`, `dart format`, `flutter analyze`, `flutter test`, or a recorded reason each could not run |
| Gate | Baseline report section “Stage 0 gate” is satisfied. No website files changed. No feature rewrite |

## Stage 1 — Toolchain restoration and command baseline

| Item | Detail |
| --- | --- |
| ID | STAGE-1 |
| Depends on | STAGE-0 |
| Status at end of Stage 0 | Not started. Blocked on this machine. See the baseline report |
| Objective | Make the Flutter commands in the master spec runnable here, then record a real analyze and test result before any feature edit |

Work:

1. Install Flutter stable that satisfies `pubspec.yaml` `sdk: ^3.10.1`. The last successful resolution on another machine recorded Flutter `3.38.3`, Dart generator `3.10.1`, and Flutter root `C:\src\flutter` in `.dart_tool/package_config.json`. Prefer that SDK when it can be restored. If a newer stable SDK is installed instead, record `flutter --version` and do not upgrade packages in the same step.
2. Install a JDK and the Android SDK before any Android compile. They are absent here. `android/local.properties` still points at missing paths and must be updated to the machine that will compile. Do not commit secrets; `local.properties` is a machine file.
3. Web compile needs the Flutter SDK only. Android compile also needs the SDK, JDK, and network for Gradle if the wrapper has not been cached.
4. From `C:\Ournear Hub\ournear_mobile`, run in order:
   - `flutter pub get`
   - `dart format --output=none --set-exit-if-changed lib test`
   - `flutter analyze`
   - `flutter test`
5. Append the command output summary to the baseline report. Do not change product code to force a green result unless a failure is a confirmed defect and the fix is limited to that defect.

Out of scope: new screens, repository rewrites, manifest branding, manifest permission edits, and `lib/main.dart` changes.

| Tests | The four commands above |
| Gate | Each command has an exit code recorded on this machine. Format, analyze, and test are either green, or each failure is listed with a file and a decision to fix it in a named later stage. Stage 2 does not start on an unclassified red suite |

## Stage 2 — Candidate shell identity and navigation gaps

| Item | Detail |
| --- | --- |
| ID | STAGE-2 |
| Depends on | STAGE-1 gate |
| Objective | Make the candidate entry identifiable as OurNear Hire and close the navigation gaps that are already specified, without building the missing product screens |

Deliverables:

- Hire scaffold background uses neutral surface `#F5F7F8` for the candidate theme, keeping teal and orange.
- Message and notification actions stay on the candidate screens that already have them. Jobs and Profile keep their current app bars in this stage.
- Reduced motion removes decorative Hire page transitions and the root navigation indicator animation. One-step swipe and bottom-tab taps still change tabs.
- Candidate Profile stops showing the Memory row. The unified shell may keep it. Do this by a candidate-specific profile entry or a flag, not by deleting Memory from the shared widget if the unified shell still needs it.
- `android/app/src/main/AndroidManifest.xml` declares `INTERNET` so a release build can call the API. The shared Android launcher label stays `ournear_mobile` because this application id also launches the unified shell.
- Web manifest name, short name, description, and colours use OurNear Hire and `#214449`. Icons stay until a design asset exists.
- Document the candidate run target: `flutter run -t lib/preview/hire_candidate_preview_main.dart`. Do not switch `lib/main.dart` in this stage. The unified shell tests stay valid.

Preserved: employer preview, jobs API behaviour, applications mock, login, non-wrapping swipe, `IndexedStack` state.

| Tests | Existing suite stays green. Add tests for the Hire surface colour, reduced-motion swipe still moving one tab, immediate page transitions, the candidate tab bar still containing exactly Home, Jobs, Applications, Profile, and Memory absent from the candidate profile entry |
| Gate | G-NAV gesture and top-bar rules that can be done without messages or notification APIs are met on the candidate entry. G-BRAND colours in the Hire theme and web manifest match the spec. Employer and unified tests still pass |

## Stage 3 — Home and job discovery on repository interfaces

| Item | Detail |
| --- | --- |
| ID | STAGE-3 |
| Depends on | STAGE-2 gate |
| Features | F-HOME, F-JOBS-SEARCH, F-JOBS-FILTERS, F-JOBS-CATALOGUE, F-JOBS-DETAIL, F-JOBS-COMPANY, F-JOBS-SAVED |
| Objective | Replace the Home placeholder with the specified Home structure, and put jobs behind a repository the UI does not bypass |

Deliverables:

- `JobsRepository` with a live implementation that wraps the existing `HireApiService` methods. `HireScreen` and `HireJobDetailScreen` call the repository, not a service constructed in the widget.
- A deterministic mock jobs repository for widget tests. The candidate app does not show those rows as if they were the account. If the API base is unset, the UI stays on the not-configured state rather than swapping in silent mock jobs.
- Home: search entry, category area, recommended and latest regions, saved-job entry. Category and saved regions use empty or unavailable states while GAP-MASTER-DATA and GAP-JOBS-SAVED are open.
- Jobs: keyword search wired to the existing `keyword` parameter. Filter and sort controls render only options supplied by a repository. With no master-data API, the control explains that options are unavailable.
- Job detail shows the parsed fields that are still hidden (responsibilities, what we offer, industry, category, closed / accepts applications) when present.
- Company details and saved jobs ship as screens with repository interfaces and explicit unavailable states. They do not invent company records.
- No Apply submit in this stage. A disabled or unavailable apply affordance may point at Stage 4.

| Tests | Repository unit tests for list, empty, error, and detail. Widget tests for Home structure, search field not changing tabs, and detail still covering the bottom bar |
| Gate | Home is no longer a placeholder. Jobs search uses `keyword`. No hard-coded industry list. Demo jobs are not shown in the candidate shell unless a test injects `MockJobsRepository`, and that injection is labelled. API register updated. Existing auth and applications tests still pass |

Stage 3 result: the candidate Home, keyword search, repository seam, detail fields, and honest saved-job, filter, and company states are in the app. GAP-MASTER-DATA, GAP-JOBS-SAVED, GAP-COMPANY, and separate field search remain open. Application submission stays in Stage 4.

## Stage 4 — Applications flow on the existing repository seam

| Item | Detail |
| --- | --- |
| ID | STAGE-4 |
| Depends on | STAGE-3 gate |
| Features | F-APP-READINESS, F-APP-WIZARD, F-APP-HISTORY, F-APP-TIMELINE |
| Objective | Extend applications from a demo list into the specified read and apply flow, still without inventing Laravel status transitions |

Deliverables:

- Keep `ApplicationsRepository`. Add methods only for behaviours the stage implements, with mock implementations and a live stub that fails as unavailable until a contract exists.
- Status filters on the list, limited to the client presentation enum until GAP-APP-STATUS is replaced by a verified server enum. The demo banner remains while the source is `mockDemo`.
- Detail timeline region. With demo data, show only the submitted and updated timestamps already on the model. Do not fabricate interview events.
- Readiness, wizard, review, questions, resume choice, and confirmation as UI flows against the mock repository. Confirmation success is allowed for the mock only behind the demo banner. The live stub must not report success.
- Duplicate submit is disabled in the client after a mock success and after a live success. It is not a substitute for a server rule.
- Job detail apply entry opens readiness. Bottom bar stays hidden for the whole wizard.

| Tests | Filter, empty live stub, wizard validation, duplicate guard, detail route does not swipe tabs |
| Gate | A candidate can walk a labelled demo application from job detail to confirmation, and can see history states including offline and empty. No new URL is called unless the API register has a row. Status labels are still marked unverified against Laravel |

Stage 4 result: readiness and the wizard are in the candidate flow. The live repository blocks list, detail, and submit without HTTP and without demo rows. A mock submit is labelled and is not server-confirmed. GAP-APP-LIST, GAP-APP-DETAIL, GAP-APP-STATUS, and GAP-APP-SUBMIT stay open. Profile editing and resume upload stay in Stage 5.

## Stage 5 — Profile and documents

| Item | Detail |
| --- | --- |
| ID | STAGE-5 |
| Depends on | STAGE-4 gate |
| Features | F-AUTH-SETUP, F-PROFILE, F-DOCS |
| Objective | Replace the candidate Profile tab contents with the profile and document screens. The Laravel profile remains the future source of truth |

Deliverables:

- `ProfileRepository` and `DocumentsRepository`, plus deterministic mocks.
- Overview, read-only professional profile, and separate edit forms for personal information, summary, experience, education, skills, qualifications, and certifications.
- Completion summary used by profile setup and by application readiness.
- Hire Resume and My Resume screens. My Resume is a single replaceable file in the mock. Preview hides the bottom bar.
- Private contact and file bytes are not on the default overview.
- The live repositories return unavailable until the API register has contracts. They do not persist a second profile on device as the canonical record. A mock may keep an in-memory profile for UI tests only, and the UI must label it as demo when that mock is active.
- Registration and password recovery screens can be built in this stage only as forms that call repository methods. Those methods stay unavailable on the live implementation (GAP-AUTH-REGISTER, GAP-AUTH-RECOVERY). Optional onboarding, with skip and without a language requirement, may land here if it has no backend dependency.

| Tests | Edit validation, one-file replace, preview route, private fields absent from the overview, demo label when the mock is active |
| Gate | Candidate Profile no longer presents the Memory row or the bare account card as the professional profile. Sign out still clears the session. No hard-coded industry or skill master list |

Stage 5 result: the candidate Profile tab is the professional overview, with a separate read-only profile, separate edit forms, and separate Hire Resume and My Resume sections. Live profile and document calls are unavailable and do not use HTTP. Mock edits are labelled and are not server persistence. GAP-PROFILE-READ, GAP-PROFILE-WRITE, GAP-PROFILE-PHOTO, GAP-RESUME-GENERATED, GAP-RESUME-UPLOAD, and GAP-RESUME-SNAPSHOT stay open. Registration and password recovery stay open. Messages and settings stay in Stage 6.

## Stage 6 — Messages, notifications, and settings

| Item | Detail |
| --- | --- |
| ID | STAGE-6 |
| Depends on | STAGE-5 gate |
| Features | F-MSG, F-NOTIF, F-PREFS |
| Objective | Add the communication and settings surfaces as non-tab flows |

Deliverables:

- Inbox, thread, and notification list behind repositories. Unread state comes from the repository. Placeholder snack bars on the top bar are replaced by these screens.
- Thread composer and links to a job or application only when the model has those ids.
- Settings: job preferences, notification preferences, privacy and account security, help, terms, privacy policy. Each shows unavailable when its gap is still open.
- Logout stays on the security screen and clears secure storage.
- Bottom bar hidden on threads and document preview. Horizontal drag in a thread does not change tabs.
- No push-notification SDK in this stage. `pushNotifications` stays unused unless a later contract requires a specific client.

| Tests | Unread badge or indicator, thread gesture does not change tabs, logout still clears the controller, unavailable copy for empty contracts |
| Gate | Messages and notifications are not bottom tabs. G-NAV top-bar rule is met with real routes. No support vendor is hard-coded |

Stage 6 result: Messages, notifications, and settings are secondary screens. Live message and notification calls throw unavailable and do not use HTTP. An unavailable list is not shown as an empty inbox. Mock rows are labelled and are not injected by the candidate shell. Send, mark read, and dismiss do not report success. Settings reuse the account session and the job-preferences form. GAP-MSG, GAP-NOTIF, GAP-PREFS, and GAP-CONTENT stay open. No push client was added.

## Stage 7 — Laravel integration for contracted gaps

| Item | Detail |
| --- | --- |
| ID | STAGE-7 |
| Depends on | STAGE-6 gate, plus an authorized API contract for each gap being closed. This stage does not inspect the website project unless a later instruction lifts that isolation rule |
| Objective | Replace mock repositories with live ones for the gaps whose contracts are actually known |

Deliverables:

- One live repository per contracted area, implementing the existing interface.
- API register rows moved from section 4 to section 2 with method, path, and error mapping taken from the mobile client.
- Gaps that still have no contract stay gaps. Their UI remains the unavailable or demo state.
- Application status labels map from the server enum. The client presentation enum is not silently treated as that enum.
- Resume download uses authorized access. Historical snapshots are shown only when the payload includes them.
- Remove demo banners for lists that the live repository has loaded. A failed live call must not fall back to demo rows without the banner.

| Tests | Fake-HTTP tests for every new client call, including 401 session clearing where the call is authenticated. Widget test that a live empty list does not show `demo-app-001` |
| Gate | Every new network call is in the API register. G-API and G-PROFILE hold. No Firebase, Supabase, or second database |

Stage 7 result: PARTIAL. The contract inventory is in the API register, section 8. Job list, job detail, login, account, and logout stay the only client calls. Fake-HTTP coverage was added for job detail, job-list HTTP 422, and logout. Every other gap stays blocked because the mobile tree has no contract. No Laravel file was opened. No new URL was called.

## Stage 8 — Android and web release candidate

| Item | Detail |
| --- | --- |
| ID | STAGE-8 |
| Depends on | STAGE-7 gate for the contracted scope, and an explicit decision on any gaps that remain unavailable |
| Objective | Produce an Android release build and a web release build of the candidate entry that meet the release gate |

Deliverables:

- `flutter build web -t lib/preview/hire_candidate_preview_main.dart` (or the candidate target Stage 2 documented, if it changed).
- `flutter build apk` or `flutter build appbundle` for the same target, with a release signing config that is not the debug keystore. The keystore is created outside the git-less workspace notes and is not committed into source.
- PWA manifest still `standalone`, brand colours, and install icons that are the OurNear Hire mark if one has been supplied. Template icons are a known limitation until then.
- Pass over Android back, text scaling, and reduced motion on the four root tabs, auth, apply, messages, and document preview.
- Candidate entry decision: if `lib/main.dart` is switched to `HireCandidateApp`, update `test/widget_test.dart` and `test/app_navigation_test.dart` in this stage and keep the unified shell runnable from its own target file. Do not drop the employer preview.

| Tests | Full `flutter test`, `flutter analyze`, both builds, and a manual pass recorded for the gesture and accessibility rules |
| Gate | G-SCOPE, G-STACK, G-NAV, G-BRAND, G-PROFILE, G-MOCK, G-API, G-A11Y, G-TEST, and G-RELEASE in the master spec. Remaining API gaps are listed as unavailable product behaviour, not as silent success |

Stage 8 result: PASS for the limited release-candidate scope. The debug APK and the default web build compile. The candidate preview remains `lib/preview/hire_candidate_preview_main.dart`. `lib/main.dart` still launches `OurNearApp`. No new HTTP call was added. Signing stays on the debug keystore. The APK was not installed and the web build was not deployed. Known gaps stay in `OURNEAR_HIRE_STAGE_8_RELEASE_CANDIDATE.md`. This is not a production recruitment release. Stage 7 remains PARTIAL.

## Stage 9 — Onboarding and account access UI

| Item | Detail |
| --- | --- |
| ID | STAGE-9 |
| Depends on | STAGE-8 gate for candidate shell stability |
| Features | F-AUTH-ONBOARDING, F-AUTH-REGISTER, F-AUTH-RECOVERY (UI only) |
| Objective | Optional onboarding plus registration and password recovery screens with honest unavailable submission. No new HTTP |

Deliverables:

- Three-page optional onboarding with skip for unauthenticated candidate entry.
- Registration and password recovery forms with local validation and navigation from login.
- `AccountAccessRepository` with `LiveAccountAccessRepository` that throws before HTTP.
- Onboarding completion in secure storage only.
- Focused tests in `test/features/auth/candidate_account_access_test.dart`. Candidate widget tests seed onboarding complete via `test/helpers/hire_candidate_test_storage.dart`.

| Tests | Onboarding navigation, registration and recovery validation, no HTTP on submit, login links, first-launch skip to jobs |
| Gate | Login, session restore, logout, four tabs, and verified API set unchanged. Registration and recovery remain GAP-AUTH-REGISTER and GAP-AUTH-RECOVERY |

Stage 9 result: PASS for the mobile-only account-access UI scope. Onboarding, registration, and recovery are frontend flows. Submit does not create accounts, send email, or open sessions. No new HTTP call was added. GAP-AUTH-REGISTER and GAP-AUTH-RECOVERY stay open. Stage 7 remains PARTIAL.

## Stage 10 — Guided candidate profile setup UI

| Item | Detail |
| --- | --- |
| ID | STAGE-10 |
| Depends on | STAGE-9 gate |
| Features | F-AUTH-SETUP (UI), reuses F-PROFILE edit forms |
| Objective | Optional guided setup over existing profile sections with honest local progress. No new HTTP |

Deliverables:

- `GuidedProfileSetupScreen` and `ProfileSetupProgress` over eight existing sections.
- Profile tab and onboarding entry points. No post-login redirect gate.
- Resume/documents link preserving Hire Resume vs My Resume unavailable semantics.
- `_savedInSession` on section forms so demo saves can return to the overview without a false discard prompt.

| Tests | `test/features/hire/profile/guided_profile_setup_test.dart` |
| Gate | Live profile repository unchanged. Four tabs unchanged. No “Saved on OurNear” for demo data |

Stage 10 result: PASS for guided setup UI. GAP-PROFILE-READ and GAP-PROFILE-WRITE stay open. No new HTTP call. Stage 7 remains PARTIAL.

## Stage 11 — Branded session gate

Verdict **PASS**. Branded candidate session gate, bootstrap failure handling with token retention on recoverable errors, retry and public browse. See `OURNEAR_HIRE_STAGE_11_SESSION_GATE.md`.

## Stage 12 — Candidate profile UX

Verdict **PASS**. Account vs professional profile labelling on Profile tab; guided setup navigation preserved. See `OURNEAR_HIRE_STAGE_12_CANDIDATE_PROFILE_UX.md`.

## Stage 13 — Job discovery and readiness

Verdict **PASS**. Debounced keyword search; application readiness copy. No new job/application HTTP. See `OURNEAR_HIRE_STAGE_13_JOB_DISCOVERY_READINESS.md`.

## Stage 14 — Candidate Home, direct Profile, and end drawer menu

| Item | Detail |
| --- | --- |
| ID | STAGE-14 |
| Depends on | STAGE-11–13 gates |
| Objective | Finalized candidate Home, Profile tab as direct professional view, `CandidateShellScaffold` end drawer with ordered menu, candidate-only dark mode. No new HTTP |

Deliverables:

- `CandidateHomeScreen` welcome banner, keyword + location (combined keyword), filter chips, Latest Jobs, menu and notifications on the app bar.
- Profile tab → `CandidateDirectProfileScreen` + `ProfessionalProfileBody`; Edit Profile → existing guided setup.
- `CandidateAppMenu`, `CandidateThemeController`, `AppVersion`.
- Tests: `test/features/hire/shell/candidate_home_menu_test.dart`, updated discovery and shell helpers.

| Tests | **135** pass, `flutter analyze` **0**, candidate `web_candidate` and debug APK **0** (preview target) |
| Gate | Four tabs unchanged. `lib/main.dart` unchanged. Verified HTTP count unchanged |

Stage 14 result: **PASS** for implemented UI and navigation. Menu-linked product areas that lack Laravel contracts remain **PARTIAL** (saved jobs persistence, applications history, documents upload, legal URLs, account deletion API).

## Stage 15 — Menu placement, dark mode, auth integration

| Item | Detail |
| --- | --- |
| ID | STAGE-15 |
| Menu | End drawer and menu icon only on Profile tab |
| Dark mode | `HireDarkTokens` + full `hireDark()` component theming |
| Auth | Login via existing `AuthService`; signup blocked until mobile contract documented |

| Tests | Menu placement, dark theme tokens, login flow (including duplicate submit guard) |
| Gate | No new HTTP beyond verified login/account/logout/jobs |

Stage 15 result: **PASS** menu and dark mode; login **PASS** (existing contract); signup **BLOCKED** (GAP-AUTH-REGISTER).

## Dependency sketch

```
STAGE-0 specification
  -> STAGE-1 toolchain and green-or-classified tests
    -> STAGE-2 shell identity and navigation gaps
      -> STAGE-3 jobs repository, Home, search
        -> STAGE-4 applications wizard and history
          -> STAGE-5 profile and documents
            -> STAGE-6 messages, notifications, settings
              -> STAGE-7 live API where a contract exists
                -> STAGE-8 release builds
                  -> STAGE-9 onboarding and account access UI (no new HTTP)
                    -> STAGE-10 guided profile setup UI (no new HTTP)
                      -> STAGE-11 session gate
                        -> STAGE-12 profile UX
                          -> STAGE-13 job discovery polish
                            -> STAGE-14 Home, direct Profile, drawer menu
```

Stages 3 through 6 may use mocks. They must not skip the repository interface. Stage 7 is the only stage that should add production URLs.

## What the next session should do

Start Stage 1. Do not start Stage 2 in the same session as an unclassified analyze or test failure. The environment at the end of Stage 0 cannot run Stage 1 until Flutter is installed.
