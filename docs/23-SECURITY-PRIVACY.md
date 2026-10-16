# Security and privacy

## AuthN/Z

Dual guards, active-user check, staff RBAC, policies on files and workspaces. See [02-AUTHENTICATION-ARCHITECTURE.md](02-AUTHENTICATION-ARCHITECTURE.md) and [03-AUTHORIZATION-ROLES-PERMISSIONS.md](03-AUTHORIZATION-ROLES-PERMISSIONS.md).

Stage 25: ordinary Admin (`users.manage`, no `roles.manage`) cannot assign Super Admin, cannot grant `roles.manage` via custom roles, and cannot assign a role whose permissions exceed their own. Private candidate resume/document downloads remain policy-gated (`candidates.view`). Hiring mutations use `ApplicationPolicy::update` (`applications.manage`). Frontend sidebar permission checks are convenience only.

**2026-10-12 (REG-SEC-003):** Admin mail diagnostic JSON (`validate`, `test-connection`, `prerequisites`) and `/admin/design-system/*` require `website.manage` on the server (`MailDiagnosticRequest`, `DesignSystemController`). Settings page load and mail save were already gated; diagnostics were the gap.

## Headers

`SecurityHeaders` middleware on web stack.

## Audit

`SecurityAuditService`, `security_activities`, `login_logs`, `recruitment_activities`, user activity (`UserActivityService`).

## Privacy

Public profiles honor share flags (`CandidatePublicShare`, `EmployerPublicShare`). Files private. Employer-visible candidate DTO limits fields.

## Rate limits / Turnstile

Throttle named limiters. Optional Turnstile on public forms.

## Webhook

WhatsApp CSRF exemption requires verify token / app secret handling in `WhatsAppWebhookController`.

## Secrets

Env vars only. Website settings store company numbers/mailboxes, not Meta tokens. Do not log OTP codes to clients (OTP tests assert this).

## Relationship reviews

`RelationshipReview` with visibility/status; admin hide path on employees.
