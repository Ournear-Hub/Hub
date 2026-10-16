# OurNear Human QA Agent — Phase B closure & Phase C plan

**Date:** 2026-10-09  
**Repo:** `D:\Ournear Hub\Ournear Hire`  
**Agent:** `qa-agent/` (Python package `ournear_qa`, not `src/`)

No Laravel/React application changes in this assessment.

---

## 1. Task 2 — Current status matrix

| Component | Implemented | Evidence | Missing | Next action |
| --- | --- | --- | --- | --- |
| **Phase A inventory** | Complete | `docs/OURNEAR_QA_AGENT_PHASE_A_INVENTORY.md`, 53 features | Per-workflow registry rows | Expand `features.yaml` in Phase C/D |
| **Project scaffold** | Complete | `pyproject.toml`, `ournear_qa/*`, `.venv` on dev machine | CI workflow | Optional later |
| **Safety gate** | Tested | `OURNEAR_ALLOW_RUN` local/staging; unit tests | Staging host allowlist env var | Phase C: `OURNEAR_STAGING_HOSTS` |
| **Base URL validation** | Tested | `local` → `127.0.0.1` / `localhost` only | Staging URL rules | Same as above |
| **Env / credentials** | Tested | `env.example` + gitignored `local.env`; **local overrides example** | Employer/admin passwords optional in env | Document fixture passwords only in CURRENT-STATE |
| **Feature registry** | Implemented | `registry/features.yaml` (53 IDs) | `phpunit`, `journeys`, dependencies | Phase C registry schema |
| **CLI** | Partially tested | `inventory`, `run --suite smoke`, `phpunit-hint` stub | Parses counts from pytest session (fixed) | Add `run --suite unit` docs |
| **Smoke: public home** | **Passed** | `pytest` 2026-10-09: HTTP 200, title, no console errors | Network failure capture | Phase C `BrowserSession` |
| **Smoke: candidate login** | **Passed** | Same run: `/login` → `/account` (QA fixture user) | Logout/session persistence | Phase D journey |
| **Failure evidence** | Implemented, not exercised | `conftest` → screenshot + `failure.json`; redaction in `evidence.py` | Playwright trace, network log | Phase C helpers |
| **Run summaries** | Tested | `artifacts/session/last-pytest-summary.json`, CLI merges into `artifacts/cli-runs/last-smoke.json` | HTML dashboard | Phase F/G |
| **Human interaction engine** | Not started | — | All Phase C helpers | **Step C1** below |
| **E2E journeys (P0 hiring)** | Not started | Manual Batch 5–7 evidence in inventory | Playwright specs | Phase D |
| **Git regression** | Not started | — | Dependency map | Phase F |

**Prior smoke ambiguity resolved:** Login had **skipped** because `env.example` (empty password) loaded **after** `local.env`. File order fixed; credentials load as `pw_set=True` without logging secrets.

**Latest verified run (this session):**

```text
tests/test_settings_gate.py — 4 passed
tests/smoke — 2 passed (home, candidate login)
Total: 6 passed in ~41s
```

Requires: local Laravel on `OURNEAR_BASE_URL`, Playwright browsers (`playwright install chromium`), QA candidate from fixtures.

---

## 2. Task 3 — Phase B closure

### Delivered in Phase B (B1–B8)

| ID | Status |
| --- | --- |
| B1 pyproject / deps | Done |
| B2 env.example + safety | Done (+ override note) |
| B3 registry ≥40 IDs | 53 IDs |
| B4 package: settings, registry, evidence, report | Done |
| B5 home smoke | **Verified pass** |
| B6 login smoke | **Verified pass** (after env order fix) |
| B7 CLI | Done |
| B8 PHPUnit hint stub | Done (needs registry `phpunit` keys) |

### Gaps closed in this pass (qa-agent only)

1. **Env precedence** — `env.example` then `local.env` so secrets in `local.env` are not wiped.
2. **Local URL guard** — reject non-localhost base URL when `OURNEAR_ALLOW_RUN=local`.
3. **Test summaries** — pytest session JSON + CLI merge (no more `passed: 0` with exit 0).

### Phase B acceptance (§8.3 inventory doc)

| Criterion | Result |
| --- | --- |
| Smoke exit 0, app up, QA user | **Yes** (6/6 this session) |
| Refuse missing / `production` allow_run | **Yes** (unit tests) |
| Failure artifacts | **Implemented** (unverified — no failing test run) |
| Registry ≥40 | **53** |
| README setup | **Yes** |
| Zero Laravel changes | **Yes** |

**Phase B: closed** for bootstrap scope. Journeys, traces, and regression remain Phase C+.

---

## 3. Task 4 — Phase C: Human Interaction Engine (spec)

**Goal:** One reusable layer on Playwright so journeys share locators, waits, diagnostics, and redaction — no random clicking.

### Module layout (proposed)

```
qa-agent/ournear_qa/browser/
  __init__.py
  session.py      # BrowserSession: console + failed request collectors
  locators.py     # get_by_role/label helpers, strict mode
  forms.py        # fill, select, submit, expect validation messages
  files.py        # synthetic PDF/txt fixtures (reuse ReadablePdfFixture bytes pattern)
  navigation.py   # goto, wait for Inertia settle (network idle / main landmark)
  redact.py       # wrap evidence sanitizer for URLs/query strings
```

### Behaviors

| Area | Rule |
| --- | --- |
| Locators | Prefer `get_by_role`, `get_by_label`, `get_by_placeholder`; document fallback `data-testid` only when added to app (avoid app changes in C1) |
| Waits | `expect(locator).to_be_visible()`, `page.wait_for_url`, response wait for form POST — no `sleep()` |
| Forms | Fill → submit → assert flash/toast or field errors via accessible text |
| Uploads | Write temp files under `qa-agent/fixtures/generated/` (gitignored), never production paths |
| Navigation | Assert HTTP OK + expected heading or route fragment |
| Diagnostics | Attach console errors + requests with status ≥400 to failure JSON |
| Timeouts | Default 15s action / 30s navigation; mark `@pytest.mark.timeout` for smoke |
| Recovery | One failure does not stop session; pytest continues (default) |

### Out of scope for Phase C1

- Full P0 hiring journeys (Phase D).
- Git-aware suite selection (Phase F).
- Modifying Laravel for `data-testid`.

---

## 4. Task 5 — Phase C implementation sequence

| Step | Change | Verify |
| --- | --- | --- |
| **C1** | Add `ournear_qa/browser/session.py` + refactor smoke tests to use it (console/network capture) | Smoke still 2/2 pass |
| **C2** | `forms.py` + `navigation.py`; add smoke assertion helper for “logged-in shell” (nav link present) | Extend login test with one post-login assertion |
| **C3** | `files.py` minimal PDF generator; **no upload test** until D1 | Unit test: file size & magic bytes |
| **C4** | Registry schema v2: `journeys`, `phpunit`, `depends_on` | `test_registry.py` + `ournear-qa inventory` |
| **C5** | Failure bundle: trace on retry=0, network snippet in `failure.json` | Force one failing test locally |

### Files to touch (C1 only)

| File | Action |
| --- | --- |
| `qa-agent/ournear_qa/browser/session.py` | Create |
| `qa-agent/tests/smoke/test_public_home.py` | Use `BrowserSession` |
| `qa-agent/tests/smoke/test_account_login.py` | Use `BrowserSession` |
| `qa-agent/README.md` | Phase B closed; C1 note |

### Risks & safety

| Risk | Control |
| --- | --- |
| Pointing at production | `OURNEAR_ALLOW_RUN` + localhost URL check |
| Credential leak | Redaction in evidence; never log `qa_settings` passwords |
| Real email/SMS | Local mail catcher only; no “forgot password” automation yet |
| Destructive data | Disposable QA users; no delete-admin tests |
| Flaky console assertions | Allowlist known third-party noise in session (documented list) |

---

## 5. Reproducible commands

```powershell
cd "D:\Ournear Hub\Ournear Hire"
php artisan serve --host=127.0.0.1 --port=8000

cd qa-agent
.venv\Scripts\activate
playwright install chromium
ournear-qa run --suite smoke
type artifacts\cli-runs\last-smoke.json
```

Unit only (no browser):

```powershell
pytest tests/test_settings_gate.py tests/test_registry.py -v
```

---

## 6. Single next action

**Implement Phase C1:** add `ournear_qa/browser/session.py` and refactor the two smoke tests to collect console errors and failed HTTP responses through that helper, then re-run smoke to confirm **6 passed** unchanged.
