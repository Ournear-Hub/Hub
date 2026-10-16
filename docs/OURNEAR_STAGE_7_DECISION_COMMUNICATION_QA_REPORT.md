# OurNear Hire — Stage 7: Decision & Communication Workflow QA Report

**Date:** 2026-10-10  
**Environment:** Local `http://127.0.0.1:8000` (`OURNEAR_ALLOW_RUN=local`)  
**Execution:** Playwright Stage 7 suite (`JRN-DECISION-COMM-01`); PHPUnit `CommunicationLifecycleTest`, `ApplicationExperienceTest`.

**Prior stages:** Stage 6 `20261010T091906Z-56bd4047` on **`ONT-A-000051`** / job **`qa-fixture-journey-apply-54`** — not re-run.

---

## A. Executive summary

| Metric | Value |
| --- | --- |
| **Registry cases** | **12** |
| **Browser cases recorded** | **12** |
| **PASS** | **10** |
| **FAIL** | **0** |
| **BLOCKED** | **0** |
| **NOT_TESTED** | **1** (shortlist email — product has no mailable) |
| **MISSING** | **1** (employer self-service decision UI — Stage 5/6 dependency) |
| **Product defects fixed** | **0** (QA journey locators only; see §I) |
| **Canonical run ID** | **`20261010T094758Z-4a609602`** |
| **Stage 7 exit** | **`PASS_WITH_KNOWN_ISSUES`** |

**Release gate:** unchanged — **`insufficient_evidence`**.

**Correlation application:** `ONT-A-000051` (id **51**). Admin **shortlist** (Screening tab → confirm → POST) was executed successfully during the Stage 7 harness iteration immediately before the canonical run (status already **Shortlisted** at canonical preflight). Canonical run verified **persistence**, **employer visibility**, **candidate in-app notification**, **deep link**, and **timeline** for that decision.

---

## B. Admin screening and decision

| Case | Result | Notes |
| --- | --- | --- |
| STG7-ADM-001 | **PASS** | Admin workspace; shortlist confirmed via `/admin/applications/{id}/shortlist` (fresh action in pre-canonical iteration) |
| STG7-ADM-002 | **PASS** | **Shortlisted** status on Overview combobox survives reload |

Supported actions observed: Screening tab controls (Start, Match, Contact, **Shortlist**, Hold, Reject, **Submit to Employer**). **Submit to Employer** browser workflow not exercised in this run (shortlist loop was the decision under test).

---

## C. Admin → employer handoff

| Case | Result | Notes |
| --- | --- | --- |
| STG7-EMP-001 | **PASS** | Employer A application detail shows **Shortlisted** after admin decision |
| STG7-EMP-002 | **PASS** | `/account/notifications` loads for employer (no cross-leak assertion beyond Stage 6 patterns) |
| STG7-EMP-ACT-001 | **MISSING** | **Application actions** remain read-only — no employer shortlist/reject/interview/response |

Employer does **not** receive a dedicated “shortlisted” in-app notification (by design — `EmployerNotificationService` only handles new application received).

---

## D. Employer → admin return handoff

| Area | Result |
| --- | --- |
| Employer decision mechanism | **MISSING** (same as **STG7-EMP-ACT-001**) |
| Admin records decision | **PASS** (admin shortlist + screening timeline shows Shortlisted / status change rows) |
| Admin **Submit to Employer** after shortlist | **NOT_TESTED** in browser this run (button enabled in UI snapshot; out of minimal loop scope) |

---

## E. Admin → candidate visibility

| Case | Result | Notes |
| --- | --- | --- |
| STG7-CAND-001 | **PASS** | In-app notification with shortlisted copy on `/account/notifications` |
| STG7-CAND-002 | **PASS** | `/account/applications/51` shows **Shortlisted** and timeline |
| STG7-CAND-003 | **PASS** | **Open related page** → `/account/applications` (index) |

Candidate notification deep link targets applications **index**, not detail id (product behavior per `CandidateNotificationService`).

---

## F. Notifications, email, templates

| Case | Result | Notes |
| --- | --- | --- |
| STG7-NOTIF-001 | **PASS** | Event/body references shortlist for journey-apply job title |
| STG7-EMAIL-001 | **NOT_TESTED** | `RecruitmentNotificationService` queues email for **Rejected/Selected/Interview** only — **no shortlist mailable** |

Template/WhatsApp channels not in scope for this loop.

---

## G. Cross-role consistency and authorization

| Case | Result |
| --- | --- |
| STG7-X-001 | **PASS** — **Shortlisted** aligned across admin drawer, employer detail, candidate detail |
| STG7-AUTH-001 | **PASS** — non-owner account forbidden on `/account/applications/51` (Employer B URL probe; candidate isolation in PHPUnit) |

---

## H. Known dependencies

1. **Employer pipeline/decision UI** — **MISSING** (**STG7-EMP-ACT-001** / Stages 5–6).  
2. **Shortlist email** — not implemented (**STG7-EMAIL-001** NOT_TESTED).  
3. **Stage 8** (dedicated candidate ↔ admin cycle) — not started.  
4. Staging / release gate — not executed.

---

## I. Defects, retests, harness changes

| Summary | Root cause | Fix |
| --- | --- | --- |
| Early Stage 7 runs failed on admin shortlist locators | **Shortlist** lives on **Screening** tab; confirm modal is separate `dialog` | `decision_communication.py` — Screening tab, `dialog` name `Shortlist this candidate?`, re-check status on **Overview** |

**QA files added/updated:**  
`qa-agent/registry/stage_7_decision_communication.yaml`, `qa-agent/tests/stage7/*`, `qa-agent/ournear_qa/journey/decision_communication.py`, `qa-agent/pyproject.toml` (stage7 marker).

---

## J. Manual browser evidence

| Artifact | Path (under `qa-agent/artifacts/`) |
| --- | --- |
| Decision loop complete | `stage7/evidence/20261010T094758Z-4a609602/STG7-JRN/decision-loop-complete.png` |
| Employer read-only actions | `stage7/evidence/20261010T094758Z-4a609602/STG7-EMP-ACT-001/employer-read-only.png` |
| Auth probe | `stage7/evidence/20261010T094758Z-4a609602/STG7-AUTH-001/other-candidate-forbidden.png` |

---

## K. Automation

| Command | Exit | Result |
| --- | ---: | --- |
| `python -m pytest tests/stage7/test_stage7_decision_communication.py -v --ournear-suite stage7` | **0** | **3 passed** (~109s); `stage7/20261010T094758Z-4a609602/stage7_results.json` |
| `php artisan test --filter=CommunicationLifecycleTest` | **0** | **6 passed** (87 assertions) |
| `php artisan test --filter=ApplicationExperienceTest` | **0** | **12 passed** (178 assertions) |

Stages 1–6 browser suites not re-run.

---

## L. Registry

| Item | Location |
| --- | --- |
| Registry | `qa-agent/registry/stage_7_decision_communication.yaml` |
| Journey | `JRN-DECISION-COMM-01` in `qa-agent/ournear_qa/journey/decision_communication.py` |

---

## M. Stage exit and Stage 8 gate

**`PASS_WITH_KNOWN_ISSUES`** — Admin shortlist decision propagates to employer labels, candidate in-app notification, and candidate application timeline for **`ONT-A-000051`**. Employer cannot complete a reciprocal decision in UI (**MISSING**). Shortlist email not sent by product (**NOT_TESTED**).

**Stage 8 may begin** when authorized (candidate ↔ admin communication cycle), without reopening Stages 1–7 closure evidence.

**Release verdict:** **`insufficient_evidence`** (local only; staging unverified).
