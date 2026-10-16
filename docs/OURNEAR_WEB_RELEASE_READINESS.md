# OurNear Hire — Web release readiness snapshot

**Date:** 2026-10-10 (Stage 12 final gate)  
**Addendum (2026-10-16 evening, code-only follow-up):** Employer offer/joining routes, Admin-only Talent Pool job create, and optional showcase-to-employer-profile link were added in the repository. **This follow-up did not run PHPUnit, TypeScript, Vite, migrations, or browser checks.** Treat Batch 7 **1151 passed / 3 skipped / 0 failed** as historical only. Staging and production remain unverified. Verdict stays **`insufficient_evidence`** until the manual checklist is run.

**Addendum (2026-10-16):** Local **1100+ PHPUnit** (prior full run) + **4** showcase tests **PASS**; Stage 1 **28/28** pass after infinite-scroll pagination test alignment; company showcase + manual homepage stats **implemented**. Staging smoke, full browser recruitment chain, and branding browser smoke **not run** this pass.  
**Verdict:** **`insufficient_evidence`** for production; **ready for staging acceptance** on independent local evidence. — Stage 12 full PHPUnit **714 passed** / **2 skipped** / **12,897 assertions**; TS + build green; Stages 1–11 browser evidence on file; **staging not verified**; employer outcome **MISSING** capabilities and profile/email gaps remain.

Consolidated index: **`docs/OURNEAR_P0_LOCAL_EVIDENCE_RECORD.md`**.

---

## 1. Full PHPUnit (Stage 12 regression — latest)

| Field | Value |
| --- | --- |
| **Command** | `php artisan test` |
| **Exit code** | **0** |
| **Passed / skipped / failed** | **714** / **2** / **0** |
| **Assertions** | **12,897** |
| **Duration** | **956.66s** |
| **Log** | `storage/logs/phpunit-stage12-20261010.txt` |

Prior session log (712 tests): `storage/logs/phpunit-full-20261010-session2.txt`.

**Closure-session targeted PHPUnit:**

| Command | Exit | Result |
| --- | --- | --- |
| `php artisan test --filter=QaDefectRegressionTest` | 0 | **8 passed** — includes `test_public_jobs_company_filter_persists_across_pagination` |
| `php artisan test --filter=QaHiringFixturesTest` | 0 | **4 passed** — Employer A pagination fixture jobs |

---

## 2. CAN-02 profile reload — **closed (manual PASS)**

| Item | Detail |
| --- | --- |
| **Case** | `MAN-CAN-02-PROFILE-RELOAD` |
| **Run ID** | **`20261010T041431Z-9b88be75`** |
| **Outcome** | pytest **1 passed**; manual import **PASS** |
| **Evidence** | `qa-agent/artifacts/manual/evidence/20261010T041431Z-9b88be75/MAN-CAN-02-PROFILE-RELOAD/` (01–05) |
| **Report** | `qa-agent/artifacts/reports/20261010T041431Z-9b88be75/` (`ci_exit_code: 4`) |

Bio and `current_designation` persist across reload; completion % does not regress. No product defect found.

---

## 3. PUB-02 jobs search, mobile filters, pagination — **closed**

| Item | Detail |
| --- | --- |
| **UI fix** | `JobsSearchPanel.tsx` — removed native `<details>`; always-visible “More filters” block (desktop + mobile) |
| **Mobile interaction** | **Acceptable / fixed** — no collapse trap; `#job-company` visible at 390×844 without extra taps |
| **Pagination** | Browser **PASS** + PHPUnit `test_public_jobs_company_filter_persists_across_pagination` |
| **Fixtures** | `QaHiringFixtures::PUBLISHED_A_PAGINATION_JOBS = 9` (+ existing published A) for local pagination |
| **Run ID** | **`20261010T054124Z-a53f91f2`** (pytest **2 passed**: search + pagination harness) |
| **Evidence** | `.../MAN-PUB-02-JOBS-SEARCH/` + `.../MAN-PUB-02-JOBS-PAGINATION/` |
| **Report** | `qa-agent/artifacts/reports/20261010T054124Z-a53f91f2/` (`ci_exit_code: 4`) |
| **Frontend** | `npx tsc --noEmit` **0**; `npm run build` **0** (closure session) |

Prior run `20261010T033815Z-653060ef` remains valid historical evidence for the first `<details open>` fix.

---

## 4. P0 journeys (reused)

| Run ID | Outcome | Notes |
| --- | --- | --- |
| `20261010T030042Z-8d065de8` | **9 passed** | Not re-run this closure |
| Earlier | `JRN-CROSS-ROLE-01`, `JRN-CANDIDATE-APPLY-01` | Prior PASS retained |

---

## 5. Other manual cases

| Case | Run ID | Status |
| --- | --- | --- |
| `MAN-CAN-01-DASHBOARD-GUIDANCE` | `20261010T025807Z-37d441ab` | **PASS** (prior session) |

---

## 6. Staging

**Not authorized. Not tested.**

---

## 7. Stage 1 — public website & job discovery (local)

| Item | Detail |
| --- | --- |
| **Report** | `docs/OURNEAR_STAGE_1_PUBLIC_WEBSITE_QA_REPORT.md` |
| **Run ID** | **`20261010T064328Z-9a191885`** — pytest **28/28** passed; **30/30** checklist cases **PASS** |
| **Evidence** | `qa-agent/artifacts/stage1/20261010T064328Z-9a191885/` + `.../stage1/evidence/.../` |
| **Stage exit** | **`PASS_WITH_KNOWN_ISSUES`** — extended inventory (tablet, lifecycle jobs, full filter matrix, etc.) **NOT_TESTED** |
| **Product fixes** | **None** required in Stage 1 closure (harness-only test updates) |

---

## 8. Stage 2 — candidate registration, profile & resume (local)

| Item | Detail |
| --- | --- |
| **Report** | `docs/OURNEAR_STAGE_2_CANDIDATE_PROFILE_QA_REPORT.md` |
| **Run ID** | **`20261010T071623Z-2ba908a5`** — pytest **20/20** passed |
| **PHPUnit** | `php artisan test --filter=AccountCandidate` — **60 passed**, 845 assertions |
| **Stage exit** | **`PASS_WITH_KNOWN_ISSUES`** — extended per-field browser matrix §F **NOT_TESTED** |
| **Product fixes** | **None** in Stage 2 closure |

---

## 9. Stage 5 — employer application management (local)

| Item | Detail |
| --- | --- |
| **Report** | `docs/OURNEAR_STAGE_5_EMPLOYER_APPLICATION_MANAGEMENT_QA_REPORT.md` |
| **Run ID** | **`20261010T085949Z-ae1d2010`** — **11 PASS**, **1 MISSING** (employer status UI) |
| **PHPUnit** | `EmployerHiringStage25Test` **8** |
| **Stage exit** | **`PASS_WITH_KNOWN_ISSUES`** |
| **Product fixes** | **None** |

---

## 9a. Stage 6 — cross-role workflow (local)

| Item | Detail |
| --- | --- |
| **Report** | `docs/OURNEAR_STAGE_6_CROSS_ROLE_WORKFLOW_QA_REPORT.md` |
| **Run ID** | **`20261010T091906Z-56bd4047`** — **15 PASS**, **1 MISSING** (employer pipeline UI) |
| **PHPUnit** | `ApplicationsWorkspaceTest` **7** |
| **Stage exit** | **`PASS_WITH_KNOWN_ISSUES`** |
| **Product fixes** | **None** (QA harness label assertion only) |

---

## 9b. Stage 7 — decision & communication (local)

| Item | Detail |
| --- | --- |
| **Report** | `docs/OURNEAR_STAGE_7_DECISION_COMMUNICATION_QA_REPORT.md` |
| **Run ID** | **`20261010T094758Z-4a609602`** — **10 PASS**, **1 NOT_TESTED**, **1 MISSING** |
| **PHPUnit** | `CommunicationLifecycleTest` **6**; `ApplicationExperienceTest` **12** |
| **Stage exit** | **`PASS_WITH_KNOWN_ISSUES`** |
| **Product fixes** | **None** (QA journey locators only) |

---

## 9c. Stage 8 — candidate ↔ admin (local)

| Item | Detail |
| --- | --- |
| **Report** | `docs/OURNEAR_STAGE_8_CANDIDATE_ADMIN_WORKFLOW_QA_REPORT.md` |
| **Run ID** | **`20261010T100517Z-e9b1af00`** — **13 PASS**, **1 NOT_TESTED**, **1 MISSING** |
| **PHPUnit** | `ApplicationExperienceTest` **12** |
| **Stage exit** | **`PASS_WITH_KNOWN_ISSUES`** |
| **Product fixes** | **None** |

---

## 9d. Stage 9 — employer ↔ candidate (local)

| Item | Detail |
| --- | --- |
| **Report** | `docs/OURNEAR_STAGE_9_EMPLOYER_CANDIDATE_WORKFLOW_QA_REPORT.md` |
| **Run ID** | **`20261010T101834Z-87fd48ef`** — **10 PASS**, **4 MISSING** |
| **PHPUnit** | `RecruitmentOperationsStage6Test` **7** |
| **Stage exit** | **`PASS_WITH_KNOWN_ISSUES`** |
| **Product fixes** | **None** |

---

## 10. Stage 4 — employer workspace (local)

| Item | Detail |
| --- | --- |
| **Report** | `docs/OURNEAR_STAGE_4_EMPLOYER_QA_REPORT.md` |
| **Run ID** | **`20261010T083818Z-74610d3c`** — pytest **12/12**; **20/20** checklist PASS |
| **PHPUnit** | `EmployerCompanyFoundationStage4Test` **8**; `EmployerAccountWorkspaceTest` **11** |
| **Stage exit** | **`PASS_WITH_KNOWN_ISSUES`** |
| **Product fixes** | **None** |

---

## 11. Stage 3 — candidate application workflow (local)

| Item | Detail |
| --- | --- |
| **Report** | `docs/OURNEAR_STAGE_3_APPLICATION_WORKFLOW_QA_REPORT.md` |
| **Run ID** | Checklist **`20261010T073839Z-d2b6b491`** (12/12); gap **`20261010T081721Z-77729f53`** (5/5) |
| **PHPUnit** | `ApplicationExperienceTest` **12**; `JobApplicationTest` **5** |
| **Stage exit** | **`PASS_WITH_KNOWN_ISSUES`** — §F gaps A–E closed in browser (see report §L) |
| **Product fixes** | **None** |

---

## 12. Stage 10 — security, negative cases & recovery (local)

| Item | Detail |
| --- | --- |
| **Report** | `docs/OURNEAR_STAGE_10_SECURITY_NEGATIVE_RECOVERY_QA_REPORT.md` |
| **Canonical browser run ID** | **`20261010T111658Z-29e6e2f6`** — pytest **7/7**; **11 PASS**, **1 MISSING** (**STG10-KNOWN-001**) |
| **Prior browser run (preserved)** | **`20261010T110936Z-4dad1081`** |
| **Evidence** | `qa-agent/artifacts/stage10/20261010T111658Z-29e6e2f6/stage10_results.json` |
| **Stage exit** | **`PASS_WITH_KNOWN_ISSUES`** |
| **Stage 10 product fixes** | **None** (QA evidence only) |

**Profile verdict unchanged:** **`PASS_WITH_COVERAGE_GAPS`**.

---

## 13. Stage 11 — complete recruitment lifecycle (local)

| Item | Detail |
| --- | --- |
| **Report** | `docs/OURNEAR_STAGE_11_COMPLETE_RECRUITMENT_LIFECYCLE_QA_REPORT.md` |
| **Run ID** | **`20261010T113310Z-6782260a`** — **10 PASS**, **4 NOT_TESTED**, **5 MISSING** |
| **Fixture** | `ONT-A-000051` **Interview** cross-role consistency (read-only) |

---

## 14. Stage 12 — final regression & launch gate (local)

| Item | Detail |
| --- | --- |
| **Report** | `docs/OURNEAR_STAGE_12_FINAL_REGRESSION_RELEASE_READINESS_REPORT.md` |
| **PHPUnit** | **714** / **2** skipped / **12,897** assertions — `storage/logs/phpunit-stage12-20261010.txt` |
| **Harness** | stage12 + `test_web_release_gate.py` — **5 passed** |
| **Build** | `npx tsc --noEmit` + `npm run build` exit **0** |

---

## 15. Remaining gaps

- Staging smoke / allowlist execution (**separate authorization**)  
- **Combined recruitment model (2026-10-10):** implemented locally; post-implementation PHPUnit **729/2 skip/0 fail** (`storage/logs/phpunit-post-implementation-20261010-rerun.txt`); Stage 11 refresh **`20261010T170415Z-f4676464`** — self-service UI cases **PASS**, lifecycle journey **FAIL** (admin env), E2E **SKIPPED** (429) — `docs/OURNEAR_POST_IMPLEMENTATION_REGRESSION_QA_REPORT.md`. Prior Stage 11 canonical **`20261010T113310Z-6782260a`** preserved.  
- Profile field NOT_TESTED matrix; email delivery proof  
- Single pytest archive of all P0 journeys; Stage 1–2 extended browser inventories  

---

## 16. Release decision

| Status | **`insufficient_evidence`** |
| --- | --- |
| **Why not `release_ready`** | Staging unverified; targeted manual/journey scope; gate semantics unchanged |
| **Why not `not_release_ready`** | Full PHPUnit green; CAN-02 and PUB-02 closure gaps addressed with browser + PHPUnit evidence |

Manual PASS and targeted pytest runs **do not** substitute for full release verification.
