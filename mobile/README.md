# OurNear Mobile

Unified Flutter client for the OurNear platform (Home, Vibes, Drops, Memory, Hire, Profile).

## Run

```bash
cd ournear_mobile
flutter pub get
flutter run
```

Optional Laravel web origin (opens real `/jobs` and `/hire` in the browser — no fabricated listings):

```bash
flutter run --dart-define=OURNEAR_WEB_BASE=http://127.0.0.1:8000
```

Public jobs JSON API (Laravel `/api/v1/jobs`):

```bash
flutter run --dart-define=OURNEAR_API_BASE=http://127.0.0.1:8000
```

Use `http://10.0.2.2:8000` from the Android emulator when Laravel runs on your host machine.

## Reference assets

Downloaded UI references live under `D:\Ournear Hub\References\` (Social Network UI, HireMe). This app does not embed Firebase/Supabase from those templates.

## Backend

Source of truth: `D:\Ournear Hub\Ournear Hire` (Laravel). Mobile foundation does not modify backend contracts.
