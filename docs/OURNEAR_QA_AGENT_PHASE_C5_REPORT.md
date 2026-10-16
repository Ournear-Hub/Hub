# OurNear Human QA Agent — Phase C5 report

**Date:** 2026-10-09  
**Scope:** Failure and trace evidence bundle (extend existing pipeline)  
**Laravel `app/` code:** unchanged.

---

## C5 gap analysis (pre-implementation)

| Capability | Before C5 | After C5 |
| --- | --- | --- |
| Screenshot on failure | Yes (`conftest` → `screenshot.png`) | Retained; wired through `write_failure_bundle()` |
| `failure.json` | Yes (`FailureEvidence` + key redaction) | Retained; deep string redaction + page context |
| `BrowserSession` diagnostics | Yes (console, page errors, HTTP ≥400, request failures) | Retained; sanitized recursively on write |
| Playwright trace | No | **Yes** — `trace.zip` on failed tests only |
| Manifest linking artifacts | No | **`manifest.json`** with `present` / `note` |
| Journey step metadata | Partial (`JourneyStepError` in `extra`) | Same fields + manifest `journey_id` / `step_id` |
| Page URL / title | No | Sanitized in `failure.json` `extra` + manifest |
| Browser name | No | From pytest-playwright `browser_name` fixture |
| Run summary JSON | Yes (`last-pytest-summary.json`) | Unchanged |

---

## Artifact layout

```
qa-agent/artifacts/<run_id>/<sanitized-test-nodeid>/
  screenshot.png      # optional if capture fails
  trace.zip           # failed tests only (Playwright viewer)
  failure.json        # backward-compatible primary record
  manifest.json       # index of artifacts for the failure
```

**Trace lifecycle**

1. Autouse fixture `_ournear_playwright_trace` starts tracing when a test uses `page`.
2. On **call** failure, `pytest_runtest_makereport` stops tracing to `trace.zip`, then writes the bundle (screenshot + JSON + manifest).
3. On **pass**, fixture teardown stops tracing **without** retaining a file.
4. `stop_context_trace()` is idempotent (no double-stop on real or mock contexts).

---

## Redaction

| Layer | Behavior |
| --- | --- |
| `sanitize_mapping` / `sanitize_value` | Redacts sensitive **keys**; recurses lists/dicts |
| `redact_sensitive_strings` | Masks `password=`, `token=`, `Bearer`, `Authorization:`, `cookie=`, `api_key=` in text |
| `sanitize_url` | Query secrets + userinfo (existing) |
| Applied to | `failure.json`, `manifest.json`, diagnostics embedded in `extra` |

No passwords, cookies, or tokens are written intentionally; tests assert synthetic leak strings are removed.

---

## Files changed

| Path | Change |
| --- | --- |
| `qa-agent/ournear_qa/trace_lifecycle.py` | **New** — start/stop trace, retain-on-failure |
| `qa-agent/ournear_qa/failure_bundle.py` | **New** — manifest, `write_failure_bundle()` |
| `qa-agent/ournear_qa/evidence.py` | `failure_case_dir()`, deep `sanitize_value()` |
| `qa-agent/ournear_qa/browser/redact.py` | `redact_sensitive_strings()` |
| `qa-agent/tests/conftest.py` | Trace autouse + unified failure hook |
| `qa-agent/tests/test_failure_bundle.py` | **New** — C5 focused tests |
| `qa-agent/README.md` | Artifact table + trace viewer hint |
| `docs/CURRENT-STATE.md` | C5 status |
| `docs/OURNEAR_QA_AGENT_PHASE_C5_REPORT.md` | This report |

---

## Tests executed (actual results, separate commands)

| Command | Result |
| --- | --- |
| `pytest tests/test_failure_bundle.py -q` | **7 passed** (~16s) |
| `pytest tests/test_failure_bundle.py tests/test_browser_session.py -q` | **17 passed** (after idempotent fix) |
| `pytest tests/test_registry_validation.py tests/test_registry.py -q` | **15 passed** (~7s) |
| `pytest tests/smoke -q` | **2 passed** (~25s) |

Journey / cross-role suites were **not** re-run (no C5 dependency; avoids apply/login throttles).

---

## Known limitations

- Trace capture requires pytest-playwright `page` / `context`; tests without `page` get `trace.note = "tracing not active"`.
- Screenshot and trace are best-effort; errors are recorded in `manifest.json` / `failure.json` `extra`, not fabricated.
- Passing tests do not create a per-test artifact directory.
- Console allowlist for third-party noise unchanged (home smoke still fails on any console error).
- No HAR export, no automatic upload, no Playwright trace on passing tests unless future env flag is added.

---

## Next roadmap item

**Phase F** — git change-aware regression (per `OURNEAR_QA_AGENT_PHASE_B_C_PLAN.md` / Phase A inventory). Not started in C5.
