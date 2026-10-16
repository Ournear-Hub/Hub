# REG-CAN-PROF-001 — Profile field audit execution (2026-10-12)

**Run ID (green):** `20261012T055640Z-cbe20eb6`  
**Base URL:** `http://127.0.0.1:8000`  
**Database:** `ournear_hire_local` (MySQL84 **Running**, probe **CONNECTED**)  
**Fixtures:** `php artisan qa:hiring-fixtures` (exit 0)  
**Release verdict:** **`insufficient_evidence`** unchanged (full 58-field manual matrix not complete; PHPUnit not re-run per instruction)

---

## 1. Commands executed

| Step | Command | Result |
| --- | --- | --- |
| Stack probe | `powershell -File scripts/check-local-stack.ps1` | MySQL **Running**, TCP **3306/8000** open, env **OK** |
| Fixtures | `php artisan qa:hiring-fixtures` | QA users/jobs ready |
| Playwright browsers | `qa-agent\.venv\Scripts\playwright.exe install chromium` | Downloaded chromium v1243 (required after venv/sandbox cache miss) |
| Profile audit (attempt 1) | `cd qa-agent && .venv\Scripts\python.exe -m pytest tests/profile_audit/test_profile_field_audit.py -v --ournear-suite=profile_audit` | **2 errors** — missing browser binaries |
| Profile audit (attempt 2) | Same pytest | **2 failed** — login label harness (`Username or Email` vs `Email or username`) |
| Harness fix | `test_profile_field_audit.py` → `FormHelper.fill_account_login()` | — |
| Profile audit (attempt 3) | Same pytest | **2 passed**, **~90s**, pytest exit **0** |

**Authoritative pytest command (final):**

```text
cd "D:\Ournear Hub\Ournear Hire\qa-agent"
.\.venv\Scripts\python.exe -m pytest tests/profile_audit/test_profile_field_audit.py -v --ournear-suite=profile_audit
```

---

## 2. Results summary

| Layer | Pass | Fail | Blocked | Notes |
| --- | ---: | ---: | ---: | --- |
| Pytest tests | **2** | **0** | **0** | Candidate + employer single-session journeys |
| Recorded field cases (`profile_audit_results.json`) | **19** | **0** | **0** | PFA-C-001…011, PFA-E-001…008 |
| Full 58-field inventory (canonical) | — | — | — | **39+ fields still NOT_TESTED** in browser per `docs/OURNEAR_PROFILE_FIELD_AUDIT_AND_REPAIR_REPORT.md` |

**Artifacts:**

- `qa-agent/artifacts/profile_audit/20261012T055640Z-cbe20eb6/profile_audit_results.json`
- Screenshots: `qa-agent/artifacts/profile_audit/evidence/20261012T055640Z-cbe20eb6/PFA-*/pass.png`
- Session summary: `qa-agent/artifacts/session/last-pytest-summary.json`

---

## 3. Field-level outcomes (this run)

All **19** cases **PASS** — save/reload persistence as defined in the suite.

**Candidate (11):** location, professional_headline, current_company, availability, employment_history, education_institution, project_name, certification_name, language_name, employment_preference, current_salary.

**Employer (8):** company_name, contact_person, about_company, company_website, industry, company_size, benefits_text, socials_linkedin.

---

## 4. Defects and fixes

| ID | Class | Detail | Resolution |
| --- | --- | --- | --- |
| ENV-001 | Environment | Playwright Chromium not present in active venv cache | `playwright install chromium` |
| QA-HARNESS-005 | Harness | Login label renamed in `Account/Login.tsx` (`Email or username`) | Use existing `FormHelper.fill_account_login()` in `test_profile_field_audit.py` |

**Application defects:** none confirmed in this run.

---

## 5. Unresolved / coverage gaps

- **58-field strict gate:** photo upload, master-data selects, privacy toggle matrix, bio-after-relogin (429-sensitive), remaining education subfields, preferences multi-selects, etc. — still **NOT_TESTED** in browser unless covered by separate Stage 2 journeys (not re-run here).
- **Stage 2 companion tests:** not executed (scope limited to `test_profile_field_audit.py` per assignment).

---

## 6. REG-CAN-PROF-001 status recommendation

| Evidence | Status |
| --- | --- |
| PHPUnit profile hardening (13 tests) | **PASS** (prior session — not re-run) |
| Browser PFA suite (this run) | **PASS** — 19/19 recorded cases |
| Full 58-field browser matrix | **PASS_WITH_COVERAGE_GAPS** |

**QA column:** **PARTIAL** → browser PFA **PASS** with documented inventory gaps; not “complete 58/58 browser verified.”

---

## 7. Next action

Extend browser coverage for high-risk **NOT_TESTED** inventory rows (photo, `preferred_language`, master-data selects, privacy saves) via additional PFA cases or controlled Stage 2 spot checks — without rerunning full PHPUnit.
