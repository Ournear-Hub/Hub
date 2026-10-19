# OurNear Hire â€” Mobile Baseline Report

| Field | Value |
| --- | --- |
| Document ID | BASELINE-CANDIDATE-STAGE-0 |
| Authority | `OURNEAR_HIRE_CANDIDATE_APP_MASTER_SPEC.md` |
| Date | 2026-10-14 |
| Workspace inspected | `C:\Ournear Hub\ournear_mobile` |
| Verdict | PASS for Stage 0. Stage 1 was not started |

Stage 0 acceptance is the documentation and inspection gate. It is not a claim that the candidate product is complete or that `flutter test` passed.

Stage 1 was completed later on the same day. Its command results are in section 15. Stage 2 was completed later on the same day. Its results are in section 16. Stage 3 was completed later on the same day. Its results are in section 17. Stage 4 was completed later on the same day. Its results are in section 18. Stage 5 was completed later on the same day. Its results are in section 19. Stage 6 was completed later on the same day. Its results are in section 20. Stage 7 was completed later on the same day. Its results are in section 21. Stage 8 was completed later on the same day. Its results are in section 22. Stage 9 was completed later on the same day. Its results are in section 23. Stage 10 was completed later on the same day. Its results are in section 24. Stages 11â€“13 were completed later on the same day. Their results are in section 25. Sections 1â€“14 remain the Stage 0 record and are not rewritten. Where section 15 disagrees with an earlier toolchain finding, section 15 is the Stage 1 machine state. Where section 16 disagrees with an earlier product-gap note, section 16 is the Stage 2 implementation record. Where section 17 disagrees with an earlier jobs or Home note, section 17 is the Stage 3 implementation record. Where section 18 disagrees with an earlier applications note, section 18 is the Stage 4 implementation record. Where section 19 disagrees with an earlier profile or documents note, section 19 is the Stage 5 implementation record. Where section 20 disagrees with an earlier messages, notifications, or settings note, section 20 is the Stage 6 implementation record. Where section 21 disagrees with an earlier integration-status note, section 21 is the Stage 7 implementation record. Where section 22 disagrees with an earlier release-candidate note, section 22 is the Stage 8 record. Where section 23 disagrees with an earlier auth-entry note, section 23 is the Stage 9 record. Where section 24 disagrees with an earlier profile-setup note, section 24 is the Stage 10 record. Stage 7 remains PARTIAL.

## 1. Workspace and isolation

| Check | Result |
| --- | --- |
| Work performed in | `C:\Ournear Hub\ournear_mobile` (Cursor workspace). This is the mobile project that was present |
| Path named in the Stage 0 brief | `D:\Ournear Hub\ournear_mobile`. `D:\` does not exist on this machine |
| Website project | `D:\Ournear Hub\Ournear Hire` was not opened, searched, edited, executed, or queried |
| Git repository | No `.git` directory. None was created. `.gitignore` is the Flutter template and was not modified |
| Files modified outside `docs/mobile/` | None |
| Employer app | Left in place. Not extended |
| Firebase / Supabase | Not added. Not present as direct dependencies |

`README.md` still describes a unified client and names the website path as the backend source of truth. Stage 0 did not edit that README. The documentation authority for the candidate app is this pack, not the README.

## 2. What was inspected

| Area | Paths |
| --- | --- |
| Package | `pubspec.yaml`, `pubspec.lock`, `analysis_options.yaml`, `.metadata` |
| Entries | `lib/main.dart`, `lib/app/ournear_app.dart`, `lib/app/hire_candidate_app.dart`, `lib/app/hire_employer_app.dart`, `lib/preview/hire_candidate_preview_main.dart`, `lib/preview/hire_employer_preview_main.dart` |
| Shell | `lib/app/hire_app_scaffold.dart`, `lib/core/navigation/root_tab_shell.dart`, `lib/core/navigation/root_tab_swipe_coordinator.dart`, `lib/core/navigation/app_shell.dart`, `lib/core/app_config/hire_app_config.dart`, `lib/core/app_config/hire_feature_flags.dart` |
| Jobs and auth | `lib/features/hire/hire_screen.dart`, `lib/features/hire/hire_job_detail_screen.dart`, `lib/core/api/hire_api_service.dart`, `lib/core/api/api_config.dart`, `lib/core/api/models/public_job.dart`, `lib/core/auth/auth_service.dart`, `lib/core/auth/auth_controller.dart`, `lib/features/auth/login_screen.dart` |
| Applications | `lib/features/hire/applications/**` |
| Profile and other shells | `lib/features/profile/profile_screen.dart`, `lib/features/home/home_screen.dart`, `lib/features/vibes/vibes_screen.dart`, `lib/features/drops/drops_screen.dart` |
| Theme and widgets | `lib/core/theme/**`, `lib/core/design_system/ournear_design_system.dart`, `lib/core/widgets/**`, `lib/core/layout/**` |
| Tests | All 15 `test/**/*_test.dart` files plus `test/helpers/fake_http_client.dart` (names and counts from source, not from a test run) |
| Android | `android/app/build.gradle.kts`, `android/build.gradle.kts`, `android/settings.gradle.kts`, `android/gradle/wrapper/gradle-wrapper.properties`, `android/gradle.properties`, `android/local.properties`, main/debug/profile manifests, `MainActivity.kt` |
| Web | `web/index.html`, `web/manifest.json`, `web/icons/` (four PNGs), existing `build/web/version.json` |

`lib/` contains 59 Dart files. The unified shell, candidate shell, and employer shell all compile as source in that tree. This session did not compile them.

## 3. Last recorded toolchain (not a live version check)

`.metadata` channel is `stable`, revision `19074d12f7eaf6a8180cd4036a430c1d76de904e`. That revision was not resolved to a version number in this session because `flutter` is not installed.

`.dart_tool/package_config.json` from a previous `pub get` on another Windows profile records:

| Field | Value |
| --- | --- |
| `flutterVersion` | `3.38.3` |
| `generatorVersion` | `3.10.1` |
| `flutterRoot` | `file:///C:/src/flutter` |
| `pubCache` | `file:///C:/Users/Lenovo/AppData/Local/Pub/Cache` |
| Package language version | `3.10` |

Those values describe the machine that last resolved packages. They are not `flutter --version` output from 2026-10-14.

## 4. Command results

### 4.1 `flutter --version`

Working directory: `C:\Ournear Hub\ournear_mobile`.

`flutter` is not on `PATH`. The command failed before any Flutter tool output:

```
flutter : The term 'flutter' is not recognized as the name of a cmdlet, function, script file, or operable program.
Check the spelling of the name, or if a path was included, verify that the path is correct and try again.
CommandNotFoundException
```

The same error was returned for `flutter doctor -v`.

Search for `flutter.bat`, depth 5:

| Location | Result |
| --- | --- |
| `C:\src\flutter\bin\flutter.bat` | Path missing. `C:\src` does not exist. This is the SDK path in `local.properties` and `package_config.json` |
| `C:\flutter\bin\flutter.bat` | Missing |
| `D:\src\flutter\bin\flutter.bat` and `D:\flutter\bin\flutter.bat` | `D:\` does not exist |
| `%LOCALAPPDATA%\flutter` and the user profile `flutter`, `develop\flutter`, `src\flutter` | Missing |
| `C:\Program Files`, `C:\Program Files (x86)`, `C:\Users\cours` | No `flutter.bat` within depth 5 |
| FVM | `%USERPROFILE%\fvm` and `%LOCALAPPDATA%\fvm` missing |

### 4.2 `flutter pub get`

Not started. There is no `flutter` executable to launch it. `pubspec.lock` already exists from the earlier resolution, so dependencies are recorded but were not fetched again.

### 4.3 `dart format --output=none --set-exit-if-changed lib test`

Not started. `dart` is not on `PATH` (`Get-Command dart` returned nothing). Format compliance of `lib/` and `test/` is unverified.

### 4.4 `flutter analyze`

Not started. Analyzer status is unverified. `analysis_options.yaml` includes `package:flutter_lints/flutter.yaml` with no extra rules enabled.

### 4.5 `flutter test`

Not started. No pass count and no fail count were produced by the test runner.

Source count, from `test` and `testWidgets` declarations:

| File | Cases |
| --- | --- |
| `test/widget_test.dart` | 1 |
| `test/app_navigation_test.dart` | 2 |
| `test/features/auth/login_flow_test.dart` | 2 |
| `test/core/auth/auth_service_test.dart` | 3 |
| `test/core/auth/auth_controller_test.dart` | 4 |
| `test/core/api/hire_api_service_test.dart` | 3 |
| `test/core/api/public_job_test.dart` | 2 |
| `test/core/app_config/hire_app_config_test.dart` | 2 |
| `test/core/navigation/root_tab_shell_test.dart` | 1 |
| `test/core/navigation/root_tab_swipe_coordinator_test.dart` | 4 |
| `test/core/widgets/platform_states_test.dart` | 1 |
| `test/core/widgets/design_system_components_test.dart` | 2 |
| `test/features/hire/applications/applications_controller_test.dart` | 4 |
| `test/features/hire/applications/mock_applications_repository_test.dart` | 3 |
| `test/features/hire/applications/candidate_applications_screen_test.dart` | 4 |
| Total | 38 cases in 15 files |

`test/helpers/fake_http_client.dart` is a helper and is not one of the 38.

Case names:

- OurNear app launches home
- bottom navigation opens each destination
- home module card opens hire tab
- login success pops screen
- login failure shows error
- login stores token and parses user
- login failure surfaces API message
- fetchAccount clears session on 401
- bootstrap unauthenticated when no stored token
- bootstrap restores authenticated session from stored token
- login failure keeps unauthenticated state
- logout clears authenticated state
- fetchJobs parses successful response
- fetchJobs handles empty list
- fetchJobs throws on server error
- PublicJob.fromJson parses API card payload
- PaginatedPublicJobs.fromJson parses list envelope
- candidate root tabs are Home, Jobs, Applications, Profile in order
- employer root tabs are Home, My Jobs, Applicants, Profile in order
- candidate shell swipes one tab without wraparound
- clampIndex never wraps past first or last tab
- resolveTabDelta ignores swipes starting in edge inset
- resolveTabDelta advances one tab from center swipe
- resolveTabDelta ignores swipe when a nested route is open
- foundation async states render accessible titles
- secondary button and success state render
- responsive frame constrains width on wide screens
- refresh populates demo applications
- refresh empty shows empty state
- refresh offline shows offline state when list empty
- findById returns matching application
- mock repository returns stable demo ids and mockDemo source
- mock repository empty configuration
- mock repository offline simulation
- shows demo banner and application cards when populated
- empty state offers browse jobs action
- opens detail screen on card tap
- offline state shows retry

Execution of all 38 is unverified in Stage 0.

## 5. Android and web build capability

| Requirement | This machine |
| --- | --- |
| Flutter SDK | Not installed. Web and Android builds cannot be started |
| Android SDK | `android/local.properties` points at `C:\Users\Lenovo\AppData\Local\Android\sdk`, which does not exist. `%LOCALAPPDATA%\Android\Sdk` does not exist. Android Studio is not under `C:\Program Files\Android\Android Studio` |
| JDK | `java` is not on `PATH`. No Java directory was found under `C:\Program Files\Java` or Eclipse Adoptium |
| Gradle project | Wrapper URL is Gradle 8.14. Android Gradle Plugin 8.11.1. Kotlin Android plugin 2.2.20. Java target 17. Numeric `minSdk` / `targetSdk` / `compileSdk` are delegated to the Flutter plugin and were not resolved |
| Previous web artifact | `build/web/version.json` exists (`ournear_mobile` 1.0.0+1). It was not built in this session |
| PWA | `web/manifest.json` uses `display: standalone`, so installation is configured where the browser supports it. Name and colours are still the Flutter template (`#0175C2`, â€œA new Flutter project.â€). Four icon PNGs exist under `web/icons/` |
| Release signing | `android/app/build.gradle.kts` uses the debug signing config for release |
| Network permission | `INTERNET` is declared in debug and profile manifests only |

Conclusion: a web build and an Android build are not possible in this environment until Stage 1 installs the SDKs. Configuration files for both targets exist and are the standard Flutter host projects plus the Hire Dart code.

## 6. Confirmed existing behaviour (code present)

â€œConfirmedâ€ here means the source implements the behaviour and, where noted, a test file asserts it. It does not mean the test runner passed on 2026-10-14.

| Feature | Evidence | Test source |
| --- | --- | --- |
| Candidate four-tab order Home, Jobs, Applications, Profile | `HireAppConfigs.candidate` | `hire_app_config_test.dart` |
| One-tab horizontal swipe, no wrap, on that shell | `RootTabShell`, `RootTabSwipeCoordinator` | `root_tab_shell_test.dart`, `root_tab_swipe_coordinator_test.dart` |
| Bottom bar remains a `NavigationBar` | `RootTabShell` | Shell swipe test taps Home after swiping |
| Tab state retained by `IndexedStack` | `RootTabShell` | Not separately asserted |
| Public job list, paging, empty, HTTP error, not-configured banner | `HireApiService`, `HireScreen` | Service and JSON tests. Screen not widget-tested |
| Public job detail load | `HireJobDetailScreen`, `fetchJobDetail` | No test |
| Login, session restore, 401 clear, logout | `AuthService`, `AuthController`, `LoginScreen` | Auth and login tests |
| Applications list, demo banner, empty, offline, detail navigation | Applications feature | Controller, mock, and screen tests |
| Shared loading, empty, error, offline, success widgets and Hire buttons | `lib/core/widgets` | Sample widget tests |
| Hire colours teal `#214449` and orange `#F15A24` | `OurNearColors`, `OurNearTheme.hireLight` | Not snapshot-tested |
| Message and notification buttons that do not add a fifth tab | `OurNearTopAppBar` on Home placeholder and Applications | Not asserted beyond the shell |

## 7. Partial behaviour

| Item | What exists | What the spec still requires |
| --- | --- | --- |
| Default entry | `lib/main.dart` opens the unified five-tab shell | Candidate product entry is the four-tab app. It is only the preview target today |
| Splash | Bootstrap spinner | Branded splash (SCR-AUTH-01) |
| Home | Placeholder empty state | Search, categories, recommended, latest, saved |
| Jobs | Live list and a thin detail | Search UI, filters, sort, catalogue, company, saved, apply, unused parsed fields |
| Applications | Mock list and a short detail, with an honest demo banner | Status filters, timeline, wizard, real API |
| Profile tab | Shared sign-in card, sign out, Memory snack bar | Professional profile and settings screens |
| Messages and notifications | Snack bars | Inbox, thread, notification list |
| Swipe | Implemented, including text-field and nested-scroll guards | Reduced motion |
| Surface colour | Canvas `#F8FAFC` | `#F5F7F8` |
| PWA identity | Standalone manifest and icons | OurNear Hire name and brand colours |
| Jobs architecture | Direct `HireApiService` | Repository interface |
| Feature flags | Declared on configs | Not consulted by widgets |

## 8. Missing candidate features

Absent as screens or client calls: profile setup, filters, industry catalogue, company details, saved jobs, application readiness, wizard, review, resume choice, job questions, submit confirmation, all professional profile forms, Hire Resume, My Resume, document preview, message inbox and thread, notification list, job preferences, notification preferences, privacy screen, help, terms, and privacy policy. Onboarding, registration, and password recovery screens exist in Stage 9 but registration and recovery submit paths have no verified HTTP contract.

The full list with entry points and states is `OURNEAR_HIRE_SCREEN_INVENTORY.md`. API gaps are `OURNEAR_HIRE_API_INTEGRATION_REGISTER.md` section 4.

## 9. Dependency notes

No package was removed or upgraded.

| Package | Locked | Concern |
| --- | --- | --- |
| `cupertino_icons` 1.0.9 | Unused in `lib/` and `test/` | Cleanup only. Not done in Stage 0 |
| `flutter_secure_storage` 9.2.4 | Used for the bearer token | Web storage is not hardware-backed. Acceptable until a security pass |
| `http` 1.6.0, `provider` 6.1.5+1, `url_launcher` 6.3.2, `flutter_tabler_icons` 1.43.0, `flutter_lints` 6.0.0 | In use | No suitability defect found from source inspection |
| `flutter pub outdated` | Not run | Newer versions were not assessed |

## 10. Defects and limitations

| ID | Severity | Detail |
| --- | --- | --- |
| DEF-TOOLCHAIN | Blocks Stage 1 and all builds | Flutter, Dart, Android SDK, and JDK are not installed. `local.properties` points at another userâ€™s paths |
| DEF-ANDROID-INTERNET | Resolved in current source; historical Stage 0 finding | Stage 0 recorded a missing main-manifest `INTERNET` permission. Checked 2026-10-19: `android/app/src/main/AndroidManifest.xml` declares `android.permission.INTERNET`. No change required in this follow-up. |
| DEF-ANDROID-SIGNING | Release process | Release builds are signed with the debug keystore |
| DEF-PWA-IDENTITY | Brand | Manifest and `index.html` still say `ournear_mobile` and use `#0175C2` |
| DEF-SURFACE | Brand | Hire canvas is `#F8FAFC`, not the specified `#F5F7F8` |
| DEF-PROFILE-MEMORY | Scope leak | Candidate Profile tab shows a Memory row because it reuses the unified profile widget |
| DEF-JOBS-FIELDS | Product | Detail model parses responsibilities, offers, industry, category, and `accepts_applications`, and the screen does not show them |
| DEF-KEYWORD | Product | `fetchJobs` supports `keyword`. The Jobs screen never sends it |
| DEF-REDUCED-MOTION | Accessibility | Swipe coordinator ignores reduced-motion settings |
| DEF-DETAIL-TEST | Test gap | `fetchJobDetail` has no unit test |
| DEF-FORMAT-UNKNOWN | Verification | Format and analyze were not run |

These are recorded. Stage 0 did not patch them, because the stage rule is to avoid changing working behaviour to make the baseline look clean, and because the toolchain cannot verify a patch.

## 11. Documentation created

| File | Role |
| --- | --- |
| `docs/mobile/OURNEAR_HIRE_CANDIDATE_APP_MASTER_SPEC.md` | Product authority |
| `docs/mobile/OURNEAR_HIRE_MOBILE_ARCHITECTURE.md` | Current structure and extension rules |
| `docs/mobile/OURNEAR_HIRE_SCREEN_INVENTORY.md` | 42 approved screens, 7 partial, 35 absent |
| `docs/mobile/OURNEAR_HIRE_API_INTEGRATION_REGISTER.md` | Five client calls, two web fallbacks, integration gaps |
| `docs/mobile/OURNEAR_HIRE_MOBILE_IMPLEMENTATION_ROADMAP.md` | Stages 0 through 8 |
| `docs/mobile/OURNEAR_HIRE_MOBILE_BASELINE_REPORT.md` | This file |

## 12. Stage 0 gate

| Criterion | Met |
| --- | --- |
| Mobile workspace inspected | Yes |
| Existing behaviour separated from the planned product | Yes, in the inventory, architecture, and sections 6â€“8 |
| Baseline commands recorded, or the reason they could not run | Yes, section 4 |
| Six documentation files with project-specific content | Yes |
| Candidate-only scope and frozen stack documented | Yes, master spec sections 2 and 3 |
| Website isolation respected | Yes |
| No feature rewrite and no website edit | Yes |
| Next stage defined | Yes. Stage 1 in the roadmap |

Stage 0 verdict: **PASS**.

## 13. Next stage

**Stage 1 â€” Toolchain restoration and command baseline.** Not started.

Reason: the environment cannot run `flutter` or `dart`, and it has no Android SDK or JDK. Starting Stage 2 or any feature work would skip the roadmap gate.

Stage 1 objectives:

1. Install Flutter stable compatible with Dart `^3.10.1`, preferring the recorded 3.38.3 SDK when it can be restored.
2. Install a JDK and Android SDK before attempting an Android build. Update `android/local.properties` to this machine.
3. Run `flutter pub get`, `dart format --output=none --set-exit-if-changed lib test`, `flutter analyze`, and `flutter test`.
4. Record exit codes. Do not edit product code unless a failure is a confirmed defect with a minimal fix.
5. Only then may Stage 2 (candidate shell identity, brand surface, reduced motion, main-manifest `INTERNET`, candidate profile without the Memory row) begin.

## 14. Website confirmation

No file under a website project was read or written. `D:\` is not mounted, and the website path was not accessed through any other drive. The only files added in this stage are the six documents under `docs/mobile/`.

## 15. Stage 1 â€” toolchain restoration and verified baseline (2026-10-14)

Stage 1 verdict: **PASS**.

Flutter 3.38.3 and Dart 3.10.1 are installed and match `pubspec.yaml` `sdk: ^3.10.1`. Dependencies resolved. Format, analyzer, and tests have recorded outcomes. Web and debug Android builds both succeeded. No candidate feature was implemented. The website project was not touched.

Stage 2 is authorized by the Stage 1 acceptance gate. Two pre-existing code findings stay open and are classified below. They are not external blockers, and they were not patched in this stage.

### 15.1 Machine

| Item | Value |
| --- | --- |
| OS | Windows 11 25H2, build 10.0.26200, 64-bit AMD64. Account `cours` is not an administrator |
| Free disk at start | About 138 GB on `C:` |
| Selected SDK | Official Flutter stable 3.38.3, because the release index still lists it with Dart 3.10.1 |
| Flutter location | `C:\Users\cours\development\flutter` |
| Archive | `https://storage.googleapis.com/flutter_infra_release/releases/stable/windows/flutter_windows_3.38.3-stable.zip` |
| SHA-256 | `dd965ac84db435225eea9c55430de262770728617d4df57a370ffc56f2591077` (matched the release index) |
| Framework revision | `19074d12f7`, the same revision already recorded in `.metadata` |
| JDK | Eclipse Temurin 17.0.20.1+1 at `C:\Users\cours\development\jdk-17.0.20.1+1` |
| JDK source | Adoptium API `https://api.adoptium.net/v3/binary/latest/17/ga/windows/x64/jdk/hotspot/normal/eclipse?project=jdk` |
| Why JDK 17 | `android/app/build.gradle.kts` targets Java 17. Android Gradle Plugin 8.11.1 and Gradle 8.14 accept JDK 17. `winget` user-scope install of Temurin had no applicable installer, so the official zip was used |
| Android command-line tools | `https://dl.google.com/android/repository/commandlinetools-win-13114758_latest.zip`, sdkmanager 19.0, installed at `C:\Users\cours\AppData\Local\Android\Sdk\cmdline-tools\latest` |
| Android packages | platform-tools; `platforms;android-36`; `build-tools;36.0.0`; NDK `28.2.13676358`. The debug build also installed build-tools 35.0.0, platforms android-34 and android-35, and CMake 3.22.1 |
| Licences | Accepted with `sdkmanager --licenses`. The Gradle build accepted the additional package licences it downloaded |

Flutterâ€™s own defaults, from `FlutterExtension.kt` in this SDK, are compileSdk 36, targetSdk 36, minSdk 24, and NDK `28.2.13676358`.

### 15.2 Environment configuration

User-level variables were set. The machine-wide PATH was not changed.

| Variable | Value |
| --- | --- |
| `JAVA_HOME` | `C:\Users\cours\development\jdk-17.0.20.1+1` |
| `ANDROID_HOME` | `C:\Users\cours\AppData\Local\Android\Sdk` |
| `ANDROID_SDK_ROOT` | `C:\Users\cours\AppData\Local\Android\Sdk` |
| User `Path` additions | `C:\Users\cours\development\flutter\bin`, the JDK `bin`, `platform-tools`, and `cmdline-tools\latest\bin` |

`android/local.properties` now points at this machine:

```
sdk.dir=C:\\Users\\cours\\AppData\\Local\\Android\\Sdk
flutter.sdk=C:\\Users\\cours\\development\\flutter
```

The previous paths (`C:\src\flutter` and `C:\Users\Lenovo\AppData\Local\Android\sdk`) do not exist here. No application id, permission, signing config, or Dart source was edited.

A new terminal is required before `flutter` is found on `PATH`. Commands in this stage were invoked with the user Flutter `bin` directory prefixed for that process.

### 15.3 Commands and exit codes

Working directory for project commands: `C:\Ournear Hub\ournear_mobile`.

| Command | Exit | Result |
| --- | --- | --- |
| `flutter --version` | 0 | Flutter 3.38.3, channel stable, revision `19074d12f7`, Engine `13e658725d`, Dart 3.10.1, DevTools 2.51.1 |
| `dart --version` | 0 | Dart SDK 3.10.1 (stable) on `windows_x64` |
| `flutter doctor -v` | 0 | Flutter, Windows, Android toolchain (SDK 36.0.0), and Chrome are healthy. Android licences are accepted. Visual Studio is absent (Windows desktop only; not required for Android or web). A pub.dev SSL check failed once during doctor; the later `flutter pub get` still downloaded packages |
| `flutter pub get` | 1 | Printed `Got dependencies!` Direct locked versions stayed at the Stage 0 set: `cupertino_icons` 1.0.9, `flutter_tabler_icons` 1.43.0, `http` 1.6.0, `url_launcher` 6.3.2, `flutter_secure_storage` 9.2.4, `provider` 6.1.5+1, `flutter_lints` 6.0.0. The non-zero exit is the Windows plugin symlink check, not a resolution failure. 22 packages have newer versions outside the current constraints. None were upgraded |
| `dart format --output=none --set-exit-if-changed lib test` | 1 | 75 files checked, 46 would change. `--output=none` was used, so no file was rewritten |
| `flutter analyze` | 1 | 1 issue. `warning - Unused import: package:ournear_mobile/core/widgets/ournear_primary_button.dart - test/core/widgets/design_system_components_test.dart:5:8 - unused_import` |
| `flutter test` | 0 | `+38: All tests passed!` |
| `flutter build web` | 0 | `Built build\web` in 147.7s. Default target `lib/main.dart` (unified shell). A Wasm dry run reported `flutter_secure_storage_web` using `dart:html` / `dart:js`. The JavaScript web build still completed. Wasm was not selected and the package was not upgraded |
| `flutter build apk --debug` | 0 | `Built build\app\outputs\flutter-apk\app-debug.apk` (151,715,392 bytes) after `assembleDebug` in 1180.0s. Same default target. The APK was not installed on a device and was not published |

`flutter pub get` text that caused exit 1:

```
Building with plugins requires symlink support.
Please enable Developer Mode in your system settings. Run
  start ms-settings:developers
to open settings.
```

`cmd /c mklink` returned â€œYou do not have sufficient privilege to perform this operation.â€ Developer Mode is off, and this account cannot enable it without approval. Android and web builds still completed, so the missing privilege did not block those two verification builds. It remains a blocker for Windows desktop plugin builds, which are outside the candidate product platforms.

### 15.4 Classified findings left for later stages

| ID | Stage 1 status | Decision |
| --- | --- | --- |
| DEF-TOOLCHAIN | Resolved on this machine | Historical Stage 0 text in section 10 stays as the record of the earlier machine |
| DEF-FORMAT-UNKNOWN | Closed as a verification gap | Format is not clean. 46 files differ. Do not mass-format unless a later stage takes that as its own change |
| Analyzer unused import | New, non-blocking | Leave `test/core/widgets/design_system_components_test.dart` unchanged in Stage 1. A later cleanup may delete that unused import. It does not fail `flutter test` |
| DEF-ANDROID-INTERNET | Historical note superseded by current-source check (2026-10-19) | The main manifest now declares `android.permission.INTERNET`; the historical Stage 0 finding is not an outstanding source defect. Existing APK presence alone does not prove live network E2E. |
| DEF-ANDROID-SIGNING | Unchanged | Debug APK uses debug signing, which is expected for `--debug`. Release signing was not changed |
| DEF-PWA-IDENTITY, DEF-SURFACE, DEF-PROFILE-MEMORY, DEF-JOBS-FIELDS, DEF-KEYWORD, DEF-REDUCED-MOTION, DEF-DETAIL-TEST | Unchanged | Still product or test gaps. Stage 2 and later, per the roadmap |
| Wasm web storage | New limitation | `flutter_secure_storage` 9.2.4 cannot compile to Wasm. The default web build is JavaScript and succeeded. Do not upgrade the package in order to chase Wasm |
| Windows Developer Mode | External, non-blocking for Android and web | Enable Developer Mode in Windows settings if Windows desktop builds are ever required. Not required for Stage 2 |
| Visual Studio | External, out of scope | Needed only for Windows desktop. Not installed |

### 15.5 Files changed in the project

| Path | Change |
| --- | --- |
| `android/local.properties` | Machine SDK paths only |
| `docs/mobile/OURNEAR_HIRE_MOBILE_BASELINE_REPORT.md` | This Stage 1 section |

`pubspec.yaml`, `pubspec.lock` direct versions, `lib/`, and `test/` were not edited. No Git repository was created. Build outputs under `build/web` and `build/app/outputs/flutter-apk/` are from this session.

### 15.6 Stage 1 gate

| Criterion | Met |
| --- | --- |
| Flutter and Dart installed and compatible with `^3.10.1` | Yes. 3.38.3 / 3.10.1 |
| Dependencies resolve | Yes. `Got dependencies!` Exit code 1 is the symlink warning described above |
| Format, analyzer, and tests recorded | Yes. Exits 1, 1, and 0. Tests: 38 passed |
| Android and web prerequisites verified | Yes. `flutter doctor` reports the Android toolchain and Chrome |
| Both platform builds pass | Yes. `flutter build web` exit 0. `flutter build apk --debug` exit 0 |
| No candidate feature implemented | Yes |
| Website and production services untouched | Yes. `D:\` was not accessed |

### 15.7 Website isolation

No command was run against `D:\Ournear Hub\Ournear Hire`. That drive is still absent. No backend, database, or website file was read or written.

### 15.8 Next stage

Stage 2 is authorized. Its objectives stay those in the roadmap: candidate shell identity, Hire colours on the Android label and web manifest, surface `#F5F7F8`, reduced-motion swipe, `INTERNET` on the main manifest, and a candidate Profile entry that does not show Memory. Do not switch `lib/main.dart` in that stage. The format drift and the unused test import are classified and are not a reason to reformat the project as part of that feature work.

## 16. Stage 2 â€” Candidate identity and shell stabilization

| Field | Value |
| --- | --- |
| Date | 2026-10-14 |
| Verdict | PASS |
| Workspace | `C:\Ournear Hub\ournear_mobile` |
| Entry left unchanged | `lib/main.dart` still runs `OurNearApp` |

### 16.1 Candidate identity and theme

The candidate `MaterialApp` title is `OurNear Hire` (`HireAppConfigs.candidate.displayName`). The employer title stays `ON Hire: Employer`.

`OurNearTheme.hireLight()` uses primary `#214449`, secondary `#F15A24`, scaffold and app bar `#F5F7F8` (`OurNearColors.hireCanvas`), white cards, and a white navigation bar. Selected navigation labels and icons use the Hire primary. `OurNearTheme.light()` still uses canvas `#F8FAFC` and brand `#2563EB`.

The Hire theme is shared with the employer preview, so that previewâ€™s scaffold surface moves to `#F5F7F8` as well. Its tabs, display name, and Memory row were not changed.

The shared Android launcher label stays `ournear_mobile`. One application id launches both the unified shell and the candidate preview, so renaming the label would rebrand the unified app.

Jobs and Profile keep their existing app bars. Message and notification actions remain on the candidate screens that already had them.

### 16.2 Reduced motion

`OurNearMotion` reads `MediaQuery.disableAnimationsOf`. On the Hire `MaterialApp`, reduced motion installs `InstantPageTransitionsBuilder` for every platform, so decorative route transitions show the destination immediately. `RootTabShell` sets `NavigationBar.animationDuration` to zero in that same case, including the unified shell, because the shell is shared. With animations enabled, the duration stays null and the framework default remains.

`RootTabSwipeCoordinator` still allows one-step swipes. Reduced motion is not an ignore reason. Edge inset, focused `EditableText`, nested horizontal scroll, and `Navigator.canPop` guards are unchanged. Tab order stays Home, Jobs, Applications, Profile.

### 16.3 Android INTERNET permission

`android/app/src/main/AndroidManifest.xml` declares `android.permission.INTERNET` once, before `<application>`. Debug and profile manifests still declare it for the Flutter tool. The packaged debug manifest at `build/app/intermediates/packaged_manifests/debug/processDebugManifestForPackage/AndroidManifest.xml` contains that permission once. Application id, launch activity, and signing were not changed.

### 16.4 Candidate Profile

`ProfileScreen` takes `showMemoryShortcut`, default true. The candidate destination passes false. The unified shell and the employer destination keep the default, so the Memory row and the unified Memory module stay. The candidate Profile tab still shows the sign-in card, the Sign in button, and Notifications. No placeholder replaced Memory.

### 16.5 PWA manifest

`web/manifest.json`: `name` and `short_name` are `OurNear Hire`, `theme_color` and `background_color` are `#214449`, `display` stays `standalone`, description is `OurNear Hire jobs and careers.`, and the existing icon paths are unchanged.

`web/index.html` title, description, Apple web app title, and `<meta name="theme-color" content="#214449">` match. The built `build/web` copies match. `flutter build web` still compiles `lib/main.dart`.

### 16.6 Files changed

| Path | Change |
| --- | --- |
| `lib/core/theme/ournear_colors.dart` | Added `hireCanvas` `#F5F7F8` |
| `lib/core/theme/ournear_theme.dart` | Hire scaffold and app bar use `hireCanvas`. Unified theme still uses `canvas` |
| `lib/core/app_config/hire_app_config.dart` | Candidate display name `OurNear Hire`. Candidate profile hides Memory |
| `lib/core/navigation/ournear_motion.dart` | Reduced-motion page transitions and navigation duration |
| `lib/app/hire_app_scaffold.dart` | Applies reduced-motion page transitions |
| `lib/core/navigation/root_tab_shell.dart` | Navigation indicator duration follows reduced motion |
| `lib/core/navigation/root_tab_swipe_coordinator.dart` | Documents that reduced motion does not cancel a swipe |
| `lib/features/profile/profile_screen.dart` | Optional Memory row. Icons follow the active theme primary |
| `android/app/src/main/AndroidManifest.xml` | One `INTERNET` permission |
| `web/manifest.json` | OurNear Hire name and `#214449` colours |
| `web/index.html` | Matching title, description, and theme colour |
| `test/core/theme/hire_theme_surface_test.dart` | Hire surface and unified canvas |
| `test/core/navigation/reduced_motion_test.dart` | Swipe, immediate transition, default duration |
| `test/features/profile/candidate_profile_screen_test.dart` | Candidate Profile omits Memory and keeps Sign in. Default profile keeps Memory |
| `test/core/app_config/hire_app_config_test.dart` | Candidate display name expectation |
| `docs/mobile/OURNEAR_HIRE_CANDIDATE_APP_MASTER_SPEC.md` | Surface token and reduced-motion rule |
| `docs/mobile/OURNEAR_HIRE_MOBILE_ARCHITECTURE.md` | Theme, motion, profile flag, manifest, INTERNET |
| `docs/mobile/OURNEAR_HIRE_MOBILE_IMPLEMENTATION_ROADMAP.md` | Stage 2 deliverables aligned with this implementation |
| `docs/mobile/OURNEAR_HIRE_SCREEN_INVENTORY.md` | Candidate profile no longer lists Memory as shown |
| `docs/mobile/OURNEAR_HIRE_MOBILE_BASELINE_REPORT.md` | This section |

`lib/main.dart`, `pubspec.yaml`, and `pubspec.lock` were not edited. No Git repository was created. The APK was not installed.

### 16.7 Tests, analyzer, and builds

| Command | Exit | Result |
| --- | --- | --- |
| Focused tests: `hire_theme_surface_test.dart`, `reduced_motion_test.dart`, `candidate_profile_screen_test.dart` | 0 | 7 tests passed |
| `flutter test` | 0 | `+45: All tests passed!` (Stage 1 was 38. This stage added 7) |
| `flutter analyze` | 1 | 1 pre-existing warning. See below |
| `flutter build web` | 0 | `Built build\web` in 170.3s. Compiles `lib/main.dart`. Same `flutter_secure_storage_web` Wasm dry-run warnings as Stage 1. JavaScript build succeeded |
| `flutter build apk --debug` | 0 | `Built build\app\outputs\flutter-apk\app-debug.apk` after `assembleDebug` in 62.6s. File size 171,304,629 bytes. Not installed and not published |
| `dart format --output=none --set-exit-if-changed lib test` | 1 | 79 files checked, 40 would change. `--output=none` did not rewrite them. Stage 1 reported 46. The drop is the Dart files formatted individually in this stage |

Analyzer text:

```
warning - Unused import: 'package:ournear_mobile/core/widgets/ournear_primary_button.dart' - test/core/widgets/design_system_components_test.dart:5:8 - unused_import
```

Existing navigation, login, job, and application tests are inside the 45 passing tests. They were not removed.

### 16.8 Known remaining issues

| Item | Status |
| --- | --- |
| Format drift | 40 files would still change. Not bulk-formatted |
| Unused test import | Unchanged from Stage 1. Not treated as fixed |
| `flutter pub get` symlink exit 1 | Unchanged. Developer Mode is still off. Not re-run as a product change |
| Wasm web storage | Unchanged. `flutter_secure_storage` 9.2.4. JavaScript web build succeeded |
| Release signing | Unchanged. Debug APK still uses the debug keystore |
| Android launcher label | Still `ournear_mobile` because the application id is shared with the unified shell |
| Jobs and Profile app bars | Still the existing plain app bars. Message and notification actions were not added to those two screens |
| Product gaps after Stage 2 | Jobs repository, Home structure, keyword search, and the rest of the screen inventory remain Stage 3 and later |

### 16.9 Website isolation

No command was run against `D:\Ournear Hub\Ournear Hire`. That drive was not opened. No Laravel route, model, database, or production configuration was read or written.

### 16.10 Next stage

Stage 3 â€” Home and job discovery on repository interfaces. It covers the jobs repository, Home structure, and keyword search, without hard-coded industries and without silent mock jobs in the candidate shell.

## 17. Stage 3 â€” Home, jobs repository, and job discovery

Stage 3 was completed later on 2026-10-14 in `C:\Ournear Hub\ournear_mobile`. `lib/main.dart` still launches `OurNearApp`. The candidate entry remains `lib/preview/hire_candidate_preview_main.dart`.

### 17.1 Verdict

PASS.

Home is a job-discovery screen. Jobs keyword search goes through `JobsRepository`. Job details return to the prior Jobs tab with the keyword kept. Loading, empty, failure, retry, and not-configured states are covered. Unsupported filters and saved jobs are explained and do not call invented endpoints. The full test suite, analyzer baseline, web build, and debug APK all match the results below.

### 17.2 What the candidate shell does now

| Surface | Behaviour |
| --- | --- |
| Home | OurNear Hire app bar, search that opens Jobs with one keyword, industry area, latest jobs, saved-jobs entry. Message and notification buttons still snack a later-phase message |
| Jobs | Search, clear, result count from `meta.total`, refresh, load more, job cards. A note says filters and sorting are not available |
| Job details | Parsed published fields. Apply Now opens a later-stage notice and does not submit. Apply Now is hidden when the job is closed or `accepts_applications` is false |
| Company | Name and logo URL already on the job. No company request |
| Saved jobs | Screen says jobs are not saved on the device and are not synced |

The latest-jobs heading is not a personalized recommendation. The candidate shell uses `LiveJobsRepository`. An unset `OURNEAR_API_BASE` shows the not-configured state. `MockJobsRepository` is for tests. A mock page, if one is shown, says the rows are not from the OurNear server.

The unified Hire tab still uses `HireScreen` with `showDiscoveryControls` false, so it does not show the candidate search field. Its not-configured copy still names `OURNEAR_API_BASE`.

### 17.3 Confirmed client API use

| Call | Parameters sent |
| --- | --- |
| `GET /api/v1/jobs` | `page`, `per_page`, and `keyword` when the keyword is non-empty |
| `GET /api/v1/jobs/{slug}` | Path slug only |

No industry, location, experience, employment-type, or sort parameter is sent. Whether the server treats `keyword` as title, skill, company, and location is unverified. The Laravel project was not inspected.

### 17.4 Gaps that stay open

| ID | Stage 3 handling |
| --- | --- |
| GAP-MASTER-DATA | `fetchIndustryShortcuts` throws and does not call HTTP. Shortcuts render only from repository data and are sent as `keyword` |
| GAP-JOBS-SEARCH | One `keyword` only. Separate fields are not presented as filters |
| GAP-JOBS-SAVED | `fetchSavedJobs` throws and does not call HTTP |
| GAP-COMPANY | No company URL. The screen uses fields already on the job |
| Application submit | `ApplyLaterScreen` says nothing has been submitted. Stage 4 |

### 17.5 Commands

Toolchain is the Stage 1 SDK: Flutter 3.38.3, Dart 3.10.1, JDK 17 at `C:\Users\cours\development\jdk-17.0.20.1+1`, Android SDK at `C:\Users\cours\AppData\Local\Android\Sdk`.

| Command | Exit | Result |
| --- | --- | --- |
| Focused jobs tests (`candidate_job_discovery_test.dart` and `jobs_repository_test.dart`) | 0 | 12 tests passed |
| `flutter test` | 0 | `+57: All tests passed!` Wall time about 79 seconds. The `+57` line is at 50 seconds of test time |
| `flutter analyze` | 1 | 1 issue: unused import in `test/core/widgets/design_system_components_test.dart:5:8`. Same warning as Stage 1 and Stage 2. Ran in 9.6 seconds |
| `flutter build web` | 0 | `Built build\web`. Compile time 123.7 seconds. Wall time about 131 seconds |
| `flutter build apk --debug` | 0 | `Built build\app\outputs\flutter-apk\app-debug.apk`. Gradle 30.3 seconds. File length 171,324,856 bytes. Not installed |

The web build printed the existing `flutter_secure_storage` 9.2.4 WebAssembly incompatibilities and still produced the JavaScript build. `flutter pub get` was not re-run. The repository was not bulk-formatted. The Stage 2 format-drift finding remains.

### 17.6 Files

New:

- `lib/features/hire/jobs/data/jobs_repository.dart`
- `lib/features/hire/jobs/data/mock_jobs_repository.dart`
- `lib/features/hire/jobs/jobs_discovery_controller.dart`
- `lib/features/hire/jobs/job_card.dart`
- `lib/features/hire/jobs/saved_jobs_screen.dart`
- `lib/features/hire/jobs/company_details_screen.dart`
- `lib/features/hire/jobs/apply_later_screen.dart`
- `lib/features/hire/home/candidate_home_screen.dart`
- `test/features/hire/jobs/jobs_repository_test.dart`
- `test/features/hire/jobs/candidate_job_discovery_test.dart`

Changed:

- `lib/core/api/hire_api_service.dart` (`isConfigured` only)
- `lib/features/hire/hire_screen.dart`
- `lib/features/hire/hire_job_detail_screen.dart`
- `lib/app/hire_candidate_app.dart`
- `lib/core/app_config/hire_app_config.dart`
- Navigation tests that looked for the old Home placeholder sentence
- API register, screen inventory, architecture sections 5.1 and 5.2, roadmap Stage 3, and this report

`lib/main.dart` was not edited.

### 17.7 Known remaining issues

Section 16.8 items that were not part of Stage 3 are unchanged: format drift, the unused test import, the `flutter pub get` symlink exit, Wasm web storage, debug signing, the shared Android launcher label `ournear_mobile`, and the plain Jobs and Profile app bars. Message and notification actions on Home are still later-phase snack bars.

### 17.8 Website isolation

No command was run against `D:\Ournear Hub\Ournear Hire`. That drive was not opened. No Laravel route, model, database, or production configuration was read or written.

### 17.9 Next stage

Stage 4 â€” applications flow on the existing repository seam. It covers readiness, the application wizard, history, and timeline. It was not started.

## 18. Stage 4 â€” Application readiness, wizard, history, and timeline

Stage 4 was completed later on 2026-10-14 in `C:\Ournear Hub\ournear_mobile`. `lib/main.dart` still launches `OurNearApp`.

### 18.1 Verdict

PASS.

Readiness and the wizard are available from Apply Now. The live applications repository does not call an applications URL, does not report a successful submission, and does not show demo rows as the candidateâ€™s applications. Mock submission stays behind an explicit repository and the demo banner.

### 18.2 What the candidate shell does now

| Surface | Behaviour |
| --- | --- |
| Readiness | Job summary, published availability, profile gap, and resume gap. Continue is hidden when the job is closed or `accepts_applications` is false |
| Wizard | Account, resume, and review. Account uses the signed-in `AuthUser`. Next is blocked until the candidate is signed in. System back moves one step |
| Resume | Says no resume source is connected. Nothing is attached |
| Questions | The step is omitted. Review says no questions were provided |
| Submit | `LiveApplicationsRepository.submitApplication` throws unavailable. The review stays on screen and can be tried again. Copy says nothing was sent |
| Mock submit | Only when `MockApplicationsRepository` is injected. Adds `demo-app-local-*`, `confirmedByServer` false, demo banner, and â€œDemo record only.â€ A second submit for the same slug is refused |
| History | The candidate tab uses the live repository and says history is not connected. Demo chips appear only for the complete mock list and are labelled as presentation labels |
| Detail | Timeline contains only supplied events. Demo events are Submitted and, when the record has it, Updated. No interview, withdrawal, or status edit |

### 18.3 Confirmed client API use

No new HTTP call was added. The verified client calls remain the Stage 3 set: `GET /api/v1/jobs`, `GET /api/v1/jobs/{slug}`, `POST /api/v1/auth/login`, `GET /api/v1/account`, and `POST /api/v1/auth/logout`.

Application list, detail, and submit have no client URL. `LiveApplicationsRepository` throws before any request.

### 18.4 Gaps that stay open

| ID | Stage 4 handling |
| --- | --- |
| GAP-APP-LIST | Live history throws unavailable and does not call HTTP |
| GAP-APP-DETAIL | Live detail throws unavailable. No interview payload exists |
| GAP-APP-STATUS | Demo labels are not a server enum. The live screen sends no status parameter |
| GAP-APP-SUBMIT | No submission URL and no idempotency key. The button disable is a client guard only |
| GAP-PROFILE-READ | Readiness says no professional profile is loaded |
| GAP-RESUME-UPLOAD | The resume step cannot attach a file |

### 18.5 Commands

| Command | Exit | Result |
| --- | --- | --- |
| Detail-focused widget tests after the surface-size fix | 0 | 5 tests passed |
| `flutter test` | 0 | `+74: All tests passed!` Wall time about 55 seconds |
| `flutter analyze` | 1 | 1 issue: unused import in `test/core/widgets/design_system_components_test.dart:5:8`. Same warning as Stage 1. Ran in 13.3 seconds |
| `flutter build web` | 0 | `Built build\web`. Compile time 203.8 seconds |
| `flutter build apk --debug` | 0 | `Built build\app\outputs\flutter-apk\app-debug.apk`. Gradle 76.4 seconds. File length 171,344,536 bytes. Not installed |

The web build printed the existing `flutter_secure_storage` WebAssembly incompatibilities and still produced the JavaScript build.

### 18.6 Files

New:

- `lib/features/hire/applications/application_readiness_screen.dart`
- `lib/features/hire/applications/application_wizard_screen.dart`
- `test/features/hire/applications/application_flow_test.dart`

Changed:

- `lib/features/hire/applications/data/applications_repository.dart` (`LiveApplicationsRepository`, draft, and submission)
- `lib/features/hire/applications/data/mock_applications_repository.dart`
- `lib/features/hire/applications/models/candidate_application.dart`
- `lib/features/hire/applications/applications_controller.dart`
- `lib/features/hire/applications/candidate_applications_screen.dart`
- `lib/features/hire/applications/candidate_application_detail_screen.dart`
- `lib/features/hire/applications/widgets/application_list_tile.dart`
- `lib/features/hire/hire_job_detail_screen.dart`
- `lib/app/hire_candidate_app.dart`
- Application and root-tab tests
- API register, screen inventory, architecture section 5.3, roadmap Stage 4, and this report

Removed: `lib/features/hire/jobs/apply_later_screen.dart`. Apply Now opens readiness.

`lib/main.dart` was not edited.

### 18.7 Known remaining issues

Section 16.8 items that were not part of Stage 4 are unchanged: format drift, the unused test import, the `flutter pub get` symlink exit, Wasm web storage, debug signing, and the shared Android launcher label `ournear_mobile`. Real application submission, history, timeline events beyond stored timestamps, profile editing, and resume upload are not connected.

### 18.8 Website isolation

No command was run against `D:\Ournear Hub\Ournear Hire`. That drive was not opened. No Laravel route, model, database, or production configuration was read or written.

### 18.9 Next stage

Stage 5 â€” profile and documents. It replaces the candidate Profile tab with the profile and document screens. It was not started.

## 19. Stage 5 â€” Candidate profile, professional profile, editing, and documents

Stage 5 was completed later on 2026-10-14 in `C:\Ournear Hub\ournear_mobile`. `lib/main.dart` still launches `OurNearApp`.

### 19.1 Verdict

PASS for the Stage 5 UI and repository boundary.

The candidate Profile tab is a professional identity overview. A separate read-only professional profile and separate edit forms exist. Hire Resume and My Resume are separate. Live profile and document methods throw before any HTTP request, so a save or upload is never reported as persisted. Mock edits stay in memory, set `persisted` to false, and the UI says nothing was saved on OurNear. Profile read, profile write, photo upload, Hire Resume generation, My Resume upload, and application resume snapshots remain open gaps.

### 19.2 What the candidate shell does now

| Surface | Behaviour |
| --- | --- |
| Profile root | Signed-out card keeps â€œSign in to OurNearâ€, Sign in, and Notifications. Signed-in view shows the account name and username from `AuthUser`, a compact teal banner, and an honest no-photo state. Professional headline, summary, skills, experience, and education appear only from the profile repository. Memory is absent. Sign out still calls `auth.logout()` |
| Completion | No percentage. Copy says completion cannot be calculated because no required-field list was provided |
| Professional profile | Read-only sections with empty states. Edit is available only when the candidate is authenticated. Private contact details and resume files are not shown. Recruiter visibility is not claimed |
| Editing | Separate forms for personal information, summary, experience, education, skills, qualifications, certifications, and job preferences. Cancel confirms before discarding a dirty form. â€œSaved on OurNear.â€ appears only when `ProfileSnapshot.persisted` is true |
| Live profile | `LiveProfileRepository.fetchProfile` and `save` throw unavailable and do not import `http`. The overview says the professional profile is not connected |
| Mock profile | Only when `MockProfileRepository` is injected. Local validation rejects an empty summary, location, skill, or experience and does not replace stored data. The banner says the demo is not a server record |
| Hire Resume | Preview text only when a repository supplies it. Otherwise the screen says generation is not connected and no resume was generated |
| My Resume | One replaceable file name in the mock. The live replace throws and says no file was uploaded. No file-type or size policy is invented. Preview does not open a file URL |
| Preferences | An unavailable screen. Full settings stay in Stage 6 |
| Wizard | The Stage 4 limitation sentences stay. `ProfileReadinessNote` adds a demo headline or demo file name only when a mock repository is in scope, and says those facts are not saved and will not be attached |

Account identity stays on `AuthUser`. Professional attributes stay on `CandidateProfile`. The candidate shell provides `LiveProfileRepository` and `LiveDocumentsRepository` unless a test injects a mock.

### 19.3 Confirmed client API use

No new HTTP call was added. The verified client calls remain the Stage 3 set: `GET /api/v1/jobs`, `GET /api/v1/jobs/{slug}`, `POST /api/v1/auth/login`, `GET /api/v1/account`, and `POST /api/v1/auth/logout`.

`GET /api/v1/account` still maps only to `AuthUser` (`name`, `username`, `role`, `email_verified`, `must_change_password`). It is not treated as the professional profile.

### 19.4 Gaps that stay open

| ID | Stage 5 handling |
| --- | --- |
| GAP-PROFILE-READ | Live fetch throws unavailable and does not call HTTP |
| GAP-PROFILE-WRITE | Live save throws unavailable. The UI says nothing was saved. No completion percentage |
| GAP-PROFILE-PHOTO | Overview says photo upload is not connected |
| GAP-RESUME-GENERATED | No generated file and no invented preview |
| GAP-RESUME-UPLOAD | Live replace throws. No file policy and no private file URL |
| GAP-RESUME-SNAPSHOT | Review still says the resume is not attached |
| GAP-AUTH-REGISTER | Still absent |
| GAP-AUTH-RECOVERY | Still absent |
| GAP-APP-LIST, GAP-APP-DETAIL, GAP-APP-STATUS, GAP-APP-SUBMIT | Unchanged from section 18 |

### 19.5 Commands

Commands below ran after the final `ProfileReadinessNote` edit. Working directory: `C:\Ournear Hub\ournear_mobile`.

| Command | Exit | Result |
| --- | --- | --- |
| `flutter test` | 0 | `+85: All tests passed!` Wall time about 74 seconds. Ended 2026-10-14T17:41:27Z |
| `flutter analyze` | 1 | 1 issue: unused import in `test/core/widgets/design_system_components_test.dart:5`. Same warning as Stage 1. Ran in 25.9 seconds. Ended 2026-10-14T17:38:07Z |
| `flutter build web` | 0 | `Built build\web`. Compile time 155.7 seconds. Ended 2026-10-14T17:42:21Z |
| `flutter build apk --debug` | 0 | `Built build\app\outputs\flutter-apk\app-debug.apk`. Gradle 63.7 seconds. File length 171,355,249 bytes. Not installed. Ended 2026-10-14T17:41:10Z |

The profile flow file has 11 tests and is included in the `+85` run. The web build printed the existing `flutter_secure_storage` 9.2.4 WebAssembly incompatibilities and still produced the JavaScript build.

### 19.6 Files

New under `lib/features/hire/profile/`:

- `models/candidate_profile.dart`
- `data/profile_repository.dart`
- `data/mock_profile_repository.dart`
- `data/documents_repository.dart`
- `data/mock_documents_repository.dart`
- `candidate_profile_screen.dart`
- `professional_profile_screen.dart`
- `edit_profile_section_screen.dart`
- `documents_screen.dart`
- `document_preview_screen.dart`
- `preferences_unavailable_screen.dart`
- `profile_demo_banner.dart`
- `profile_readiness_note.dart`

New test: `test/features/hire/profile/candidate_profile_flow_test.dart`.

Changed:

- `lib/app/hire_candidate_app.dart`
- `lib/core/app_config/hire_app_config.dart`
- `lib/features/hire/applications/application_readiness_screen.dart`
- `lib/features/hire/applications/application_wizard_screen.dart`
- API register, screen inventory, architecture section 5.4, roadmap Stage 5, and this report

`lib/features/profile/profile_screen.dart` remains the unified and employer account card, with Memory on by default. The candidate tab uses `CandidateProfileScreen`.

`lib/main.dart` was not edited.

### 19.7 Known remaining issues

Section 16.8 items that were not part of Stage 5 are unchanged: format drift, the unused test import, the `flutter pub get` symlink exit, Wasm web storage, debug signing, and the shared Android launcher label `ournear_mobile`. Real profile persistence, photo upload, Hire Resume generation, My Resume upload, and application resume snapshots are not connected. Registration, password recovery, messages, notifications, and full settings were not built.

### 19.8 Website isolation

No command was run against `D:\Ournear Hub\Ournear Hire`. That drive was not opened. No Laravel route, model, database, or production configuration was read or written.

### 19.9 Next stage

Stage 6 â€” messages, notifications, and settings. It was not started.

## 20. Stage 6 â€” Messages, notifications, and settings

Stage 6 was completed later on 2026-10-14 in `C:\Ournear Hub\ournear_mobile`. `lib/main.dart` still launches `OurNearApp`.

### 20.1 Verdict

PASS for the Stage 6 screens and repository boundary.

Messages, notifications, and settings are secondary destinations. The four bottom tabs remain Home, Jobs, Applications, and Profile. Live message and notification methods throw before any HTTP request. An unavailable list is not shown as an empty inbox. Send, mark read, and dismiss do not report success. Mock rows appear only when a test injects a mock repository, and the screen labels them as demo.

### 20.2 What the candidate shell does now

| Surface | Behaviour |
| --- | --- |
| Messages | Home and Applications app-bar actions open the inbox. Signed out, the screen asks the candidate to sign in and does not load an inbox. Signed in, the live repository says messages are not connected. â€œYou have no messages.â€ appears only after a successful live list with no rows |
| Thread | Opens from a supplied conversation. Send is disabled. A job or application reference is shown only when that record includes it. The bottom bar is covered. A horizontal drag does not change tabs |
| Notifications | Home, Applications, and the Profile row open the list. The live repository says notifications are not connected. Mark read and dismiss say nothing was changed and do not clear unread. No push client and no new device permission |
| Mock data | `demo-msg-001` and `demo-notif-001` are in-memory only. The banner says they are not from OurNear and do not mean an employer or recruiter made contact |
| Settings | Account name and username come from `AuthUser`. Job preferences open the existing profile form. Job alerts, notification preferences, help, terms, and the privacy policy say they are not connected. No support vendor is named. Password change and account deletion have no action. Sign out calls `AuthController.logout()` and clears the shown account |
| Employer and unified shells | Message and notification snack bars remain on the employer placeholders. The unified Profile screen still snacks for Notifications and still shows Memory |

### 20.3 Confirmed client API use

No new HTTP call was added. The verified client calls remain `GET /api/v1/jobs`, `GET /api/v1/jobs/{slug}`, `POST /api/v1/auth/login`, `GET /api/v1/account`, and `POST /api/v1/auth/logout`.

### 20.4 Gaps that stay open

| ID | Stage 6 handling |
| --- | --- |
| GAP-MSG | Live inbox and thread throw unavailable and do not call HTTP. Send stays disabled |
| GAP-NOTIF | Live list, mark read, and dismiss throw unavailable. No push client |
| GAP-PREFS | Settings explain the gap. No preference toggles. Sign out uses the existing logout |
| GAP-CONTENT | Help, terms, and privacy policy say the content is not connected |
| GAP-PROFILE-READ, GAP-PROFILE-WRITE, GAP-PROFILE-PHOTO, GAP-RESUME-GENERATED, GAP-RESUME-UPLOAD, GAP-RESUME-SNAPSHOT | Unchanged from section 19 |
| GAP-APP-LIST, GAP-APP-DETAIL, GAP-APP-STATUS, GAP-APP-SUBMIT | Unchanged from section 18 |
| GAP-AUTH-REGISTER, GAP-AUTH-RECOVERY | Still absent |

### 20.5 Commands

Commands below ran after the Stage 6 screens were in place. Working directory: `C:\Ournear Hub\ournear_mobile`.

| Command | Exit | Result |
| --- | --- | --- |
| Focused communication and settings tests | 0 | `+12: All tests passed!` About 6 seconds |
| `flutter test` | 0 | `+97: All tests passed!` Wall time about 51 seconds. Ended 2026-10-14T17:54:07Z |
| `flutter analyze` | 1 | 1 issue: unused import in `test/core/widgets/design_system_components_test.dart:5`. Same warning as Stage 1. Ran in 18.6 seconds. Ended 2026-10-14T18:00:53Z |
| `flutter build web` | 0 | `Built build\web`. Compile time 103.7 seconds. Ended 2026-10-14T18:02:15Z |
| `flutter build apk --debug` | 0 | `Built build\app\outputs\flutter-apk\app-debug.apk`. Gradle 25.2 seconds. File length 171,355,249 bytes. Not installed. Ended 2026-10-14T18:01:04Z |

The web build printed the existing `flutter_secure_storage` 9.2.4 WebAssembly incompatibilities and still produced the JavaScript build.

### 20.6 Files

New under `lib/features/hire/communication/`:

- `models/candidate_conversation.dart`
- `models/candidate_notification.dart`
- `data/messages_repository.dart`
- `data/mock_messages_repository.dart`
- `data/notifications_repository.dart`
- `data/mock_notifications_repository.dart`
- `communication_demo_banner.dart`
- `communication_signed_out.dart`
- `communication_routes.dart`
- `messages_inbox_screen.dart`
- `message_thread_screen.dart`
- `notifications_screen.dart`

New settings screen: `lib/features/hire/settings/candidate_settings_screen.dart`.

New test: `test/features/hire/communication/candidate_communication_test.dart`.

Changed:

- `lib/app/hire_candidate_app.dart`
- `lib/core/app_config/hire_app_config.dart` (candidate actions only)
- `lib/features/hire/applications/candidate_applications_screen.dart`
- `lib/features/hire/profile/candidate_profile_screen.dart`
- `lib/features/hire/profile/edit_profile_section_screen.dart` (optional initial section)
- API register, screen inventory, architecture sections 5.1 and 5.6, roadmap Stage 6, and this report

`lib/main.dart` was not edited. The employer shell still uses the later-phase snack bar for messages and notifications.

### 20.7 Known remaining issues

Section 16.8 items that were not part of Stage 6 are unchanged: format drift, the unused test import, the `flutter pub get` symlink exit, Wasm web storage, debug signing, and the shared Android launcher label `ournear_mobile`. Real messages, notifications, preference saves, help content, terms, and the privacy policy are not connected. Profile persistence, resume generation, resume upload, and application submission remain open from earlier stages. No push-notification SDK was added.

### 20.8 Website isolation

No command was run against `D:\Ournear Hub\Ournear Hire`. That drive was not opened. No Laravel route, model, database, or production configuration was read or written.

### 20.9 Next stage

Stage 7 â€” Laravel integration for contracted gaps. It was not started.

## 21. Stage 7 â€” Laravel API integration for contracted gaps

Stage 7 was completed later on 2026-10-14 in `C:\Ournear Hub\ournear_mobile`. `lib/main.dart` still launches `OurNearApp`.

### 21.1 Verdict

PARTIAL.

The contract inventory is recorded. The five verified client calls remain integrated, and fake-HTTP coverage was added for job detail, job-list HTTP 422, and logout. No other feature could be integrated. The mobile tree has no contract for those gaps, and this stage did not open the Laravel project to discover one. Live profile, document, application, message, and notification methods still throw before any HTTP request.

### 21.2 What changed in the client

| Area | Result |
| --- | --- |
| Job detail | `GET /api/v1/jobs/{slug}` is covered for a mapped success, HTTP 404 as not found, and HTTP 500 as unavailable |
| Job list | HTTP 422 stays a client error. The list still sends only `page`, `per_page`, and optional `keyword` |
| Logout | `POST /api/v1/auth/logout` is asserted with an empty body and the bearer token. The local session clears after HTTP 500 and after a thrown request |
| Blocked repositories | Applications, profile, documents, messages, and notifications still fail closed. A failed call does not become demo data or a saved record |
| New HTTP calls | None |

### 21.3 Confirmed client API use

The verified client calls remain `GET /api/v1/jobs`, `GET /api/v1/jobs/{slug}`, `POST /api/v1/auth/login`, `GET /api/v1/account`, and `POST /api/v1/auth/logout`.

`GET /api/v1/account` still maps only to `AuthUser`.

### 21.4 Gaps that stay blocked

GAP-JOBS-SEARCH beyond one keyword, GAP-JOBS-SAVED, GAP-MASTER-DATA, GAP-COMPANY as a separate resource, GAP-AUTH-REGISTER, GAP-AUTH-RECOVERY, GAP-AUTH-VERIFY, GAP-PROFILE-READ, GAP-PROFILE-WRITE, GAP-PROFILE-PHOTO, GAP-RESUME-GENERATED, GAP-RESUME-UPLOAD, GAP-RESUME-SNAPSHOT, GAP-APP-LIST, GAP-APP-DETAIL, GAP-APP-STATUS, GAP-APP-SUBMIT, GAP-MSG, GAP-NOTIF, GAP-PREFS, GAP-CONTENT, and GAP-ONBOARDING.

Each of these needs an authorized contract before the mobile client can call it. That authorization was not part of this stage.

### 21.5 Commands

Working directory: `C:\Ournear Hub\ournear_mobile`.

| Command | Exit | Result |
| --- | --- | --- |
| `flutter test test/core/api/stage7_contract_boundary_test.dart` | 0 | `+6: All tests passed!` |
| `flutter test` | 0 | `+103: All tests passed!` Wall time about 43 seconds. Ended 2026-10-14T18:50:20Z |
| `flutter analyze` | 1 | 1 issue: unused import in `test/core/widgets/design_system_components_test.dart:5`. Same warning as Stage 1. Ran in 10.2 seconds. Ended 2026-10-14T18:51:02Z |
| `flutter build web` | 0 | `Built build\web`. Compile time 150.0 seconds. Ended 2026-10-14T18:53:16Z |
| `flutter build apk --debug` | 0 | `Built build\app\outputs\flutter-apk\app-debug.apk`. Gradle 94.4 seconds. File length 189,672,153 bytes. Not installed. Ended 2026-10-14T18:52:40Z |

The web build printed the existing `flutter_secure_storage` 9.2.4 WebAssembly incompatibilities and still produced the JavaScript build.

### 21.6 Files

New test: `test/core/api/stage7_contract_boundary_test.dart`.

Changed documentation: API register section 8 and the job-detail and logout test notes, screen inventory Stage 7 note, architecture section 5.7, roadmap Stage 7 result, and this report.

No feature screen or repository gained a new URL. `lib/main.dart` was not edited.

### 21.7 Known remaining issues

Section 16.8 items that were not part of Stage 7 are unchanged: format drift, the unused test import, the `flutter pub get` symlink exit, Wasm web storage, debug signing, and the shared Android launcher label `ournear_mobile`. The blocked gaps in section 21.4 remain unavailable product behaviour.

### 21.8 Website isolation

No command was run against `D:\Ournear Hub\Ournear Hire`. That drive was not opened. No Laravel route, model, database, or production configuration was read or written.

### 21.9 Next stage

Stage 8 â€” Android and web release candidate. It was not started. It depends on a decision about the gaps that remain unavailable.

## 22. Stage 8 â€” Android and web release candidate

Stage 8 was completed later on 2026-10-14 in `C:\Ournear Hub\ournear_mobile`. `lib/main.dart` still launches `OurNearApp`. Stage 7 stays PARTIAL.

### 22.1 Verdict

PASS for the limited release-candidate scope.

This is a packaging, navigation, and disclosure check. It is not a production recruitment release. The debug APK was not installed. The web build was not deployed. No new HTTP call was added.

### 22.2 Configuration left in place

| Item | Value |
| --- | --- |
| Default entry | `lib/main.dart` runs `OurNearApp` |
| Candidate preview | `lib/preview/hire_candidate_preview_main.dart` runs `HireCandidateApp` |
| Application ID | `com.ournear.ournear_mobile` |
| Launcher label | `ournear_mobile` |
| In-app candidate title | OurNear Hire |
| Signing | Debug. The release build type in `android/app/build.gradle.kts` still uses the debug signing config |
| API base | `OURNEAR_API_BASE` defaults to empty. No host was embedded |
| Web metadata | Title OurNear Hire, manifest name OurNear Hire, `display` `standalone`, theme `#214449` |
| Permissions | No new permission. INTERNET remains the existing declaration |

No source configuration was changed in this stage.

### 22.3 Checks

Widget smoke tests walked the candidate shell at 360Ã—640 and 1280Ã—800. Both kept four tabs, the not-configured jobs state, the unavailable application history, the signed-out profile without Memory, and message and notification screens that do not claim an empty inbox. Settings sign-out cleared the visible account name. No overflow exception was reported at those sizes.

A browser session of the served candidate build was not opened. Compile-time web output is not a browser runtime pass.

### 22.4 Commands

Working directory: `C:\Ournear Hub\ournear_mobile`.

| Command | Exit | Result |
| --- | --- | --- |
| `flutter test test/release/candidate_release_candidate_smoke_test.dart` | 0 | `+3: All tests passed!` About 7 seconds |
| `flutter test` | 0 | `+106: All tests passed!` Wall time about 89 seconds. Ended 2026-10-14T18:59:56Z |
| `flutter analyze` | 1 | 1 issue: unused import in `test/core/widgets/design_system_components_test.dart:5`. Same warning as Stage 1. Ran in 25.4 seconds. Ended 2026-10-14T18:59:15Z |
| `flutter build web` | 0 | Compiles `lib/main.dart`. `Built build\web`. Compile time 162.8 seconds. Ended 2026-10-14T19:01:28Z |
| `flutter build apk --debug` | 0 | `Built build\app\outputs\flutter-apk\app-debug.apk`. Gradle 80.9 seconds. File length 189,672,153 bytes. Debug signing. Not installed. Ended 2026-10-14T19:00:18Z |
| `flutter build web -t lib/preview/hire_candidate_preview_main.dart -o build/web_candidate` | 0 | Candidate preview target. Compile time 143.7 seconds. Ended 2026-10-14T19:04:16Z. Not deployed |

The web builds printed the existing `flutter_secure_storage` 9.2.4 WebAssembly incompatibilities and still produced JavaScript builds.

### 22.5 Files

New test: `test/release/candidate_release_candidate_smoke_test.dart`.

New report: `docs/mobile/OURNEAR_HIRE_STAGE_8_RELEASE_CANDIDATE.md`.

Documentation notes were added to the API register, screen inventory, architecture section 5.8, roadmap Stage 8, and this report.

`lib/main.dart` was not edited.

### 22.6 Website isolation

No command was run against `D:\Ournear Hub\Ournear Hire`. That drive was not opened.

### 22.7 Next decision

The roadmap ends at Stage 8. The next decision is whether to authorize the blocked API contracts listed in section 21.4 before any production release. Stage 9 added account-access UI only. Backend registration and recovery contracts were not started.

## 23. Stage 9 â€” Onboarding and account access UI

Stage 9 was completed on 2026-10-15 in `C:\Ournear Hub\ournear_mobile`. `lib/main.dart` still launches `OurNearApp`. Stage 7 stays PARTIAL. Stage 8 limited RC verdict is unchanged.

### 23.1 Verdict

PASS for the mobile-only account-access UI scope.

Registration and password recovery are not operational end to end. No new HTTP call was added.

### 23.2 Behaviour

| Area | Record |
| --- | --- |
| Onboarding | Three pages for unauthenticated candidate entry after bootstrap. Skip or complete stores `ournear_hire_candidate_onboarding_complete` locally. Signed-in users skip onboarding |
| Registration | Local validation only. `LiveAccountAccessRepository.register` throws before HTTP |
| Password recovery | Email validation only. `requestPasswordRecovery` throws before HTTP. Copy states no email was sent |
| Navigation | Login links to registration and recovery. Four bottom tabs unchanged |
| Verified HTTP | Unchanged five-call set from Stage 7 |

### 23.3 Commands

Working directory: `C:\Ournear Hub\ournear_mobile`.

| Command | Exit | Result |
| --- | --- | --- |
| `flutter test test/features/auth/candidate_account_access_test.dart` | 0 | `+6: All tests passed!` About 4 seconds |
| `flutter test` | 0 | `+112: All tests passed!` About 60 seconds |
| `flutter analyze` | 1 | 1 pre-existing issue: unused import in `test/core/widgets/design_system_components_test.dart:5`. Ran in 9.3 seconds |
| `flutter build web` | 0 | Compiles `lib/main.dart`. `Built build\web`. Compile time 104.4 seconds. Wasm dry run reported `flutter_secure_storage` 9.2.4 incompatibilities; JavaScript build succeeded |
| `flutter build apk --debug` | 0 | `Built build\app\outputs\flutter-apk\app-debug.apk`. Gradle 33.6 seconds. File length 189,672,153 bytes. Not installed |

### 23.4 Files

New: `lib/core/onboarding/onboarding_preferences.dart`, `lib/features/auth/data/account_access_repository.dart`, `lib/features/auth/onboarding/candidate_onboarding_screen.dart`, `lib/features/auth/registration_screen.dart`, `lib/features/auth/password_recovery_screen.dart`, `test/helpers/hire_candidate_test_storage.dart`, `test/features/auth/candidate_account_access_test.dart`, `docs/mobile/OURNEAR_HIRE_STAGE_9_ONBOARDING_ACCOUNT_ACCESS.md`.

Modified: `lib/app/hire_app_scaffold.dart`, `lib/features/auth/login_screen.dart`, `lib/app/hire_candidate_app.dart`, candidate tests using onboarding storage helper, mobile documentation pack.

`lib/main.dart` was not edited.

### 23.5 Website isolation

No command was run against `D:\Ournear Hub\Ournear Hire`. That drive was not opened.

### 23.6 Next decision

Wire GAP-PROFILE-READ/WRITE and GAP-APP-SUBMIT on Laravel before expanding live HTTP in the mobile client. Next mobile-only work could be offline account display caching (explicit product decision) or employer shell parity checks.

## 25. Stages 11â€“13 â€” Session gate, profile UX, job discovery

Completed 2026-10-15 in `C:\Ournear Hub\ournear_mobile`. `lib/main.dart` unchanged. Stage 7 remains PARTIAL.

| Stage | Verdict | Summary |
| --- | --- | --- |
| 11 | PASS | Branded session gate, bootstrap retry, token kept on recoverable verify failure |
| 12 | PASS | Profile tab account vs professional labelling; guided setup preserved |
| 13 | PASS | Search debounce; readiness honesty; no new HTTP |

### Commands (final)

| Command | Exit | Result |
| --- | --- | --- |
| `flutter test` | 0 | +133 all passed (~29s wall in last run) |
| `flutter analyze` | 0 | No issues (unused import in design_system test fixed) |
| `flutter build web` | 0 | `build\web` ~146s |
| `flutter build web -t lib/preview/hire_candidate_preview_main.dart -o build/web_candidate` | 0 | ~154s |
| `flutter build apk --debug` | 0 | `app-debug.apk` ~48s Gradle |

Wasm dry-run warnings for `flutter_secure_storage` may still appear on web builds. APK not installed. No browser/device manual pass recorded.

### API

Still exactly five verified HTTP operations. Stages 11â€“13 added none.

### Isolation

`D:\Ournear Hub\Ournear Hire` not accessed.

## 24. Stage 10 â€” Guided candidate profile setup UI

Stage 10 was completed on 2026-10-15 in `C:\Ournear Hub\ournear_mobile`. `lib/main.dart` still launches `OurNearApp`. Stage 7 stays PARTIAL.

### 24.1 Verdict

PASS for guided profile setup UI. No new HTTP call was added.

### 24.2 Commands

| Command | Exit | Result |
| --- | --- | --- |
| `flutter test test/features/hire/profile/guided_profile_setup_test.dart` | 0 | +9. About 8 seconds |
| `flutter test` | 0 | +121. About 104 seconds |
| `flutter analyze` | 1 | 1 pre-existing unused import in `test/core/widgets/design_system_components_test.dart:5`. About 16 seconds |
| `flutter build web` | 0 | `Built build\web`. About 174 seconds. Wasm dry-run warnings for `flutter_secure_storage` |
| `flutter build apk --debug` | 0 | Debug APK built. See Gradle timing in build log |

APK was not installed. Web was not deployed.

### 24.3 Files

New: `lib/features/hire/profile/profile_setup/*`, `test/features/hire/profile/guided_profile_setup_test.dart`, `docs/mobile/OURNEAR_HIRE_STAGE_10_GUIDED_PROFILE_SETUP.md`.

Modified: `candidate_profile_screen.dart`, `candidate_onboarding_screen.dart`, `edit_profile_section_screen.dart`, mobile documentation pack.

### 24.4 Website isolation

No access to `D:\Ournear Hub\Ournear Hire`.

