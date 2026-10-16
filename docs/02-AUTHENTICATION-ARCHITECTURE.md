# Authentication architecture

**Constraint:** Do not change this without explicit product/engineering approval.

## Guards (`config/auth.php`)

| Guard | Driver | Provider | Use |
| --- | --- | --- | --- |
| `account` | session | `users` → `App\Models\User` | Default; public/account |
| `admin` | session | same | `/admin/*` |
| `web` | session | same | Laravel default leftover; routing uses account/admin |

Password broker: `users` / `password_reset_tokens`.

## Channel selection

`App\Http\Middleware\UseAuthChannel`: if path is `admin` or `admin/*`, `Auth::shouldUse('admin')`, else `account`. Intended URLs are partitioned (`App\Support\IntendedUrl`).

Guest redirect: admin paths → `admin.login`; else `login`.
Authenticated redirect: admin paths → `admin.dashboard`; else `account.home`.

## Account (candidate/employer)

| Item | Location |
| --- | --- |
| Login/register/logout | `app/Http/Controllers/Account/AuthenticatedSessionController.php`, `RegisteredUserController.php` |
| Routes | `routes/account.php` (`login`, `register`, `logout`, password reset) |
| Username | Unique; availability `register.check-username` |
| Email verification | Account verification routes |
| Middleware | `auth:account`, `account.member`, `account.verified`, `account.candidate`, `account.employer` |
| Ensure classes | `EnsureAccountMember`, `EnsureAccountEmailVerified`, `EnsureCandidate`, `EnsureEmployer`, `EnsureActiveUser` |

Account login identifier: email **or** username (covered by DualGuard tests).

## Admin

| Item | Location |
| --- | --- |
| Login | `app/Http/Controllers/Admin/Auth/AuthenticatedSessionController.php` |
| Routes | `routes/admin.php` prefix `admin`, names `admin.*` |
| Middleware | `admin.auth`, `admin`, `password.change` |
| Password change | `EnsurePasswordChanged` + Admin profile password routes |

Staff must have `UserRole::Admin`. Candidate/employer cannot use admin login (tested). Stage 25 did not merge guards or change session coexistence.

## Sessions

Two independent session guards can coexist (DualGuard tests). Logout of one must not destroy the other.

## Password reset

Separate account and admin forgot/reset controllers under each namespace. Same `password_reset_tokens` table.

## Photos

`GET /users/{user}/photo` uses `auth.either` (`AuthenticateEitherGuard`).

## Risks

- Wrong guard on a new route leaks or 403s incorrectly.
- Merging admin and account sessions would break coexistence tests.
- `EnsureActiveUser` can lock inactive users on both channels.

Tests: `tests/Feature/DualGuardAuthenticationTest.php`, `AccountAuthExperienceTest.php`, admin login tests in panel suites.
