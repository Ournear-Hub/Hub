# OurNear Human QA Agent — Phase C4 report

**Date:** 2026-10-09  
**Scope:** Registry schema v2 (journeys, phpunit, depends_on), validation, tests.  
**Application code:** unchanged.

---

## Files changed

| Path | Change |
| --- | --- |
| `qa-agent/registry/features.yaml` | `version: 2`; optional `phpunit` / `depends_on` on features; **4 journeys** |
| `qa-agent/registry/SCHEMA.md` | **Created** — field reference |
| `qa-agent/ournear_qa/registry_validate.py` | **Created** — validation + cycle detection |
| `qa-agent/ournear_qa/registry.py` | Optional `validate=True`; `load_and_validate_registry()` |
| `qa-agent/ournear_qa/cli.py` | `validate-registry`; `inventory` validates before print |
| `qa-agent/tests/test_registry_validation.py` | **Created** — 14 tests |
| `qa-agent/tests/test_registry.py` | Assert **53** features + validation |

---

## Schema decisions

### Single registry file

No second registry. `features.yaml` holds both `features` and optional `journeys`.

### Feature `depends_on`

- Values are **feature ids** only.
- Validated: exists, not self, **DAG** (cycles rejected).
- Legacy rows without `depends_on` remain valid.

### Journey `depends_on`

- Values are **journey ids** only (execution/prerequisite ordering).
- Link to product features via `feature_ids`.
- `status`: `planned` | `partial` | `implemented` | `not_applicable` — **does not imply Playwright implementation**.

### PHPUnit mappings

- Format: `ClassNameTest` (regex-validated).
- Each class must exist under repo `tests/Feature/` or `tests/Unit/`.
- Mapped only where Phase A inventory named a class **and** the file exists (18 features mapped; others unmapped intentionally).

### Journeys registered (schema only unless noted)

| ID | Status | Notes |
| --- | --- | --- |
| `JRN-SMOKE-HOME-01` | partial | Maps to existing smoke test |
| `JRN-SMOKE-LOGIN-01` | partial | Maps to existing smoke test |
| `JRN-CANDIDATE-APPLY-01` | planned | Batch 7 human evidence; not automated in qa-agent |
| `JRN-EMPLOYER-RESUME-01` | planned | Depends on apply journey |

---

## Validation behavior

`validate_registry(data)` checks:

- Version 1 or 2; non-empty unique feature ids (**53**).
- Optional journey ids unique; required journey fields present.
- `feature_ids` reference real features.
- PHPUnit list format and **file existence** in Laravel `tests/`.
- Feature and journey **dependency graphs acyclic**.

CLI:

```powershell
cd qa-agent
.venv\Scripts\ournear-qa validate-registry
```

---

## Tests executed (actual results)

**Focused registry tests first:**

```powershell
.venv\Scripts\python.exe -m pytest tests/test_registry_validation.py tests/test_registry.py -v
```

**15 passed**, 0 failed (~4s).

**Regression (registry + C1/C2 + smoke + settings):**

```powershell
.venv\Scripts\python.exe -m pytest tests/test_registry_validation.py tests/test_registry.py tests/test_forms_navigation.py tests/test_browser_session.py tests/smoke tests/test_settings_gate.py -v
```

**42 passed**, 0 failed, 0 skipped (~59s).

CLI: `validate-registry` → `{"ok": true, "feature_count": 53, "journey_count": 4}`.

---

## Limitations

- Most features still have **no** `phpunit` key (not guessed).
- Journeys are **documentation + validation targets**; Phase D must add executable Playwright specs.
- No git-aware regression wiring yet (Phase F).
- Feature dependency edges are minimal (apply/resume/employer chain only).

---

## Next recommended step

**Phase D:** Implement Playwright journey runners for `JRN-CANDIDATE-APPLY-01` and `JRN-EMPLOYER-RESUME-01` using `FormHelper` / `NavigationHelper`, gated on local fixtures — without changing Laravel routes.
