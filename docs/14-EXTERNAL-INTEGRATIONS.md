# External integrations

Never paste credential values.

| Integration | Purpose | Config | Status |
| --- | --- | --- | --- |
| SMTP / log mail | Transactional email | `MAIL_*`, website settings From | IMPLEMENTED transport; delivery depends on env. **Brevo:** `MAIL_MAILER=smtp`, host `smtp-relay.brevo.com`, port `587`, TLS (STARTTLS via `smtp` scheme), `MAIL_USERNAME` = SMTP login, `MAIL_PASSWORD` = **SMTP key** (not HTTP API key). Verify sender/domain in Brevo before delivery tests. |
| Meta WhatsApp Cloud | OTP + outbound message records | `config/whatsapp.php`, `WHATSAPP_*` | FOUNDATION; unconfigured by default |
| WhatsApp webhook | Delivery status | `/webhooks/whatsapp` | IMPLEMENTED (signature/token checks in controller/tests) |
| SMS OTP | Mobile verify | `config/otp.php`, `SMS_OTP_DRIVER` | FOUNDATION unconfigured |
| Cloudflare Turnstile | Bot on public forms | `TURNSTILE_*` | OPTIONAL; empty in tests |
| Analytics | Site tag | `ANALYTICS_ENABLED`, `ANALYTICS_ID` | OPTIONAL |
| Filesystem | Private uploads | disks | IMPLEMENTED local |
| Payment gateways (Stripe etc.) | — | — | **Not present.** Finance is internal invoices |

## Failure behavior

Unconfigured WhatsApp/SMS must fail honestly without marking verified (OTP tests). Webhook unsigned callbacks must not mutate status.

## Tests

`WhatsAppCloudFoundationTest`, `WhatsAppOtpVerificationTest`, `MobileOtpVerificationTest`, `CompanyCommunicationSettingsTest`.
