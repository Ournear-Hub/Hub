# Phase F1/F2 — Independent QA report (Agent 2)

**Run ID:** `20261011T001700Z-f1f2-qa`  
**Date:** 2026-10-11 (local session)  
**Role:** Agent 2 — QA only (no application code changes)  
**Implementation reference:** `docs/OURNEAR_PHASE_F1_HOMEPAGE_SERVICE_REPORT.md`  
**Register item:** REG-PROD-004  
**Evidence JSON:** `qa-agent/artifacts/phase_f1_f2/20261011T001700Z-f1f2-qa/f1f2_results.json`  
**Screenshots:** **None** (browser QA blocked — see §2)

---

## 1. Scope

Independent verification of recruiter-first homepage and compact public service pages:

| Route | Page |
| --- | --- |
| `/` | Homepage |
| `/hire` | Hiring |
| `/jobs` | Jobs index |
| `/talent-pool` | Talent Pool |
| `/staffing` | Staffing |

**Viewports required:** desktop **1440×900**, mobile **390×844**.  
**Also in scope:** public navigation regression, jobs **company filter** (PUB-02 / MAN-PUB-02), console errors on these pages.  
**Out of scope (unchanged):** Phase F3 admin nav, C1/C2 journeys, publication policy, admin sidebar.

---

## 2. Environment readiness

| Check | Command | Result | Exit |
| --- | --- | --- | ---: |
| Local config | `php scripts/verify-local-env.php` | **PASS** (`ournear_hire_local`, Vite manifest) | 0 |
| MySQL | `php scripts/probe_local_mysql.php` | **BLOCKED — REG-ENV-001** (`NO_MATCH`; `127.0.0.1:3306` not accepting connections) | 1 |
| App server | Playwright → `http://127.0.0.1:8000/` | **DOWN** (`ERR_CONNECTION_REFUSED`) | — |
| Playwright Chromium | Prior Agent 2 install | **Available** (cannot exercise pages without app) |

**Note:** PHPUnit below uses **SQLite** test harness; it does **not** replace browser/visual F1/F2 acceptance.  
**No** database switch, reset, stash, or application edits were performed.

---

## 3. Commands executed

```text
cd "D:\Ournear Hub\Ournear Hire"
php scripts/verify-local-env.php
php scripts/probe_local_mysql.php
php artisan test --filter=PublicHomePageTest
php artisan test --filter=PublicPlatformPagesTest
php artisan test --filter=ResponsiveStage17Test
php artisan test --filter=QaDefectRegressionTest
cd qa-agent
python -m pytest tests/smoke/test_public_home.py -v --ournear-suite=smoke
```

| Command | Outcome |
| --- | --- |
| `PublicHomePageTest` | **PASS** — 1 test, 13 assertions (~81s) |
| `PublicPlatformPagesTest` | **PASS** — hire, talent-pool, staffing Inertia components (~13s) |
| `ResponsiveStage17Test` | **PASS** — `/`, `/jobs`, `/hire`, `/talent-pool`, `/staffing` return **200** (~71s) |
| `QaDefectRegressionTest` | **PASS** — 8 tests including company filter + pagination (~23s) |
| `test_public_home.py` | **FAIL** — connection refused (infrastructure) |

**Not run (blocked):** `php artisan serve`, `php artisan qa:hiring-fixtures`, `pytest tests/manual/test_manual_evidence_cases.py::test_man_pub_02_*` (live browser + optional fixtures).

---

## 4. Page-by-page browser results (desktop / mobile)

| Page | Desktop 1440×900 | Mobile 390×844 | Notes |
| --- | --- | --- | --- |
| `/` | **BLOCKED** | **BLOCKED** | App not listening |
| `/hire` | **BLOCKED** | **BLOCKED** | |
| `/jobs` | **BLOCKED** | **BLOCKED** | |
| `/talent-pool` | **BLOCKED** | **BLOCKED** | |
| `/staffing` | **BLOCKED** | **BLOCKED** | |

**Routing/rendering, sticky header, mobile menu, overflow, refresh, internal links, images:** **NOT TESTED** (browser).

---

## 5. CTA destination verification (interactive)

| CTA (per F1 report) | Expected (auth-dependent) | Guest browser | Signed-in browser |
| --- | --- | --- | --- |
| Start Hiring (home/header) | `/hire` → guest flows | **BLOCKED** | **BLOCKED** |
| Start Hiring (`/hire` hero) | `/register` or `/account/hiring` | **BLOCKED** | **BLOCKED** |
| Find / Browse Jobs | `/jobs` | **BLOCKED** | **BLOCKED** |
| Request Staffing | `/for-employers` | **BLOCKED** | **BLOCKED** |
| Explore Talent / module cards | `/talent-pool`, `/hire`, etc. | **BLOCKED** | **BLOCKED** |
| Talent Pool primary CTA | `/hire` or `/account/hiring` | **BLOCKED** | **BLOCKED** |

**Static alignment (source only — not a browser PASS):** Implementation report destinations match current React sources (`Home.tsx`, `Hire.tsx`, `Staffing.tsx`, `TalentPool.tsx`, `Header.tsx`). Agent 2 did **not** click or load these in a browser this run.

---

## 6. Company-filter regression

| Layer | Test | Result |
| --- | --- | --- |
| PHPUnit | `QaDefectRegressionTest` — company filter narrows, persists pagination, employer query param | **PASS** |
| Browser | `test_man_pub_02_jobs_search` / pagination manual cases | **BLOCKED** (no app server) |

Filter **behavior change in UI** is **NOT TESTED** this run.

---

## 7. Console errors and navigation regression

| Check | Result |
| --- | --- |
| Console errors on F1/F2 pages | **BLOCKED** |
| Public nav / guest vs auth header | **BLOCKED** |
| Broken images / layout shift | **BLOCKED** |

**Server-side route smoke (PHPUnit):** marketing routes including F1/F2 paths return **200** — **PASS** (`ResponsiveStage17Test`).

---

## 8. Result summary (check counts)

| Status | Count | What it covers |
| --- | ---: | --- |
| **PASS** | 4 | PHPUnit bundles (home Inertia, platform pages, marketing 200s, QA defect regressions) |
| **FAIL** | 1 | Playwright smoke (connection refused — infrastructure) |
| **BLOCKED** | 10+ | 5 pages × 2 viewports; CTAs; console; MAN-PUB-02 browser |
| **NOT TESTED** | — | Guest vs employer vs candidate CTA matrix; sticky/header UX; mobile drawer |

**Confirmed product defects for Agent 1:** **None** from this run (no browser evidence).

**Environment blockers:** **REG-ENV-001** (MySQL down for local app/DB workflows); local **`php artisan serve`** not running.

---

## 9. REG-PROD-004 status

| Field | Value |
| --- | --- |
| Builder (Agent 1) | FIXED — AWAITING QA (unchanged) |
| Agent 2 QA | **NOT QA-CONFIRMED** |
| Reason | No desktop/mobile screenshots; no interactive CTA or visual checks |

---

## 10. Overall verdict

**INSUFFICIENT EVIDENCE** for Phase F1/F2 browser acceptance.

PHPUnit and route smoke support **no server errors on Inertia render** for scoped routes and **regression company-filter logic**, but release-style F1/F2 sign-off requires a **new browser pass** after:

1. Start MySQL → `probe_local_mysql.php` not `NO_MATCH`  
2. `php artisan serve` (or established local process)  
3. Re-run this matrix at **1440×900** and **390×844** with screenshots under a **new run ID**  
4. Optional: `pytest tests/manual/test_manual_evidence_cases.py -k "pub_02"` with fixtures if company labels required  

---

*Agent 2 — no commits, pushes, or code fixes.*
