# OurNear Hire — Stage 9: Employer ↔ Candidate Workflow QA Report

**Date:** 2026-10-10  
**Environment:** Local `http://127.0.0.1:8000` (`OURNEAR_ALLOW_RUN=local`)  
**Execution:** Playwright Stage 9 suite (`JRN-EMPLOYER-CANDIDATE-01`); PHPUnit `RecruitmentOperationsStage6Test`.

**Prior stages:** Stage 8 `20261010T100517Z-e9b1af00` — **`ONT-A-000051`** at **Interview**; shortlist notification and snapshot integrity not re-run.

---

## A. Executive summary

| Metric | Value |
| --- | --- |
| **Registry cases** | **14** |
| **Browser cases recorded** | **14** |
| **PASS** | **10** |
| **FAIL** | **0** |
| **BLOCKED** | **0** |
| **NOT_TESTED** | **0** |
| **MISSING** | **4** (employer pipeline, employer interview UI, employer messaging, candidate interview response) |
| **Product defects fixed** | **0** |
| **Canonical run ID** | **`20261010T101834Z-87fd48ef`** |
| **Stage 9 exit** | **`PASS_WITH_KNOWN_ISSUES`** |

**Release gate:** unchanged — **`insufficient_evidence`**.

**Correlation application:** `ONT-A-000051` (id **51**). Admin **Interview #1** scheduled in this run (phone, Stage 9 QA Recruiter); candidate received **Interview scheduled** in-app notification. Employer and candidate both show **Interview** application status.

---

## B. Employer application visibility

| Case | Result | Notes |
| --- | --- | --- |
| STG9-EMP-001 | **PASS** | Hiring list + detail; **Interview** status and `ONT-A-000051` |
| STG9-EMP-002 | **PASS** | Resume download readable PDF |
| STG9-EMP-003 | **PASS** | Detail persists after reload |
| STG9-EMP-ACT-001 | **MISSING** | Read-only **Application actions** — no pipeline/interview controls |

Stages 5–8 employer-action gap unchanged.

---

## C. Interview workflow

| Case | Result | Notes |
| --- | --- | --- |
| STG9-INT-ADM-001 | **PASS** | Admin drawer **Interview** tab — schedule via PUT `/admin/applications/51/interview` |
| STG9-INT-ADM-002 | **PASS** | Interview row survives reload |
| STG9-INT-EMP-001 | **MISSING** | No employer-side scheduling UI |
| STG9-CAND-001 | **PASS** | In-app **Interview scheduled** notification after admin save |
| STG9-CAND-002 | **PASS** | Candidate detail status **Interview** |

**Observation:** Interview **status** on the application does not imply a scheduled interview until admin saves Interview tab data (Stage 9 proved scheduling in same session). Candidate application **detail does not expose** datetime/location fields in UI — notification + status only (product behavior).

Interview **email** delivery not asserted (Stage 7/8 scope gap remains).

---

## D. Employer-to-candidate communication

| Case | Result | Notes |
| --- | --- | --- |
| STG9-COMM-001 | **MISSING** | `MessageService` — in-app portal messages to **admin users only**; no employer→candidate channel |

---

## E. Candidate responses

| Case | Result | Notes |
| --- | --- | --- |
| STG9-CAND-RESP-001 | **MISSING** | No accept/decline interview or message reply on candidate application UI (withdraw remains separate, Stage 3) |

---

## F. Cross-role consistency and security

| Case | Result |
| --- | --- |
| STG9-X-001 | **PASS** — **Interview** on employer detail and candidate detail |
| STG9-AUTH-001 | **PASS** — Employer B isolation (list/detail/resume) |
| STG9-AUTH-002 | **PASS** — Non-owner forbidden on `/account/applications/51` |

---

## G. Defects

None in product code this stage. QA harness only (`employer_candidate_workflow.py`, Stage 9 tests/registry).

---

## H. Manual browser evidence

| Artifact | Path (under `qa-agent/artifacts/`) |
| --- | --- |
| Workflow | `stage9/evidence/20261010T101834Z-87fd48ef/STG9-JRN/workflow.png` |
| Employer read-only | `stage9/evidence/.../STG9-MISSING/employer-read-only.png` |
| Employer B | `stage9/evidence/.../STG9-AUTH-001/employer-b.png` |
| Auth | `stage9/evidence/.../STG9-AUTH-002/forbidden.png` |

Results: `stage9/20261010T101834Z-87fd48ef/stage9_results.json`

---

## I. Automation

| Command | Exit | Result |
| --- | ---: | --- |
| `python -m pytest tests/stage9/test_stage9_employer_candidate.py -v --ournear-suite stage9` | **0** | **4 passed** (~215s) |
| `php artisan test --filter=RecruitmentOperationsStage6Test` | **0** | **7 passed** (115 assertions) |

Earlier stage browser suites not re-run.

---

## J. Registry

| Item | Location |
| --- | --- |
| Registry | `qa-agent/registry/stage_9_employer_candidate.yaml` |
| Journey | `qa-agent/ournear_qa/journey/employer_candidate_workflow.py` |

---

## K. Dependencies and Stage 10 gate

- Employer self-service hiring actions — **MISSING** (Stages 5–9).  
- Candidate correction-request — **MISSING** (Stage 8).  
- Shortlist/interview **email** — not delivery-tested.  
- **`ONT-A-000051`** now has a scheduled admin interview row — later runs should treat schedule step as idempotent (verify existing).

**Stage 10 may begin** when authorized (security/recovery / next gate in release plan).

**Release verdict:** **`insufficient_evidence`**.
