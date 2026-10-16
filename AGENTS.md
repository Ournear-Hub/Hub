# OurNear Hire — agent instructions

You are working in the **OurNear Hire** Laravel 12 + Inertia + React project (OurNear platform; recruitment/hiring module: Jobs, applications, ATS, employer hiring workspace).

## Before you code

1. Read `docs/CURRENT-STATE.md`.
2. Read `docs/AI-READING-GUIDE.md` and open **only** the module docs it names for the task.
3. Search existing code (`app/`, `routes/`, `resources/js/`) before creating files.
4. Follow `docs/DEVELOPMENT-RULES.md` (including local dev: `scripts/start-local-web-server.ps1`, Rules A–F).
5. Obey `.cursor/rules/ournear-core-governance.mdc` (rule precedence, conflict-blocking, amendment approval). If a task conflicts with governance, stop and report a `RULE BLOCKER`; do not implement until an amendment is explicitly approved.

## Hard constraints

- One canonical `App\Models\User`. Dual guards `admin` and `account`.
- Staff RBAC: reuse `PermissionName` / `AccessCatalog`. `roles.manage` is Super Admin only. Do not add a second permission catalog.
- Do not duplicate screening/matching/shortlist engines. Reuse `ScreeningMatchService`. The Hiring Command Center only aggregates existing workspaces.
- Public person IDs use `OurNearHireIdentity` (`users.person_number`, display `ON-HIR-{CAN|REC|USER}-###`). Keep existing `business_id` values.
- Automation must not auto-hire (no auto shortlist/select/offer/reject).
- Do not reuse WhatsApp OTP for recruitment messaging.
- Do not modify `ournear_app_Hostinger_2026-09-19/` as if it were the live app.
- Do not commit, push, or deploy unless the user asks.
- Do not mix the User dashboard, Hiring workspace, and OurNear Admin navigation. Public primary nav is Jobs, Hiring (`/hire`), Talent Pool, Staffing, Companies, About Us. `/hire` ≠ `/admin/hiring`.

## After you code

- Run the relevant tests and, if UI changed, `npx tsc --noEmit` and `npm run build`.
- Update `docs/CURRENT-STATE.md` (and module docs) when architecture, routes, tables, or permissions change.
- Report files changed, tests run, and assumptions.

**Instruction system:** `AGENTS.md`, `docs/` (start with `CURRENT-STATE.md` and `DEVELOPMENT-RULES.md`), and always-applied Cursor rule `.cursor/rules/ournear-core-governance.mdc`.
