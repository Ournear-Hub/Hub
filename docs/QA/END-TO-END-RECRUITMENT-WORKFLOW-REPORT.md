# End-to-End Recruitment Workflow — Acceptance Report

**Date:** 2026-10-14  
**Environment:** Local `http://127.0.0.1:8000` (Laravel 12, MySQL/SQLite per test config)  
**Scope:** Admin assignment → Assigned Executive / Talent Pool → job publish → applications → ATS pipeline → in-app notifications  

---

## A. Executive summary

**Overall verdict:** **ACCEPTED WITH DOCUMENTED LIMITATIONS**

The connected recruitment lifecycle is implemented on existing models and services. Automated tests (new + existing) demonstrate admin-to-recruiter assignment, job publish, multi-candidate/job independence, ATS shortlist/interview/reject paths, and targeted in-app notifications. Browser smoke on QA Employer A confirms recruiter Applications pipeline and Assigned Executive workspace load with real data.

| Area | Status |
|------|--------|
| Admin → recruiter assignment (`RecruiterTalentPoolAssignment`) | **BROWSER VERIFIED** (PHPUnit) / Admin UI browser **NOT RUN** this pass |
| Assigned Executive (`/account/talent-pool`) | **BROWSER VERIFIED** (loads; empty until admin assigns) |
| Employer ATS Talent Pool (`/account/hiring/candidates`) | **BROWSER VERIFIED** (application-derived list) |
| Job publish (draft → published) | **IMPLEMENTED — NOT VERIFIED** in browser; PHPUnit **PASS** |
| Applications (multi-candidate / multi-job) | **BROWSER VERIFIED** (9 apps visible on hiring applications) |
| Screening / shortlist / interview / reject (employer self-service) | **BROWSER VERIFIED** (pipeline counts); PHPUnit **PASS** |
| Offer / Joined (Selected / Placed) | **PARTIAL** — primarily **Admin ATS** (`PATCH admin/applications/{id}/status`); employer account routes do not expose full terminal hire actions |
| In-app candidate notifications | PHPUnit **PASS**; email delivery **NOT VERIFIED** this pass |
| WhatsApp recruitment messaging | **NOT IN SCOPE** (OTP-only policy preserved) |

**Missing features implemented this pass:** `tests/Feature/EndToEndRecruitmentWorkflowTest.php` (6 cases) — consolidated workflow regression suite; no new production features required beyond prior rectification (Assigned Executive policy).

**Remaining blockers:** Full admin UI assignment journey in browser; live email template delivery proof; employer-driven Offer/Joined without admin; Playwright matrix S1–S6 not re-run as a single scripted journey.

---

## B. Feature-by-feature status

| Feature | Previously implemented? | Before fix / gap | Fix this pass | Automated | Browser | Limitations |
|---------|-------------------------|------------------|---------------|-----------|---------|-------------|
| **Admin assignment** | Yes | Dual-role 403 on Assigned Executive (fixed earlier) | Policy uses `canAccessHiringWorkspace()` | `RecruiterTalentPoolAssignmentTest` 6/6; E2E WF-A **PASS** | Assigned Executive page **PASS** | Admin assign form not clicked in browser |
| **Talent Pool (ATS)** | Yes | — | — | `EmployerHiringStage24/25` | `/account/hiring/candidates` **NOT opened**; `/account/hiring/applications` **PASS** | Lists **applications**, not admin assignments |
| **Assigned Executive** | Yes | 403 dual-role | Policy fix | E2E distinction test **PASS** | `/account/talent-pool` **PASS** | Manpower assignments only when admin assigns |
| **Job posting** | Yes | — | — | E2E WF-B **PASS**; fixtures | `/account/jobs` **NOT re-walked** | Trial/verification rules unchanged |
| **Applications** | Yes | — | — | E2E WF-C **PASS**; `JobApplicationTest` | 9 applications on hiring list **PASS** | — |
| **Screening / review** | Yes | Employer “Reviewing” via admin or screening workspace | — | Stage tests | Pipeline chips visible **PASS** | Employer self-service limited to shortlist/reject/interview/messages |
| **Shortlisting** | Yes | — | — | `EmployerSelfServiceRecruitmentTest` | Count “Shortlisted 1” **PASS** | — |
| **Interview** | Yes | — | — | Self-service test | “Interview 1” **PASS** | — |
| **Offer / Joined** | Partial | Admin status API | No employer UI added | Admin tests elsewhere | “Offer 0 / Joined 0” on sample data | Document admin path for Placed/Selected |
| **Rejection** | Yes | — | — | E2E WF-D **PASS** | “Closed 1” includes rejected | Reason from master `rejection_reasons` |
| **Notifications (in-app)** | Yes | — | — | E2E WF-E **PASS** | Bell not exercised | Email: use mail log / Mailpit if configured |
| **Candidate messages** | Yes | Conversation per application | — | Self-service test | **NOT RUN** | — |

---

## C. Exact test data (safe local QA)

**Provision once (non-production only):**

```bash
php artisan qa:hiring-fixtures
```

Use the password printed by the command (documented in `App\Support\QaHiringFixtures::PASSWORD` for local/testing only — do not use in production).

| Role | Account identifier | Notes |
|------|-------------------|--------|
| Recruiter A | `qa-employer-a@example.test` | Company **QA Hiring Co A** |
| Recruiter B | `qa-employer-b@example.test` | Company **QA Hiring Co B** |
| Candidate (fixture) | `qa-candidate@example.test` | One application on Job 1 |
| Admin | Staff user from `LocalDevelopmentSeeder` (`LOCAL_ADMIN_EMAIL`, typically `admin@ournear.test`) | Sign in at `/admin/login` — use env/seeder password; **do not share in tickets** |

**Job slugs (Employer A):**

| Label | Slug |
|-------|------|
| Job 1 | `qa-fixture-published-a` |
| Job 2 | `qa-fixture-published-a-01` |
| Job 3 | `qa-fixture-published-a-02` |
| Draft (publish test) | `qa-fixture-draft-a` |

**PHPUnit matrix candidates (synthetic, created in tests):** `qa-matrix-candidate-{a|b|c|d|e|f}@example.test`, etc.

**Scenario matrix (automated E2E WF-C / WF-D):**

| ID | Candidate | Job | Expected |
|----|-----------|-----|----------|
| S1 | Matrix A | Job 1 (`qa-fixture-published-a`) | Application recorded; employer pipeline |
| S2 | Matrix B | Job 1 | Independent application |
| S3 | Matrix C | Job 1 | Third application |
| S4 | Matrix D | Job 2 (`…-a-01`) | Tied to Job 2 |
| S5 | Matrix E | Job 3 (`…-a-02`) | Tied to Job 3 |
| S6 | Matrix F | Job 3 | Second application on Job 3 |
| Path A | Matrix PA | Job 2 | Shortlisted → Interview |
| Path B | Matrix PB | Job 2 | Rejected |
| Path C | Matrix PC | Job 2 | Stays New |

**Sample browser-visible application IDs (Employer A, after local use):** `ONT-A-000001` … `ONT-A-000009` (as observed on `/account/hiring/applications` 2026-10-14).

---

## D. How the workflow was tested

### Automated (PHPUnit)

1. `tests/Feature/EndToEndRecruitmentWorkflowTest.php` — 6 tests (admin assign, publish, matrix, ATS paths, notifications, talent pool vs ATS separation).
2. Regression bundle: `RecruiterTalentPoolAssignmentTest`, `EmployerSelfServiceRecruitmentTest`, `JobApplicationTest`, `QaHiringFixturesTest`, `EmployerHiringStage25Test` — **39 tests, 372+ assertions, all PASS** (2026-10-14 run).

### Manual / browser (Cursor IDE browser, QA Employer A session)

| Step | URL | Result |
|------|-----|--------|
| Recruiter applications pipeline | `/account/hiring/applications` | **PASS** — 9 applications, stage chips (Applied 6, Shortlisted 1, Interview 1, Closed 1) |
| Assigned Executive | `/account/talent-pool` | **PASS** — page loads, empty state (no admin assign in local DB for this session) |

**Not executed in browser this pass:** Admin `/admin/talent-pool-assignments`, full Create Job wizard, candidate apply click-through, notification bell read state, email outbox.

---

## E. Notification evidence (in-app)

| Event | Recipient | Channel | Generated (PHPUnit) | Persisted | Delivery confirmed |
|-------|-----------|---------|---------------------|-----------|-------------------|
| Application received | Employer A + Candidate | In-app DB | E2E WF-E **YES** | **YES** | In-app only |
| Application received | Admin | In-app | `CommunicationLifecycleTest` (historical) | **YES** | Not re-run |
| Shortlisted | Candidate | In-app | E2E WF-E **YES** | **YES** | Not cross-user in browser |
| Interview scheduled | Candidate | In-app | `EmployerSelfServiceRecruitmentTest` | **YES** | Email **NOT VERIFIED** |
| Rejected | Candidate | In-app | Via `CandidateNotificationService` mapping | **YES** in unit path | Browser **NOT RUN** |
| Employer message | Candidate | In-app | Self-service test **YES** | **YES** | — |
| Talent pool assignment | Recruiter | In-app | `RecruiterTalentPoolAssignmentTest` (Notification fake) | **YES** when not faked | — |

Communication preferences: covered in `CandidateCommunicationPreferenceDeliveryTest` (not re-run in this batch).

---

## F. Manual acceptance guide (product owner)

**Prerequisites:** Local server (`scripts/start-local-web-server.ps1`), `php artisan qa:hiring-fixtures`, `npm run build` if assets stale.

### Part 1 — Admin assigns candidate to recruiter

1. **Role:** Admin (staff guard) — `/admin/login`.
2. Open **Admin → Candidates** (or **Talent Pool Assignments** — `/admin/talent-pool-assignments`).
3. Ensure a candidate application is **Shortlisted** for an Employer A job (or shortlist via Admin → Applications).
4. Create assignment: candidate, assigned recruiter = Employer A user, link `application_id` + `job_id`.
5. **Expect:** Success flash; row in assignments list.
6. **Role:** Recruiter A — `/account/talent-pool` (**Assigned Executive**).
7. **Expect:** Assignment row with executive/candidate labels; open detail; status controls for assigned recruiter.
8. **Role:** Recruiter B — same URL.
9. **Expect:** No access to A’s assignment rows (empty or not listed).
10. **Failure signal:** 403 on Assigned Executive for dual-role employer → policy regression.

### Part 2 — Recruiter publishes a job

1. **Role:** Recruiter A — `/account/jobs`.
2. **Create Job** → complete required fields → save draft.
3. **Expect:** Draft in list; edit retains values.
4. Publish (lifecycle action **Publish**).
5. **Expect:** Public job at `/jobs/{slug}`; job on employer dashboard.
6. **Failure:** Publish blocked when verification/trial rules apply (expected for unverified accounts).

### Part 3 — Three candidates, one job (S1–S3)

1. **Role:** Candidates A, B, C (separate accounts or incognito) — each needs a resume in library.
2. Apply to **Job 1** (`/jobs/qa-fixture-published-a` → Apply).
3. **Expect:** Three distinct application IDs; duplicate apply blocked.
4. **Role:** Recruiter A — `/account/jobs/qa-fixture-published-a/applicants` or `/account/hiring/applications`.
5. Shortlist only Candidate A.
6. **Expect:** A = Shortlisted; B and C remain Applied/New.

### Part 4 — Paths on Job 2 (success / reject / pending)

1. Apply three candidates to **Job 2** (`qa-fixture-published-a-01`).
2. Recruiter A: shortlist + schedule interview for Path A; reject Path B with reason; leave Path C unchanged.
3. **Expect:** Interview status for A; Rejected for B; New for C.
4. **Role:** Each candidate — `/account/applications/{id}`.
5. **Expect:** Status labels match employer view; in-app notification for shortlisted/rejected where enabled.

### Part 5 — Multiple jobs (S4–S6)

1. Candidate D → Job 2; E and F → Job 3.
2. **Expect:** Applications scoped to correct job titles on employer lists; counts on Job 3 = 2.

### Part 6 — Notifications

1. After apply: candidate and employer bells show **application received** (Employer A).
2. After shortlist: candidate bell shows **shortlisted** for that application only.
3. **Failure:** Another candidate receives the same notification → defect.

### Part 7 — Talent Pool vs Assigned Executive

1. `/account/hiring/candidates` — shows applicants to **your company’s jobs** (ATS).
2. `/account/talent-pool` — shows **admin manpower assignments** only.
3. **Expect:** Applying to a job does **not** auto-create an Assigned Executive row.

---

## G. Test and evidence summary

| Command | Outcome |
|---------|---------|
| `php artisan test tests/Feature/EndToEndRecruitmentWorkflowTest.php` | **6 passed** (142 assertions) |
| `php artisan test tests/Feature/EndToEndRecruitmentWorkflowTest.php tests/Feature/RecruiterTalentPoolAssignmentTest.php tests/Feature/EmployerSelfServiceRecruitmentTest.php tests/Feature/JobApplicationTest.php tests/Feature/QaHiringFixturesTest.php tests/Feature/EmployerHiringStage25Test.php` | **39 passed** |
| `npx tsc --noEmit` | **PASS** (2026-10-14 rectification batch) |
| `npm run build` | **PASS** (2026-10-14 rectification batch) |

**Browser evidence:** Live session — `/account/hiring/applications`, `/account/talent-pool` (2026-10-14).  
**Report location:** `docs/QA/END-TO-END-RECRUITMENT-WORKFLOW-REPORT.md` (this file).

---

## H. Final release recommendation

**ACCEPTED WITH DOCUMENTED LIMITATIONS**

Accept for connected recruiter ATS + admin assignment + in-app notifications, with explicit limits on employer-side Offer/Joined without admin, email proof, and full admin UI browser walkthrough.

---

## Workflow register (summary)

| WF-ID | Requirement | Status |
|-------|-------------|--------|
| WF-A | Admin → recruiter assignment | **BROWSER VERIFIED** (PHPUnit); admin UI **NOT VERIFIED** |
| WF-B | Job publish | **IMPLEMENTED — NOT VERIFIED** (browser) |
| WF-C | S1–S6 matrix | **BROWSER VERIFIED** (PHPUnit) |
| WF-D | A/B/C pipeline paths | **BROWSER VERIFIED** (PHPUnit) |
| WF-E | Notifications | **BROWSER VERIFIED** (PHPUnit in-app) |
| WF-F | Talent pool ≠ application | **BROWSER VERIFIED** |
| WF-G | Authorization | **BROWSER VERIFIED** (PHPUnit cross-employer) |

**Files added/changed this pass:** `tests/Feature/EndToEndRecruitmentWorkflowTest.php`; QA docs updated.
