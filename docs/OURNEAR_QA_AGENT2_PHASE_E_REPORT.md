# Agent 2 — Phase E job cards & classifications browser QA

**Run ID:** `20261011T062000Z-phase-e-qa`  
**Date:** 2026-10-11  
**Role:** Agent 2 (QA only)  
**Reference:** `docs/OURNEAR_PHASE_E_JOB_CARDS_CLASSIFICATIONS_REPORT.md`  
**Artifacts:** `qa-agent/artifacts/agent2/20261011T062000Z-phase-e-qa/` (`phase_e_results.json`, `e-*.png`, `phase_e_browser_qa.py`)

---

## 1. Environment

| Check | Result |
| --- | --- |
| `php scripts/probe_local_mysql.php` | **PASS** — `CONNECTED`, exit **0** |
| **REG-ENV-001** | **PASS** |
| Application | `http://127.0.0.1:8000` — **200** |
| Fixtures | **NOT RUN** |

**Not re-run:** `PhaseEJobCardsClassificationTest`, `QaDefectRegressionTest`, `PublicWebsiteTest`, `JobsWorkspaceTest`, typecheck, build.

---

## 2. Commands

```powershell
cd "D:\Ournear Hub\Ournear Hire"
php scripts/probe_local_mysql.php

cd qa-agent
python artifacts/agent2/20261011T062000Z-phase-e-qa/phase_e_browser_qa.py
python -m pytest tests/manual/test_manual_evidence_cases.py::test_man_pub_02_jobs_search -v --ournear-suite=manual
```

| Command | Exit | Result |
| --- | ---: | --- |
| `phase_e_browser_qa.py` | **0** | 12/12 scripted Phase E scenarios **PASS** |
| `test_man_pub_02_jobs_search` | **1** | **FAIL** — “No matching roles” after company filter |

---

## 3. Phase E browser results

| Scenario | Desktop | Mobile |
| --- | --- | --- |
| Home **Open roles** cards (6) with classification line (`·`) | **PASS** | **PASS** |
| Home card click → job detail URL | **PASS** | **PASS** |
| Featured badge visible on home | **PASS** (observed) | **PASS** |
| `/jobs` filter options include Construction / Civil | **PASS** | **PASS** |
| URL filter `?industry=Construction&category=Civil` reflects in results | **PASS** | **PASS** |
| Combined `industry` + `company` query params | **PASS** | **PASS** |
| No horizontal overflow | **PASS** | **PASS** |

**Screenshots (artifact dir):** `e-home-desktop.png`, `e-home-mobile.png`, `e-home-detail-*.png`, `e-jobs-index-*.png`, `e-jobs-filtered-*.png`, `e-jobs-industry-filter-*.png`, `e-jobs-company-combo-*.png`.

**Phase E gaps (not in script — NOT TESTED this run):** interactive Search-button filter apply (URL query used instead); pagination + filter retention; clear-filters restore; explicit side-by-side legacy-string vs FK-only job card parity; per-field location-only missing-classification matrix.

**Deferred scope (unchanged):** skills on jobs, Saved Jobs unification — **not tested** as defects. **Employer Not Verified** badge observed on cards (Phase E Agent 1 noted REG-PHP-003 product question) — **not scored** as new defect.

**Phase E script counts:** **PASS 12** · **FAIL 0** · **BLOCKED 0** · **NOT TESTED 4+** (gaps above).

---

## 4. Regression — company filter (MAN-PUB-02)

| Expected | Actual |
| --- | --- |
| Company filter narrows to fixture employer jobs | **FAIL** — heading **“No matching roles”** after filter (`test_man_pub_02_jobs_search`, ~15s) |

**Note:** URL combo `?company=Bartell%20LLC&industry=Construction` **PASS** in Phase E script; MAN-PUB-02 uses UI Search + **`QA Hiring Co A`** — failure may be fixture/data or UI path specific.

**Agent 1:** Investigate public jobs company filter vs live fixture data (`QA Hiring Co A` / company dropdown values). May affect **REG-PHP-002** browser evidence.

**Reproduction (MAN-PUB-02):**

1. Open `http://127.0.0.1:8000/jobs`.
2. Keyword **QA Fixture Published Role** → Search → expect job link `qa-fixture-published-a`.
3. Select company **QA Hiring Co A** → Search.
4. **Actual:** heading **No matching roles** (expected: filtered results).

---

## 5. Priority B — other scopes (this run)

Reused prior Agent 2 evidence; **not re-executed** in this batch:

| Scope | Last evidence | This run |
| --- | --- | --- |
| **G1** pipeline | **PARTIAL PASS** — `20261011T043000Z-g1-pipeline-qa` | **NOT RE-RUN** |
| **C3** photo | **FAIL** — `20261011T014500Z-c3-photo-qa` | **NOT RE-RUN** |
| **F3** admin nav | **PARTIAL** — `20261011T011700Z-targeted-exec` | **NOT RE-RUN** |
| **F1/F2** public | **PARTIAL** — same | **NOT RE-RUN** |
| **C1** | **PASS** — `20261011T043000Z-g1-pipeline-qa` | **NOT RE-RUN** |
| **C2** | **FAIL** — compare panel | **NOT RE-RUN** |

---

## 6. Register / release

| ID | QA status (this run) |
| --- | --- |
| **REG-ENV-001** | **PASS** |
| **REG-PROD-008** | **PARTIAL** — classification/card browser **PASS**; MAN-PUB-02 company filter **FAIL** |
| **REG-PHP-002** | Browser regression **FAIL** (MAN-PUB-02 this run) — PHPUnit not re-run |

**Verdict:** **`insufficient_evidence`**

---

*Agent 2 — no code changes.*
