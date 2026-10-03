# Documentation index

Master map: what exists, where it lives, where it is documented.

| Area | Document | Status | Source (start here) | Database | Routes | Tests | Dependencies |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Snapshot | [CURRENT-STATE.md](CURRENT-STATE.md) | Live | — | — | — | Cited Stage 26 suite; not re-run this docs audit | All |
| Overview | [00-PROJECT-OVERVIEW.md](00-PROJECT-OVERVIEW.md) | — | — | — | — | — | — |
| Architecture | [01-SYSTEM-ARCHITECTURE.md](01-SYSTEM-ARCHITECTURE.md) | IMPLEMENTED | `bootstrap/app.php` | — | `routes/*` | — | Auth |
| Auth | [02-AUTHENTICATION-ARCHITECTURE.md](02-AUTHENTICATION-ARCHITECTURE.md) | IMPLEMENTED | `config/auth.php`, `UseAuthChannel` | `users` | account/admin login | DualGuard | User model |
| AuthZ | [03-AUTHORIZATION-ROLES-PERMISSIONS.md](03-AUTHORIZATION-ROLES-PERMISSIONS.md) | IMPLEMENTED | `PermissionName`, `AccessCatalog` | roles/permissions | admin policies | Stage 2 + Stage 25 | Auth |
| Database | [04-DATABASE-ARCHITECTURE.md](04-DATABASE-ARCHITECTURE.md) | IMPLEMENTED | `app/Models`, migrations | all | — | RefreshDatabase | — |
| Candidate | [05-CANDIDATE-MODULE.md](05-CANDIDATE-MODULE.md) | IMPLEMENTED | Account controllers | candidate_* | `account.*` | Stage 3, 23F | Auth, files |
| Employer/Recruiter | [06-EMPLOYER-RECRUITER-MODULE.md](06-EMPLOYER-RECRUITER-MODULE.md) | IMPLEMENTED / Command Center IMPLEMENTED | EmployerJobs, HiringCommandCenter | employer_profiles | account.employer, admin.hiring | Stage 4 + 26 | Jobs |
| Jobs | [07-JOBS-MODULE.md](07-JOBS-MODULE.md) | IMPLEMENTED | `Job` | job_listings | public + admin + employer | Stage 5 | Employer |
| Applications | [08-APPLICATIONS-MODULE.md](08-APPLICATIONS-MODULE.md) | IMPLEMENTED | Pipeline, ScreeningMatch | applications | apply + admin | F3, shortlist | Jobs, files |
| Files | [09-RESUME-DOCUMENTS-FILES.md](09-RESUME-DOCUMENTS-FILES.md) | IMPLEMENTED | disks, libraries | resume/document tables | download routes | resume tests | Privacy |
| Comms | [10-NOTIFICATIONS-COMMUNICATION.md](10-NOTIFICATIONS-COMMUNICATION.md) | MIXED | Alert/OTP/WhatsApp services | notifications, challenges | webhooks, settings | OTP/WhatsApp tests | Settings |
| Routes | [11-API-AND-ROUTES.md](11-API-AND-ROUTES.md) | No JSON API | `routes/*.php` | — | all web | — | Auth |
| Frontend | [12-FRONTEND-ARCHITECTURE.md](12-FRONTEND-ARCHITECTURE.md) | IMPLEMENTED | `resources/js` | — | Inertia | tsc/build | — |
| Tests | [13-TESTING-ARCHITECTURE.md](13-TESTING-ARCHITECTURE.md) | IMPLEMENTED | `tests/` | sqlite | — | PHPUnit | — |
| Integrations | [14-EXTERNAL-INTEGRATIONS.md](14-EXTERNAL-INTEGRATIONS.md) | MIXED | config/whatsapp, mail | outbound messages | webhooks | foundation tests | Env |
| Deploy | [15-DEPLOYMENT-ENVIRONMENT.md](15-DEPLOYMENT-ENVIRONMENT.md) | — | `.env.example` | mysql | — | — | Secrets out |
| History | [16-STAGE-HISTORY.md](16-STAGE-HISTORY.md) | — | tests + migrations | — | — | Stage*Test | Stale V1.1 file |
| Debt | [17-KNOWN-ISSUES-TECHNICAL-DEBT.md](17-KNOWN-ISSUES-TECHNICAL-DEBT.md) | — | Hostinger dump, bootstrapper | — | — | — | — |
| Future | [18-FUTURE-DEVELOPMENT-GUIDE.md](18-FUTURE-DEVELOPMENT-GUIDE.md) | — | — | — | — | — | Rules |
| Ops ATS | [19-RECRUITMENT-OPERATIONS.md](19-RECRUITMENT-OPERATIONS.md) | IMPLEMENTED | Placement/Interview services | placements… | admin.* | F4–F6 | Applications |
| Automation | [20-RECRUITMENT-AUTOMATION.md](20-RECRUITMENT-AUTOMATION.md) | FOUNDATION+ | RecruitmentAutomation* | automation tables | admin.automations | Stage 24 | Match service |
| Growth | [21-GROWTH-CRM-PARTNERS-INVESTORS.md](21-GROWTH-CRM-PARTNERS-INVESTORS.md) | IMPLEMENTED | Crm/Partner controllers | crm_*, partners | admin.crm etc | Stages 7–10 | Employers |
| Public/CMS | [22-PUBLIC-WEBSITE-CMS.md](22-PUBLIC-WEBSITE-CMS.md) | IMPLEMENTED | PublicSite, PublicPlatform, WebsiteContent | website_* | public + admin CMS | PublicWebsiteTest | Settings |
| Security | [23-SECURITY-PRIVACY.md](23-SECURITY-PRIVACY.md) | IMPLEMENTED | middleware, audits | login_logs | — | security tests | Auth |
| Command Center | [24-HIRING-COMMAND-CENTER.md](24-HIRING-COMMAND-CENTER.md) | IMPLEMENTED | RecruiterHiringCommandCenterService | existing ATS tables | `admin.hiring.index` | Stage 26 | AuthZ, ops |
| Hire ID / import | [25-HIRE-IDENTITY-AND-RESUME-IMPORT.md](25-HIRE-IDENTITY-AND-RESUME-IMPORT.md) | IMPLEMENTED | OurNearHireIdentity, ResumeImportService | person_number, resume_import_* | `admin.candidates.imports.*` | HireIdentityResumeImportTest | Files, users |
| Rules | [DEVELOPMENT-RULES.md](DEVELOPMENT-RULES.md) | — | — | — | — | — | — |
| Reading | [AI-READING-GUIDE.md](AI-READING-GUIDE.md) | — | — | — | — | — | — |
| Gaps | [DOCUMENTATION-GAPS.md](DOCUMENTATION-GAPS.md) | — | — | — | — | — | — |
| Deps | [DOCUMENTATION-DEPENDENCY-MAP.md](DOCUMENTATION-DEPENDENCY-MAP.md) | — | — | — | — | — | — |

Repo root: `AGENTS.md` (OurNear Hire agent instructions). Canonical live snapshot: `CURRENT-STATE.md`. Historical: `V1.1_DEVELOPMENT_STATE.md`. Hostinger-era exports belong outside this repo (e.g. `D:\OURNEAR TALENT\Backup\`), not under the Laravel root.
