# C1 — Cross-role journey completion (notifications + apply)

**Date:** 2026-10-10  
**Journey ID:** `JRN-CROSS-ROLE-01` (extended)  
**Agent 2 status:** **PASS** on focused re-run after label fix; **awaiting fresh run_id** for release gate archive  
**Release impact:** Closes admin → candidate status notification path; duplicate-apply rules covered in PHPUnit

---

## Scope

- Admin moves application to **Reviewing** → candidate sees **Under review** (not “Reviewing”).
- Candidate receives in-app notification (“being reviewed”) via existing notification services.
- Cross-role flow: employer job → candidate apply → admin screening/application views (prior baseline retained).

---

## Product / harness changes

| Area | Change |
| --- | --- |
| `qa-agent/ournear_qa/journey/candidate_status_notification.py` | Assert candidate label **Under review** |
| `qa-agent/ournear_qa/journey/cross_role.py` | Extended steps for notification check |
| `tests/journey/test_cross_role_e2e.py` | Journey entry point |

**PHPUnit (no browser):** `ApplicationExperienceTest` duplicate apply + notification dedup — **2 passed** (session note).

---

## Execution

**Command:**

```text
cd qa-agent
python -m pytest tests/journey/test_cross_role_e2e.py -v --ournear-suite=journey
```

**Outcome:** **PASSED** (~168s) after correcting Playwright text expectation to match `ApplicationStatus::toCandidateStatusLabel()`.

**Prior failure:** Assertion on visible text “Reviewing” — product correctly shows “Under review”.

---

## Evidence gaps (honest)

| Item | Status |
| --- | --- |
| Unique run_id folder under `qa-agent/artifacts/` for this closure | **Not recorded in this doc** — operator should capture on next green run |
| Desktop 1440×900 + mobile 390×844 explicit artifact manifest | Use journey defaults; attach paths on next run |
| Staging / production | **NOT TESTED** |

---

## Agent 2 retest checklist

1. `php scripts/verify-local-env.php` — all OK including VITE_MANIFEST  
2. `php artisan qa:hiring-fixtures`  
3. `php artisan serve` @ `127.0.0.1:8000`  
4. Export valid `OURNEAR_QA_ADMIN_*` and candidate credentials per `qa-agent` settings  
5. Run pytest command above; store artifacts under `qa-agent/artifacts/c1-cross-role/<run_id>/`

---

## Verdict

| | |
| --- | --- |
| **C1 implementation** | **COMPLETE** |
| **Independent browser proof for release archive** | **FIXED — AWAITING QA** (one documented green run with run_id) |
