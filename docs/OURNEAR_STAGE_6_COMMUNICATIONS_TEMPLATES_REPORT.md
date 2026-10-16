# Agent 1 — Stage 6: Centralized communications & editable message templates

**Date:** 2026-10-11  
**Workspace:** `D:\Ournear Hub\Ournear Hire`  
**Role:** Agent 1 (implementation)

---

## 1. Requirements reused (not rebuilt)

- `RecruitmentNotificationService`, `CandidateNotificationService`, `EmployerNotificationService`, and existing in-app notification classes.
- Operational email transport (`app/Mail/*`), `UsesCompanyMailIdentity`, and website settings for From/Reply-To.
- Company communication settings UI and scorecard template admin (separate concern; unchanged).
- Admin RBAC via `PermissionName` and existing Inertia admin shell.
- Queue/mail conventions (mailables remain `ShouldQueue`; no fake delivery claims).

---

## 2. New functionality

| Area | Implementation |
| --- | --- |
| **6A Registry** | `message_templates` table + `MessageTemplate` model; stable `template_key`; channel, audience, subject/body, variables JSON, active + system-required flags; duplicate keys prevented. |
| **6B Admin UI** | `/admin/message-templates` — list, search/filter, create optional templates, edit, activate/deactivate (non-system), variable catalog, JSON preview (sample data only). Nav: Communications → Message templates. |
| **6C Rendering** | `MessageTemplateRenderer` — `{{snake_case}}` placeholders only; allowlist per system key; HTML escape for email; unsupported placeholders stripped. |
| **6D Workflow wiring** | Candidate in-app bodies for application + interview lifecycle; email subject + intro line for rejected/selected/interview mailables; **employer** in-app bodies and **employer operational emails** for application received, interview response, application message, and correction request (`EmployerRecruitmentMail` + `EmployerNotificationService`, same triggers as in-app). Safe fallbacks when template inactive/missing. Employer email respects `CommunicationPreferenceService::canSend(System)`. **REG-COM-002 (2026-10-12):** candidate in-app + operational email also respect `System` opt-out for linked users; account verification exempt. |
| **6E Preview** | `POST /admin/message-templates/preview` — renders only; no mail, no DB mutations on applications. |
| **6F Audit** | `message_template_audits` on create/update/activate/deactivate. |

**Permissions:** view — `notifications.view` or `campaigns.view`/`campaigns.manage`; manage — `campaigns.manage`.

**Supported channels:** `email`, `in_app`.

**Supported variables (global allowlist for custom templates):** `candidate_name`, `job_title`, `company_name`, `application_reference`, `application_status`, `interview_when`, `platform_name`, `support_email`. System keys may use subsets defined in `SystemMessageTemplateKey`.

---

## 3. Routes and services

| Route | Name |
| --- | --- |
| `GET /admin/message-templates` | `admin.message-templates.index` |
| `POST /admin/message-templates` | `admin.message-templates.store` |
| `PUT /admin/message-templates/{messageTemplate}` | `admin.message-templates.update` |
| `POST /admin/message-templates/{messageTemplate}/active` | `admin.message-templates.active` |
| `POST /admin/message-templates/preview` | `admin.message-templates.preview` |

Services: `MessageTemplateService`, `MessageTemplateRenderer`, `MessageTemplateVariableCatalog`.

---

## 4. Files changed (summary)

**New:** enums `MessageTemplateChannel`, `MessageTemplateAudience`, `SystemMessageTemplateKey`; models `MessageTemplate`, `MessageTemplateAudit`; migration `2026_10_11_120000_create_message_templates_tables.php`; policy, requests, controller, rule, mail trait; `resources/js/Pages/Admin/MessageTemplates/Index.tsx`; `tests/Feature/MessageTemplateManagementTest.php`.

**Updated:** `CandidateNotificationService`, `EmployerNotificationService`, `SystemMessageTemplateKey`, `MessageTemplateVariableCatalog`, `MessageTemplateService`, `ApplicationRejectedMail`, `ApplicationSelectedMail`, `InterviewScheduledMail`, **`EmployerRecruitmentMail`**, **`resources/views/mail/employer-recruitment.blade.php`**, mail markdown views, `routes/admin.php`, `AppServiceProvider`, `Sidebar.tsx`, `tests/Feature/EmployerMessageTemplateTest.php`, `docs/10-NOTIFICATIONS-COMMUNICATION.md`, this report, issue register.

**Employer email system keys (20 system templates total):**

| Event | `template_key` | Variables (allowlist) |
| --- | --- | --- |
| New application | `recruitment.employer.application_received.email` | `candidate_name`, `job_title`, `company_name`, `application_reference`, `platform_name`, `support_email` |
| Interview response | `recruitment.employer.interview_candidate_responded.email` | above + `interview_response_label`, `interview_number` |
| Application message | `recruitment.employer.application_message_received.email` | above + `message_preview` |
| Correction request | `recruitment.employer.correction_request_submitted.email` | `candidate_name`, `job_title`, `application_reference`, `platform_name`, `support_email` |

Fallback: when the active DB template is missing/inactive, `MessageTemplateService::renderForApplication` uses the per-event default subject/intro passed from `EmployerNotificationService` (same strings as pre-template hardcoded copy). Email layout adds a **View application** button using the existing employer application URL (absolute). Queued via `Mail::queue` inside the same `DB::afterCommit` block as in-app notifications.

---

## 5. Automated verification (this batch)

| Command | Result |
| --- | --- |
| `php artisan test --filter=MessageTemplateManagementTest` | **PASS** — 10 tests, 34 assertions (reconfirmed `20261011T152900Z-reg-com-001-browser`) |
| `php artisan test --filter=EmployerMessageTemplateTest` | **PASS** — 8 tests, 44 assertions (employer in-app + employer email integration) |
| `php artisan test --filter=MessageTemplateManagementTest` | **PASS** — 10 tests, 34 assertions (admin list shows **20** system keys) |
| `php artisan test --filter=EmployerHiringStage22Test` | **PASS** — 6 tests (employer in-app recipients unchanged) |
| `php artisan test --filter=AccountVerificationEmailTest` | **PASS** — 6 tests (verification unaffected) |
| `pytest tests/admin/test_reg_com_001_message_templates_browser.py -m reg_com_001` | **BLOCKED** — 8 skipped (MySQL84 / port 3306); harness + `scripts/qa-message-template-probe.php` ready — report `qa-agent/artifacts/agent2/20261011T152900Z-reg-com-001-browser/` |
| `php artisan test --filter=CommunicationLifecycleTest` | **PASS** — 6 tests, 87 assertions (prior batch) |
| `php artisan test --filter=CompanyCommunicationSettingsTest` | **PASS** — 9 tests, 2280 assertions (scorecard/settings unchanged) |
| `npm run typecheck` | **PASS** |
| `npm run build` | **PASS** (Vite 7.3.6, ~86s) |

---

## 6. Remaining limitations

- SMS/WhatsApp recruitment copy is **not** wired to this registry (OTP/WhatsApp foundations unchanged).
- Email bodies still use existing Markdown layouts; templates control **subject** and **intro line**, not full HTML redesign.
- Optional custom template keys are stored but **not** auto-bound to events until product maps them.
- Employer operational emails use the same Markdown shell as other recruitment mail (subject + intro from registry; not full HTML body redesign).
- `syncSystemTemplates()` still refreshes system metadata on sync (same as Stage 6A); administrator body/subject edits persist until the next full sync run — production should run sync only on deploy/migration, not on every request.
- Browser admin QA for message templates **BLOCKED** on local MySQL (`20261011T152900Z-reg-com-001-browser`); Playwright suite added, **REG-COM-001** remains **PARTIAL** until green browser run.
- SMTP/real delivery proof remains **REG-PROD-002** (log mailer only unless configured).
- Release verdict: **`insufficient_evidence`** (unchanged).

---

## REG-COM-002 — Candidate preference delivery (2026-10-12)

### Root cause

Stage 6 wired employer operational **email** to `CommunicationPreferenceService::canSend(System)` but left candidate recruitment **in-app** and **operational email** paths always on (`docs/17-KNOWN-ISSUES-TECHNICAL-DEBT.md`).

### Fix

| Component | Behavior |
| --- | --- |
| `CandidateNotificationService::send()` / profile incomplete | Skip when `Push` opted out (in-app channel) |
| `RecruitmentNotificationService::queueToApplicationEmail()` | Skip for linked candidates when `System` opted out (email channel) |
| `VerifyAccountEmailNotification` | Unchanged (required verification) |
| Templates, fingerprints, `DB::afterCommit` (in-app) | Unchanged |

### Tests (not run)

`tests/Feature/CandidateCommunicationPreferenceDeliveryTest.php` — **NOT RUN — deferred by product owner**

---

## REG-COM-003 — Employer channel split (2026-10-12)

### Root cause

Employer operational **email** used `System` opt-out while in-app was always delivered — correct intent, but **candidate REG-COM-002** initially gated both on `System`, and the product model treats **Push** (in-app) and **System** (operational email) as separate persisted channels.

### Intended behavior

| Channel | Controls |
| --- | --- |
| `CommunicationChannel::Push` | Employer + candidate **in-app** recruitment notifications |
| `CommunicationChannel::System` | Employer + candidate operational **email** |

Email opt-out (`System` off) does **not** suppress in-app when `Push` remains on, and vice versa.

### Files

`CommunicationPreferenceService` (helpers + legacy Push normalize), `CommunicationChannel::defaultOptIn`, `EmployerNotificationService`, candidate services aligned to helpers, `Account/Settings/Communication.tsx` copy, `EmployerCommunicationPreferenceConsistencyTest.php`.

### Tests (not run)

`EmployerCommunicationPreferenceConsistencyTest` + updated `CandidateCommunicationPreferenceDeliveryTest` — **NOT RUN**

---

## REG-COM-004 — Employer account settings (2026-10-12)

### Limitation / root cause

Employer preferences were persisted and honored at delivery time (REG-COM-003) but **account settings** blocked employers: `abort_unless($user->isCandidate())` on GET and candidate-only `FormRequest::authorize()`, so employers could only change preferences via admin CRM.

### Implementation

| Area | Change |
| --- | --- |
| Authorization | `User::isAccountMember()` (candidate + employer) on GET/PUT communication settings |
| Routes | Unchanged: `account.settings.communication`, `account.settings.communication.update` |
| UI | `SettingsNav` Communication link for employers; shared `Account/Settings/Communication.tsx` |
| Persistence | Existing `CommunicationPreferenceService::ensureDefaults()` / `sync()` — no new tables |

Cross-user protection: sync always uses `$request->user()`; spoofed `user_id` in body ignored (covered in tests).

### Tests (not run)

`AccountEmployerCommunicationSelfServiceTest` (9 cases); `AccountCandidateCommunicationTest` employer line updated — **NOT RUN — deferred by product owner**

### Remaining gaps

Runtime QA, inbox/SMTP proof, and partner/investor roles (not in scope for account communication self-service).

---

## 7. Git

No commit, push, or deployment performed.
