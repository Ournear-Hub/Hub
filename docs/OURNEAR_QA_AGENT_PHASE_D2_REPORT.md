# OurNear Human QA Agent — Phase D2 report

**Date:** 2026-10-09  
**Journey:** `JRN-EMPLOYER-RESUME-01`  
**Laravel `app/` code:** unchanged.

---

## Verified employer workflow (browser)

| Step | Route / UI |
| --- | --- |
| Login | `/login` → `/account` (**Dashboard**) |
| Job applicants | `/account/jobs/{slug}/applicants` — job title heading, **View application** |
| Application detail | `/account/jobs/{slug}/applications/{id}` — **Application ID** (`ONT-A-*`), contact snapshot, **Resume** card |
| Inline resume | **View** → popup or download; PDF validated (`%PDF`, `%%EOF`, synthetic `QA readable resume` marker) |
| Download resume | **Download** → Playwright download; same PDF checks |
| Cross-job list | `/account/hiring/applications` — owning employer sees application row |
| Reload | Detail + hiring list remain after reload |

**Resume HTTP (PHPUnit-aligned):** `GET /account/employer-applications/{application}/resume` (optional `?disposition=inline`).

**Discovery (read-only):** `php scripts/inspect-qa-employer-journey-target.php` — latest QA candidate application on Employer A journey-apply jobs (falls back to any Employer A job). Outputs numeric `application_id`, `business_id`, `job_slug`, `resume_path_set` (no secrets).

---

## Employer A/B isolation (browser)

| Check | Expected |
| --- | --- |
| Employer B `/account/hiring/applications` | Does not list Employer A `ONT-A-*` from target |
| Employer B application detail URL | 403 or forbidden UX; no target business id in body |
| Employer B resume URL | Same as PHPUnit `EmployerHiringStage25Test` |

---

## Files changed

| Path | Role |
| --- | --- |
| `scripts/inspect-qa-employer-journey-target.php` | Read-only D1/D2 application target discovery |
| `scripts/analyze-qa-application-resume.php` | Optional local resume byte analysis (ops) |
| `qa-agent/ournear_qa/journey/employer_resume.py` | D2 steps, PDF checks, isolation helpers |
| `qa-agent/tests/journey/test_employer_resume_journey.py` | Owner + Employer B isolation tests |
| `qa-agent/ournear_qa/settings.py` | `OURNEAR_QA_EMPLOYER_B_*` |
| `qa-agent/config/env.example` | Employer B env placeholders |
| `qa-agent/registry/features.yaml` | `JRN-EMPLOYER-RESUME-01` → **partial**, pytest assertions |
| `qa-agent/tests/conftest.py` | Journey evidence uses `journey_id` on `JourneyStepError` |
| `qa-agent/ournear_qa/journey/candidate_apply.py` | `JourneyStepError.journey_id`; duplicate test pagination-safe fixture job link |
| `qa-agent/config/local.env` | Employer A/B credentials for local runs (gitignored) |

---

## Journey steps (Employer A)

| Step ID | Assertion |
| --- | --- |
| D2-01 | Login + **Dashboard** |
| D2-02 | Applicants list for discovered `job_slug` |
| D2-03–04 | Open application; **Application ID** matches discover script |
| D2-05 | Resume card **View** / **Download** hrefs include `/account/employer-applications/{id}/resume` |
| D2-06 | Inline resume opens; PDF bytes readable (popup, View-triggered download, or inline URL) |
| D2-07 | **Download** produces readable PDF |
| D2-08–09 | Detail persists after reload; row on `/account/hiring/applications` |

Target application is the **latest** journey-apply submission (reuses D1 data; tests do not submit new applications).

---

## Execution results (2026-10-09, local `127.0.0.1:8000`)

**Employer journey (focused):**

```text
pytest tests/journey/test_employer_resume_journey.py -q
2 passed
```

**Journey folder (includes D1 regression):**

```text
pytest tests/journey -q
5 passed, 1 failed
```

| Test | Result | Notes |
| --- | --- | --- |
| `test_jrn_employer_resume_owner_workflow` | Pass | |
| `test_jrn_employer_resume_employer_b_isolation` | Pass | |
| `test_jrn_candidate_apply_duplicate_and_persistence` | Pass | Pagination-safe link to `qa-fixture-published-a` |
| `test_jrn_candidate_apply_new_application` | **Fail** | Submit blocked: modal **dialog + iframe** on apply page (environment/prerequisite; not employer journey) |
| Discovery unit tests (2) | Pass | |

**Registry + smoke:**

```text
pytest tests/test_registry_validation.py tests/smoke -q
16 passed
```

**PHPUnit (employer isolation reference):**

```text
php artisan test --filter=EmployerHiringStage25Test
8 passed (54 assertions)
```

---

## Evidence and defects

- **Failure classification (D1 new apply):** Blocked prerequisite / environment — third-party iframe dialog after **Submit application** prevents navigation to success state. Employer D2 tests do not depend on creating a new application in the same run.
- **Test hardening:** Resume **View** locator scoped to Resume card (avoids nav **Overview** link). Headless Chromium often downloads inline PDFs; D2 accepts popup, View-triggered download, or canonical inline URL download, then validates PDF bytes.
- **No application defects** confirmed for employer resume authorization in this phase (browser B isolation matches PHPUnit).

---

## Registry

`JRN-EMPLOYER-RESUME-01` status set to **`partial`**: employer browser workflow implemented; admin resume journey not in scope.

---

## Remaining gaps

- Admin resume journey (`JRN-ADMIN-*` / planned admin phases).
- D1 new-apply stability when apply submit shows captcha/iframe dialog on local.
- Optional: dedicated pytest for `discover_employer_journey_target()` stdout parsing (mirror D1 discovery tests).
- Candidate applications list pagination: duplicate test now scans pages 1–3; consider server-side filter by job slug if list grows further.

---

## Local prerequisites

```powershell
cd "D:\Ournear Hub\Ournear Hire"
php artisan qa:hiring-fixtures
php scripts/inspect-qa-employer-journey-target.php
cd qa-agent
# config/local.env: OURNEAR_ALLOW_RUN=local, QA account emails/passwords (see env.example)
.venv\Scripts\python.exe -m pytest tests/journey/test_employer_resume_journey.py -v
```
