# OurNear Hire — consolidated P0 local evidence record

**As-of:** 2026-10-10 (closure session)  
**Scope:** Local `127.0.0.1:8000` only. **Staging not authorized or executed.**  
**Release verdict:** **`insufficient_evidence`** (see `docs/OURNEAR_WEB_RELEASE_READINESS.md`).

This record **does not** imply a single full-matrix pytest run. Each row is an **independent** command/run with its own `run_id` where applicable.

---

## 1. Full PHPUnit (unchanged this session)

| Field | Value |
| --- | --- |
| Command | `php artisan test` |
| Exit | **0** |
| Passed / skipped / failed | **712** / **2** / **0** |
| Assertions | **12,839** |
| Duration | **1019.74s** |
| Log | `storage/logs/phpunit-full-20261010-session2.txt` |

**Targeted this closure session (product delta only):**

| Command | Exit | Result |
| --- | --- | --- |
| `php artisan test --filter=QaDefectRegressionTest` | 0 | **8 passed** (232 assertions) — includes new `test_public_jobs_company_filter_persists_across_pagination` |
| `php artisan test --filter=QaHiringFixturesTest` | 0 | **4 passed** — pagination fixture jobs (`PUBLISHED_A_PAGINATION_JOBS = 9`) |

---

## 2. Targeted automated P0 journeys (reused — not re-run)

| Run ID | Command scope | Outcome | Notes |
| --- | --- | --- | --- |
| `20261010T030042Z-8d065de8` | `pytest` journey suite (targeted) | **9 passed** | Employer publish, resume, admin application, smoke — valid evidence retained |
| Prior batches | `JRN-CROSS-ROLE-01`, `JRN-CANDIDATE-APPLY-01` | **Passed** (earlier session) | Intentionally not re-run (apply budget / cost) |

---

## 3. Manual companion cases (Playwright harness)

| Case | Run ID | Pytest node | Outcome | Evidence root |
| --- | --- | --- | --- | --- |
| `MAN-CAN-01-DASHBOARD-GUIDANCE` | `20261010T025807Z-37d441ab` | `test_man_can_01_dashboard_guidance` | **PASS** (prior session) | `qa-agent/artifacts/manual/evidence/20261010T025807Z-37d441ab/MAN-CAN-01-DASHBOARD-GUIDANCE/` |
| `MAN-CAN-02-PROFILE-RELOAD` | `20261010T041431Z-9b88be75` | `test_man_can_02_profile_reload` | **PASS** | `.../20261010T041431Z-9b88be75/MAN-CAN-02-PROFILE-RELOAD/` |
| `MAN-PUB-02-JOBS-SEARCH` | `20261010T054124Z-a53f91f2` | `test_man_pub_02_jobs_search` | **PASS** | `.../20261010T054124Z-a53f91f2/MAN-PUB-02-JOBS-SEARCH/` |
| PUB-02 pagination + clear (same pytest run) | `20261010T054124Z-a53f91f2` | `test_man_pub_02_jobs_company_pagination` | **PASS** | `.../20261010T054124Z-a53f91f2/MAN-PUB-02-JOBS-PAGINATION/` (linked in manual import for `MAN-PUB-02-JOBS-SEARCH`) |

Manual imports:

- `qa-agent/artifacts/manual/results/20261010T041431Z-manual-can02.json` → run `20261010T041431Z-9b88be75`
- `qa-agent/artifacts/manual/results/20261010T054124Z-manual-pub02-closure.json` → run `20261010T054124Z-a53f91f2`

---

## 4. Product fixes verified this session

| Area | Change | Verification |
| --- | --- | --- |
| Mobile / desktop jobs filters | Removed native `<details>` toggle; always-visible “More filters” block in `JobsSearchPanel.tsx` | Manual `05-mobile-company-filter-visible.png`; no collapse trap |
| Public jobs pagination + company | `withQueryString()` already on server; fixture adds 9 extra Employer A published jobs | PHPUnit pagination test + manual page-2 flow |
| CAN-02 reload | No product defect found | Manual PASS on bio + designation persistence |

---

## 5. P0 items still not covered locally

| Item | Status |
| --- | --- |
| Staging smoke / journeys | **Not authorized** |
| Single archived run of **all** registry journeys in one pytest session | **Not executed** |
| `JRN-CROSS-ROLE-01` / new-apply journey | Reused prior PASS only |
| Employer P1 surfaces (staffing, partnership, automations) | PHPUnit only; limited human QA |
| Full smoke registry (`JRN-SMOKE-*`) | Partial |

---

## 6. QA reports (HTML/JSON)

| Run ID | Report path | Typical `ci_exit_code` |
| --- | --- | --- |
| `20261010T054124Z-a53f91f2` | `qa-agent/artifacts/reports/20261010T054124Z-a53f91f2/` | **4** (targeted manual) |
| `20261010T041431Z-9b88be75` | `qa-agent/artifacts/reports/20261010T041431Z-9b88be75/` | **4** |
| `20261010T030042Z-8d065de8` | `qa-agent/artifacts/reports/20261010T030042Z-8d065de8/` (if generated) | journey batch |

Regenerate: `ournear-qa report --run-id <id> --html --json --ci-exit` from `qa-agent/`.

---

## 7. Gate semantics (unchanged)

- Manual **PASS** does not replace Playwright journey matrix or full PHPUnit as release proof.
- Targeted pytest exit **4** is expected for suite-limited runs.
- Verdict remains **`insufficient_evidence`** until staging and broader scope are satisfied.
