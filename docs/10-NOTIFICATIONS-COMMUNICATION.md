# Notifications and communication

## In-app admin — IMPLEMENTED

`AdminAlertService` + `LifecycleNotification` + `AdminNotificationEvent` (includes `automation_recommended`). Fingerprint de-dupe. Recipients: active admin users with `notifications.view`. UI: `Admin\NotificationsController`.

## In-app candidate — IMPLEMENTED

`CandidateNotificationService` + `CandidateNotificationEvent`. Own inbox only.

## Editable message templates — IMPLEMENTED (Stage 6)

Central registry: `message_templates`, admin UI `/admin/message-templates`, `MessageTemplateService` + `{{variable}}` renderer with allowlist. System keys under `App\Enums\SystemMessageTemplateKey` seed on migration (**20** system keys including four employer email templates). Candidate + employer recruitment in-app bodies and operational emails (candidate rejected/selected/interview + **employer** application received, interview response, application message, correction request via `EmployerRecruitmentMail`) read active templates; inactive/missing templates use safe fallbacks. Preview endpoint does not send mail. Audit: `message_template_audits`. Tests: `MessageTemplateManagementTest`, `EmployerMessageTemplateTest`.

## Email — IMPLEMENTED (transport + admin SMTP)

`app/Mail/*`, `RecruitmentNotificationService`. Company From/Reply-To from website settings via `MessageSending` listener (`UsesCompanyMailIdentity`). **Admin → Settings → Email delivery** persists encrypted SMTP settings on `website_settings` and applies them at runtime via `MailConfigurationService` (including queue workers). Diagnostics: validate, connection test, test send, verification preview. Account verification uses `VerifyAccountEmailNotification` with signed URLs from `ApplicationUrlResolver` (Website URL / APP_URL). Local `.env.example` remains bootstrap fallback when admin delivery is disabled. Do not claim inbox delivery without a real test recipient.

Report: `docs/OURNEAR_REG_PROD_002_EMAIL_MANAGEMENT_REPORT.md`.

## Internal messages — IMPLEMENTED

Admin-to-admin `Conversation` / `Message` (`MessagesController`). Not candidate chat.

## Communication preferences — IMPLEMENTED

`UserCommunicationPreference`, `CommunicationPreferenceService`.

**Semantics (REG-COM-002 / REG-COM-003):** recruitment delivery uses two persisted channels:

| Channel enum | Meaning | Default opt-in |
| --- | --- | --- |
| `push` | In-app notifications (account notification center) | yes |
| `system` | Operational recruitment **email** | yes |
| `marketing_email`, `marketing_sms`, `whatsapp` | Marketing / campaigns (not recruitment operational mail) | no |

Helpers: `canSendRecruitmentInApp()` → `Push`; `canSendRecruitmentEmail()` → `System`.

| Path | Preference gate |
| --- | --- |
| Employer in-app recruitment | `canSendRecruitmentInApp()` (`Push`) |
| Employer operational recruitment **email** | `canSendRecruitmentEmail()` (`System`) before `EmployerRecruitmentMail` queue |
| Candidate in-app recruitment | `canSendRecruitmentInApp()` (`Push`) |
| Candidate operational email | `canSendRecruitmentEmail()` (`System`) for linked `User`; guest snapshot email unchanged |
| Account verification email | **Not** gated (required security) |

Legacy accounts with `push` still `system_default` + opted out are normalized to opted-in on read (`ensureDefaults`) so in-app delivery matches intended defaults without a migration.

**Account self-service (REG-COM-004):** Candidates and employers manage the same persisted rows via `/account/settings/communication` (`SettingsController` + `UpdateAccountCommunicationPreferencesRequest`; `isAccountMember()` gate). Admin CRM `PUT admin/users/{user}/preferences` remains staff-only. Settings nav shows **Communication** for candidate and employer roles.

## Mobile OTP — FOUNDATION / IMPLEMENTED against unconfigured driver

`MobileVerificationService`, `SmsOtpGateway`, `config/otp.php`. Default driver unconfigured. Tests use `RecordingSmsOtpGateway`.

## WhatsApp — FOUNDATION + OTP IMPLEMENTED

- Cloud send: `WhatsAppMessagingService`, `WhatsAppGateway`, `config/whatsapp.php`, `whatsapp_outbound_messages`.
- Webhook: `WhatsAppWebhookController` (verify + status callback). CSRF exempt.
- OTP: authentication template only (`WhatsAppVerificationService`). **Do not** send recruitment messages through OTP templates.
- Click-to-chat company number: website settings, not Graph credentials.

`WHATSAPP_PROVIDER=unconfigured` until Meta credentials exist.

## Automation communication

Stage 24 `notify_admin` = in-app only. No SMS/WhatsApp/email from the dispatcher.

## Tests

`CommunicationLifecycleTest`, `AccountCandidateCommunicationTest`, `AccountEmployerCommunicationSelfServiceTest` (REG-COM-004 — **NOT RUN**), `CompanyCommunicationSettingsTest`, `WhatsAppCloudFoundationTest`, `WhatsAppOtpVerificationTest`, `MobileOtpVerificationTest`.
