# B2-Staging-Deployment

Status: Pending - deferred until GitHub-first batches are complete

## Scope

Establish a safe, repeatable staging deployment process for the existing OurNear Hire Laravel application using Hostinger shared hosting and the intended staging hostname staging.ournear.com.

This batch covers deployment-boundary discovery, isolation evidence, deployment tooling, recovery documentation, and acceptance preparation.

No server deployment is authorized by this plan alone.

## Tasks

### B2.1 - Branch and repository baseline
- [x] Switch to the existing feature/staging-deployment branch.
- [x] Confirm local branch tracking and clean working tree before editing this plan.
- [x] Read this existing Batch 2 plan.
- [ ] Retry remote synchronization if required.
- [ ] Record the Batch 2 implementation commit and evidence.

### B2.2 - Identify the staging boundary
- [ ] Confirm ownership and intended use of staging.ournear.com.
- [ ] Establish the actual staging document root.
- [ ] Establish the actual Laravel project/code directory.
- [ ] Establish how the document root reaches Laravel public/.
- [ ] Identify the staging environment-file path without printing secrets.
- [ ] Identify the staging database name and connection target without disclosing credentials.
- [ ] Identify staging storage, uploads, and writable directories.
- [ ] Determine the PHP version and required extensions.
- [ ] Determine whether staging shares code, directories, database, uploads, or configuration with ournear.com.
- [ ] Identify dependencies and data belonging to hire.ournear.com.
- [ ] Document available backups and a recovery procedure.
- [ ] Record evidence supporting staging/production isolation.

### B2.3 - Define deployment and recovery procedure
- [ ] Document pre-deployment backup requirements.
- [ ] Define artifact transfer and deployment steps for Hostinger.
- [ ] Define dependency, cache, permissions, and migration handling.
- [ ] Define smoke tests and post-deployment acceptance checks.
- [ ] Define rollback and recovery steps.
- [ ] Ensure secrets are excluded from version control.
- [ ] Ensure deployment targets are explicit and validated.
- [ ] Ensure scripts fail closed if the target is production or ambiguous.

### B2.4 - Prepare version-controlled artifacts
- [ ] Add only artifacts justified by the confirmed hosting arrangement.
- [ ] Avoid hardcoded production paths and credentials.
- [ ] Avoid destructive database operations and automatic production fallbacks.
- [ ] Document required operator inputs and failure handling.

### B2.5 - Review and accept
- [ ] Review the complete diff and changed-file list.
- [ ] Run relevant documentation, script, and repository checks.
- [ ] Confirm the deployment procedure cannot silently target production.
- [ ] Confirm isolation and recovery evidence is documented.
- [ ] Commit Batch 2 changes on feature/staging-deployment.
- [ ] Push the branch and confirm the remote update.
- [ ] Obtain explicit approval before any actual staging deployment.

## Acceptance criteria

- Staging hostname, document root, Laravel code path, environment, database, storage, and uploads are identified.
- Isolation from ournear.com is established with evidence.
- Dependencies and preservation requirements for hire.ournear.com are documented.
- Backup and recovery steps are actionable.
- Deployment tooling, if required, validates the target and fails closed.
- No secrets are committed.
- The diff contains only approved Batch 2 changes.
- Relevant checks pass and the implementation is pushed to the existing feature/staging-deployment branch.
- No production changes occur.
- Actual deployment remains blocked until explicitly approved.

## Implementation notes

- Repository: https://github.com/ournear/Hub.git
- Local repository: C:\Ournear Hub\Hire-Remote-Audit
- Batch branch: feature/staging-deployment
- Intended staging hostname: staging.ournear.com
- Current hosting: Hostinger shared hosting.
- DNS/CDN: Cloudflare.
- Production hostname: ournear.com; protected from Batch 2 changes.
- hire.ournear.com must be preserved until its dependencies and data are understood.
- Laravel is the source of truth. Do not introduce another backend.
- Do not assume document roots or project paths from directory names.
- Never print or commit environment secrets.
- Never run production migrations, resets, rollbacks, or destructive commands.
- Do not disable TLS verification or force-push.
- Do not deploy merely to discover whether isolation exists.
- Historical test results are not new Batch 2 test evidence.
- A completed checklist item requires actual supporting evidence.

## Pending work

1. Resume hosting-boundary discovery after the GitHub-first implementation batches are complete.
2. Establish and document isolation and recovery evidence.
3. Prepare deployment artifacts only after the hosting layout is understood.
4. Review, validate, commit, and push the remaining Batch 2 work.
5. Obtain explicit deployment approval.

## Deferral decision - 2026-10-17

Hostinger work is deferred until the GitHub-first implementation batches are complete. Do not inspect or modify Hostinger as part of the current work. Preserve this batch and resume it later for staging boundary discovery, production isolation evidence, backup/recovery planning, and deployment readiness.

No staging or production deployment is authorized by this decision.