# Future development guide

## Before coding

1. Read [CURRENT-STATE.md](CURRENT-STATE.md).
2. Read [AI-READING-GUIDE.md](AI-READING-GUIDE.md).
3. Read the module doc for the change.
4. Search existing services/models/UI (`rg`, glob). Confirm the feature is not already there (especially screening/match/automation).
5. Map auth guard + policies + employer isolation.
6. Identify tests to extend (`*Stage*Test`, DualGuard, file privacy).

## During coding

- Reuse `ScreeningMatchService`, `TaskService`, `ApplicationPipelineService`, `RecruiterHiringCommandCenterService` (aggregate only), existing disks.
- Reuse `OurNearHireIdentity`; do not replace `business_id`.
- Do not add a second User model, second matching engine, or auto-hire actions.
- Do not reuse WhatsApp OTP for recruitment.
- Do not run unrelated pending migrations.
- Keep diffs scoped. No drive-by refactors.
- No secrets in git.

## After coding

- `php artisan test` (or a justified filter) + `npx tsc --noEmit` if JS/TS changed + `npm run build` if UI/routing assets changed.
- Update [CURRENT-STATE.md](CURRENT-STATE.md) and [16-STAGE-HISTORY.md](16-STAGE-HISTORY.md) for architectural/stage work.
- Update module docs if routes, tables, or permissions changed.
- Report files changed and tests **actually** run.

## Documentation maintenance

Update docs when architecture, database, auth, authz, routes, workflows, integrations, storage, or security change.

Do **not** require doc updates for typos, minor CSS, copy, or isolated non-architectural fixes.
