# Database architecture

Engine: **MySQL** for app (`.env.example`: `ournear_talent`). Tests: **SQLite memory**.

Canonical identity: `users` (`App\Models\User`). Profiles: `candidate_profiles`, `employer_profiles`, `admin_profiles`.

Jobs table Eloquent model is `Job` → `job_listings` (historical Laravel `jobs` queue table also exists from framework).

## Core recruitment

| Table / model | Purpose |
| --- | --- |
| `users` | Login, role, username, headline, whatsapp fields, registration_source, `person_number` (Hire display) |
| `candidate_profiles` | Talent pool, privacy/share, professional FKs |
| `employer_profiles` | Company, posting flags |
| `job_listings` / `Job` | Jobs, publishing_status, employer_id |
| `applications` | Snapshot apply data, status, resume_path, user_id nullable |
| `application_screenings` | Decision, stage, shortlisted_at |
| `application_interviews` | Many interviews per application |
| `employer_submissions` | Client review of a candidate for a job |
| `placements`, `placement_offers` | Offer/join |
| `employees` | Created on join |
| `replacements` | Replacement cases |
| `invoices`, `payments` | Internal billing |
| `tasks` | Polymorphic operational tasks |
| `recruitment_activities` | Timeline/audit for recruitment |
| `recruitment_automations`, `recruitment_automation_executions` | Stage 24 |

## Talent / files

`candidate_resumes`, `candidate_documents`, `candidate_employment_histories`, skills pivots, `candidate_certifications`, `candidate_languages`, `candidate_educations`, `candidate_projects`, `employer_documents`, `saved_jobs`, `relationship_reviews`, `resume_import_batches`, `resume_import_items`.

## Platform

`roles`, `permissions`, `login_logs`, `security_activities`, `notifications` (Laravel), `conversations`, `messages`, master data tables (locations, industries, …), `website_*`, `media_assets`, `social_links`, `employer_enquiries`, `contact_enquiries`, `freelancers`.

## Growth

`crm_leads`, `crm_opportunities`, `channel_partners`, `channel_partner_agreements`, `employer_attributions`, `commission_entries`, `commission_payments`, `investors`, `investment_opportunities`, `investor_interests`, `campaigns`, `referral_links`, `referrals`, `share_events`, `tags`, `saved_searches`.

## Comms identity

`verification_challenges` (mobile/whatsapp OTP), `whatsapp_outbound_messages`.

## Relationships (simplified)

```text
User 1—0..1 CandidateProfile / EmployerProfile / AdminProfile
Job N—1 EmployerProfile
Application N—1 Job; N—0..1 User
Application 1—0..1 ApplicationScreening
Application 1—N ApplicationInterview
Application 1—N EmployerSubmission → Placement → Employee
Invoice N—1 Employee/Placement
```

## Soft deletes

Not universally used. Check each model; do not assume `SoftDeletes`.

## Status fields (exact)

- Application: `ApplicationStatus`
- Screening decision: `ScreeningDecision`
- Placement: `PlacementStatus`
- Submission: `EmployerSubmissionStatus`
- Job publishing: `JobPublishingStatus`

## Indexes / FKs

See individual migrations. Stage 24 executions use short FK names (`ra_exec_*`) because MySQL identifier length.

## Secrets

Never document DB passwords. `.env.example` uses empty `DB_PASSWORD` as a local template only.
