# Testing architecture

## Framework

PHPUnit 11 (`phpunit.xml`). Suites: `tests/Unit`, `tests/Feature`. `RefreshDatabase`, SQLite `:memory:`. Queue `sync`, mail `array`, WhatsApp/SMS fakes in `TestCase`.

**Do not copy secrets from `phpunit.xml` into docs or chats.** It contains a testing `APP_KEY` for the suite only.

## Base

`tests/TestCase.php` — dual-guard `actingAs`, `makeAdmin` / `makeCandidate` / `makeEmployer` / `makeStaff`, fake disks, recording OTP gateways.

## Coverage clusters (examples)

| Area | Tests |
| --- | --- |
| Auth | `DualGuardAuthenticationTest`, `AccountAuthExperienceTest` |
| Candidate | Stage 3, 23F, resume/document, dashboard |
| Employer | Stage 4, employer workspace |
| Jobs/ATS | Stage 5, JobsWorkspace |
| Operations | Stage 6, Phase F3–F6, finance, replacement |
| CRM/partners/investors/campaigns | Stages 7–10 |
| UX production | Stages 12–20 |
| Comms | WhatsApp/Mobile OTP, company communication |
| Automation | `RecruitmentAutomationStage24Test` |
| Admin RBAC | `AdminRbacStage25Test`, `RolesPermissionsStage2Test`, `UsersRolesSecurityTest` |
| Hiring Command Center | `RecruiterHiringCommandCenterStage26Test` |
| Hire identity and bulk resume import | `HireIdentityResumeImportTest` |

## Frontend tests

No Jest/Vitest suite found. Typecheck + Vite build are the frontend gates.

## Baseline

Last cited full run in `CURRENT-STATE.md` (Stage 26 era, 2026-09-29): **585 passed / 10,896 assertions**. **Not re-run in the documentation-only audit that added docs 24–25.** Do not treat those numbers as verified until `php artisan test` is executed again.

## Running

```bash
php artisan test
php artisan test --filter=RecruitmentAutomationStage24Test
npx tsc --noEmit
npm run build
```
