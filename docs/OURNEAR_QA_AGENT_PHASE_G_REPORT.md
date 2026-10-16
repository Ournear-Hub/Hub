# OurNear Human QA Agent — Phase G report

**Date:** 2026-10-09  
**Scope:** Run-summary normalization, static dashboard, CI-oriented JSON/JUnit, release-readiness  
**Laravel `app/` code:** unchanged.

---

## Summary

Phase G extends the existing pytest session summary into a **normalized run record**, then generates **local static HTML**, **JSON**, and optional **JUnit XML** via `ournear-qa report` (planning and reporting remain separate from `run` and `plan-regression`).

---

## Reporting model

| Artifact | Location | Role |
| --- | --- | --- |
| `run-record.json` | `artifacts/<run_id>/` | Canonical per-run evidence (tests, counts, suite, scope) |
| `last-pytest-summary.json` | `artifacts/session/` | Backward-compatible aggregate + `blocked` count + `run_record` pointer |
| `report.json` | `artifacts/reports/<run_id>/` | Coverage + release readiness + CI exit hint |
| `dashboard.html` | same | Human-readable, escaped static dashboard |
| `junit.xml` | same (optional) | CI parsers |

**Outcomes (per test):**

| Status | Meaning |
| --- | --- |
| `passed` | Executed and passed |
| `failed` | Executed and failed |
| `skipped` | Runner skip (not a throttle block) |
| `blocked` | Skip reason matches rate limit / 429 / blocked / preflight markers |
| `not_run` | Reserved for future selection-vs-execution gaps |
| `unknown` | Could not classify |

Blocked/skipped/not-run features are **not** counted as successful coverage.

**Regression scope:** `targeted` for suite runs (`smoke`, `registry`, `journey`, `unit`); never inferred as full application regression.

---

## Feature coverage semantics

Derived from **executed** pytest results mapped to registry journeys/features (`coverage_map.py`). Reports:

- Features with pass/fail/blocked evidence this run
- Mapped features with **no** execution evidence
- Features with **no** automation mapping in registry

A green targeted smoke run does **not** imply release-ready for the whole product.

---

## Release readiness

Explicit rules (`release_readiness.py`):

- Failures → `not_release_ready`
- Targeted scope, blocked critical journeys, or large not-executed mapped set → `insufficient_evidence`
- `release_ready` only when no blocking reasons remain (rare for suite-limited runs)

Critical journeys monitored: smoke login, candidate apply, employer resume, admin application, cross-role.

---

## CLI

```powershell
cd qa-agent
ournear-qa run --suite smoke          # unchanged; writes run-record via pytest
ournear-qa report --html --json       # latest run
ournear-qa report --run-id <id> --junit --ci-exit
```

| Exit (`--ci-exit`) | Meaning |
| --- | --- |
| 0 | Complete run; no failures; readiness OK for stated scope |
| 1 | Failures |
| 2 | Incomplete / blocked |
| 4 | Insufficient evidence (typical for targeted suites) |

`report` does **not** execute tests. `plan-regression` remains planning-only.

---

## Files added/changed

| Path | Role |
| --- | --- |
| `ournear_qa/run_record.py` | Normalized run record + blocked skip detection |
| `ournear_qa/coverage_map.py` | Execution-based feature/journey coverage |
| `ournear_qa/release_readiness.py` | Verdict + CI exit helper |
| `ournear_qa/dashboard_html.py` | Escaped static HTML |
| `ournear_qa/junit_export.py` | JUnit XML |
| `ournear_qa/run_report.py` | Report assembly |
| `ournear_qa/report.py` | `RunSummary.from_run_record_dict()` |
| `tests/conftest.py` | Per-test rows, `--ournear-suite`, writes `run-record.json` |
| `ournear_qa/cli.py` | `report` command; `run` passes suite label |
| `examples/github-actions-qa-agent.yml.example` | Documented CI pattern (no secrets) |
| `docs/OURNEAR_QA_AGENT_PHASE_G_REPORT.md` | This report |

C5 failure bundles, F regression planner, and full-suite CLI paths unchanged.

---

## Tests executed (separate commands)

| Command | Result |
| --- | --- |
| `pytest tests/test_run_report.py -q` | **8 passed** (~13s) |
| `pytest tests/test_registry_validation.py tests/test_registry.py tests/test_cli_suites.py -q` | **17 passed** (~14s) |
| `pytest tests/test_failure_bundle.py -q -k "not page"` | **7 passed** (~10s) |

Journey/cross-role suites were **not** run for Phase G validation.

---

## Limitations

- PHPUnit/Laravel tests are listed in registry but not captured unless executed outside qa-agent (marked not executed).
- No hosted dashboard server; open `dashboard.html` locally or publish CI artifacts.
- Empty history: `report` exits **2** with clear JSON error until at least one pytest run creates `run-record.json`.
- Duration/start-end depend on pytest session hooks (best-effort).

---

## Next roadmap item

From Phase B plan backlog: **staging URL allowlist** (`OURNEAR_STAGING_HOSTS`) and optional **adopt CI workflow** using the example YAML — not implemented in Phase G.
