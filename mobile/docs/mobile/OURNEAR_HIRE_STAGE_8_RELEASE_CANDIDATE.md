# OurNear Hire — Stage 8 release-candidate limitations

| Field | Value |
| --- | --- |
| Document ID | RC-CANDIDATE-STAGE-8 |
| Date | 2026-10-15 |
| Scope | Limited release candidate for UI, packaging, navigation, and compile-time validation |
| Classification | Not the fully integrated production recruitment app |

This candidate is **not** production-signed, Play Store-ready, deployed, or verified on a physical device. A successful debug APK or web compile does not mean a candidate can submit an application, save a profile, upload a resume, or exchange messages.

## What this candidate includes

The candidate preview entry is `lib/preview/hire_candidate_preview_main.dart`. It launches `HireCandidateApp`. `lib/main.dart` still launches the unified `OurNearApp` and was not switched.

The candidate shell has four tabs: Home, Jobs, Applications, Profile. Messages, notifications, and settings are secondary screens.

Public job list and job detail can call the verified jobs endpoints when `OURNEAR_API_BASE` is set at build time with `--dart-define`. When it is empty, the app shows the existing not-configured state and does not invent jobs. This stage did not embed an API host, token, or password.

## Applications

- Live application list is unavailable.
- Live application details are unavailable.
- Live status history is unavailable.
- Live application submission is unavailable. The wizard does not report that an application was sent.
- Application resume snapshots are unavailable.
- Demo application ids appear only when a test injects `MockApplicationsRepository`.

## Candidate profile and documents

- Live professional profile read and write are unavailable.
- Profile photo upload is unavailable.
- Hire Resume generation is unavailable.
- My Resume upload and replacement are unavailable.
- Server-backed profile preferences are unavailable.
- A mock profile edit is in-memory demo state and is not saved on OurNear.
- `GET /api/v1/account` maps to `AuthUser` only.

## Messages and notifications

- Live messages and conversations are unavailable.
- Message sending is unavailable and is not reported as successful.
- Live notification retrieval is unavailable.
- Notification delivery, read, and dismiss operations are unavailable and are not reported as successful.
- No push client and no extra device permission were added.

## Account and content

- Registration, password recovery, and onboarding UI were added in Stage 9. Submit paths for registration and recovery are still not connected. See `OURNEAR_HIRE_STAGE_9_ONBOARDING_ACCOUNT_ACCESS.md`.
- Help, terms, and the privacy policy are not connected.
- Saved jobs, the industry catalogue, separate search fields, and a company resource remain unavailable.

## Release limitations

| Item | Stage 8 fact |
| --- | --- |
| Android application ID | `com.ournear.ournear_mobile`. Unchanged |
| Android launcher label | `ournear_mobile`. Unchanged. The in-app candidate title is OurNear Hire |
| Signing | Debug. `android/app/build.gradle.kts` assigns the debug signing config to the release build type. This stage did not create a release keystore |
| INTERNET permission | Declared in the main manifest. Debug and profile manifests also declare it for the Flutter tool. No new permission was added |
| API configuration | `OURNEAR_API_BASE` and `OURNEAR_WEB_BASE` default to empty. No production URL was assumed |
| Web metadata | `web/index.html` title is OurNear Hire. `web/manifest.json` name is OurNear Hire, `display` is `standalone`, and theme color is `#214449`. Icons were not replaced |
| Analyzer | The unused import in `test/core/widgets/design_system_components_test.dart` remains. It predates this stage |
| WebAssembly | `flutter_secure_storage` 9.2.4 still reports WebAssembly incompatibilities. The JavaScript web build is the artifact |
| Device install | Not performed |
| Browser runtime | Not completed. Widget tests covered 360×640 and 1280×800. The candidate web target compiled to `build/web_candidate`, and a local static server returned HTTP 200, but a browser session was not opened. The required `flutter build web` compiles `lib/main.dart` |
| Deployment | None |

## Verified HTTP calls

No call was added in Stage 8. The client still uses only:

- `GET /api/v1/jobs`
- `GET /api/v1/jobs/{slug}`
- `POST /api/v1/auth/login`
- `GET /api/v1/account`
- `POST /api/v1/auth/logout`
