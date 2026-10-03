# Project overview

## Purpose

**OurNear** is the platform. **OurNear Hire** is the recruitment/hiring module; **OurNear Jobs** is the public job board. Candidates apply, employers post and enquire, and internal staff run ATS, staffing, finance, CRM, marketing attribution, and website CMS.

## Product scope (implemented)

- Public marketing site, jobs listing, company directory, Hire/Talent Pool/Staffing pages, freelancer intake, contact/employer enquiries, SEO robots/sitemap, public candidate/company profiles (share-gated).
- Candidate account: profile, professional data, files, applications, notifications.
- Employer account: company jobs (eligibility-gated), limited applicant views, documents.
- Admin/staff: jobs, candidates, applications, screening, interviews, submissions, placements, employees, replacements, finance, CRM, partners, investors, campaigns, automation, CMS, security, **Hiring Command Center**.

## User types (code)

`App\Enums\UserRole`: `admin`, `candidate`, `employer`, `partner`, `investor`.

Staff operating the admin UI are `UserRole::Admin` plus a `roles` slug (`StaffRoleSlug`). Partners/investors as **account portals** are not implemented; investor/partner **admin** modules exist.

## Major workflows

1. Public apply (account candidate; guests are redirected to login for apply UI). Snapshot applications may have `user_id` null.
2. Admin screening → shortlist (human) → submit to employer → client review → placement → join → employee → optional replacement/invoice.
3. Enquiries → CRM lead/opportunity → convert to employer.
4. Campaign referral cookies → attribution.
5. Automation: event → rule → existing match service → task/notification (no auto-hire).

## Architecture

Laravel 12 + Inertia 3 + React 19. Dual session authentication. Policies + permission catalog. Services, not repositories. See [01-SYSTEM-ARCHITECTURE.md](01-SYSTEM-ARCHITECTURE.md).

## Implementation vs plans

| Area | Status |
| --- | --- |
| Candidate portal | IMPLEMENTED |
| Employer account jobs | IMPLEMENTED |
| Recruiter Command Center | IMPLEMENTED (`/admin/hiring`) |
| Stage 24 automation | IMPLEMENTED (foundation) |
| WhatsApp recruitment messaging | PLANNED / not OTP |
| Flutter / mobile app | Not in this repository |
