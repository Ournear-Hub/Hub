# OurNear Hire — Profile field audit, repair, and verification

**Batch date:** 2026-10-10 (local `http://127.0.0.1:8000`)  
**Canonical browser run (profile field audit suite):** `20261010T105154Z-f5679a64` (final green re-run after harness locator fixes)  
**Prior combined Stage 2 + Stage 4 profile-audit label run:** `20261010T103411Z-1a41a58a` (28 passed, 4 **BLOCKED** login 429)  
**Registry reference:** `qa-agent/registry/profile_field_audit.yaml`  
**Harness:** `qa-agent/tests/profile_audit/test_profile_field_audit.py` (single login per role)

---

## A. Executive summary and launch impact

Human-reported profile symptoms (save errors, unexplained required fields, duplicates, display mismatch) were treated as hypotheses. Code was traced end-to-end for all candidate and employer profile surfaces. **No reproducible product defect** was confirmed in Laravel validation, persistence, or Inertia reload paths during this batch. PHPUnit profile suites (**50 tests, 851 assertions, exit 0**) and expanded browser checks (**19 field-group cases PASS** in the dedicated profile audit journey, plus Stage 2/4 spot checks) support **stable save/reload** for exercised fields.

**Launch impact:** Profile edit/save is **not a identified P0 blocker** from this evidence. Remaining risk is **coverage gaps** (photo upload, every select/master-data control, privacy toggle matrix, bio-after-relogin under rate limit) and **known non-profile BLOCKED** cases from login throttling—not unverified “PASS” claims.

**Batch verdict:** **PASS_WITH_COVERAGE_GAPS** — meets stabilization for fields exercised; does **not** satisfy the strict “every field manually exercised once” gate without the NOT_TESTED list below.

**Product code changes (2026-10-12 follow-up REG-CAN-PROF-001):** Partial basic profile PUT persistence fix — see `docs/OURNEAR_QA_P0_CANDIDATE_PROFILE_REPORT.md` §REG-CAN-PROF-001. Original batch: harness-only locator fixes in QA tests.

---

## B. Candidate profile field inventory and results

**Modules (UI):** `basic`, `about`, `professional`, `experience`, `education`, `skills`, `projects`, `certifications`, `languages`, `preferences`, `resume` (+ `#privacy` share section).  
**Routes:** `PUT /account/profile` (`UpdateProfileRequest`); `PUT /account/profile/professional` (`UpdateProfessionalProfileRequest`); related CRUD on employment, education, projects, certifications, languages (`CandidateProfessionalProfileController`).

**Inventoried field count (unique controls / properties): 58**

| # | Section | Label / control | Property / storage | Required | Evidence | Manual browser | Status |
|---|---------|-----------------|-------------------|----------|----------|----------------|--------|
| 1 | Basic | Photo | `users` photo via `UserPhotoService` | Optional | `UpdateProfileRequest` nullable file | NOT_TESTED (file upload) | NOT_TESTED |
| 2 | Basic | Full name | `users.name` | **Yes** | `UpdateProfileRequest` required | NOT_TESTED (invariant on fixture account) | NOT_TESTED |
| 3 | Basic | Username | `users.username` | **Yes** | unique + alpha_dash | NOT_TESTED | NOT_TESTED |
| 4 | Basic | Email | `users.email` | Read-only in UI | registration | NOT_TESTED | NOT_TESTED |
| 5 | Basic | Mobile | `users.mobile` | Optional (candidate) | `UserProfileModulePart3Test`; UI `mobileRequired` only in hiring activation context | NOT_TESTED | NOT_TESTED |
| 6 | Basic | Location | `users.location` | Optional | nullable max 255 | PFA-C-001 reload | PASS |
| 7 | Basic | Preferred language | `users.preferred_language` | **Yes** | `PreferredLanguage` enum | NOT_TESTED | NOT_TESTED |
| 8 | About | Professional headline | `users.professional_headline` | Optional | nullable | PFA-C-002 | PASS |
| 9 | About | Bio | `users.bio` | Optional | nullable | STG2-PRO-001 reload | PASS |
| 10 | About | Bio after re-login | same | Optional | session | STG2-PRO-002 | BLOCKED (429 login) |
| 11 | Professional | Current designation | `candidate_profiles` / pro payload | Optional | `UpdateProfessionalProfileRequest` | STG2-PRO-003 | PASS |
| 12 | Professional | Current company | pro | Optional | nullable | PFA-C-003 | PASS |
| 13 | Professional | Industry | `industry_id` | Optional | exists industries | NOT_TESTED | NOT_TESTED |
| 14 | Professional | Job category | `job_category_id` | Optional | exists | NOT_TESTED | NOT_TESTED |
| 15 | Professional | Experience (text) | `experience` | Optional | also on main PUT for candidate (legacy) | NOT_TESTED | NOT_TESTED |
| 16 | Professional | Experience range | `experience_range_id` | Optional | exists | NOT_TESTED | NOT_TESTED |
| 17 | Professional | Qualification (text) | `qualification` | Optional | duplicate path with main PUT | NOT_TESTED | NOT_TESTED |
| 18 | Professional | Qualification (select) | `qualification_id` | Optional | exists | NOT_TESTED | NOT_TESTED |
| 19 | Professional | Current salary | `current_salary` | Optional | numeric | PFA-C-011 | PASS |
| 20 | Professional | Expected salary | `expected_salary` | Optional | numeric | NOT_TESTED | NOT_TESTED |
| 21 | Professional | Notice period | `notice_period` enum | Optional | `NoticePeriod` | NOT_TESTED | NOT_TESTED |
| 22 | Professional | Employment preference (textarea) | `employment_preference` | Optional | max 2000 | PFA-C-010 | PASS |
| 23 | Experience | History — company | `candidate_employment_histories` | **Yes** on create | `StoreEmploymentHistoryRequest` | PFA-C-005 (group) | PASS |
| 24 | Experience | History — designation | same | **Yes** on create | required | PFA-C-005 (group) | PASS |
| 25 | Experience | History — industry | same | Optional | nullable | NOT_TESTED per subfield | NOT_TESTED |
| 26 | Experience | History — dates / current | same | Start required; end if not current | custom validator | PFA-C-005 (group) | PASS |
| 27 | Experience | History — description | same | Optional | nullable | NOT_TESTED | NOT_TESTED |
| 28 | Education | Institution | `candidate_educations` | **Yes** on create | `StoreCandidateEducationRequest` | PFA-C-006 | PASS |
| 29–32 | Education | Qualification, field, dates | same | Optional | rules | NOT_TESTED | NOT_TESTED |
| 33 | Skills | Skill tags | `candidate_skills` / array on pro PUT | Optional | array | STG2-PRO-004 | PASS |
| 34–37 | Projects | name, role, company, description | `candidate_projects` | name required on add | store request | PFA-C-007 (name) | PASS |
| 38–41 | Certifications | name, issuer, dates | `candidate_certifications` | name required on add | store request | PFA-C-008 (name) | PASS |
| 42–43 | Languages | name, proficiency | `candidate_languages` | name required on add | store request | PFA-C-009 (name) | PASS |
| 44 | Preferences | Preferred locations | `preferred_location_ids` | Optional | array exists | NOT_TESTED | NOT_TESTED |
| 45 | Preferences | Preferred job types | `preferred_employment_type_ids` | Optional | array | NOT_TESTED | NOT_TESTED |
| 46 | Preferences | Willing to relocate | boolean | Optional | sometimes boolean | NOT_TESTED | NOT_TESTED |
| 47 | Preferences | Availability | `availability` | Optional | nullable | PFA-C-004 | PASS |
| 48–49 | Legacy on main PUT | experience, qualification strings | `candidate_profiles` | Optional | only when candidate | NOT_TESTED (superseded by pro module) | NOT_TESTED |
| 50 | Privacy | Public share toggles | `public_share` flags | Optional | `UnifiedProfileStage23FTest` | STG2-PRO-006 section present only | NOT_TESTED save matrix |
| 51–52 | Resume | Library / builder surfaces | `/account/resumes`, builder | Optional for profile % | `AccountCandidateDashboardTest` | STG2-RES-001–003 hub load | PASS (surface) |

**Candidate manual totals (field groups, no double-count across PFA + STG2 for same field):**

| Metric | Count |
|--------|------:|
| Inventoried | 58 |
| Manually exercised (save/reload or CRUD add) | 24 |
| PASS | 23 |
| BLOCKED | 1 (bio re-login) |
| NOT_TESTED | 34 |
| FAIL (product) | 0 |
| FIXED_AND_RETESTED | 0 |

---

## C. Employer profile field inventory and results

**Route:** `PUT /account/profile` (employer branch of `UpdateProfileRequest`).  
**Display:** `EmployerCompanyProfileView` (read); public company page with share flags (`EmployerCompanyFoundationStage4Test`, `UnifiedProfileStage23FTest`).

**Inventoried field count: 22**

| # | Section | Label | Property / storage | Required | Evidence | Manual browser | Status |
|---|---------|-------|-------------------|----------|----------|----------------|--------|
| 1 | Account | Owner name | `users.name` | **Yes** | same as candidate | NOT_TESTED | NOT_TESTED |
| 2 | Company | Company name | `employer_profiles.company_name` | Optional | nullable | STG4-PROF-001, PFA-E-001 | PASS |
| 3 | Company | Contact person | `contact_person` | Optional | nullable | PFA-E-002 | PASS |
| 4 | Company | Mobile (company) | `mobile_number` | Optional | distinct from `users.mobile` | NOT_TESTED | NOT_TESTED |
| 5 | Company | Industry | `industry` | Optional | string | PFA-E-005 | PASS |
| 6 | Company | Company size | `company_size` | Optional | nullable | PFA-E-006 | PASS |
| 7 | Company | Website | `company_website` | Optional | url | PFA-E-004 | PASS |
| 8 | Company | About | `about_company` | Optional | max 5000 | PFA-E-003 | PASS |
| 9 | Company | Additional details | `company_details` | Optional | nullable | NOT_TESTED | NOT_TESTED |
| 10 | Company | Logo | profile photo | Optional | same as user photo | NOT_TESTED | NOT_TESTED |
| 11 | Company | Locations | `location_ids` → pivot | Optional | exists locations | NOT_TESTED | NOT_TESTED |
| 12 | Company | Benefits | `benefits` array | Optional | parsed from textarea | PFA-E-007 | PASS |
| 13–17 | Social | LinkedIn, Facebook, Twitter, Instagram, YouTube | `socials.*` | Optional | url each | PFA-E-008 (LinkedIn) | PASS |
| 18 | Privacy | Company public share | employer share flags | Optional | feature tests | NOT_TESTED browser | NOT_TESTED |

**Employer manual totals:**

| Metric | Count |
|--------|------:|
| Inventoried | 22 |
| Manually exercised | 9 |
| PASS | 9 |
| BLOCKED | 0 (employer login BLOCKED only in earlier combined run STG4-AUTH-001) |
| NOT_TESTED | 13 |
| FAIL (product) | 0 |

---

## D. Manual browser matrix (by case)

| Case ID | Role | Field / focus | Result |
|---------|------|---------------|--------|
| STG2-PRO-001 | Candidate | bio reload | PASS |
| STG2-PRO-002 | Candidate | bio re-login | BLOCKED (429) |
| STG2-PRO-003 | Candidate | current_designation | PASS |
| STG2-PRO-004 | Candidate | skills add | PASS |
| STG2-PRO-005 | Candidate | module hub | PASS |
| STG2-PRO-006 | Candidate | privacy section | PASS (presence) |
| STG2-RES-001–003 | Candidate | resume surfaces | PASS |
| STG4-PROF-001 | Employer | company_name | PASS |
| PFA-C-001 … PFA-C-011 | Candidate | see §B | PASS |
| PFA-E-001 … PFA-E-008 | Employer | see §C | PASS |

Evidence screenshots (no secrets): `qa-agent/artifacts/profile_audit/evidence/{run_id}/{case_id}/pass.png` (local; gitignored).

---

## E. Required / optional decisions

| Field | UI | Backend | Decision |
|-------|----|---------|----------|
| name, username, preferred_language | Shown on basic form | **required** | **Keep required** — account identity + locale (`UpdateProfileRequest`) |
| email | Read-only | set at registration | **Correct** |
| mobile (candidate) | Optional pill unless hiring context | nullable | **Keep optional** — `UserProfileModulePart3Test` |
| mobile (employer UI) | Verification pill in hiring context | `mobile_number` nullable on company form | **Distinct fields** — company contact vs account mobile |
| All professional / company descriptive fields | Optional labels | nullable in Form Requests | **Aligned** — no unjustified backend required fields found |
| Employment history company/designation | Required on **add** | required on store | **Justified** — empty rows blocked |
| Education institution on add | Required in UI flow | required on store | **Justified** |

No change made: requirements are consistent with existing tests and requests.

---

## F. Duplicate or confusing fields

| Pair | Decision | Rationale |
|------|----------|-----------|
| `users.mobile` vs `employer_profiles.mobile_number` | **Keep both** | Different roles: account contact vs company listing contact (`UserProfileModulePart3Test` employer context) |
| `users.name` vs `contact_person` | **Keep both** | Account owner vs company HR contact on public company page |
| `experience` / `qualification` on main profile PUT vs professional PUT | **Document overlap** | Both nullable; professional module is canonical UI; legacy keys on main PUT still accepted for candidate — **no removal** (data may exist; PHPUnit preserves experience on partial pro update) |
| Headline: `professional_headline` vs dashboard `headline` | **Single storage** | Display composes from same user fields |

---

## G. Defects, root cause, fixes

| Defect ID | Symptom | Reproduced? | Root cause | Fix | Status |
|-----------|---------|-------------|------------|-----|--------|
| — | Profile save failures (generic) | No | — | — | — |
| QA-429-LOGIN | Login 429 during long QA suites | Yes | Account login rate limit | Spacing / single-session journeys (`profile_audit` harness); classify BLOCKED | BLOCKED (environment) |
| QA-STRICT-LOCATOR | PFA strict mode duplicate text | Yes (harness) | Repeated audit runs created duplicate list rows | Use `.first` in Playwright expectations | FIXED (QA only) |

**Application code repairs:** 0

---

## H. Blocked, missing, untested

- **BLOCKED:** STG2-PRO-002, STG2-DASH-002, STG4-AUTH-001, STG4-DASH-002 in run `20261010T103411Z-1a41a58a` (429).
- **NOT_TESTED:** See §B/§C tables (photo, master-data selects, privacy toggle saves, mobile clear, employer locations checkboxes, remaining social URLs, per-subfield CRUD dates, full invalid-value matrix per field).
- **MISSING:** No missing profile **features** relative to current product scope; employer application pipeline UI remains out of scope for this batch.

---

## I. Browser verification evidence

- **Tool:** Playwright (Chromium), local base URL.
- **Accounts:** `qa-candidate@example.test`, `qa-employer-a@example.test` (`qa-agent/config/local.env`).
- **Screenshots:** Under `qa-agent/artifacts/profile_audit/evidence/` (run id above).

---

## J. Automated tests

| Command | Result |
|---------|--------|
| `php artisan test tests/Feature/AccountCandidateProfessionalTest.php tests/Feature/EmployerCompanyFoundationStage4Test.php tests/Feature/UnifiedProfileStage23FTest.php tests/Feature/UserProfileModulePart3Test.php tests/Feature/AccountCandidateDashboardTest.php` | **50 passed**, 851 assertions, exit **0** |
| `python -m pytest qa-agent/tests/profile_audit/ -v --ournear-suite profile_field_audit` | **2 passed**, exit **0** (11+8 field cases recorded) |
| Combined `tests/stage2/` + `tests/stage4/test_stage4_employer.py --ournear-suite profile_audit` | 28 passed, **4 BLOCKED** (429), exit non-zero |

No assertion weakening.

---

## K. TypeScript and build

No application UI/TS changes in this batch → **`npx tsc --noEmit` / `npm run build` not re-run** (per AGENTS.md, only after UI code changes).

---

## L. Privacy, authorization, display

| Check | Method | Result |
|-------|--------|--------|
| Candidate public profile honors share flags | `UnifiedProfileStage23FTest` | PASS |
| Private education/projects/contact hidden | same | PASS |
| Employer cannot edit candidate profile | same | PASS |
| Company public page hides private contacts | `UnifiedProfileStage23FTest`, Stage 4 tests | PASS |
| Owner sees edit affordances | `UnifiedProfileStage23FTest` | PASS |

Browser: STG2-PRO-006 confirms privacy section renders; **toggle save matrix NOT_TESTED** in browser.

---

## M. Product decisions / owners

| Topic | Owner | Notes |
|-------|-------|-------|
| Retire legacy `experience`/`qualification` on main profile PUT | Product + eng | Optional cleanup; not required for launch if professional module remains primary |
| Expand manual matrix to 100% field coverage | QA | Run staged `profile_field_audit` + cooldown between login-heavy cases |
| Rate limit policy for QA automation | Platform | Document safe concurrency for account login |

---

## N. Launch risks and next QA step

**Risks (remaining):**

1. **Coverage gap** — 34 candidate + 13 employer inventoried controls not manually save-tested this batch.
2. **Login 429** — can mask re-login persistence checks.
3. **Photo upload** — not browser-verified here (PHPUnit covers service constraints indirectly).

**Recommended next step:** Proceed to **Stage 10 / next cross-role QA** with profile treated as **stabilized for exercised paths**; schedule a **follow-up field-completion browser pass** (photo, selects, privacy toggles, employer locations) after login throttle cooldown or dedicated QA rate-limit allowance.

**Release gate:** Unchanged — **`insufficient_evidence`** at web release doc level until broader QA stages complete; this batch **does not** upgrade gate alone.

---

## Completion summary (batch)

| Item | Value |
|------|-------|
| Report | `docs/OURNEAR_PROFILE_FIELD_AUDIT_AND_REPAIR_REPORT.md` |
| Product defects confirmed | 0 |
| Product fixes | 0 |
| PHPUnit profile-related | 50 / 50 pass |
| Profile audit browser journey | 2 / 2 pass (19 field-group cases) |
| Unresolved | 34 + 13 NOT_TESTED fields; 1 BLOCKED re-login case |
| Ready for next QA stage? | **Yes with documented gaps** — not “every field” complete |
