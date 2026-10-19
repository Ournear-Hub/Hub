# OurNear Hire Candidate — API configuration for live integration

| Field | Value |
| --- | --- |
| Document ID | CANDIDATE-STAGING-CONFIG |
| Workspace | `C:\Ournear Hub\ournear_mobile` |
| Preview entry | `lib/preview/hire_candidate_preview_main.dart` |

## Defines

| Define | Purpose |
| --- | --- |
| `OURNEAR_API_BASE` | Laravel JSON origin (no trailing path). Example hosts: `http://127.0.0.1:8000` (desktop), `http://10.0.2.2:8000` (Android emulator → host machine) |
| `OURNEAR_WEB_BASE` | Optional public website origin for “Browse jobs on web” (`/jobs`) |

Both are compile-time `String.fromEnvironment` values in `lib/core/api/api_config.dart`. They are **empty by default** in release artifacts built without `--dart-define`.

## Why Jobs shows “OURNEAR_API_BASE is not configured”

`ApiConfig.apiBase` is a **compile-time** `String.fromEnvironment('OURNEAR_API_BASE')` (see `lib/core/api/api_config.dart`). It defaults to **empty** when Flutter is started or built without `--dart-define` / `--dart-define-from-file`.

| Symptom | Typical cause |
| --- | --- |
| Jobs / Home not-configured message | Preview started as `flutter run` or static `build/web_candidate` **without** API defines |
| Wrong host | Define points at a host where `GET /api/v1/jobs` is not Laravel JSON (e.g. port **8080** is the Flutter web UI, not the API) |
| Error / offline after defines set | Laravel not running, network, or HTTP error — not the same as “not configured” |

**You cannot fix an already running Chrome session in place.** Stop the current preview and relaunch once with defines below (Laravel on this machine was observed responding `HTTP 200` on `http://127.0.0.1:8000/api/v1/jobs` on 2026-10-16; CORS header `Access-Control-Allow-Origin: *` for web).

Committed local defines (no secrets): `config/candidate_dev_api.json`.

## Run candidate preview against a backend

```powershell
cd "C:\Ournear Hub\ournear_mobile"
flutter run -t lib/preview/hire_candidate_preview_main.dart -d chrome --web-port=8080 `
  --dart-define-from-file=config/candidate_dev_api.json
```

Or explicit defines:

```powershell
flutter run -t lib/preview/hire_candidate_preview_main.dart -d chrome --web-port=8080 `
  --dart-define=OURNEAR_API_BASE=http://127.0.0.1:8000 `
  --dart-define=OURNEAR_WEB_BASE=http://127.0.0.1:8000
```

VS Code / Cursor: run launch config **“Candidate preview (Chrome, local Laravel :8000)”** (`.vscode/launch.json`).

Replace the host with your **authorized** staging origin. Do not commit credentials or tokens.

## Build web preview with API base

```powershell
flutter build web -t lib/preview/hire_candidate_preview_main.dart -o build/web_candidate `
  --dart-define=OURNEAR_API_BASE=http://127.0.0.1:8000 `
  --dart-define=OURNEAR_WEB_BASE=http://127.0.0.1:8000
```

## Android APK for physical device (same Wi‑Fi as Laravel)

Laravel must listen on all interfaces, e.g. `php artisan serve --host=0.0.0.0 --port=8000`.

1. Copy `config/candidate_android_lan.example.json` → `config/candidate_android_lan.json`.
2. Set `OURNEAR_API_BASE` / `OURNEAR_WEB_BASE` to your PC’s LAN IP (not `127.0.0.1`).
3. Build:

```powershell
flutter build apk --release -t lib/preview/hire_candidate_preview_main.dart `
  --dart-define-from-file=config/candidate_android_lan.json
```

Artifact: `build/app/outputs/flutter-apk/app-release.apk` (release build type; signing uses debug keystore per `android/app/build.gradle.kts` until release keys are configured).

**Emulator:** use `config/candidate_android_emulator_api.json` (`10.0.2.2:8000`).

**Production/staging HTTPS:** use an authorized HTTPS base in a private JSON file — do not commit secrets or guess public URLs.

## Build debug APK with API base

```powershell
flutter build apk --debug -t lib/preview/hire_candidate_preview_main.dart `
  --dart-define=OURNEAR_API_BASE=http://10.0.2.2:8000 `
  --dart-define=OURNEAR_WEB_BASE=http://10.0.0.2:8000
```

## Live verification checklist (manual)

1. Jobs tab loads real rows from `GET /api/v1/jobs` (no mock fallback).
2. Sign in via Login tab → `POST /api/v1/auth/login`; restart app → session restores via `GET /api/v1/account`.
3. Applications tab (signed in) → `GET /api/v1/account/applications` (all pages).
4. Apply on an open job → `POST /api/v1/jobs/{slug}/apply` with `{}` body; list refreshes on return.
5. Bookmark (signed in) → `POST`/`DELETE /api/v1/account/saved-jobs/{slug}`.
6. Sign out → `POST /api/v1/auth/logout`; local session clears.

## Implementation follow-up (not executed)

Automated tests, web/APK builds, staging checks, and physical-device checks were **not executed in this pass**. Commands in this file are for the next manual stage. Do not treat a previous local probe as acceptance of this pass.

The Jobs tab now sends catalogue filters from `GET /api/v1/jobs/filters`. Profile completion, resume primary/delete, and record edit/delete require a signed-in candidate and `OURNEAR_API_BASE`.

Still not a mobile action: password change, account deletion, application status query filters, and opening a resume file download inside the app.

## Not available without new authorized contracts

See `OURNEAR_HIRE_API_INTEGRATION_REGISTER.md` section 4 for GAP-PREFS, GAP-APP-STATUS, GAP-RESUME-SNAPSHOT, and GAP-COMPANY. Registration, recovery, profile read/write, resume upload, saved-jobs list, messaging, and notifications already have client calls when `OURNEAR_API_BASE` is set.

## Secrets

Store test account credentials outside the repo. Never log bearer tokens or passwords from the app or tests.
