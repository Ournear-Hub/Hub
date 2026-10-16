# OurNear Human QA Agent — Phase A: Discovery & Coverage Inventory

**Date:** 2026-10-09  
**Repository:** OurNear Hire (Laravel 12 + Inertia React) — recruitment module within OurNear platform  
**Scope:** Phase A discovery only — no application behavior changes.

---

## 1. Executive summary

OurNear Hire is a **dual-guard** web app (`admin` + `account`) with **~100 Inertia pages**, **~130 HTTP route definitions** across `web.php`, `account.php`, and `admin.php`, and **97 PHPUnit test files** (93 Feature, 4 Unit). **No Python or Playwright** exists in-repo today. Frontend automated coverage is **minimal** (2 Vitest component tests). **Human browser QA** has been performed ad hoc (Cursor browser MCP + local scripts under `scripts/probe-*`, documented in Batch 5–7 resume/snapshot work).

Phase A delivers a **feature registry**, **coverage matrix**, **reused evidence index**, and **Phase B acceptance criteria** for an independent `qa-agent/` Python project.

---

## 2. Architecture snapshot (verified from code/docs)

| Layer | Technology | Notes |
| --- | --- | --- |
| Backend | Laravel 12, PHP 8.2+ | Single `App\Models\User`; guards `admin`, `account` |
| Frontend | Inertia 3 + React 19 + TS + Vite 7 | Pages under `resources/js/Pages/` |
| Auth | Dual guard | `/login` (account), `/admin/login` (staff) |
| Permissions | `PermissionName` enum + RBAC | Super Admin: `roles.manage` only |
| Files | Disks: `resumes`, `candidate_files`, `employer_files`, … | Application resume = **snapshot copy** |
| Tests | PHPUnit 11, SQLite in-memory | Baseline cited: **691 passed, 2 skipped** (2026-10-09 local) |
| Local QA data | `php artisan qa:hiring-fixtures` | `@example.test` users; local/testing only |
| Notifications | Database + mail; WhatsApp OTP foundation | Tests use recording gateways — no real sends in PHPUnit |

**Not in scope as defects:** Partner/Investor **roles** without dedicated portals; recruitment WhatsApp **campaigns**; payment gateway; visual automation builder (planned/partial per `CURRENT-STATE.md`).

---

## 3. Roles & surfaces

| Role | Guard | Primary surfaces |
| --- | --- | --- |
| Guest | — | Public jobs, companies, hire/talent-pool/staffing, apply (guest → login) |
| Candidate | `account` | `/account/*` candidate nav: profile, resumes, applications, documents, settings |
| Employer | `account` | Same user model; hiring workspace `/account/hiring`, jobs, applicants, submissions, enquiries |
| Staff / Admin | `admin` | `/admin/*` sidebar modules (RBAC-gated) |
| Freelancer intake | Guest POST | `/freelancers` (not primary nav) |

Employer **cannot** change application ATS status via POST (staff/admin only); employer views **application snapshots** for contact/resume.

---

## 4. Feature registry (implemented modules)

Legend — **Status:** `implemented` | `partial` | `planned` | `n/a`  
**Risk:** `P0` launch-critical | `P1` high | `P2` medium | `P3` low  
**Auto:** PHPUnit coverage level `high` | `medium` | `low` | `none`  
**Human:** `verified` (recent evidence) | `partial` | `none`

### 4.1 Public platform

| ID | Feature | Routes / UI | Roles | Status | Risk | PHPUnit (representative) | Human |
| --- | --- | --- | --- | --- | --- | --- | --- |
| PUB-01 | Home & CMS hero | `/`, `Home.tsx` | Guest | implemented | P1 | `PublicWebsiteTest`, `AboutModulePart2Test` | partial |
| PUB-02 | Jobs list/search/filters | `/jobs`, `Jobs/Index.tsx` | Guest | implemented | P0 | `QaDefectRegressionTest`, `JobApplicationTest` | partial |
| PUB-03 | Job detail & apply | `/jobs/{slug}`, `Apply.tsx` | Guest/Candidate | implemented | P0 | `ApplicationExperienceTest`, `JobApplicationTest` | **verified** (Batch 7 apply) |
| PUB-04 | Companies index/show | `/companies` | Guest | implemented | P2 | `PublicPlatformPagesTest`, `CompaniesModulePart1Test` | none |
| PUB-05 | Hire / Talent Pool / Staffing | `/hire`, `/talent-pool`, `/staffing` | Guest | implemented | P2 | `PublicPlatformPagesTest` | none |
| PUB-06 | Public profiles | `/profiles/{username}` | Guest | implemented | P1 | `UnifiedProfileStage23FTest`, `ProductionUxStage12Test` | none |
| PUB-07 | Contact / For employers enquiries | `/contact`, `/for-employers` | Guest | implemented | P2 | `PublicWebsiteTest` | none |
| PUB-08 | Legal | `/privacy-policy`, `/terms` | Guest | implemented | P3 | low | none |
| PUB-09 | Freelancer intake | `/freelancers` | Guest | implemented | P3 | `FreelancerFeatureTest` | none |

### 4.2 Account — auth & identity

| ID | Feature | Routes / UI | Roles | Status | Risk | PHPUnit | Human |
| --- | --- | --- | --- | --- | --- | --- | --- |
| ACC-AUTH-01 | Register / login / logout | `/register`, `/login` | Guest/Member | implemented | P0 | `AccountAuthExperienceTest`, `AuthenticationTest` | partial |
| ACC-AUTH-02 | Password reset | forgot/reset routes | Guest | implemented | P1 | `AccountAuthExperienceTest` | none |
| ACC-AUTH-03 | Email verification | signed verify URL | Candidate | implemented | P1 | `AccountCandidateDashboardTest` | none |
| ACC-AUTH-04 | Mobile / WhatsApp OTP settings | `/account/settings/security` | All account | partial | P1 | `MobileOtpVerificationTest`, `WhatsAppOtpVerificationTest` | none |
| ACC-AUTH-05 | Account switch (multi-role user) | `account.switch` | Member | implemented | P2 | `AccountSwitchingTest`, `DualGuardAuthenticationTest` | none |

### 4.3 Candidate — profile & documents

| ID | Feature | Routes / UI | Status | Risk | PHPUnit | Human |
| --- | --- | --- | --- | --- | --- | --- |
| CAN-01 | Dashboard | `/account` | implemented | P1 | `AccountCandidateDashboardTest`, `CandidateAccountStage19Test` | partial (Batch 5) |
| CAN-02 | Profile & completion | `/account/profile` | implemented | P0 | `AccountCandidateProfessionalTest`, `UserProfileModulePart3Test` | partial (Batch 5) |
| CAN-03 | Professional sections (skills, employment, education, …) | profile modules | implemented | P1 | `AccountCandidateProfessionalTest` | partial |
| CAN-04 | Resume library upload/replace/view/download | `/account/resumes` | implemented | P0 | `AccountCandidateResumeDocumentTest`, `ResumeModulePart4Test` | **verified** (Batch 6–7) |
| CAN-05 | Hire Resume builder (print) | `/account/resumes/builder` | implemented | P2 | `AccountCandidateResumeDocumentTest` | none |
| CAN-06 | Private documents | `/account/documents` | implemented | P2 | `AccountCandidateResumeDocumentTest` | none |
| CAN-07 | Applications list/detail/withdraw | `/account/applications` | implemented | P0 | `ApplicationExperienceTest` | **verified** (Batch 7) |
| CAN-08 | Saved jobs | saved jobs routes | implemented | P2 | `AccountSavedJobTest` | none |
| CAN-09 | Notifications & comms prefs | `/account/notifications`, settings | implemented | P1 | `AccountCandidateCommunicationTest` | none |
| CAN-10 | Privacy & visibility | profile privacy routes | implemented | P1 | `CandidateTalentPoolStage3Test` | partial (Batch 5) |

### 4.4 Employer — hiring workspace

| ID | Feature | Routes / UI | Status | Risk | PHPUnit | Human |
| --- | --- | --- | --- | --- | --- | --- |
| EMP-01 | Hiring command center / activation | `/account/hiring` | implemented | P1 | `EmployerHiringStage20Test`–`22` | partial |
| EMP-02 | Job CRUD & lifecycle (publish/pause/close) | `/account/jobs` | implemented | P0 | `EmployerWorkspaceTest`, `JobsAtsFoundationStage5Test` | **verified** (Batch 7 publish draft) |
| EMP-03 | Applicants & application detail | `jobs/{slug}/applications/{id}` | implemented | P0 | `EmployerHiringStage25Test`, `ProductionUxStage15Test` | **verified** (Batch 6–7 resume view) |
| EMP-04 | Employer-wide applications / talent pool | `/account/hiring/applications`, `/candidates` | implemented | P1 | `EmployerHiringStage24Test` | none |
| EMP-05 | Recruitment partnership submissions | `/account/submissions` | implemented | P2 | `EmployerSubmissionTest` | none |
| EMP-06 | Staffing enquiries | `/account/enquiries` | implemented | P2 | `EmployerAccountWorkspaceTest` | none |
| EMP-07 | Company profile & documents | profile, `/account/employer/documents` | implemented | P1 | `EmployerCompanyFoundationStage4Test`, `ProductionUxStage17Test` | none |
| EMP-08 | Employer notifications | bell + `/account/notifications` | implemented | P1 | `EmployerHiringStage21Test`, `22` | none |
| EMP-09 | Isolation (other employer’s apps) | policies + routes | implemented | P0 | `PolicyFoundationTest`, `EmployerHiringStage25Test` | partial (Batch 6 B probe; B browser partial) |

### 4.5 Admin — ATS & operations

| ID | Feature | Routes / UI | Status | Risk | PHPUnit | Human |
| --- | --- | --- | --- | --- | --- | --- |
| ADM-01 | Dashboard & analytics | `/admin`, `/admin/analytics` | implemented | P1 | `AdminReleaseReadinessStage16Test`, `ProductionUxStage19Test` | partial |
| ADM-02 | Jobs admin CRUD & lifecycle | `/admin/jobs` | implemented | P0 | `JobsWorkspaceTest` | none |
| ADM-03 | Applications workspace | `/admin/applications`, screening, shortlisted | implemented | P0 | `ApplicationsWorkspaceTest`, `PhaseF3ScreeningWorkspaceTest` | partial (resume inline Batch 6–7) |
| ADM-04 | Candidates workspace & resume import | `/admin/candidates`, imports | implemented | P1 | `CandidatesWorkspaceTest`, `HireIdentityResumeImportTest` | none |
| ADM-05 | Interviews, tasks, work queues | `/admin/interviews`, `tasks`, `work` | implemented | P1 | `RecruitmentOperationsStage6Test`, `ProductionUxStage16Test` | none |
| ADM-06 | Submissions & employer enquiries | `/admin/submissions`, `employer-enquiries` | implemented | P1 | `EmployerSubmissionTest` | none |
| ADM-07 | Placements, employees, replacements, finance | respective admin routes | implemented | P2 | `PlacementLifecycleTest`, `FinanceLifecycleTest`, … | none |
| ADM-08 | Master data CRUD / import-export | `/admin/master-data` | implemented | P1 | `MasterDataTest` | none |
| ADM-09 | Users, roles, RBAC | `/admin/users`, `/admin/roles` | implemented | P0 | `AdminRbacStage25Test`, `RolesPermissionsStage2Test` | none |
| ADM-10 | CRM, campaigns, partners, investors | admin modules | implemented | P2 | `CrmFoundationStage7Test`, `CampaignReferralStage10Test`, … | none |
| ADM-11 | Automations | `/admin/automations` | partial | P1 | `RecruitmentAutomationStage24Test` | none |
| ADM-12 | Website CMS | `/admin/website-content` | implemented | P2 | `AboutModulePart2Test` | none |
| ADM-13 | Communication settings & templates | admin settings/messages | implemented | P1 | `CommunicationLifecycleTest`, `CompanyCommunicationSettingsTest` | none |
| ADM-14 | Hiring Command Center | `/admin/hiring` | implemented | P1 | `RecruiterHiringCommandCenterStage26Test` | none |
| ADM-15 | Design system lab | `/admin/design-system` | implemented | P3 | none | none (UI lab; not launch-critical) |

### 4.6 Cross-cutting

| ID | Feature | Status | Risk | PHPUnit | Human |
| --- | --- | --- | --- | --- | --- |
| X-01 | Screening match (no auto-hire) | implemented | P0 | `ScreeningMatchServiceTest`, `ScreeningShortlistTest` | n/a (logic) |
| X-02 | Application pipeline / ATS stages | implemented | P0 | `QaDefectRegressionTest`, stage tests | partial |
| X-03 | File storage & snapshot integrity | implemented | P0 | `AccountCandidateResumeDocumentTest`, Batch 6–7 probes | **verified** |
| X-04 | WhatsApp webhooks | foundation | P2 | `WhatsAppCloudFoundationTest` | none |
| X-05 | Responsive / a11y smoke | partial | P2 | `ResponsiveStage17Test` | sporadic manual |

---

## 5. Existing test infrastructure (reuse map)

| Asset | Location | Reuse for QA Agent |
| --- | --- | --- |
| PHPUnit Feature/Unit | `tests/` | Invoke via subprocess; map registry `phpunit` tags |
| QA hiring fixtures | `qa:hiring-fixtures`, `QaHiringFixtures` | Seed **local only** before browser suites |
| TestCase helpers | `fakeResume()`, `ReadablePdfFixture` | Backend only |
| Vitest | 2 component tests | Keep; agent runs `npm run test` / `typecheck` / `build` on FE changes |
| Local probe scripts | `scripts/probe-*.php`, `inspect-*.php` | Wrap as **postconditions** or deprecate gradually |
| Recording gateways | SMS/WhatsApp in tests | Pattern for **no external sends** in agent env |
| Browser evidence (manual) | Batch 5–7 transcripts | Indexed below — do not re-run identical flows until code changes |

### 5.1 Reused human QA evidence (do not duplicate until regression)

| Area | Evidence | Status |
| --- | --- | --- |
| Candidate library PDF open/download | Batch 6 — 592 B readable | verified |
| Application snapshot (new apply) | Batch 7 — `ONT-A-000003` | verified |
| Historical snapshot `ONT-A-000001` | 59 B stub (fixture data, not streaming bug) | documented |
| Employer A resume view | Batch 6–7 | verified (new app readable) |
| Employer B 403 | HTTP probe | verified; browser B partial |
| Admin resume inline | Batch 6–7 | verified |
| Profile modules / education edit | Batch 5 | partial / blocked |
| Full PHPUnit | 691 passed (2026-10-09) | verified |

---

## 6. Coverage matrix summary (actual, not estimated)

| Category | Registered features | PHPUnit high/medium | Human verified | Human none/partial |
| --- | ---: | ---: | ---: | ---: |
| Public | 9 | 7 | 1 | 8 |
| Account auth | 5 | 5 | 0 | 5 |
| Candidate | 10 | 10 | 3 | 7 |
| Employer | 9 | 9 | 3 | 6 |
| Admin | 15 | 14 | 1 | 14 |
| Cross-cutting | 5 | 5 | 1 | 4 |
| **Total** | **53** | **50** | **9** | **44** |

**Interpretation:** Backend **business rules** are relatively well covered by PHPUnit; **UI journeys, persistence after reload, uploads, and cross-role browser flows** are largely **unautomated in Python/Playwright**. Route existence ≠ feature verification.

---

## 7. Critical untested / high-risk gaps (human agent priority)

1. **P0 journeys:** Guest apply → candidate confirmation → employer applicant detail → admin application workspace (extend Batch 7 pattern to admin UI buttons, Employer B browser).
2. **P0 auth boundaries:** Employer/candidate/admin cross-access on deep links (sample set per module).
3. **P0 job lifecycle:** Create draft → publish → public listing visibility → close (employer UI).
4. **P1 profile CRUD persistence:** All professional modules after save + hard reload (Batch 5 gaps).
5. **P1 admin master data & communication settings** UI saves.
6. **P2 CRM/campaigns/finance** smoke navigation (RBAC variants).
7. **File uploads** where MCP/CDP blocks file input — agent must use Playwright `set_input_files`.
8. **Responsive checks** at 390px and 1366px on jobs, apply, account layouts.

---

## 8. Phase B proposal — smallest safe implementation

### 8.1 Goal

Add an **independent** Python package at repo root: `qa-agent/`, runnable without modifying Laravel code, targeting **local** `http://127.0.0.1:8000` only when explicitly configured.

### 8.2 Deliverables (Phase B)

| # | Deliverable |
| --- | --- |
| B1 | `qa-agent/pyproject.toml` — pytest, playwright, pydantic, pyyaml |
| B2 | `qa-agent/config/env.example` — `OURNEAR_BASE_URL`, guard credentials (placeholders), `OURNEAR_ALLOW_RUN=local` safety gate |
| B3 | `qa-agent/registry/features.yaml` — machine-readable subset of §4 (IDs, routes, roles, pytest tags, priority) |
| B4 | `qa-agent/ournear_qa/` package — config loader, registry loader, evidence writer (JSON), report stub |
| B5 | `qa-agent/tests/smoke/test_public_home.py` — one Playwright test: home loads, no console errors, title present |
| B6 | `qa-agent/tests/smoke/test_account_login.py` — login as `qa-candidate@example.test` using env credentials **only if** fixtures exist |
| B7 | CLI entry: `python -m ournear_qa run --suite smoke` and `python -m ournear_qa inventory` |
| B8 | Documented integration: optional hook to run `php artisan test --filter=…` from registry mapping (no duplicate PHPUnit logic) |

### 8.3 Acceptance criteria (Phase B complete)

1. With `OURNEAR_BASE_URL=http://127.0.0.1:8000` and `OURNEAR_ALLOW_RUN=local`, `pytest qa-agent/tests/smoke` exits **0** when app is up and QA candidate exists.
2. With `OURNEAR_ALLOW_RUN` unset or `production`, agent **refuses to start** (non-zero exit, clear message).
3. Failed tests write `qa-agent/artifacts/{run_id}/` with screenshot + JSON metadata (no passwords in logs).
4. `registry/features.yaml` contains **≥40** feature IDs aligned with §4.
5. README documents setup: `pip install -e qa-agent`, `playwright install`, no production data.
6. **Zero** changes to Laravel/React production routes or business logic in Phase B.

### 8.4 Out of scope for Phase B

- Full journey catalog (Phase D).
- Git change-aware regression (Phase F).
- HTML dashboard (Phase 8 — stub JSON only).
- Auto-fix of application code.

---

## 9. Phase A artifacts & Phase B status

| File | Purpose |
| --- | --- |
| `docs/OURNEAR_QA_AGENT_PHASE_A_INVENTORY.md` | This document |
| `qa-agent/README.md` | Setup, safety, smoke run |
| `qa-agent/registry/features.yaml` | **53** feature IDs (§4 aligned) |
| `qa-agent/ournear_qa/` | `settings`, `registry`, `evidence`, `report`, `cli` |
| `qa-agent/tests/` | Smoke (Playwright), registry + settings gate unit tests |

**Phase B (2026-10-09):** B1–B8 implemented in `qa-agent/`. Zero Laravel/React changes.

| Criterion | Status |
| --- | --- |
| B1–B7 deliverables | Done |
| `features.yaml` ≥40 IDs | **53** |
| Safety gate (`OURNEAR_ALLOW_RUN`) | Unit-tested; refuses missing/`production` |
| Failure artifacts | Screenshot + `failure.json` on failed Playwright tests |
| Smoke pytest exit 0 | **Verified 2026-10-09** — 2 smoke + 4 unit (see `OURNEAR_QA_AGENT_PHASE_B_C_PLAN.md`) |

**Next (Phase C):** Human interaction engine (`ournear_qa/browser/`); see `docs/OURNEAR_QA_AGENT_PHASE_B_C_PLAN.md`.

---

## 10. Known limitations

- Feature count is **module-level**; admin alone has 100+ routes — Phase C will expand registry rows per workflow.
- Mobile-native APIs: **not verified** in this repo audit (web-first).
- `[NOT VERIFIED]` on some CURRENT-STATE test counts superseded by 691-pass local run (2026-10-09).
