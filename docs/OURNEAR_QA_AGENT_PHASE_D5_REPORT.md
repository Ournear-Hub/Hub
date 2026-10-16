# OurNear Human QA Agent — Phase D5 report

**Date:** 2026-10-09  
**Scope:** Journey preflight (fixture capacity + apply budget evidence) and CLI journey/registry suites  
**Laravel `app/` code:** unchanged. **New read-only script:** `scripts/inspect-qa-journey-capacity.php`.

---

## D5 task selection (rationale)

| Source | Next bounded work after D4 cross-role |
| --- | --- |
| `docs/OURNEAR_QA_AGENT_PHASE_B_C_PLAN.md` | CLI still missing **`run --suite journey`** (and documented registry run) |
| `docs/OURNEAR_QA_AGENT_PHASE_D4_REPORT.md` | Combined runs blocked by **apply** and **login** throttles; need safe capacity visibility |
| `docs/OURNEAR_QA_AGENT_PHASE_C1_REPORT.md` | **C5** (failure trace bundle) remains open — **not** duplicated here |
| Phase sequence | **D5 = operational closure** for Phase D journeys (preflight + CLI), not a new browser journey |

**Not chosen:** Phase F git-aware regression (too large); C5 failure traces (separate C-track item); new P0 feature journeys (out of charter).

---

## Implementation

### Read-only capacity script

`scripts/inspect-qa-journey-capacity.php` (local/testing only):

- Counts **unapplied** and **not-yet-created** slots among `qa-fixture-journey-apply` … `-60` (does not equal “60 free applies”).
- Reports candidate **applications in the last 60 minutes** (DB `created_at`) vs documented **8/hour** apply limit — **advisory** for scheduling (Laravel limiter uses cache keys; may differ slightly from DB count).
- Emits `cross_role_apply_feasible` / `new_apply_test_feasible` without creating jobs or clearing caches.

### Python + CLI

| File | Role |
| --- | --- |
| `qa-agent/ournear_qa/journey/preflight.py` | `inspect_journey_capacity()`, `JourneyCapacityReport`, `blocked_reason` |
| `qa-agent/ournear_qa/cli.py` | `preflight-journey` (exit **0** ready, **3** blocked); `run --suite journey`; `run --suite registry` |
| `qa-agent/tests/test_journey_preflight.py` | Parse + blocked-reason unit tests |
| `qa-agent/tests/test_cli_suites.py` | Suite registration tests |
| `qa-agent/README.md` | Preflight and suite commands |

**Commands:**

```powershell
cd "D:\Ournear Hub\Ournear Hire"
php scripts/inspect-qa-journey-capacity.php

cd qa-agent
ournear-qa preflight-journey
ournear-qa run --suite journey
ournear-qa run --suite registry -- -q
```

Pass extra pytest flags after `--` (e.g. `-q`).

---

## D4 validation closure (executed in this session)

Documented in full in `docs/OURNEAR_QA_AGENT_PHASE_D4_REPORT.md` (§ Validation closure).

| Command | Outcome |
| --- | --- |
| `inspect-qa-journey-capacity.php` | 37 then 36 provision slots; **8/8** applies last 60m at check time |
| `pytest tests/journey/test_cross_role_e2e.py -v` | **1 passed** (~86s) |
| `pytest tests/journey -q` (after ~75s spacing) | **14 passed**, **0 skipped** (~185s) |
| `ournear-qa preflight-journey` | **exit 3**, `blocked_reason`: job-applications 8/8 (before cross-role consumed budget headroom) |

---

## Verification (D5 — each command separate)

| Command | Result |
| --- | --- |
| `pytest tests/test_journey_preflight.py tests/test_cli_suites.py tests/test_cross_role_helpers.py -v` | **6 passed** (~19s) |
| `ournear-qa run --suite registry -- -q` | **15 passed** (~7s) |
| `pytest tests/smoke -q` | **2 passed** (~67s) |

No Laravel `app/` changes. No throttle bypass.

---

## Risks and gaps

- **Preflight vs limiter:** DB application count is scheduling guidance; a **429** on apply POST remains authoritative (tests skip with `Retry-After` when present).
- **Provision slots ≠ apply budget:** Many slug slots can exist while hourly submit budget is **0**.
- **Combined suite timing:** Still requires spacing (~70s) between heavy runs to avoid account-login **429** skips.
- **Registry journey status:** `JRN-CROSS-ROLE-01` remains **`partial`** in YAML until policy promotes after repeated green runs.

---

## Next phase (repository roadmap)

| Track | Item |
| --- | --- |
| **C5** (B/C plan) | Failure bundle: Playwright trace + richer network snippet in `failure.json` |
| **Phase F** (inventory / C4) | Git change-aware regression and dependency-selected pytest |
| **Phase D+** (inventory P0 gaps) | Additional bounded journeys (e.g. employer job publish lifecycle) — not started |

Stop after D5 per charter.
