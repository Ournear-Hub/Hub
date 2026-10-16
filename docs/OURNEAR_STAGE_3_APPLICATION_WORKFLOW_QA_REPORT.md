# OurNear Hire — Stage 3: Candidate Application Workflow QA Report

**Date:** 2026-10-10  
**Environment:** Local `http://127.0.0.1:8000` (`OURNEAR_ALLOW_RUN=local`)  
**Execution:** Playwright Stage 3 suite + PHPUnit application tests; journey logic reused from `JRN-CANDIDATE-APPLY-01` (`run_new_application_steps` / `run_duplicate_guard_steps`).

**Prior stages:** Stage 1 `20261010T064328Z-9a191885`; Stage 2 `20261010T071623Z-2ba908a5` — not re-run.

---

## A. Executive summary

| Metric | Value |
| --- | --- |
| **Checklist cases** | **12** (`qa-agent/registry/stage_3_application_workflow.yaml`) |
| **Browser cases recorded** | **12** (11 tests; STG3-INT-001 recorded with STG3-APPLY-001) |
| **PASS** | **12** |
| **FAIL** | **0** |
| **BLOCKED** | **0** |
| **NOT_TESTED (extended)** | See §F |
| **MISSING** | **0** |
| **Product defects fixed** | **0** |
| **Canonical run ID** | **`20261010T073839Z-d2b6b491`** |
| **Stage 3 exit** | **`PASS_WITH_KNOWN_ISSUES`** |

**Release gate:** **`insufficient_evidence`** (unchanged).

**Resume model (verified):** Applications store a **resume snapshot** at submit time; library replace does not alter existing application snapshots (`ApplicationExperienceTest::test_candidate_can_apply_with_a_library_resume_without_merging_the_snapshot`).

---

## B. Guest → login → apply

| Case | Result | Evidence |
| --- | --- | --- |
| STG3-AUTH-001 | Guest `/jobs/qa-fixture-published-a/apply` → `/login`; intended URL in **session** (not query string) | `.../STG3-AUTH-001/guest-redirect.png` |
| STG3-AUTH-002 | Draft apply → **404** | `.../STG3-AUTH-002/draft-apply.png` |

---

## C. Application submission & duplicate prevention

| Case | Result | Notes |
| --- | --- | --- |
| STG3-APPLY-001 | **PASS** — provision job → apply → confirmation → `/account/applications` → reload → duplicate guard | Reuses `JRN-CANDIDATE-APPLY-01` steps |
| STG3-INT-001 | **PASS** — `ONT-A-*` on confirmation | Same run as APPLY-001 |
| STG3-APPLY-002 | **PASS** — fixture job A: **Applied**; `/apply` redirect blocked | No new submission |
| STG3-APPLY-003 | **PASS** — apply page shows resume-ready copy | Published B or redirect if already applied |

Server-side duplicate enforcement confirmed in PHPUnit (`duplicate_post`, `duplicate candidate cannot apply`).

---

## D. Application history & timeline

| Case | Result | Evidence |
| --- | --- | --- |
| STG3-HIST-001 | List shows fixture application / ONT-A | `.../STG3-HIST-001/list.png` |
| STG3-HIST-002 | Hard reload preserves list | `.../STG3-HIST-002/reload.png` |
| STG3-HIST-003 | Detail page shows timeline/status | `.../STG3-HIST-003/detail.png` |
| STG3-HIST-004 | Logout → login; same ONT-A visible | `.../STG3-HIST-004/after-relogin.png` |
| STG3-HIST-005 | Filters All / Active / Completed / Rejected | `.../STG3-HIST-005/filters.png` |
| STG3-MOB-001 | Mobile list 390×844 | `.../STG3-MOB-001/mobile-list.png` |

---

## E. Authorization & data integrity (automated)

| Area | Evidence |
| --- | --- |
| Cross-candidate application access denied | `ApplicationExperienceTest::test_candidate_cannot_access_another_candidates_application_or_change_status` |
| Guest/employer cannot open candidate history | `ApplicationExperienceTest::test_guest_and_employer_cannot_open_application_history` |
| Snapshot vs library | `ApplicationExperienceTest::test_candidate_can_apply_with_a_library_resume_without_merging_the_snapshot` |
| Guest apply no DB row | `ApplicationExperienceTest::test_guest_apply_redirects_to_login_and_does_not_create_an_application` |

Browser **NOT_TESTED:** employer resume download UI, admin workspace (Stage 4+).

---

## F. Extended inventory — browser coverage

| Topic | Status | Notes |
| --- | --- | --- |
| Post-login intended URL lands on apply page | **NOT_TESTED** | Session-based; STG3-AUTH-001 proves guest intended store only |
| Double-click submit / refresh mid-POST | **PASS** (gap) | **STG3-GAP-A** — `20261010T081721Z-77729f53`; 2 POST responses, **1** DB row |
| Apply rate limit 8/hour | **Harness only** | Local `8/hour`; gap runs use `php artisan cache:clear` between heavy suites |
| Closed job apply (public paths) | **PASS** (gap) | **STG3-GAP-C** — closed `qa-fixture-*` → **404** show/apply; count **0** |
| Resume replace after apply (candidate view) | **PASS** (gap) | **STG3-GAP-D** — snapshot filename unchanged after library replace |
| Withdraw application | **PASS** (gap) | **STG3-GAP-B** — confirm dialog, **Withdrawn**, persists reload |
| Invalid file upload on apply page | **PHPUnit only** | `JobApplicationTest` |
| Concurrent apply requests | **NOT_TESTED** | No parallel POST harness |
| Employer view of snapshot resume | **NOT_TESTED** | Stage 4+ employer workspace |

---

## G. Defect register

No confirmed Stage 3 product defects. Harness fixes: guest intended assertion (session not URL); test order/sleep for login throttle after relogin test.

---

## H. Automated test results

| Command | Exit | Result |
| --- | --- | --- |
| `pytest tests/stage3 --ournear-suite stage3` | **0** | **11 passed**, ~155s |
| `php artisan test --filter=ApplicationExperienceTest` | **0** | **12 passed**, 178 assertions |
| `php artisan test --filter=JobApplicationTest` | **0** | **5 passed**, 38 assertions |
| `php scripts/qa-provision-journey-apply-job.php` | **0** | Fresh journey apply job per E2E run |
| `php artisan qa:hiring-fixtures` | **0** | Fixture A application + resumes |
| `JRN-CANDIDATE-APPLY-01` pytest module | **Not run separately** | Logic executed inside STG3-APPLY-001/002; standalone import path issue (`tests.journey`); Stage 3 suite is canonical browser proof |
| Full `php artisan test` | **Not re-run** | Baseline **712 passed** (2026-10-10) — no product PHP changes |
| `tsc` / `npm run build` | **Not run** | No frontend product changes |

---

## I. Artifacts

| Item | Path |
| --- | --- |
| Registry | `qa-agent/registry/stage_3_application_workflow.yaml` |
| Tests | `qa-agent/tests/stage3/test_stage3_application.py` |
| Results JSON | `qa-agent/artifacts/stage3/20261010T073839Z-d2b6b491/stage3_results.json` |
| Screenshots | `qa-agent/artifacts/stage3/evidence/20261010T073839Z-d2b6b491/STG3-*/` |
| Run record | `qa-agent/artifacts/20261010T073839Z-d2b6b491/run-record.json` |

---

## J. Stage exit

**`PASS_WITH_KNOWN_ISSUES`** — core checklist (**12/12**, run **`20261010T073839Z-d2b6b491`**) plus gap closure (**5/5**, run **`20261010T081721Z-77729f53`**) cover apply, history, duplicate guard, closed-job rejection, withdraw, and resume snapshot integrity in browser. Remaining §F items: post-login intended landing, concurrent apply, invalid-file browser matrix, employer snapshot UI.

---

## L. Gap closure addendum (2026-10-10)

| Item | Value |
| --- | --- |
| **Gap run ID** | **`20261010T081721Z-77729f53`** |
| **Command** | `pytest tests/stage3/test_stage3_gap_closure.py --ournear-suite stage3_gap_closure` |
| **Result** | **5 passed**, ~137s, exit **0** |
| **Registry** | `qa-agent/registry/stage_3_gap_closure.yaml` |
| **Results JSON** | `qa-agent/artifacts/stage3/20261010T081721Z-77729f53/stage3_gap_results.json` |
| **Evidence** | `qa-agent/artifacts/stage3/evidence/20261010T081721Z-77729f53/STG3-GAP-*/` |
| **Canonical checklist** | **`20261010T073839Z-d2b6b491`** — **not re-run** |

| Case | Status | Summary |
| --- | --- | --- |
| STG3-GAP-A | **PASS** | Double-click submit; reload; revisit `/apply` → **Applied**; `application_count=1` |
| STG3-GAP-B | **PASS** | Withdraw confirm → flash + **Withdrawn** badge; button hidden after reload |
| STG3-GAP-C | **PASS** | Closed fixture job → **404** (guest + candidate); no application row |
| STG3-GAP-D | **PASS** | Snapshot **Application resume.pdf** unchanged after **qa-gap-snapshot-replace.pdf** library replace |
| STG3-GAP-E | **PASS** | Journey evidence sufficient via STG3-APPLY-001/002; journey pytest import fixed |

**Harness / scripts (no product PHP changes):** `test_stage3_gap_closure.py`, `stage_3_gap_closure.yaml`, `qa-application-count.php`, `qa-close-fixture-job.php`, provision script skips closed jobs, `test_candidate_apply_journey.py` import fix.

**Fresh PHPUnit (gap session):** `php artisan test --filter=ApplicationExperienceTest` — **12 passed**, 178 assertions, exit **0**.

**Defects fixed:** **0** product defects; server duplicate prevention validated under double POST (GAP-A).

---

## K. Stage 4 preview — employer registration, profile, jobs

1. Employer registration/login and `/account` employer dashboard.  
2. Company profile view/edit persistence.  
3. Job list, create, edit, publish/draft/lifecycle (employer workspace).  
4. Reuse `JRN-EMPLOYER-*`, `EmployerHiringStage*Test`, employer QA credentials.  
5. **Local scope:** Stage 3 checklist + §F gap items **A–E** closed (**20261010T081721Z-77729f53**); Stage 4 may proceed under **`PASS_WITH_KNOWN_ISSUES`** and release gate **`insufficient_evidence`** (staging still required for release).
