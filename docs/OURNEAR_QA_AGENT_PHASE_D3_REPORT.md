# OurNear Human QA Agent — Phase D3 report

**Date:** 2026-10-09  
**Journey:** `JRN-ADMIN-APPLICATION-01`  
**Laravel `app/` code:** unchanged.

---

## Verified admin workflow (browser)

| Step | Route / UI |
| --- | --- |
| Admin login | `GET/POST /admin/login` → authenticated admin URL (not `/admin/login`) |
| Dashboard | `GET /admin` — **Operations dashboard** |
| Applications workspace | `GET /admin/applications` — list + filters |
| Open QA application | `GET /admin/applications?search={ONT-A-*}&application={id}` — drawer title = business id |
| Application reference | Overview tab — **Application ID** row matches discover script |
| Resume | Resume tab — **View Resume** / **Download Resume** → PDF with synthetic `QA readable resume` marker |
| Status | Overview — combobox **Application status**; if status is **New**, one PATCH to **Reviewing** (no auto-retry) |
| Persistence | Reload — status value retained |

**Resume HTTP:** `GET /admin/applications/{application}/resume` (PHPUnit-aligned).

**Target discovery:** `scripts/inspect-qa-employer-journey-target.php` (shared with D2; now includes `status=`).

---

## Permission checks (browser)

| Actor | Check | Expected |
| --- | --- | --- |
| QA candidate (`/login` account guard) | `GET /admin/applications`, `GET /admin/applications/{id}/resume` | Forbidden or admin login redirect; no target business id in body |
| QA employer B | Same | Same |
| Admin | Full workflow above | Allowed |

PHPUnit reference: `ApplicationsWorkspaceTest::test_candidate_and_employer_cannot_access_application_workspaces`.

---

## Files changed

| Path | Role |
| --- | --- |
| `qa-agent/ournear_qa/journey/admin_application.py` | D3 steps, admin login URL guard, resume + status |
| `qa-agent/tests/journey/test_admin_application_journey.py` | Owner + account/employer isolation tests |
| `scripts/inspect-qa-employer-journey-target.php` | Adds `status=` for admin status logic |
| `qa-agent/registry/features.yaml` | `JRN-ADMIN-APPLICATION-01` (**partial**); ADM-03 `human: partial` |
| `qa-agent/ournear_qa/journey/candidate_apply.py` | `clear_cookies()` before account login (cross-journey isolation) |
| `qa-agent/ournear_qa/journey/employer_resume.py` | Same |
| `qa-agent/config/local.env` | `OURNEAR_QA_ADMIN_*` (gitignored; match local `LOCAL_ADMIN_*` / seeder) |

---

## Journey steps (admin)

| Step ID | Assertion |
| --- | --- |
| D3-01 | Admin login POST + **Operations dashboard** |
| D3-02 | **Applications** workspace index |
| D3-03 | Drawer open for discovered application |
| D3-04 | **Application ID** in overview |
| D3-06–07 | Resume download readable (PDF marker) |
| D3-08 | **New → Reviewing** once when status is New; otherwise verify unchanged |
| D3-09 | Status persists after reload |

Uses latest QA journey application (same pool as D1/D2); does not create a new application in the admin test.

---

## Test results (separate commands)

**1. Focused admin journey**

```text
pytest tests/journey/test_admin_application_journey.py -q
3 passed
```

**2. PHPUnit admin applications**

```text
php artisan test --filter=ApplicationsWorkspaceTest
7 passed (100 assertions)
```

**3. Full journey folder (D1 + D2 + D3 + helpers)**

```text
pytest tests/journey -q
Run A: 10 passed, 1 failed (employer B login timeout after long combined run)
Run B: 11 passed
```

Failure class (Run A): **test defect / environment load** — account login did not leave `/login` after admin+candidate tests in one session; mitigated by `clear_cookies()` at journey login entry; re-run green.

**4. Registry + smoke**

```text
pytest tests/test_registry_validation.py tests/smoke -q
16 passed
```

**5. D2 employer-only regression (spot check after cookie fix)**

```text
pytest tests/journey/test_employer_resume_journey.py -q
2 passed
```

---

## Defects and blocked prerequisites

- **No application defects** filed from D3 browser runs.
- **Admin credentials:** `OURNEAR_QA_ADMIN_EMAIL` / `OURNEAR_QA_ADMIN_PASSWORD` must match local admin user (see `scripts/diagnose_local_admin.php` if login fails).
- **Status side effect:** Journey application may remain **Reviewing** after a run when it was **New**; does not mutate seeded `qa-fixture-published-a` used by employer PHPUnit/list tests.
- **Apply rate limit (D1):** Unchanged from D2.1 — new-apply may skip on HTTP 429.

---

## Remaining gaps

- Admin journeys for screening queue, shortlist submit, interviews (out of D3 scope).
- Full-journey suite under heavy local use: watch account login throttle; re-run if a single login timeout occurs after many tests.
- ADM-03 registry `human` remains **partial** until broader admin workspace coverage beyond this journey.

---

## References

- D1: `docs/OURNEAR_QA_AGENT_PHASE_D1_REPORT.md`
- D2: `docs/OURNEAR_QA_AGENT_PHASE_D2_REPORT.md`
- D2.1: `docs/OURNEAR_QA_AGENT_PHASE_D2_1_REPORT.md`
