# REG-PROD-001 — Employer recruitment pipeline browser verification

**Date:** 2026-10-14  
**Workspace:** `C:\Ournear Hub\Ournear Hire`  
**Issue register:** `REG-PROD-001` → **CLOSED — PASS** (local browser + targeted PHPUnit)

## Summary

Employer job-scoped and hiring-wide ATS pipeline UI, applicant detail, resume delivery, cross-employer isolation, and employer self-service transitions (shortlist, interview schedule, reject, messaging) were re-verified in Chromium against local MySQL fixtures. No product-code changes were required; qa-agent journey harnesses were synced to current account home and application UI.

## Environment

| Check | Result |
| --- | --- |
| MySQL | `scripts/probe_local_mysql.php` — **CONNECTED** |
| App | `http://127.0.0.1:8000/` — **HTTP 200** |
| Playwright | `qa-agent/.venv/Scripts/python.exe` + `OURNEAR_ALLOW_RUN=local` |
| Fixtures | `qa-employer-a@example.test`, `qa-employer-b@example.test`, `qa-candidate@example.test` (`qa-agent/config/local.env`) |

## Coverage reused (not re-run without cause)

| Evidence | Scope |
| --- | --- |
| `qa-agent/artifacts/agent2/20261011T043000Z-g1-pipeline-qa/g1_results.json` | Hiring-wide pipeline load/filter/reload, per-job applicants, detail ATS labels, resume HTTP 200, cross-employer block, mobile — **9/9 PASS** (re-executed 2026-10-14; JSON unchanged) |
| `docs/OURNEAR_QA_EXECUTION_20261012T100000Z.md` | Prior PHPUnit pipeline tests (superseded by 2026-10-14 re-run below) |

## Browser journeys executed (2026-10-14)

| Journey | Role / fixture | Stages verified | Result |
| --- | --- | --- | --- |
| G1 pipeline QA | Employer A | Open `/account/hiring/applications`, stage chips/filter/reload; per-job applicants; detail labels; resume GET 200; Employer B isolation; mobile | **PASS** (9/9) |
| `test_reg_prod_001_employer_reject_persists_after_reload` | Candidate apply → Employer A | Fresh apply on provisioned journey job; reject with reason; reload detail; `qa-application-status-probe.php` **Rejected** | **PASS** — evidence `qa-agent/artifacts/agent2/20261014T120000Z-reg-prod-001-browser/` |
| `test_jrn_employer_resume_owner_workflow` | Employer A; `inspect-qa-employer-journey-target.php` | Applicants list → application detail → inline View + Download PDF → reload → hiring-wide list shows `business_id` | **PASS** |
| `test_jrn_employer_resume_employer_b_isolation` | Employer B | Cannot access Employer A resume routes | **PASS** |
| `test_stg11_self_service_end_to_end` | Employer A + Candidate | Shortlist (when eligible) → schedule interview → employer message → candidate application detail (interviews/messages) | **PASS** — `qa-agent/artifacts/stage11/evidence/` (run from pytest session) |

### Status transitions (browser)

| Action | Route | Prior → result | Persistence |
| --- | --- | --- | --- |
| Reject | `POST …/reject` on journey apply job | **New** → **Rejected** | Detail reload shows Rejected; probe `status=Rejected` |
| Shortlist | Self-service workflow on provisioned published job | **New** → **Shortlisted** (when button enabled) | Reload via workflow steps |
| Schedule interview | Application Show | After shortlist → interview scheduled | Candidate **Interviews** heading visible |
| Messaging | Application Show | N/A | Send message step completes |

**Not browser-verified (by design):** Employer UI does not expose admin-only **Selected / Offer / Joined** transitions; those remain admin recruitment steps on the same application record. No register gap for REG-PROD-001 on those stages.

**Notifications:** Not asserted in this REG-PROD-001 batch (covered elsewhere by REG-BRW-003 / PHPUnit).

## Targeted PHPUnit (2026-10-14)

```text
php artisan test tests/Feature/EmployerJobApplicantPipelineTest.php
php artisan test tests/Feature/EmployerHiringApplicationsPipelineTest.php
php artisan test tests/Feature/EmployerSelfServiceRecruitmentTest.php
```

**Result:** **17 passed** (128 assertions), ~32s.

## qa-agent harness changes (non-product)

| File | Reason |
| --- | --- |
| `ournear_qa/journey/account_auth.py` | Post-login home: **Personal Dashboard** (candidate) or **Company workspace** (employer), not h1 “Dashboard” |
| `ournear_qa/journey/candidate_apply.py` | Applications list shows job title, not public `business_id` |
| `ournear_qa/journey/employer_resume.py` | Resume block is `region` “Applicant resume overview”, not `section.rounded-lg` |
| `ournear_qa/journey/recruitment_self_service.py` | Disambiguate **Schedule** submit vs **Schedule interview** |
| `tests/journey/test_reg_prod_001_employer_reject_browser.py` | Evidence run id `20261014T120000Z-reg-prod-001-browser` |

## Remaining gaps / blocked

| Item | Status |
| --- | --- |
| `test_stg11_self_service_controls_on_correlation_fixture` | **BLOCKED** — fixture `ONT-A-000051` absent locally (`inspect-qa-application-by-business-id.php` → `found=0`); not required for REG-PROD-001 closure |
| Talent Pool MySQL E2E | **BLOCKED** (unauthorized migration) — out of scope |
| Staging / REG-BRW-005 | Not authorized |

## Commands (replay)

```powershell
cd "C:\Ournear Hub\Ournear Hire\qa-agent"
$env:OURNEAR_ALLOW_RUN="local"
.\.venv\Scripts\python.exe -m pytest `
  tests/journey/test_reg_prod_001_employer_reject_browser.py `
  tests/journey/test_employer_resume_journey.py `
  tests/stage11/test_stage11_lifecycle.py::test_stg11_self_service_end_to_end -v
```
