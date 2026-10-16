# OurNear Human QA Agent (Python)

Independent browser and regression harness for **OurNear Hire**. Does not modify the Laravel application.

## Status

- **Phase A:** Complete — see [../docs/OURNEAR_QA_AGENT_PHASE_A_INVENTORY.md](../docs/OURNEAR_QA_AGENT_PHASE_A_INVENTORY.md)
- **Phase B:** Closed (smoke verified locally; see [../docs/OURNEAR_QA_AGENT_PHASE_B_C_PLAN.md](../docs/OURNEAR_QA_AGENT_PHASE_B_C_PLAN.md))
- **Phase C1:** Browser diagnostics session — [../docs/OURNEAR_QA_AGENT_PHASE_C1_REPORT.md](../docs/OURNEAR_QA_AGENT_PHASE_C1_REPORT.md)
- **Phase C2:** Forms/navigation helpers — [../docs/OURNEAR_QA_AGENT_PHASE_C2_REPORT.md](../docs/OURNEAR_QA_AGENT_PHASE_C2_REPORT.md)
- **Staging allowlist + CI:** [../docs/OURNEAR_QA_AGENT_STAGING_ALLOWLIST_REPORT.md](../docs/OURNEAR_QA_AGENT_STAGING_ALLOWLIST_REPORT.md)
- **P0 employer job publish:** [../docs/OURNEAR_QA_P0_EMPLOYER_JOB_PUBLISH_REPORT.md](../docs/OURNEAR_QA_P0_EMPLOYER_JOB_PUBLISH_REPORT.md)
- **P0 cross-role (`JRN-CROSS-ROLE-01`):** [../docs/OURNEAR_QA_P0_CROSS_ROLE_REPORT.md](../docs/OURNEAR_QA_P0_CROSS_ROLE_REPORT.md)
- **Web release readiness snapshot:** [../docs/OURNEAR_WEB_RELEASE_READINESS.md](../docs/OURNEAR_WEB_RELEASE_READINESS.md)
- **Trial posting expiry (PHPUnit evidence):** [../docs/OURNEAR_QA_P0_TRIAL_EXPIRY_REPORT.md](../docs/OURNEAR_QA_P0_TRIAL_EXPIRY_REPORT.md)
- **Candidate profile persistence (PHPUnit evidence):** [../docs/OURNEAR_QA_P0_CANDIDATE_PROFILE_REPORT.md](../docs/OURNEAR_QA_P0_CANDIDATE_PROFILE_REPORT.md)
- **FE-103 DetailHeader browser QA:** [../docs/OURNEAR_QA_FE103_DETAIL_HEADER_BROWSER_REPORT.md](../docs/OURNEAR_QA_FE103_DETAIL_HEADER_BROWSER_REPORT.md) — `pytest tests/journey/test_fe103_detail_header_browser.py`
- **LR Register + Account/Home:** [../docs/OURNEAR_QA_LR_REGISTER_ACCOUNT_HOME_BROWSER_REPORT.md](../docs/OURNEAR_QA_LR_REGISTER_ACCOUNT_HOME_BROWSER_REPORT.md) — `pytest tests/journey/test_lr_register_account_home_browser.py`

## Safety

1. Copy `config/env.example` to `config/local.env` (gitignored).
2. Set `OURNEAR_ALLOW_RUN=local` and `OURNEAR_BASE_URL=http://127.0.0.1:8000`.
3. Never point at production. Never commit real passwords.

### Staging (explicit opt-in)

Staging runs require **both**:

- `OURNEAR_ALLOW_RUN=staging`
- `OURNEAR_STAGING_HOSTS=exact.hostname.example` (comma-separated, **exact** hostname match — no wildcards)

Only **`smoke`** and **`registry`** suites may execute in staging mode (journeys submit applications and log in repeatedly). Validate config without HTTP:

```bash
ournear-qa preflight-staging --suite smoke
```

PR CI uses `.github/workflows/qa-agent-checks.yml` (unit/registry only). The smoke example workflow is optional and requires your own host + secrets.

## Prerequisites

- **Python 3.11+** on PATH (`python` or `py -3`)
- Local Laravel at `OURNEAR_BASE_URL` (default `http://127.0.0.1:8000`)
- Optional: `php artisan qa:hiring-fixtures` for QA login smoke test

## Setup (Phase B)

```bash
cd qa-agent
python -m venv .venv
.venv\Scripts\activate   # Windows
pip install -e ".[dev]"
playwright install chromium
copy config\env.example config\local.env
# Set OURNEAR_QA_CANDIDATE_PASSWORD (e.g. Password1! from fixtures doc)
```

## Run journeys (Phase D1+)

```bash
ournear-qa preflight-journey
ournear-qa run --suite journey
# or: pytest tests/journey -v
```

**P0 employer job publish** (`JRN-EMPLOYER-JOB-PUBLISH-01`): draft → publish → public visibility → edit → close, plus employer B / candidate denial. Requires `php artisan qa:hiring-fixtures` and `scripts/inspect-qa-employer-job-form-defaults.php` (master data). Focused run:

```bash
pytest tests/journey/test_employer_job_publish_journey.py tests/test_employer_job_publish_unit.py -v
```

Requires local Laravel, `php artisan qa:hiring-fixtures`, then repo-root `php scripts/qa-provision-journey-apply-job.php`, and `php` on PATH. Use `preflight-journey` (exit **3** when apply/login budget or fixtures block new submissions) before scheduling cross-role or new-apply tests. Allow ~70s between heavy journey runs to avoid account-login **429** skips.

Cross-role E2E (one application, all roles):

```bash
ournear-qa preflight-journey
pytest tests/journey/test_cross_role_e2e.py -v
```

Registry validation: `ournear-qa run --suite registry -- -q`

## Git-aware regression plan (Phase F)

Planning only — does **not** run tests or replace full suites:

```bash
ournear-qa plan-regression
ournear-qa plan-regression --json
ournear-qa plan-regression --base main
```

Exit **4** when the plan recommends full/broader regression (broad-impact paths, unmapped files, coverage gaps). Path rules live in `registry/regression_rules.yaml`.

## Operator workflow (automated + human)

End-to-end session without writing a new prompt each time:

1. **Configure environment** — `config/local.env` from `config/env.example`; `OURNEAR_ALLOW_RUN=local`; `OURNEAR_BASE_URL=http://127.0.0.1:8000`; fixture passwords in env only (never commit).
2. **Validate registry** — `ournear-qa validate-registry`
3. **Preflight** — `ournear-qa preflight-journey` before journeys that apply or cross-role; `ournear-qa preflight-staging --suite smoke` only when staging is explicitly authorized.
4. **Run automated tests** — `ournear-qa run --suite smoke` or `ournear-qa run --suite journey`; note the `run_id` under `artifacts/<run_id>/`.
5. **Export manual checklist** — journeys plus companion cases in `registry/manual_cases.yaml`:

```bash
ournear-qa export-manual --format markdown --out artifacts/manual/checklists/latest.md
ournear-qa export-manual --format json --feature-id CAN-01 --out artifacts/manual/checklists/can-01.json
ournear-qa export-manual --format csv --risk P0 --out artifacts/manual/checklists/p0.csv
```

6. **Human execution** — tester fills Result (`PASS` / `FAIL` / `BLOCKED` / `NOT_RUN`), evidence paths, and notes in the exported checklist (or build JSON — see below).
7. **Record manual results** — create `manual-results.json`:

```json
{
  "version": 1,
  "manual_run_id": "20261010-manual-session",
  "environment": { "base_url": "http://127.0.0.1:8000", "allow_run": "local" },
  "tester": "initials-only",
  "results": [
    { "test_id": "JRN-SMOKE-LOGIN-01", "status": "PASS", "evidence_refs": ["artifacts/manual/evidence/login.png"] },
    { "test_id": "MAN-CAN-01-DASHBOARD-GUIDANCE", "status": "NOT_RUN", "notes": "Scheduled next session" }
  ]
}
```

```bash
ournear-qa validate-manual-results path/to/manual-results.json
ournear-qa import-manual-results path/to/manual-results.json --attach-run-id <pytest_run_id>
```

8. **Consolidated report** — `ournear-qa report --run-id <pytest_run_id> --html --json --ci-exit`  
   Human rows appear in `report.json` and `dashboard.html` under **Human manual checklist** and are **not** counted as Playwright/pytest passes.
9. **Interpret readiness** — `--ci-exit 4` remains normal for targeted/local runs (`insufficient_evidence`). Manual PASS alone does not upgrade to `release_ready`.

**Integrity:** unknown `test_id`, invalid status, duplicate ids, and secret-like evidence paths are rejected. Notes are sanitized (passwords redacted). Staging guards and suite allowlists are unchanged.

## Run reports and dashboard (Phase G)

Pytest runs write `artifacts/<run_id>/run-record.json` (per-test outcomes including **blocked** vs **skipped**). Generate reports without executing tests:

```bash
ournear-qa report --html --json
ournear-qa report --run-id <run_id> --junit --ci-exit
```

Outputs under `artifacts/reports/<run_id>/` (`dashboard.html`, `report.json`, optional `junit.xml`). CI example: `examples/github-actions-qa-agent.yml.example`.

| `--ci-exit` code | Meaning |
| --- | --- |
| 0 | Run complete; no failures; evidence sufficient for scope |
| 1 | Test failures |
| 2 | Incomplete/blocked run |
| 4 | Insufficient evidence / targeted-only (not full release sign-off) |

## Run smoke tests

```bash
# Laravel app must be running locally
ournear-qa run --suite smoke
# or: pytest tests/smoke -v
```

Failed browser tests write `artifacts/<run_id>/<test-nodeid>/`:

| File | Purpose |
| --- | --- |
| `screenshot.png` | Full-page capture at failure (when available) |
| `trace.zip` | Playwright trace (failed tests only; discarded on pass) |
| `failure.json` | Sanitized expected/actual, diagnostics, journey step |
| `manifest.json` | Index of artifacts with honest `present` / `note` fields |

View traces: `playwright show-trace artifacts/<run_id>/.../trace.zip`. CLI summary: `artifacts/cli-runs/last-smoke.json`.

## PHPUnit bridge (optional)

```bash
ournear-qa phpunit-hint PUB-03
# prints a suggested filter when registry maps phpunit tags (expand in Phase C)
```

## Registry

Feature IDs and priorities: `registry/features.yaml` (schema v2: `registry/SCHEMA.md`).

```bash
ournear-qa validate-registry
ournear-qa phpunit-hint PUB-03
```
