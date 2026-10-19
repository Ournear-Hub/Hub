# API and routes

The primary product surface is server-rendered Inertia over web middleware. A growing **`/api/v1`** JSON layer supports mobile and external consumers; it reuses the same Laravel services and database settings as the website.

## REST API (`routes/api.php`)

Base path: `/api/v1` (prefixed by the application URL). Unless noted, endpoints are **public** and do not require authentication.

| Method | URI | Name | Auth | Notes |
| --- | --- | --- | --- | --- |
| GET | `/site/contact` | `api.v1.site.contact` | None | Public Contact Us fields from `website_settings` |
| GET | `/jobs` | `api.v1.jobs.index` | None | Paginated public job cards |
| GET | `/jobs/{job}` | `api.v1.jobs.show` | None | Job slug |
| POST | `/auth/login` | `api.v1.auth.login` | None | Issues Sanctum token (`throttle:account-login`) |
| GET | `/account` | `api.v1.account.show` | Sanctum + `api.account` | Candidate account payload (see below) |
| POST | `/auth/logout` | `api.v1.auth.logout` | Sanctum + `api.account` | Revokes current token |
| GET/PATCH | `/account/profile/personal` | `api.v1.account.profile.personal.*` | Sanctum + candidate | Personal profile |
| GET/PATCH | `/account/profile/professional` | `api.v1.account.profile.professional.*` | Sanctum + candidate | Professional profile |
| POST/DELETE | `/account/profile/professional/skills` … | `api.v1.account.profile.professional.skills.*` | Sanctum + candidate | Skills attach/detach |
| POST/PATCH/DELETE | `/account/profile/professional/employment-histories` … | `api.v1.account.profile.professional.employment-histories.*` | Sanctum + candidate | Employment history |
| GET/POST/PATCH/DELETE | `/account/resumes` … | `api.v1.account.resumes.*` | Sanctum + candidate | Resume library + builder |
| GET/POST/DELETE | `/account/saved-jobs` … | `api.v1.account.saved-jobs.*` | Sanctum + candidate | Saved jobs |
| GET | `/account/applications` … | `api.v1.account.applications.*` | Sanctum + candidate | Application list/detail/resume |
| GET/POST | `/account/verification/email` … | `api.v1.account.verification.email.*` | Sanctum + candidate | Email verification status/resend |
| POST | `/jobs/{slug}/apply` | `api.v1.jobs.apply` | Sanctum + candidate | Submit application (`throttle:job-applications`) |

**Middleware:** `api.account` → account guard user with `account:mobile` ability. `api.candidate` → candidate role only. Public job routes use `sanctum.optional` so an optional `Authorization: Bearer` token adds `is_saved` for candidates without requiring auth.

**JSON envelope:** Successful reads return `{ "data": … }`. Mutations often include `message` plus `data`. Validation failures use **422** with Laravel `errors` object. Auth failures **401**; wrong role **403**.

### Authentication

#### `POST /api/v1/auth/login`

**Auth:** None (`throttle:account-login`).

**Body (JSON):**

| Field | Rules |
| --- | --- |
| `login` | Required — email or username |
| `password` | Required |
| `device_name` | Optional — Sanctum token label (default `ournear-mobile`) |

**Success (200):**

```json
{
  "token": "plain-text-token",
  "token_type": "Bearer",
  "expires_at": "2026-10-15T12:00:00+00:00",
  "user": { "username": "jane", "email": "jane@example.com", "role": "candidate", "email_verified": true, "ournear_id": "ON-HIR-CAN-001", "profile_completion": 42 }
}
```

Candidate `user` matches **`GET /account`** `data` (via `MobileCandidatePresenter`). Non-candidate accounts receive a smaller user object (no `profile_completion`).

**Errors:** **422** invalid credentials (`login` / `password` messages from `AccountCredentialService`).

#### `GET /api/v1/account`

**Auth:** `Authorization: Bearer {token}` with `account:mobile`.

**Success (200):** Candidate `data` includes `name`, `username`, `email`, `role`, `mobile`, `location`, `bio`, `professional_headline`, `preferred_language`, `photo_url`, `ournear_id`, `profile_completion`, `completion_items`, `email_verified`, `mobile_verified`, `must_change_password`. Passwords and internal admin fields are never returned.

#### `POST /api/v1/auth/logout`

**Auth:** Bearer token. Deletes the current token. **200** `{ "message": "Logged out." }`.

### Public jobs (optional saved state)

#### `GET /api/v1/jobs` / `GET /api/v1/jobs/{slug}`

**Auth:** None. Optional bearer via `sanctum.optional`.

When the bearer identifies a **candidate**, each job payload includes **`is_saved`** (boolean). Guests and non-candidates do not receive `is_saved` (field omitted). Job visibility rules match the public website (`Job::isPubliclyVisible()`).

### Candidate profile

Uses the same form requests and services as `/account/profile` (`UpdateProfileRequest`, `UpdateProfessionalProfileRequest`, skill/history requests). **Functional area** and **experience notes** are not required for mobile updates; omitted fields on PATCH are not cleared.

| Method | Path | Notes |
| --- | --- | --- |
| GET | `/account/profile/personal` | Personal fields + `qualification` |
| PATCH/PUT | `/account/profile/personal` | Same validation as web; multipart supported for `photo` / `remove_photo` |
| GET | `/account/profile/professional` | `CandidateProfile::toProfessionalArray()` minus `public_share` |
| PATCH/PUT | `/account/profile/professional` | Partial updates allowed |
| POST | `/account/profile/professional/skills` | Body: `{ "name": "Scaffolding" }` → **201** with updated professional payload |
| DELETE | `/account/profile/professional/skills/{skill}` | Detach skill |
| POST | `/account/profile/professional/employment-histories` | Same rules as web store |
| PATCH/PUT | `/account/profile/professional/employment-histories/{history}` | Update row owned by profile |
| DELETE | `/account/profile/professional/employment-histories/{history}` | Delete row owned by profile |

**Ownership:** All routes scoped to the authenticated candidate’s profile. **403** if token is not a candidate (`api.candidate`).

### Unified identity (website + mobile)

- **One `User` record** backs website (`auth:account`) and mobile (`Sanctum` + `account:mobile`). There is no separate Flutter identity store.
- **Personal** fields (`name`, `username`, `email`, `mobile`, `photo`, `preferred_language`, verification flags) are updated via `/account/profile/personal` (web) or `PATCH /api/v1/account/profile/personal` (mobile).
- **Professional** candidate data (summary, skills, employment, resumes, applications) stays on `CandidateProfile` and related tables; mobile uses `/account/profile/professional` and resume/application routes above.
- **Employer / recruiter / admin** capabilities are not granted by login alone; role and permission checks match the web guards.
- Responses never include `password`, password hashes, reset tokens, or other secrets.

**Public staffing requirements** are accepted on the website only: `POST /for-employers` with `from_staffing=1` (guest-accessible, throttled). There is no mobile JSON endpoint for staffing intake at this time.

### Candidate resumes

| Method | Path | Notes |
| --- | --- | --- |
| GET | `/account/resumes` | Library list + metadata |
| GET | `/account/resumes/builder` | Resume builder snapshot (`ResumeBuilderService`) |
| POST | `/account/resumes` | Multipart upload (`throttle:account-files`) |
| PATCH | `/account/resumes/{resume}` | Metadata update |
| POST | `/account/resumes/{resume}/replace` | Replace file (`throttle:account-files`) |
| POST | `/account/resumes/{resume}/primary` | Set primary resume |
| DELETE | `/account/resumes/{resume}` | Delete when product allows |
| GET | `/account/resumes/{resume}/download` | Authorized file download |
| GET | `/account/resumes/{resume}/view` | Authorized inline view |

**Ownership:** Resume IDs must belong to the authenticated user. Other users’ resumes → **403/404** per policy.

### Saved jobs

| Method | Path | Notes |
| --- | --- | --- |
| GET | `/account/saved-jobs?status=all\|open\|closed` | Paginated saved job cards |
| POST | `/account/saved-jobs/{slug}` | **201** `{ "data": { "job_id", "job_slug", "is_saved": true } }` — idempotent save (no duplicate rows) |
| DELETE | `/account/saved-jobs/{slug}` | **200** `{ "is_saved": false }` — idempotent unsave |

### Applications

| Method | Path | Notes |
| --- | --- | --- |
| GET | `/account/applications` | Candidate’s applications (paginated) |
| GET | `/account/applications/{application}` | Detail + permitted timeline/status labels |
| GET | `/account/applications/{application}/resume` | Download application resume when authorized |

**Ownership:** Application must belong to the authenticated candidate. Cross-user access → **403**.

### Email verification

| Method | Path | Notes |
| --- | --- | --- |
| GET | `/account/verification/email` | `{ "email", "email_verified", "verified_at" }` |
| POST | `/account/verification/email/resend` | Sends **`VerifyAccountEmailNotification`** (`throttle:6,1`). Does not mark verified from the client. |

Already-verified resend → **200** with message that email is verified.

### Job application submission

#### `POST /api/v1/jobs/{slug}/apply`

**Auth:** Candidate bearer. **Throttle:** `job-applications`.

**Body:** No trusted `email_verified` flag — verification is read from `users.email_verified_at` only.

**Success (201):**

```json
{
  "message": "Application submitted.",
  "data": {
    "application_id": 1,
    "business_id": "ONT-A-000001",
    "job_id": 10,
    "job_slug": "site-engineer",
    "status": "In review"
  }
}
```

**Unverified candidate (403):**

```json
{
  "message": "Verify your email to apply for jobs.",
  "error_code": "email_verification_required",
  "errors": { "email": ["Verify your email to apply for jobs. …"] }
}
```

Uses **`ApplicationSubmissionService::submitFromProfile`** — same path as web **`POST /jobs/{job}/apply`**. Duplicate applications, closed jobs, and profile eligibility failures → **422** (or **403** when service surfaces email verification).

### Public web (Inertia) — homepage showcase props

**`GET /`** (web, not JSON API) includes:

- **`homeGlanceStats`** — four manual admin counters from **Admin → Website Content → Homepage Statistics** (`registered_companies`, `talent_pool_companies`, `staffing_companies`, `registered_users`); formatted for display; never derived from live DB counts.
- **`homeShowcaseCompanies`** — active companies assigned to **Home Page Companies** (`name`, `logo_url` only; non-clickable on the page).

**`GET /talent-pool`** and **`GET /staffing`** include **`talentPoolShowcaseCompanies`** / **`staffingShowcaseCompanies`** with the same public shape for their respective assignments.

### `GET /api/v1/site/contact`

Returns persisted public contact details (same source as `/contact`, `/account/contact`, and **Admin → Settings → Website → Contact Us page**).

**Example (200):**

```json
{
  "data": {
    "office_address": "Building, City",
    "enquiry_email": "hello@example.com",
    "contact_phone": "+91 80 0000 0000",
    "whatsapp_number": "+91 98765 43210"
  }
}
```

Optional fields are JSON `null` when unset. The response never includes SMTP credentials, notification recipient lists, or other admin-only configuration.

**Errors:** Standard Laravel JSON error envelopes for server failures (5xx). No authentication is required for this route.

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
