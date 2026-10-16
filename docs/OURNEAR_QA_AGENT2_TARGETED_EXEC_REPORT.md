# Agent 2 — Targeted QA execution (REG-ENV-001 resolved)

**Run ID:** `20261011T011700Z-targeted-exec`  
**Date:** 2026-10-11  
**Role:** Agent 2 (QA only)  
**Prior continuation:** `20261011T003700Z-targeted-continuation` (preserved)  
**Artifacts:** `qa-agent/artifacts/agent2/20261011T011700Z-targeted-exec/`

---

## 1. Environment

| Step | Command | Exit | Result |
| --- | --- | ---: | --- |
| Probe | `php scripts/probe_local_mysql.php` | **0** | `CONNECTED user=root password_set=yes` |
| Capacity | `php scripts/inspect-qa-journey-capacity.php` | **0** | `fixtures_ready=yes`, `provision_would_succeed=yes`, `cross_role_apply_feasible=yes` |
| Fixtures | `php artisan qa:hiring-fixtures` | — | **NOT RUN** (existing fixtures + 8 provision slots sufficient) |
| Screening seed | `php scripts/qa-provision-screening-compare.php` | **0** | Job `109`, slug `qa-fixture-screening-compare`, 2 applications |
| App | `php artisan serve --host=127.0.0.1 --port=8000` | — | **Running**; home HTTP **200** |

**REG-ENV-001:** **PASS** (this run).

**Skipped reruns:** `verify-local-env.php`, `ResponsiveStage17Test` (already evidenced).

---

## 2. C1 / C2 journeys

| Journey | Command | Result | Evidence |
| --- | --- | --- | --- |
| **C1** cross-role | `pytest tests/journey/test_cross_role_e2e.py -v --ournear-suite=journey` | **PASS** | ~180s; slug cap no longer blocking |
| **C2** screening compare | `pytest tests/journey/test_screening_comparison_browser.py -v --ournear-suite=journey` | **FAIL** | Pytest run id `20261010T192340Z-76069e14`; fails after **Compare selected** |

### C2 defect (Agent 1)

| Field | Value |
| --- | --- |
| **ID** | REG-BRW-004 (recurrence) |
| **Step** | C2-06-open-compare |
| **Expected** | `#screening-compare-panel` shows selected candidate name **QA Screening Applicant 2** (exact text) |
| **Actual** | `get_by_text("QA Screening Applicant 2", exact=True)` not visible within 10s |
| **Repro** | Admin login → `/admin/screening?job_id=109` → select 2 compare checkboxes → **Compare selected** → panel opens but name missing |
| **Priority** | P0 (blocks C2 closure) |

---

## 3. Phase F3 — Admin sidebar (browser)

Super-admin via `qa-agent/config/local.env`. Evidence script + screenshots in artifact folder.

| Check | Result |
| --- | --- |
| Eight groups present (Overview … Administration) | **PASS** |
| Single-expand accordion (Recruitment → Administration) | **PASS** (`expanded_count` ≤ 1 per toggle) |
| Active route sample `/admin/jobs` | **PASS** (`data-active` count **1** when logged in) |
| HCC / Analytics permission smoke (restricted role) | **NOT TESTED** |
| Keyboard / full mobile drawer | **PARTIAL** (mobile screenshot; limited interaction) |
| `/admin/design-system` in sidebar | **Absent** (documented F3 gap — product decision) |
| Console errors (F3 session) | **None** captured |

**REG-PROD-003:** **Not fully QA-confirmed** — core interactive checks **PASS** for super-admin; partial-role and deep a11y **NOT TESTED**.

Screenshots: `f3-admin-*.png` in run folder.

---

## 4. Phase F1/F2 — Public pages (browser)

| Page | Desktop 1440×900 | Mobile 390×844 |
| --- | --- | --- |
| `/` | **PASS** (HTTP 200) | **PASS** |
| `/hire` | **PASS** | **PASS** |
| `/jobs` | **PASS** | **PASS** |
| `/talent-pool` | **PASS** | **PASS** |
| `/staffing` | **PASS** | **PASS** |

**Guest CTAs (recheck `cta_recheck.py`):**

| CTA | Destination | Result |
| --- | --- | --- |
| Start Hiring (home) | `/hire` | **PASS** |
| Find Jobs (home) | `/jobs` | **PASS** |
| Request Staffing (`/staffing`) | `/for-employers` | **PASS** |

Authenticated employer/candidate CTA matrix: **NOT TESTED** this run.

**Company filter:**

| Test | Result |
| --- | --- |
| Evidence script (`#job-company` + Search) | **PASS** |
| `test_man_pub_02_jobs_search` | **PASS** (~28s) |

Screenshots: `public-*-desktop.png`, `public-*-mobile.png`, `jobs-company-filter-desktop.png`.

**REG-PROD-004:** **Not fully QA-confirmed** — desktop/mobile render + guest CTAs + company filter **PASS**; signed-in CTA paths and full visual sign-off checklist **incomplete**.

---

## 5. Summary counts

| Status | Count | Notes |
| --- | ---: | --- |
| **PASS** | 15+ | C1, public 10 viewports, F3 core, CTAs, filters, MAN-PUB-02 |
| **FAIL** | 1 | C2 compare panel |
| **NOT TESTED** | 3+ | Auth CTAs, partial-admin permissions, exhaustive a11y |

---

## 6. Register impact

| ID | New QA status |
| --- | --- |
| **REG-ENV-001** | **PASS** |
| **REG-BRW-003** | **PASS** (this run) |
| **REG-BRW-004** | **FAIL** (compare panel) |
| **REG-PROD-003** | **PARTIAL** (super-admin browser PASS; not full sign-off) |
| **REG-PROD-004** | **PARTIAL** (browser matrix + guest CTAs; auth CTAs open) |

**Release verdict:** **`insufficient_evidence`** — C2 P0 defect open; REG-PROD-003/004 not fully confirmed per register rules.

---

*Agent 2 — no application code changes, commits, or deploys.*
