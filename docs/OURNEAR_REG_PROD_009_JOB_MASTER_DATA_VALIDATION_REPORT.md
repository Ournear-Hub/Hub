# REG-PROD-009 — Job form master-data ID validation

**Date:** 2026-10-12  
**Workspace:** `D:\Ournear Hub\Ournear Hire`  
**Scope:** Server-side validation for admin + employer job create/update (`StoreJobRequest`, `UpdateJobRequest`)

---

## 1. Confirmed gaps

| Gap | Root cause | Resolution |
| --- | --- | --- |
| Invalid skill/qualification IDs could reach sync | `JobListingRequirementService::sync()` filters with `whereIn` (drops unknown IDs without surfacing errors to the client) | Request validation tightened; array elements validated individually with field-level errors |
| Zero / non-numeric requirement IDs skipped silently | `validateJobRequirements()` used `continue` for `id <= 0` | Explicit errors on `qualification_ids.{n}` / `skill_ids.{n}` |
| Weak FK rules | Plain `exists:` without `min:1` | `Rule::exists()` + `integer` + `min:1` on all master FK fields |
| Cross-company `employer_id` on account jobs | Only stripped in controller payload | `validateEmployerAssignment()` rejects spoofed `employer_id` for hiring workspace users |
| Nested validation errors hidden in UI | `JobForm` only read top-level error keys | `fieldError()` also surfaces first nested key (e.g. `qualification_ids.0`) |

**Note:** Skills table has no `is_active` lifecycle; existence checks are sufficient. Qualifications, locations, employment types, experience ranges, and industry hierarchy use existing `isSelectable()` / hierarchy rules.

---

## 2. Endpoints covered

- `POST /admin/jobs` (`admin.jobs.store`)
- `PUT /admin/jobs/{job}` (`admin.jobs.update`)
- `POST /account/jobs` (`account.employer.jobs.store`)
- `PUT /account/jobs/{job}` (`account.employer.jobs.update`)

Shared trait: `App\Http\Requests\Admin\Concerns\ValidatesJobMasterData`.

---

## 3. Validation rules (summary)

**Required on create / nullable on update:** `industry_id`, `job_category_id`, `job_title_id`, `location_id`, `employment_type_id`, `experience_range_id` — integer, min 1, must exist in respective tables.

**Optional arrays:** `qualification_ids` (max 15), `skill_ids` (max 25) — distinct arrays; each element integer, min 1, exists; inactive qualifications rejected unless already linked on the job being edited; hierarchy checks for industry → category → title; inactive lookups rejected for new selections.

**Employer assignment:** Non-admin hiring users cannot set `employer_id` to another company's profile.

Publication, trial, and authorization policies unchanged.

---

## 4. Files changed

| File | Purpose |
| --- | --- |
| `app/Http/Requests/Admin/Concerns/ValidatesJobMasterData.php` | Stricter rules, employer assignment, requirement array validation |
| `app/Http/Requests/Admin/StoreJobRequest.php` | Hook employer validation; shared master-data prep |
| `app/Http/Requests/Admin/UpdateJobRequest.php` | Same |
| `resources/js/Components/Admin/JobForm.tsx` | Display nested validation errors |
| `tests/Feature/JobMasterDataValidationTest.php` | **Added — NOT RUN** |
| `docs/OURNEAR_RELEASE_ISSUE_REGISTER.md` | REG-PROD-009 builder status |
| `docs/OURNEAR_REG_PROD_009_JOB_MASTER_DATA_VALIDATION_REPORT.md` | This report |

---

## 5. Tests added (NOT RUN — deferred by product owner)

`JobMasterDataValidationTest` — 8 cases: valid IDs accepted; missing qual/skill IDs rejected; inactive qual/location rejected; hierarchy mismatch rejected; malformed IDs rejected; update preserves optional requirement links when arrays omitted; employer `employer_id` spoof rejected; employer update rejects invalid skill ID.

**Automated checks:** PHPUnit, Playwright, `npm run build`, and TypeScript checks were **not executed** in this pass.

---

## 6. Remaining limitations

- Runtime/browser verification and full regression suite still required before release sign-off.
- Skills remain a flat library without inactive flag; product may add lifecycle later.
- `JobListingRequirementService` still defensively filters IDs at sync time; validation is the primary integrity gate.
