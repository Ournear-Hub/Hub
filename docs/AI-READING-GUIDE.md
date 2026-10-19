# AI reading guide

Goal: small task → small context. Do not load the whole repo.

## Always (any coding task)

1. [CURRENT-STATE.md](CURRENT-STATE.md)
2. [AI-READING-GUIDE.md](AI-READING-GUIDE.md) (this file)
3. [DEVELOPMENT-RULES.md](DEVELOPMENT-RULES.md)

Then search code for the feature name before adding files.

## Small UI / copy

CURRENT-STATE → [12-FRONTEND-ARCHITECTURE.md](12-FRONTEND-ARCHITECTURE.md) → the specific Page/Component.

## Candidate feature

CURRENT-STATE → [02-AUTHENTICATION-ARCHITECTURE.md](02-AUTHENTICATION-ARCHITECTURE.md) → [05-CANDIDATE-MODULE.md](05-CANDIDATE-MODULE.md) → applications/files docs if needed.

## Employer / jobs posting

CURRENT-STATE → [06-EMPLOYER-RECRUITER-MODULE.md](06-EMPLOYER-RECRUITER-MODULE.md) → [07-JOBS-MODULE.md](07-JOBS-MODULE.md) → posting eligibility service.

## Recruiter / ATS / screening

CURRENT-STATE → [03-AUTHORIZATION-ROLES-PERMISSIONS.md](03-AUTHORIZATION-ROLES-PERMISSIONS.md) → [08-APPLICATIONS-MODULE.md](08-APPLICATIONS-MODULE.md) → [19-RECRUITMENT-OPERATIONS.md](19-RECRUITMENT-OPERATIONS.md) → **do not** rewrite `ScreeningMatchService`.

## Hiring Command Center

CURRENT-STATE → [24-HIRING-COMMAND-CENTER.md](24-HIRING-COMMAND-CENTER.md) → [19-RECRUITMENT-OPERATIONS.md](19-RECRUITMENT-OPERATIONS.md). Route `admin.hiring.index`. Reuse existing queries/services; do not add a second match/shortlist engine.

## Hire identity / bulk resume import

CURRENT-STATE → [25-HIRE-IDENTITY-AND-RESUME-IMPORT.md](25-HIRE-IDENTITY-AND-RESUME-IMPORT.md) → [09-RESUME-DOCUMENTS-FILES.md](09-RESUME-DOCUMENTS-FILES.md). Keep `person_number` immutable; keep `business_id`.

## Public platform pages (Companies / Hire / Talent Pool / Staffing / Development Programme)

CURRENT-STATE → [22-PUBLIC-WEBSITE-CMS.md](22-PUBLIC-WEBSITE-CMS.md). Do not confuse `/hire` with `/admin/hiring`.

## Automation

CURRENT-STATE → [20-RECRUITMENT-AUTOMATION.md](20-RECRUITMENT-AUTOMATION.md) → [08-APPLICATIONS-MODULE.md](08-APPLICATIONS-MODULE.md).

## Admin / RBAC

CURRENT-STATE → [03-AUTHORIZATION-ROLES-PERMISSIONS.md](03-AUTHORIZATION-ROLES-PERMISSIONS.md) → `AccessCatalog`, `AuthorizationBootstrapper`, `RoleManagementService`, `UserManagementService`, policies, `routes/admin.php`. Do not add a second permission system. Tests: `AdminRbacStage25Test`.

## Authentication change

CURRENT-STATE → [02-AUTHENTICATION-ARCHITECTURE.md](02-AUTHENTICATION-ARCHITECTURE.md) → DualGuard tests. Stop if you would add a User model.

## Database change

CURRENT-STATE → [04-DATABASE-ARCHITECTURE.md](04-DATABASE-ARCHITECTURE.md) → module doc → existing migrations. Do not migrate unrelated pending files.

`users.person_number` is the immutable Hire person number. Display it with `OurNearHireIdentity`. Do not replace `business_id`.

## Files / documents

CURRENT-STATE → [09-RESUME-DOCUMENTS-FILES.md](09-RESUME-DOCUMENTS-FILES.md) → [23-SECURITY-PRIVACY.md](23-SECURITY-PRIVACY.md).

## WhatsApp / SMS / email

CURRENT-STATE → [10-NOTIFICATIONS-COMMUNICATION.md](10-NOTIFICATIONS-COMMUNICATION.md) → [14-EXTERNAL-INTEGRATIONS.md](14-EXTERNAL-INTEGRATIONS.md). OTP ≠ recruitment WhatsApp.

## CRM / partners / campaigns

[21-GROWTH-CRM-PARTNERS-INVESTORS.md](21-GROWTH-CRM-PARTNERS-INVESTORS.md).

## Public site / CMS

[22-PUBLIC-WEBSITE-CMS.md](22-PUBLIC-WEBSITE-CMS.md).

## Deploy / env

[15-DEPLOYMENT-ENVIRONMENT.md](15-DEPLOYMENT-ENVIRONMENT.md) — no secrets.

Do **not** start from `ournear_app_Hostinger_2026-09-19/`.
