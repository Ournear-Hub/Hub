# Agent 2 — Phase G1 employer pipeline browser QA

**Run ID:** `20261011T043000Z-g1-pipeline-qa`  
**Date:** 2026-10-11  
**Role:** Agent 2 (QA only)  
**Reference:** `docs/OURNEAR_PHASE_G1_EMPLOYER_PIPELINE_REPORT.md`  
**Artifacts:** `qa-agent/artifacts/agent2/20261011T043000Z-g1-pipeline-qa/` (`g1_results.json`, `g1-*.png`, `g1_browser_qa.py`)

---

## 1. Environment

| Check | Result |
| --- | --- |
| `php scripts/probe_local_mysql.php` | **PASS** — `CONNECTED`, exit **0** |
| **REG-ENV-001** | **PASS** |
| Application | `php artisan serve` on `127.0.0.1:8000` — **200** |
| Fixtures | **NOT RUN** (`fixtures_ready=yes`, journey capacity OK) |

**Not re-run:** Agent 1 PHPUnit (`EmployerHiringStage25Test`, `EmployerSelfServiceRecruitmentTest`), typecheck, build.

---

## 2. Commands

```powershell
cd "D:\Ournear Hub\Ournear Hire"
php scripts/probe_local_mysql.php

cd qa-agent
$env:OURNEAR_ALLOW_RUN="local"
python artifacts/agent2/20261011T043000Z-g1-pipeline-qa/g1_browser_qa.py

python -m pytest tests/journey/test_cross_role_e2e.py -v --ournear-suite=journey
python -m pytest tests/journey/test_screening_comparison_browser.py -v --ournear-suite=journey
```

| Command | Exit | Result |
| --- | ---: | --- |
| `g1_browser_qa.py` | **0** | 9/9 scripted G1 scenarios **PASS** |
| C1 cross-role | **0** | **PASS** (~110s) |
| C2 screening | **1** | **FAIL** — compare panel candidate name not visible (recurrence) |

---

## 3. Phase G1 results

| ID | Scenario | Result | Notes |
| --- | --- | --- | --- |
| **A** | Hiring-wide pipeline load (8 stage chips) | **PASS** | `g1-hiring-wide-desktop.png` |
| **A** | `?stage=under_review` filter + reload preserve | **PASS** | `g1-stage-under-review.png` |
| **A** | Clear filter via **All** chip | **PASS** | |
| **B** | Per-job applicants page | **PASS** | `g1-job-applicants.png` |
| **C** | Application detail ATS/pipeline labels | **PASS** | `g1-application-detail.png` |
| **D** | Resume download/view (Employer A) | **PASS** | HTTP **200** |
| **D** | Shortlist / reject / interview / messaging / corrections | **NOT TESTED** | No destructive mutation in this script |
| **E** | Cross-employer isolation (B → A detail/resume) | **PASS** | **403/404** |
| **F** | Mobile pipeline + detail layout | **PASS** | `g1-hiring-wide-mobile.png`, `g1-detail-mobile.png` |

**G1 counts:** **PASS 9** · **NOT TESTED 1** (extended self-service actions) · **FAIL 0**

---

## 4. Priority B (partial)

| Scope | This run | Notes |
| --- | --- | --- |
| **C1** | **PASS** | Slug cap not blocking |
| **C2** | **FAIL** | REG-BRW-004 — panel text assertion |
| **F3** admin sidebar | **NOT RE-RUN** | Prior **PARTIAL** — `20261011T011700Z-targeted-exec` |
| **F1/F2** public pages | **NOT RE-RUN** | Prior **PARTIAL** — same run |
| **REG-PROD-006** C3 photo | **NOT RE-RUN** | Prior browser **FAIL** — `20261011T014500Z-c3-photo-qa` |

---

## 5. Defects for Agent 1

| ID | Area | Summary |
| --- | --- | --- |
| **REG-BRW-004** | C2 screening compare | Compare panel does not show selected candidate name (exact text) — unchanged from prior exec |

No new G1 pipeline defects filed this run.

---

## 6. Register / release

| ID | QA status (this run) |
| --- | --- |
| **REG-ENV-001** | **PASS** |
| **REG-PROD-001** | **PARTIAL** — core G1 browser **PASS**; full self-service action matrix **NOT TESTED** |
| **REG-BRW-003** | **PASS** (C1) |
| **REG-BRW-004** | **FAIL** (C2) |

**Verdict:** **`insufficient_evidence`**

---

*Agent 2 — no code changes.*
