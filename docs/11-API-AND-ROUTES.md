# API and routes

There is **no** versioned JSON API. The app is server-rendered Inertia over web middleware.

## Public (`routes/web.php`)

| Method | URI | Name | Notes |
| --- | --- | --- | --- |
| GET | `/` | `home` | |
| GET | `/jobs` | `jobs.index` | |
| GET | `/jobs/{job}` | `jobs.show` | slug |
| GET/POST | `/jobs/{job}/apply` | `jobs.apply` / `jobs.apply.store` | throttle applications |
| POST | `/jobs/{job}/share` | `jobs.share` | |
| GET | `/profiles/{user:username}` | `profiles.show` | |
| GET | `/companies` | `companies.index` | Public company directory |
| GET | `/hire` | `hire` | Public Hire marketing |
| GET | `/talent-pool` | `talent-pool` | Public share-gated people |
| GET | `/staffing` | `staffing` | Public staffing marketing |
| GET | `/companies/{employer}` | `companies.show` | |
| GET/POST | `/freelancers` | freelancer intake | throttle |
| GET/POST | `/for-employers`, `/contact` | enquiries | throttle |
| GET/POST | `/webhooks/whatsapp` | verify + callback | throttle; no CSRF |
| GET | `/up` | health | framework |
| GET | `/robots.txt`, `/sitemap.xml` | SEO | |

## Account (`routes/account.php`)

Prefix `/account` when authenticated. Guest: `/login`, `/register`, password reset. Named `account.*` for in-app pages.

Middleware stack typically `auth:account` + `account.member` + role middleware.

## Admin (`routes/admin.php`)

Prefix `/admin`. Guest login `admin.login`. Authenticated `admin.auth` + `admin` + `password.change`.

Important names: `admin.dashboard`, `admin.hiring.index`, `admin.jobs.*`, `admin.candidates.*`, `admin.candidates.imports.*`, `admin.applications.*`, `admin.screening.index`, `admin.shortlisted.index`, `admin.submissions.*`, `admin.placements.*`, `admin.employees.*`, `admin.replacements.*`, `admin.finance.*`, `admin.crm.*`, `admin.partners.*`, `admin.investors.*`, `admin.campaigns.*`, `admin.automations.*`, `admin.website-content.*`, `admin.roles.*`, `admin.users.*`.

Authorization: policies on each action, not a single `can:permission` route middleware for everything.

## Webhooks

WhatsApp only as first-class public webhook.

## Rate limiting

Named limiters in `AppServiceProvider` (job applications, OTP, WhatsApp webhook, account files, enquiries, freelancer).
