# Known issues and technical debt

Do not treat this as a punch list to implement in this audit.

## CRITICAL

- Leftover `ournear_app_Hostinger_2026-09-19/` can be mistaken for the live app (HIGH deploy risk).
- Dual-guard mistakes on new routes (security).

## HIGH

- Stale `V1.1_DEVELOPMENT_STATE.md` vs actual stages 1–26 + public platform pages.
- Local migration order: later 28 Sep migrations can be applied while some 27 Sep files remain pending (`2026_09_27_220000`–`270000`, `2026_09_28_120000` 23B). Not bundled by Stage 25 or 26. Missing interview columns (`lifecycle_status`, `sort_order`) make `/admin/applications` and `/admin/screening` 500 on some local MySQL DBs; Command Center degrades interview counts instead of requiring those migrations.
- WhatsApp webhook is CSRF-exempt — signature/token logic must stay correct.
- Private file disks if a route is added without policy checks.

## MEDIUM

- No `app/Jobs`; queue workers may be idle or unused depending on env.
- ~~`CommunicationPreferenceService::canSend` not consistently applied to all mail.~~ **Candidate recruitment paths addressed (REG-COM-002, 2026-10-12).** Employer in-app when System opted out unchanged; marketing/SMS/WhatsApp campaign sends may still need audit.
- Automation executions store JSON evidence; history UI is admin-only, not Command Center.
- MySQL FK name length forced short names on automation tables.
- `tasks` table may be missing on some local DBs if Stage 6 migration pending; Stage 24 `task_id` may lack FK.
- README is still generic Laravel skeleton.

## LOW

- `web` guard unused by app routing.
- UserRole partner/investor without portals.
- Large Inertia pages.

## FUTURE IMPROVEMENT

- Visual automation builder.
- WhatsApp recruitment templates (not OTP).
- Assignment end dates for staffing automation.
- CI workflow in-repo.
- Vitest/Jest frontend tests.

## Security notes (document only)

phpunit.xml includes a testing APP_KEY — do not reuse in production. Never commit real `WHATSAPP_ACCESS_TOKEN` / DB passwords.
