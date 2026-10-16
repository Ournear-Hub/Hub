# OurNear Hire — Stage 8: Candidate ↔ Admin Workflow QA Report

**Date:** 2026-10-10  
**Environment:** Local `http://127.0.0.1:8000` (`OURNEAR_ALLOW_RUN=local`)  
**Execution:** Playwright Stage 8 suite (`JRN-CANDIDATE-ADMIN-01`); PHPUnit `ApplicationExperienceTest`.

**Prior stages:** Stage 7 `20261010T094758Z-4a609602` on **`ONT-A-000051`** — shortlist notification and employer UI verified there; not re-run.

---

## A. Executive summary

| Metric | Value |
| --- | --- |
| **Registry cases** | **15** |
| **Browser cases recorded** | **15** |
| **PASS** | **13** |
| **FAIL** | **0** |
| **BLOCKED** | **0** |
| **NOT_TESTED** | **1** (Interview status email) |
| **MISSING** | **1** (post-submit correction-request workflow) |
| **Product defects fixed** | **0** (QA timeline heading locator only) |
| **Canonical run ID** | **`20261010T100517Z-e9b1af00`** |
| **Stage 8 exit** | **`PASS_WITH_KNOWN_ISSUES`** |

**Release gate:** unchanged — **`insufficient_evidence`**.

**Correlation application:** `ONT-A-000051` (id **51**). End state after Stage 8: admin status **Interview**; candidate detail shows **Interview**; resume snapshot **`Application resume.pdf`** unchanged after profile navigation.

---

## B. Candidate application visibility

| Case | Result | Notes |
| --- | --- | --- |
| STG8-CAND-001 | **PASS** | List shows `ONT-A-000051` |
| STG8-CAND-002 | **PASS** | Detail **Shortlisted** (pre-admin-update baseline in same session) |
| STG8-CAND-003 | **PASS** | **Timeline** lists submitted → under review → shortlisted |
| STG8-CAND-004 | **PASS** | `?filter=active` includes record |
| STG8-CAND-005 | **PASS** | Reload + logout/re-login |
| STG8-CAND-006 | **PASS** | After admin PATCH, candidate sees **Interview** |

Resume snapshot download link present on detail (**Stage 3** snapshot model reused, not re-audited).

---

## C. Admin candidate/application access

| Case | Result | Notes |
| --- | --- | --- |
| STG8-ADM-001 | **PASS** | Drawer for `ONT-A-000051` |
| STG8-ADM-002 | **PASS** | **Candidate** tab + link to `/admin/candidates` |
| STG8-ADM-003 | **PASS** | Status **Shortlisted → Interview** via Overview combobox; persists after reload |

Full Stage 6 admin handoff not repeated.

---

## D. Profile / application corrections

| Case | Result | Notes |
| --- | --- | --- |
| STG8-CORR-001 | **PASS** | `/account/profile` reachable; application snapshot filename unchanged |
| STG8-CORR-002 | **MISSING** | No dedicated “correction request” workflow — supported model is profile edit + immutable application snapshot |

---

## E. Notifications and links

| Case | Result | Notes |
| --- | --- | --- |
| STG8-NOTIF-001 | **PASS** | Stage 7 shortlist notification visible; **Mark read** + reload (no duplicate shortlist action this run) |
| STG8-NOTIF-002 | **NOT_TESTED** | No email mailable for **Interview** status change |

Deep link behavior for shortlist remains **applications index** (Stage 7 evidence).

---

## F. Authorization and integrity

| Case | Result |
| --- | --- |
| STG8-AUTH-001 | **PASS** — non-owner direct URL forbidden on `/account/applications/51` |
| STG8-AUTH-002 | **PASS** — candidate denied admin applications workspace |

PHPUnit: `ApplicationExperienceTest::test_candidate_cannot_access_another_candidates_application_or_change_status` (not re-run as separate browser case).

---

## G. Defects and harness notes

| Issue | Resolution |
| --- | --- |
| Timeline heading locator expected “Application timeline” | UI heading is **Timeline** — fixed in `candidate_admin_workflow.py` |

---

## H. Manual browser evidence

| Artifact | Path (under `qa-agent/artifacts/`) |
| --- | --- |
| Workflow complete | `stage8/evidence/20261010T100517Z-e9b1af00/STG8-JRN/workflow-complete.png` |
| Auth — non-owner | `stage8/evidence/20261010T100517Z-e9b1af00/STG8-AUTH-001/forbidden.png` |
| Auth — admin denied | `stage8/evidence/20261010T100517Z-e9b1af00/STG8-AUTH-002/admin-denied.png` |

Results JSON: `stage8/20261010T100517Z-e9b1af00/stage8_results.json`

---

## I. Automation

| Command | Exit | Result |
| --- | ---: | --- |
| `python -m pytest tests/stage8/test_stage8_candidate_admin.py -v --ournear-suite stage8` | **0** | **3 passed** (~107s) |
| `php artisan test --filter=ApplicationExperienceTest` | **0** | **12 passed** (178 assertions) |

---

## J. Dependencies and risks

- Employer decision UI remains **MISSING** (Stages 5–7); out of Stage 8 scope.  
- **`ONT-A-000051`** is now **Interview** — later stages should account for status or use another fixture.  
- Staging / release gate not executed.

---

## K. Stage exit and Stage 9 gate

**`PASS_WITH_KNOWN_ISSUES`** — Candidate ↔ admin visibility, admin status update with candidate **Interview** visibility, notifications, snapshot integrity, and auth boundaries evidenced.

**Stage 9 may begin** when authorized (**employer ↔ candidate** workflow per release plan).

**Release verdict:** **`insufficient_evidence`**.
