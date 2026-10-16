# OurNear Human QA Agent — Phase C1 report

**Date:** 2026-10-09  
**Scope:** Shared Playwright `BrowserSession` with diagnostics; smoke integration; focused tests.  
**Application code:** unchanged.

---

## Files created or changed

| Path | Change |
| --- | --- |
| `qa-agent/ournear_qa/browser/__init__.py` | **Created** — exports `BrowserSession` |
| `qa-agent/ournear_qa/browser/session.py` | **Created** — listeners, records, classification |
| `qa-agent/ournear_qa/browser/redact.py` | **Created** — URL sanitization |
| `qa-agent/tests/conftest.py` | `browser_session` fixture; diagnostics in `failure.json`; pytest 9 timestamp fix |
| `qa-agent/tests/smoke/test_public_home.py` | Uses `browser_session`; same assertions |
| `qa-agent/tests/smoke/test_account_login.py` | Uses `browser_session`; same assertions |
| `qa-agent/tests/test_browser_session.py` | **Created** — 10 focused tests |

---

## Diagnostic events supported

| Event | Record | Used in smoke assertions? |
| --- | --- | --- |
| `console` (`type=error`) | `ConsoleErrorRecord` | **Yes** — home smoke only |
| `pageerror` | `PageErrorRecord` | No (evidence on failure) |
| `requestfailed` | `RequestFailureRecord` + `classification` | No |
| `response` (status ≥ 400) | `HttpErrorRecord` | No |

**Classification (`requestfailed`):**

- `cancelled` — navigation abort / client cancel markers (`ERR_ABORTED`, `NS_BINDING_ABORTED`, `ERR_BLOCKED_BY_CLIENT`, etc.). Not counted in `actionable_request_failures()`.
- `failed` — other network failures; actionable for investigation, not auto-failing smoke tests.

**Bounds:** up to 50 records per category per session.

**Idempotency:** `BrowserSession.attach(page)` stores one session on the page; listeners register once.

---

## Redaction behavior

- **URLs:** userinfo → `[REDACTED]@host`; sensitive query keys (password, token, secret, …) → `[REDACTED]` values; max length 512.
- **Failure JSON:** existing `evidence.sanitize_mapping` redacts keys matching password/secret/token on top-level evidence fields; `browser_diagnostics` attached under `extra` on test failure.
- **Not logged:** credential env vars, form fills, cookies, or Authorization headers.

---

## Tests executed (actual results)

**Command:**

```powershell
cd "D:\Ournear Hub\Ournear Hire\qa-agent"
.venv\Scripts\python.exe -m pytest tests/test_browser_session.py tests/smoke tests/test_settings_gate.py tests/test_registry.py -v --tb=short
```

**Result:** **17 passed**, 0 failed, 0 skipped (~69s).

| Group | Count |
| --- | ---: |
| `test_browser_session.py` | 10 |
| `tests/smoke` | 2 |
| `test_settings_gate.py` | 4 |
| `test_registry.py` | 1 |

*(Prior baseline was 6 passed; this run adds 10 browser-session tests and re-verifies smoke + unit gates.)*

---

## Known limitations

- Diagnostics are **not** attached to passing tests (only failure `failure.json` via conftest).
- Login smoke does **not** assert on HTTP/console noise; only route/account URL as before.
- No Playwright trace or HAR yet (Phase C5 in plan).
- Staging URL allowlist unchanged (local host guard only).
- Console allowlist for third-party noise not implemented (home still fails on any console error).

---

## Remaining Phase C1 work

**None** — Phase C1 scope complete. Next planned step per `OURNEAR_QA_AGENT_PHASE_B_C_PLAN.md` is **Phase C2** (forms/navigation helpers), not started here.
