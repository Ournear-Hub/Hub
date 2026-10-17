# OurNear Environment and Deployment Safety

## Environment files
- Commit `.env.example` only when it contains placeholders and safe local defaults.
- Never commit `.env`, `.env.local`, `.env.production`, API keys, passwords, signing keys, or private certificates.
- Keep staging and production credentials separate.
- If a secret is accidentally committed, revoke or rotate it; deleting the file in a later commit is not sufficient.

## Database safety
- Never run database resets or destructive migrations against production.
- Do not run `migrate:rollback` on production without an approved recovery plan.
- Back up the target database before approved production schema changes.
- Confirm the target environment and database before executing migrations.

## Deployment boundaries
- `develop` is the integration branch.
- `feature/*` branches hold individual implementation work.
- `release/*` branches hold release candidates.
- `main` remains production-protected and must not be updated without explicit approval.
- Staging deployment must not overwrite `ournear.com` production files, configuration, uploads, or database.

## Git safety
- Review `git status` and `git diff` before staging changes.
- Review `git diff --cached` before committing.
- Push completed work to its feature branch for backup.
- Do not use force-push, destructive reset, or shared-branch rewrites as routine recovery methods.
