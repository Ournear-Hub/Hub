# System architecture

## Request path

```text
HTTP
  → public/index.php
  → bootstrap/app.php (routing, middleware aliases, guest/user redirects)
  → UseAuthChannel: admin/* uses guard admin; else account
  → CaptureReferralCode (referral cookie; cookie excluded from encryption)
  → EnsureActiveUser
  → HandleInertiaRequests (shared props)
  → SecurityHeaders
  → Controller / Inertia page
  → Service
  → Model / DB
```

CSRF exception: `webhooks/whatsapp` (`bootstrap/app.php`).

## Layers

| Layer | Location | Notes |
| --- | --- | --- |
| Routes | `routes/web.php`, `account.php`, `admin.php`, `console.php` | No `routes/api.php` |
| HTTP | `app/Http/Controllers`, `Requests`, `Middleware` | Dual admin/account controllers |
| AuthZ | `app/Policies`, `PermissionName`, `AccessCatalog` | Gate::policy in `AppServiceProvider` |
| Domain | `app/Services`, `app/Enums`, `app/Support` | Prefer services over fat controllers |
| Data | `app/Models`, `database/migrations` | Canonical `User` |
| UI | `resources/js/Pages`, `Components`, `Layouts` | Inertia |
| Mail | `app/Mail` | `MessageSending` listener sets company From/Reply-To |
| Console | `app/Console/Commands/SendRecruitmentReminders.php` | Hourly schedule |

## What does not exist

- `app/Jobs` — [UNUSED]
- Domain `app/Events` / `Listeners` besides mail `MessageSending`
- REST JSON API for mobile
- Repository classes

## Background work

- **Scheduler:** `Schedule::command('recruitment:send-reminders')->hourly()` in `routes/console.php`. Command runs interview/task reminders and `RecruitmentAutomationProcessor::processDue()`.
- **Queue:** `QUEUE_CONNECTION` default `database` in config; composer `dev` script runs `queue:listen`. Application code generally dispatches notifications after commit, not queued Job classes. Tests force `sync`.

## File storage

Disks in `config/filesystems.php`: `local` (private), `resumes`, `candidate_files`, `employer_files`, `freelancer_files`, `public`. Downloads go through authorized controllers, not public URLs.

## Frontend/backend contract

Controllers return `Inertia::render`. Shared auth/flash via `HandleInertiaRequests`. No separate API client except axios in package.json (not a first-class API layer).

## Health

Laravel health route `/up` from `bootstrap/app.php`.
