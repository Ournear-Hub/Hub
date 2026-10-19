# Stage 11 — Branded session gate (SCR-AUTH-01)

| Field | Value |
| --- | --- |
| Verdict | **PASS** |
| Date | 2026-10-15 |
| Scope | Candidate app entry only |

## Objective

Replace the plain bootstrap spinner with an OurNear Hire–branded session gate integrated with existing auth and onboarding.

## Implemented

- `CandidateSessionGate` — checking, recoverable error (network/server), shell passthrough.
- `AuthController` — single-flight bootstrap, `BootstrapFailureKind`, token retained on non-401 verify failures, `retryBootstrap()`, `dismissBootstrapFailureForPublicBrowsing()`.
- `_HireBootstrapGate` — wraps candidate flow with the gate; employer keeps simple spinner.

## Files

| Created | Modified |
| --- | --- |
| `lib/features/auth/candidate_session_gate.dart` | `lib/core/auth/auth_controller.dart`, `lib/app/hire_app_scaffold.dart` |
| `test/features/auth/candidate_session_gate_test.dart` | `test/core/auth/auth_controller_test.dart` |

## Tests (focused)

`flutter test test/features/auth/candidate_session_gate_test.dart test/core/auth/auth_controller_test.dart` — pass.

## API

No new HTTP operations. Still uses `GET /api/v1/account` on bootstrap when a token exists.

## Limitations

- Distinguishes 401 (session cleared) from other HTTP errors and transport failures; does not cache account identity offline beyond the stored token.

## Builds (final session)

See consolidated Stages 11–13 run: full suite **133** pass, `flutter analyze` **0**, web / web_candidate / debug APK **0**.
