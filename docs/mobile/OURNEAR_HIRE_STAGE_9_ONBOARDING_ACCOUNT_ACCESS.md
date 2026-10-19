# OurNear Hire — Stage 9 onboarding and account access

| Field | Value |
| --- | --- |
| Document ID | STAGE-9-CANDIDATE-ACCOUNT-ACCESS |
| Date | 2026-10-15 |
| Scope | Mobile frontend only. No Laravel or website changes |
| Verdict | PASS for UI and navigation. Registration and recovery submit stay unavailable |

## Implemented screens

| Screen | Code | Notes |
| --- | --- | --- |
| Optional onboarding (3 pages) | `lib/features/auth/onboarding/candidate_onboarding_screen.dart` | Skip, back, next, get started, sign-in link |
| Registration | `lib/features/auth/registration_screen.dart` | Local validation; submit shows unavailable message |
| Password recovery | `lib/features/auth/password_recovery_screen.dart` | Email validation; no fake inbox success |
| Login links | `lib/features/auth/login_screen.dart` | Keys `login-to-register`, `login-to-recovery` |

Entry gating for onboarding lives in `_HireBootstrapGate` (`lib/app/hire_app_scaffold.dart`) for the candidate variant only.

## Local-only behaviour

- Onboarding completion: secure storage key `ournear_hire_candidate_onboarding_complete` via `OnboardingPreferences`.
- Account access submit: `LiveAccountAccessRepository` throws `AccountAccessException` before any HTTP call.

## Not connected (backend gaps)

- GAP-AUTH-REGISTER — no verified registration endpoint in the mobile contract inventory.
- GAP-AUTH-RECOVERY — no verified password recovery endpoint.

The UI must not be read as end-to-end account creation or email reset.

## Verified HTTP (unchanged)

- `GET /api/v1/jobs`
- `GET /api/v1/jobs/{slug}`
- `POST /api/v1/auth/login`
- `GET /api/v1/account`
- `POST /api/v1/auth/logout`

## Tests

- `test/features/auth/candidate_account_access_test.dart` — onboarding, registration, recovery, login navigation, first launch skip to jobs.
- `test/helpers/hire_candidate_test_storage.dart` — seeds onboarding complete for shell tests.

## Isolation

- `lib/main.dart` unchanged (unified `OurNearApp`).
- Website/backend workspace `D:\Ournear Hub\Ournear Hire` not touched.
- Four candidate bottom tabs unchanged.
