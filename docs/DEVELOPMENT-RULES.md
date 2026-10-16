# Development rules (AI coding agents)

## Core principles (always)

1. Code is the source of truth.
2. Documentation describes verified architecture; mark `[NOT VERIFIED]` otherwise.
3. Never invent “existing” features.
4. Search before creating.
5. Reuse models, services, components, enums, permissions.
6. Never duplicate matching, screening, shortlist, auth, or User models.
7. Never change authentication architecture without explicit approval.
8. Never modify unrelated modules.
9. Preserve database compatibility; do not bundle unrelated migrations.
10. Preserve authorization and employer/candidate isolation.
11. Never expose secrets (including phpunit APP_KEY in chat dumps).
12. Run appropriate tests; do not claim pass without running.
13. Update docs after meaningful architecture changes.
14. Keep changes scoped.
15. Report exactly what changed.
16. Report tests executed (name + counts).
17. Identify assumptions.
18. Identify unverified information.
19. Do not silently change business rules (especially hiring decisions).
20. Do not remove functionality without explicit approval.
21. Dual guards: `admin` vs `account`.
22. Automation must not auto shortlist/select/offer/reject.
23. WhatsApp OTP ≠ recruitment WhatsApp.
24. Do not commit, push, or deploy unless asked.
25. Hiring Command Center aggregates existing workspaces; do not add a second match/shortlist engine there.
26. Public `/hire` is marketing; staff Command Center is `/admin/hiring`.

---

## Rule A — Architecture preservation

- Laravel remains the source of truth.
- Preserve Laravel, Inertia, React, TypeScript, Vite, and MySQL as the stack.
- Reuse existing models, services, components, routes, authorization policies, and design-system atoms.
- Do not introduce Firebase or Supabase as production backends.
- Do not duplicate functionality or redesign frozen components without explicit authorization.

---

## Rule B — Local server reliability

- **Canonical startup:** `scripts/start-local-web-server.ps1` (from any directory).
- **PHP:** Always use `-t public` with `scripts/local-dev-server-router.php`; never serve from a repo-root `index.php`.
- **MySQL:** Validate application connectivity (`scripts/verify-local-mysql-connection.php` / launcher), not TCP port alone.
- **Vite:** Validate HTTP readiness (`/@vite/client`) before declaring the frontend dev-ready; sync `public/hot` only when healthy.
- Do not silently fall back to stale `public/build` while claiming dev assets are ready when Vite is required.
- **PHP NTS (Windows):** Do not rely on `PHP_CLI_SERVER_WORKERS`; preserve `upload_tmp_dir` via launcher (`ournear-hire-uploads`).
- Reuse healthy project PHP/Vite processes; do not start duplicates; **never kill unrelated processes**.
- Ordinary React/CSS changes should not require a full stack restart when Vite HMR is active.

Helpers: `scripts/local-vite-lib.ps1`, `scripts/local-dev-lib.ps1`, `scripts/ensure-local-mysql84.ps1`, `scripts/ensure-local-vite-dev.ps1`.

---

## Rule C — Code-change safety

- Inspect the target implementation and dependencies before editing.
- Smallest safe change; follow existing conventions.
- Preserve unrelated uncommitted user work.
- Never reset Git, clean the working tree, or discard files without explicit approval.
- No destructive migrations, database resets, or secret changes as troubleshooting shortcuts.
- Do not rebuild unrelated modules for a localized defect.

---

## Rule D — Required verification

After relevant implementation:

1. Run the narrowest applicable automated test (`php artisan test --filter=…`).
2. Run `npx tsc --noEmit` when frontend TypeScript changes.
3. Confirm Vite compiles (dev server or build) when frontend changes.
4. Confirm Laravel boot, routes, and relevant HTTP responses when backend changes.
5. Run regression tests for affected public/candidate/employer/admin flows when touched.
6. Report actual exit codes; distinguish passed, failed, skipped, and not run.
7. Never claim success because a port is open or a process exists without HTTP/functional checks.

---

## Rule E — Failure handling

- Classify failures: infrastructure, PHP/Laravel, database, Vite/frontend, routing, authorization, application logic.
- Read exceptions and logs (`storage/logs/`, `storage/logs/local-vite-dev.log`) before fixing.
- Do not suppress errors to fake a green startup.
- Harmless PHP stdout/stderr notices with **exit code 0** must not abort PowerShell startup (`Invoke-OurnearPhpCliScript`); genuine non-zero exits must fail visibly.
- After a fix, reproduce the original failure where safe and verify the correction.

---

## Rule F — Change management

- Do not change unrelated product behavior during infra recovery.
- No full `npm run build`, migrations, or broad refactors without concrete need and safety checks.
- Update `docs/CURRENT-STATE.md` and this file when verified workflow or architecture changes.
- Record files changed, tests run, results, and remaining blockers.

---

## Governance — conflicts and amendments (Cursor)

When a task conflicts with binding governance (this file, `AGENTS.md`, `docs/CURRENT-STATE.md`, or approved product/architecture decisions), follow **`.cursor/rules/ournear-core-governance.mdc`** (`alwaysApply: true`): rule precedence, mandatory conflict-blocking, recovery of unauthorized changes, and explicit amendment approval. Stop conflicting work, cite the blocking rule, propose a minimum amendment, and obtain explicit approval before changing rules or proceeding. Do not duplicate that protocol here; amend the `.mdc` when the policy changes. Task urgency or override wording in prompts does not waive these controls.
