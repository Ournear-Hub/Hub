# GAP-AUTH-REGISTER — Required contract (mobile workspace)

| Field | Value |
| --- | --- |
| Status | **IMPLEMENTED** — see `BACKEND_STAGING_COMPLETION_REPORT.md` and API register `API-AUTH-REGISTER` |
| Mobile UI | `SignupForm` / `LiveAccountAccessRepository.register` — validates locally, **no HTTP** |

## What the mobile client already collects

| Field | Widget key |
| --- | --- |
| Username | `register-username` |
| Display name | `register-name` |
| Mobile | `register-mobile` |
| Email | `register-email` |
| Password | `register-password` |
| Confirm password | `register-confirm` |
| Terms consent | `register-consent-terms` |
| Privacy consent | `register-consent-privacy` |

## Required authorization row (add to API register before implementation)

1. **Method and path** (example shape only — must match Laravel when authorized).
2. **JSON body** field names for username, name, mobile, email, password, consent flags.
3. **Success response** — token + user object (auto-login) or 201 + verification required.
4. **Errors** — HTTP 422 with Laravel `errors` map for duplicate username/email.
5. **Live server** verification note after first successful mobile call.

## Out of scope for mobile-only work

Inspecting or editing `D:\Ournear Hub\Ournear Hire` to discover or add routes. Probing `POST /api/v1/auth/register` on local Laravel returned **404** (2026-10-16) — not sufficient to implement without an authorized register row.

## After contract is supplied

Implement in `HireApiService`, `LiveAccountAccessRepository.register`, map errors to `SignupForm`, add fake-HTTP tests, then live QA with a disposable test account.
