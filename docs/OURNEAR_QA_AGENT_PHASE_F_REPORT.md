# OurNear Human QA Agent — Phase F report

**Date:** 2026-10-09  
**Scope:** Git-aware regression selection (`plan-regression`)  
**Laravel `app/` code:** unchanged.

---

## Summary

Phase F adds read-only Git change detection and an explainable regression plan derived from `registry/features.yaml`, `registry/regression_rules.yaml`, feature `depends_on`, and journey `assertions` / `phpunit` mappings. **`ournear-qa run --suite …` is unchanged** — planning does not execute tests.

---

## Change detection (`ournear_qa/git_changes.py`)

| Mode | Git commands | Includes |
| --- | --- | --- |
| **Default (working tree)** | `diff --name-status`, `diff --cached --name-status`, `ls-files --others --exclude-standard` | Unstaged, staged, untracked |
| **`--base <rev>`** | `rev-parse --verify`, `diff --name-status <rev>`, `diff --cached --name-status <rev>` | Index + working tree vs base |

**Metadata:** `HEAD`, branch (or detached), `compare_method`, normalized paths (`/`).

**Renames/deletes:** Parsed from `--name-status` (`R100`, `D`, etc.); rename retains `old_path` + new `path` for mapping.

**Errors (no working-tree mutation):**

- `GitNotRepositoryError` — not a git repo
- `GitInvalidRevisionError` — bad `--base`
- `GitChangeError` — other git failures

**Empty diff:** `changes=[]`, `empty=True` — distinct from git failure.

Git is invoked with **argument lists only** via injectable `GitCommandRunner` (default `SubprocessGitRunner`).

---

## Regression mapping (`ournear_qa/regression_plan.py`)

1. Match each changed path against `registry/regression_rules.yaml`:
   - **`broad_impact_globs`** → `recommend_full_regression` (routes, migrations, config, …)
   - **`feature_path_rules`** → feature IDs
   - **`qa_agent_rules`** → pytest targets under `qa-agent/`
   - **`qa_broad_impact_globs`** → registry + smoke pytest minimum
2. Expand **dependents** via feature `depends_on` (downstream features that depend on a changed feature).
3. Collect **PHPUnit** class names from affected features + journey `phpunit:` assertions.
4. Collect **pytest** paths from journey `pytest:` assertions and QA rules.
5. **Unmapped paths** → `recommend_broader_regression` + listed in plan.
6. **Coverage gaps** — direct features with no `phpunit` and no journey pytest/phpunit assertion.
7. **Rate-limit-sensitive** journey tests flagged when cross-role / candidate apply QA code changes.

Each selection includes `changed_path`, `rule_id`, features, dependency expansion, and selected tests.

---

## CLI

```powershell
cd qa-agent
ournear-qa plan-regression
ournear-qa plan-regression --json
ournear-qa plan-regression --base main
ournear-qa plan-regression --repo "D:\Ournear Hub\Ournear Hire"
```

| Exit code | Meaning |
| --- | --- |
| **0** | Plan produced; targeted regression appears sufficient |
| **4** | Plan produced; **full** or **broader** regression recommended (broad paths, unmapped paths, coverage gaps) |
| **1** | Operational error (git, registry) |

Human output lists recommendations, PHPUnit filters, pytest targets, unmapped paths, gaps, and rate-limit warnings.

---

## Files changed

| Path | Role |
| --- | --- |
| `qa-agent/ournear_qa/git_changes.py` | Git snapshot + errors |
| `qa-agent/ournear_qa/regression_plan.py` | Plan builder + human format |
| `qa-agent/registry/regression_rules.yaml` | Path → feature / QA rules |
| `qa-agent/ournear_qa/cli.py` | `plan-regression` command |
| `qa-agent/tests/test_git_changes.py` | Git layer tests (mock + temp repos) |
| `qa-agent/tests/test_regression_plan.py` | Mapping / fallback tests |
| `qa-agent/tests/test_cli_plan_regression.py` | CLI smoke on workspace repo |
| `docs/OURNEAR_QA_AGENT_PHASE_F_REPORT.md` | This report |
| `docs/CURRENT-STATE.md` | Phase F status |
| `qa-agent/README.md` | Usage |

---

## Tests executed (separate commands)

| Command | Result |
| --- | --- |
| `pytest tests/test_git_changes.py tests/test_regression_plan.py tests/test_cli_plan_regression.py -v` | **19 passed** (~29s) |
| `pytest tests/test_registry_validation.py tests/test_registry.py tests/test_cli_suites.py tests/test_journey_preflight.py -q` | **19 passed** (~33s) |
| `pytest tests/test_failure_bundle.py -q -k "not page"` | **7 passed** (~21s) |
| `pytest tests/smoke -q` | **2 passed** (~45s) |

Journey / cross-role suites were **not** run (rate-limit sensitivity; not required for selector validation).

---

## Known limitations

- Path rules are **prefix/glob heuristics**, not a full Laravel dependency graph.
- Many features still lack `phpunit` mappings → legitimate **coverage gap** warnings.
- `--base` compares working tree + index to revision; it does not compute merge-base triple-dot history alone.
- Plan lists journey tests but does **not** auto-run them; operator must respect preflight / throttles.
- No HTML dashboard or CI wiring (roadmap **Phase G**).

---

## Next roadmap item

**Phase G** (from Phase B plan): richer run summaries / optional HTML dashboard and CI-friendly regression execution hooks — not started here.
