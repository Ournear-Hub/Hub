# REG-PROD-002 — Email management, SMTP configuration, and verification links

**Date:** 2026-10-11  
**Workspace:** `D:\Ournear Hub\Ournear Hire`  
**Status:** Code complete — live SMTP inbox proof remains operator-dependent

## Summary

Administrators can manage supported mail transport settings from **Admin → Settings → Email delivery** (and **Communications → Email delivery**). Saved settings are encrypted at rest, applied at runtime (including queue job processing), and paired with diagnostics for validation, SMTP connection checks, test sends, and verification-email preview.

Account verification and related security emails now generate signed URLs using the configured public application URL and include explicit action buttons plus plain-text fallback links.

## Admin workflow

1. Set **Website URL** and **Primary company email** on the Website tab (sender identity).
2. Open **Email delivery** tab: enable admin-managed delivery, enter SMTP host/port/encryption/username/password (Brevo pattern supported, not hardcoded).
3. Save, then run **Validate configuration**, **Test SMTP connection**, **Preview verification email**, and optionally **Send test email** to a mailbox you control.

## Configuration precedence

1. **Environment** (`.env`) — bootstrap fallback when admin delivery is disabled or incomplete.
2. **Admin mail settings** — when `mail_enabled` is true and SMTP fields are complete, overrides `mail.default` and `mail.mailers.smtp.*`.
3. **Website settings** — `office_email` / company name for From; support/recruitment/reply-to for Reply-To (existing `WebsiteSettingService` + `MessageSending` listener).

Queue workers re-apply configuration on `JobProcessing` so subsequent mail jobs pick up changes without redeploy.

## Security

- SMTP password: Laravel `encrypted` cast on `website_settings.smtp_password`.
- Password never returned to Inertia/JSON; UI shows `smtp_password_configured` only.
- Blank password on update preserves existing credential; **Clear saved SMTP password** removes it.
- Website settings form still **prohibits** smtp fields (mass-assignment guard).
- Diagnostics sanitize error messages; no credential logging.
- **2026-10-12:** Validate configuration, test SMTP connection, and prerequisites JSON endpoints require `website.manage` via `MailDiagnosticRequest` (same as save/send-test/preview). Recruiters and other non-CMS staff receive 403 on direct URL access.

## Verification links

- `VerifyAccountEmailNotification` replaces default `VerifyEmail` for account members.
- `ApplicationUrlResolver` applies `website_url` (or `APP_URL`) before signed route generation.
- Email change and hiring verification services apply the same URL resolver.
- `SecurityMailMessage` adds action button + copy-paste URL line.

## Schema

Migration `2026_10_11_140000_add_mail_delivery_settings_to_website_settings_table.php` adds mail/SMTP/diagnostic columns to `website_settings`.

## Tests (PHPUnit)

- `AdminMailSettingsTest`
- `AccountVerificationEmailTest`
- Existing `CompanyCommunicationSettingsTest` (smtp prohibited on website form)

Run:

```bash
php artisan test --filter=AdminMailSettingsTest
php artisan test --filter=AccountVerificationEmailTest
php artisan test --filter=CompanyCommunicationSettingsTest
npm run typecheck
npm run build
```

## Browser acceptance (local, scoped)

- Harness: `qa-agent/tests/admin/test_reg_prod_002_email_settings_browser.py` (`reg_prod_002`)
- Evidence: `qa-agent/artifacts/agent2/20261011T163000Z-reg-prod-002-browser/REG_PROD_002_BROWSER_CLOSURE_REPORT.md`
- Proves: admin can open Email delivery, save SMTP host, value persists after reload; validate/preview diagnostics render feedback
- Does **not** prove: live SMTP auth, inbox delivery, or DNS

## External prerequisites (not auto-verified)

- Valid SMTP credentials with provider (e.g. Brevo SMTP login + SMTP key).
- Sender/domain verification at provider.
- SPF, DKIM, DMARC DNS records.
- Public HTTPS **Website URL** matching production.
- Inbox delivery — only evidenced by receiving a test message; connection success ≠ inbox placement.

## Limitations

- No live production/staging SMTP or DNS validation was performed in this batch.
- Admin panel cannot modify DNS or provider account approval state.
- Release readiness for email delivery still requires operator-run test send + provider evidence.
