# B3-Release-Automation

Status: In progress - GitHub-only implementation

## Scope

Establish repeatable CI validation and controlled release governance for the OurNear Hire web and mobile repository.

This batch covers GitHub Actions validation, branch and pull-request checks, release traceability, operator instructions, and production approval safeguards.

CI validation is not deployment authorization. A successful workflow must not automatically deploy to staging or production. No Hostinger access or server changes are part of this batch.

## Tasks

### B3.1 - Establish CI coverage
- [ ] Preserve the existing Laravel/frontend and Flutter validation jobs.
- [ ] Add PHPUnit/Laravel automated tests.
- [ ] Add frontend Vitest tests.
- [ ] Retain Composer validation, TypeScript checks, frontend build, Flutter analysis, and Flutter tests.
- [ ] Use isolated CI test configuration; do not connect to production services or databases.
- [ ] Avoid Composer setup scripts that generate environment files, build the app through an alternate path, or run migrations.

### B3.2 - Validate branch and pull-request workflows
- [ ] Run CI for feature branches, develop, staging, main, and release branches as configured.
- [ ] Validate pull requests targeting the intended integration and release branches.
- [ ] Keep workflow permissions minimal.
- [ ] Confirm workflow changes do not introduce deployment commands or production credentials.

### B3.3 - Define release traceability
- [ ] Document the approved branch-promotion path based on the repository's actual branch strategy.
- [ ] Define how a release is associated with a commit, version or tag, CI result, and approval evidence.
- [ ] Define release notes and release-record expectations.
- [ ] Document operator recovery and failed-release handling without destructive automation.

### B3.4 - Define production approval safeguards
- [ ] Require explicit human approval before any production release or deployment.
- [ ] Keep CI and deployment authorization separate.
- [ ] Do not add automatic production deployment.
- [ ] Document GitHub environment and branch-protection configuration that an authorized repository administrator must review.
- [ ] Do not change repository settings or branch protection without explicit authorization.

### B3.5 - Review and accept
- [ ] Review the complete diff and changed-file list.
- [ ] Run relevant local checks where supported.
- [ ] Confirm CI configuration and test commands are valid.
- [ ] Confirm no secrets or production credentials are introduced.
- [ ] Commit and push the reviewed changes on feature/release-automation.
- [ ] Record CI run evidence and remaining limitations.

## Acceptance criteria

- CI includes Laravel/PHPUnit tests and frontend tests alongside existing type, build, and Flutter checks.
- CI uses isolated test configuration and does not target production data or services.
- Relevant feature-branch and pull-request events are covered.
- Workflow permissions remain minimal.
- CI does not deploy automatically to staging or production.
- Release traceability and approval requirements are documented.
- Changes are reviewed, checked, committed, and pushed on the intended branch.
- Any unverified GitHub settings or external approval requirements are documented rather than assumed.

## Implementation notes

- Repository: https://github.com/ournear/Hub.git
- Local repository: C:\Ournear Hub\Hire-Remote-Audit
- Batch branch: feature/release-automation
- Existing CI workflow: .github/workflows/main.yml
- Application source of truth: Laravel.
- PHP target for CI: 8.4; Node.js target: 22; Flutter channel: stable.
- Laravel tests run with PHPUnit against the isolated MySQL 8.4 service configured by GitHub Actions. CI does not connect to the local or production database.
- Do not run Composer's setup script in CI because it includes environment setup and forced migrations.
- Do not commit secrets, production environment files, tokens, or credentials.
- Do not disable TLS verification, force-push, or run destructive database commands.
- Do not modify Hostinger, Cloudflare, production paths, or production infrastructure.
- Do not treat historical test results as evidence for this batch.
- A checklist item is complete only when supported by current implementation or execution evidence.

## Release governance procedure

### Branch promotion

The proposed promotion path is:

1. `feature/*` -> pull request into `develop`.
2. `develop` -> pull request into `staging`.
3. `staging` -> pull request into `main` after the required checks and release review.
4. Use `release/*` only for an explicitly identified release candidate or release-specific stabilization effort.

This is a proposed process, not proof that the repository currently enforces it. An authorized repository administrator must confirm the branch strategy and configure the applicable branch protection or rulesets before this process is treated as enforced policy. Do not bypass existing repository controls or push directly to protected branches.

### Release record

For each release candidate, maintain a record containing:

- Release identifier or version.
- Source branch and exact Git commit SHA.
- Intended target environment and scope.
- Link to the relevant pull request.
- Link to the GitHub Actions run and its final result.
- Summary of changes and known limitations.
- Named human approver and approval evidence, where required.
- Release decision, date, and any follow-up actions.

Do not record secrets, access tokens, passwords, or production environment values.

### Approval and deployment safeguards

- A passing CI run is validation evidence, not deployment approval.
- This workflow must not deploy to staging or production.
- Require explicit human authorization before a production release or deployment.
- Before any future deployment workflow is introduced, an authorized administrator must review GitHub environment protection rules, required reviewers, branch restrictions, and the permissions granted to the workflow.
- This batch does not configure repository settings or authorize a deployment.

### Failed release and recovery

1. Stop promotion when required checks fail or approval evidence is missing.
2. Record the failed workflow run, affected commit, failure summary, and owner of the next action.
3. Correct the issue on a working branch and submit a reviewed pull request; rerun the required checks.
4. If a change has already been released and recovery is required, assess impact and use a reviewed revert or corrective commit through the approved release process.
5. Do not force-push, delete shared branches, run destructive database commands, or attempt an unapproved production rollback.
6. Record the recovery decision, commit, CI evidence, and outcome.

### Evidence and enforcement status

Documented procedures are not equivalent to configured enforcement. Branch protection, required status checks, GitHub environment approvals, and release execution remain unverified until an authorized administrator confirms their settings and the relevant GitHub evidence is available.
## Pending work

1. Complete the final review of the workflow and documentation.
2. Run supported local checks and review any failures.
3. Commit and push the reviewed changes to `feature/release-automation`.
4. Confirm the GitHub Actions run succeeds and record its URL and result.
5. Have an authorized repository administrator confirm the actual branch strategy, branch protection, required status checks, and any release approval settings.
6. Keep Batch 3 in progress until the required implementation and CI evidence are recorded.
