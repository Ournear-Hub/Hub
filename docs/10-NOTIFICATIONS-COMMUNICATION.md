# Notifications and communication

## In-app admin — IMPLEMENTED

`AdminAlertService` + `LifecycleNotification` + `AdminNotificationEvent` (includes `automation_recommended`). Fingerprint de-dupe. Recipients: active admin users with `notifications.view`. UI: `Admin\NotificationsController`.

## In-app candidate — IMPLEMENTED

`CandidateNotificationService` + `CandidateNotificationEvent`. Own inbox only.

## Email — IMPLEMENTED (transport)

`app/Mail/*`, `RecruitmentNotificationService`. Company From/Reply-To from website settings via `MessageSending` listener (`UsesCompanyMailIdentity`). Local `.env.example` uses `MAIL_MAILER=log`. Do not claim SMTP delivery without a real mailer.

Stage 23B company mailbox/WhatsApp **settings UI** exists; migration `2026_09_28_120000` may be **pending** on some databases. [PARTIALLY IMPLEMENTED] until migrated.

## Internal messages — IMPLEMENTED

Admin-to-admin `Conversation` / `Message` (`MessagesController`). Not candidate chat.

## Communication preferences — IMPLEMENTED (storage)

`UserCommunicationPreference`, `CommunicationPreferenceService::canSend`. [UNUSED / UNCERTAIN] whether all recruitment mails consult `canSend` (historically not all paths).

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

`CommunicationLifecycleTest`, `AccountCandidateCommunicationTest`, `CompanyCommunicationSettingsTest`, `WhatsAppCloudFoundationTest`, `WhatsAppOtpVerificationTest`, `MobileOtpVerificationTest`.
