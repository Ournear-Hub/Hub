# Deployment and environment

## Local

`.env.example` documents local MySQL, `APP_DEBUG=true`, `MAIL_MAILER=log`, `QUEUE_CONNECTION=database`. Composer scripts: `setup`, `dev` (serve + queue:listen + pail + vite), `test`.

Windows bind notes have appeared in prior ops docs; [NOT VERIFIED] in this audit.

## Production (documented constraints, not a runbook with secrets)

- `APP_ENV=production`, `APP_DEBUG=false`, generated `APP_KEY`
- Web root `public/`
- Real mailer
- `TRUSTED_PROXIES` if SSL terminated
- Cron: `php artisan schedule:run`
- Queue worker if `QUEUE_CONNECTION=database`
- Migrate pending files **intentionally**; do not `migrate:rollback` destructive Stage 7–10 downs on production (comment in `.env.example`)

## Do not deploy

`ournear_app_Hostinger_2026-09-19/` leftover copy.

## Git / CI

Working tree is often far ahead of `origin/main`. No application GitHub Actions workflow verified in this audit. [NOT VERIFIED]

## Cache / session

`.env.example` uses database session and cache stores; those tables must exist.

## Domains

Not hardcoded as production URLs in this audit. `APP_URL` is environment-specific.
