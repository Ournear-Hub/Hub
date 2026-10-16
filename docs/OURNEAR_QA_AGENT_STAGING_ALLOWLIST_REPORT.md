# OurNear Human QA Agent — Staging host allowlist and CI adoption

**Date:** 2026-10-09  
**Scope:** `OURNEAR_STAGING_HOSTS`, explicit staging execution mode, staging preflight, safe PR CI workflow. No Laravel `app/` changes. No live staging or production browser runs during implementation.

---

## 1. Objective

Make staging execution **explicitly opt-in**, restricted by an **exact hostname allowlist**, compatible with existing **local-only** safeguards. Adopt CI automation only where the repository can run safely without secrets or arbitrary hosts from untrusted PR input.

---

## 2. Configuration

| Variable | Role |
| --- | --- |
| `OURNEAR_ALLOW_RUN` | `local` (default path) or **`staging`** (opt-in). Unset/invalid values refuse to run. |
| `OURNEAR_BASE_URL` | Target origin. **Local:** host must be `127.0.0.1`, `localhost`, or `::1`. **Staging:** must pass allowlist + URL policy. |
| `OURNEAR_STAGING_HOSTS` | Comma-separated hostnames. Normalized to lowercase; whitespace trimmed. **Empty/unset does not enable staging.** |

**Example (local `config/local.env`, not committed):**

```env
OURNEAR_ALLOW_RUN=staging
OURNEAR_BASE_URL=https://staging.example.test
OURNEAR_STAGING_HOSTS=staging.example.test,qa-staging.example.test
```

---

## 3. Host matching and URL policy

Implementation: `qa-agent/ournear_qa/staging_hosts.py`.

- **Exact match only** on `urlparse(OURNEAR_BASE_URL).hostname` (case-insensitive). No wildcards, suffix rules, or “contains staging” heuristics.
- **Schemes:** `http` and `https` only.
- **Credentials:** Userinfo in the URL is rejected.
- **Ports:** Allowlist entries are hostnames only. **Port is taken from `OURNEAR_BASE_URL` only**; hostname match ignores port. Documented in preflight as `port_policy`.
- **Wildcards / leading dot:** Rejected at parse time with `StagingHostError`.

Local validation in `QaSettings` is unchanged: `OURNEAR_ALLOW_RUN=local` still requires a loopback host.

---

## 4. Staging execution guard

Implementation: `qa-agent/ournear_qa/execution_guard.py`, wired in `ournear-qa run` via `assert_browser_run_permitted`.

Staging browser runs require **all** of:

1. `OURNEAR_ALLOW_RUN=staging`
2. Valid `OURNEAR_BASE_URL` (Pydantic + staging URL rules)
3. Non-empty `OURNEAR_STAGING_HOSTS` with **exact** hostname match
4. Existing QA credentials/fixture prerequisites for the suite (unchanged)
5. Suite allowed for staging (see below)

A hostname in the allowlist **without** `OURNEAR_ALLOW_RUN=staging` does **not** switch local runs to staging.

**Permitted suites in staging:** `smoke`, `registry` only.

**Blocked in staging:** `journey`, `unit` (and any other suite) — journeys perform application submissions, repeated logins (rate limits), and employer/admin mutations.

Planning (`plan-regression`) and reporting (`report`) remain **non-executing**.

---

## 5. Staging preflight

```bash
ournear-qa preflight-staging
ournear-qa preflight-staging --suite smoke
```

- **No HTTP**, no form posts, no mutations.
- JSON includes: execution mode, parsed host, allowlist entries, check list, suite eligibility, port policy, and a note that success is **configuration-only** (not app health or test pass).
- Passwords/tokens are **not** included in output (covered by unit test).
- **Exit codes:** `0` when `OURNEAR_ALLOW_RUN` is not `staging` (informational). When mode is `staging`, `0` if `configuration_ok`, else **`3`**.

---

## 6. CI adoption

| Artifact | Purpose |
| --- | --- |
| `.github/workflows/qa-agent-checks.yml` | **Committed safe PR job:** `validate-registry` + focused pytest (includes staging guard tests). `OURNEAR_ALLOW_RUN=local`, fixed loopback URL. **No staging secrets, no browser journeys against remote hosts.** |
| `qa-agent/examples/github-actions-qa-agent.yml.example` | **Supplemental** manual/self-hosted smoke example; not the default workflow. Points to the checks workflow for PRs. |

**PR safety:** Workflow uses `permissions: contents: read`. Base URL and allow run are **fixed in YAML**, not derived from PR titles or fork-provided env files.

**Not activated (requires your environment):**

- Automatic **staging** or **remote smoke** in GitHub-hosted runners (no app server, no `OURNEAR_STAGING_HOSTS` / QA passwords in repo).
- Copy the example workflow only on a **self-hosted** or custom runner with Laravel running, secrets in GitHub Actions secrets, and an explicit allowlisted staging host if using `OURNEAR_ALLOW_RUN=staging`.

---

## 7. Failure behavior

- Settings load failures (`ValidationError`, `StagingHostError`) explain the failed check; **no fallback URL**.
- `ournear-qa run --suite journey` with `OURNEAR_ALLOW_RUN=staging` raises `ExecutionNotAllowedError` before pytest.
- Production-like hosts fail unless listed explicitly (exact entry); listing production hosts is an operator mistake — the agent does not infer “production” from naming.

---

## 8. Verification evidence

Commands run on **2026-10-09** (Windows, `qa-agent/.venv`, synthetic env in tests — **no requests to staging/production**).

| Command | Result |
| --- | --- |
| `pytest tests/test_staging_guard.py tests/test_settings_gate.py -v` | **19 passed** |
| CI-equivalent subset (`pytest … -q -k "not page"`, same list as `qa-agent-checks.yml`) | **71 passed** |
| `ournear-qa validate-registry` (local env) | OK (53 features, 6 journeys) |
| `ournear-qa preflight-staging --suite smoke` (local mode) | Exit **0**, informational JSON (`ok: false` for staging readiness; expected) |

**Not run:** Full journey matrix; browser smoke against a real staging host; GitHub Actions execution in cloud (workflow file reviewed only).

---

## 9. Files touched (summary)

| Area | Paths |
| --- | --- |
| Allowlist / URL | `qa-agent/ournear_qa/staging_hosts.py` |
| Suite guard | `qa-agent/ournear_qa/execution_guard.py` |
| Preflight | `qa-agent/ournear_qa/staging_preflight.py` |
| Settings | `qa-agent/ournear_qa/settings.py`, `qa-agent/config/env.example` |
| CLI | `qa-agent/ournear_qa/cli.py` (`preflight-staging`, `run` guard) |
| Tests | `qa-agent/tests/test_staging_guard.py` |
| CI | `.github/workflows/qa-agent-checks.yml`, `qa-agent/examples/github-actions-qa-agent.yml.example` |
| Docs | `qa-agent/README.md`, `docs/CURRENT-STATE.md`, this report |

---

## 10. Completion criteria

| Criterion | Status |
| --- | --- |
| Staging opt-in via `OURNEAR_ALLOW_RUN=staging` | Done |
| Exact allowlist via `OURNEAR_STAGING_HOSTS` | Done |
| Local guards preserved | Done |
| Staging preflight (non-executing) | Done |
| Safe CI integrated; smoke example documented | Done |
| Deterministic unit tests | Done |

---

## 11. Next roadmap item

**Optional self-hosted / staging pipeline activation:** Run `ournear-qa run --suite smoke` (or staging allowlisted smoke) on a controlled runner with app + secrets configured; wire `ournear-qa report --ci-exit` and artifact upload per the example YAML. **Product QA track (inventory):** additional bounded journeys (e.g. employer job publish lifecycle) remain separate from this safety/CI item.
