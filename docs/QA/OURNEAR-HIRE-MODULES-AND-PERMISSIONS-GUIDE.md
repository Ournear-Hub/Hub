# OurNear Hire — Modules and Permissions Guide

**Last updated:** 2026-10-15  
**Audience:** Product, admin staff, recruiters, employer company users  

This guide explains how **Hiring**, **Talent Pool**, and **Staffing** differ, what each Candidate Allocation option means, and how company module access works. Status labels:

| Label | Meaning |
| --- | --- |
| **Implemented** | Code merged in working tree |
| **PHPUnit verified** | Covered by automated tests in this pass |
| **Browser verified** | Manually checked on local `127.0.0.1:8000` |
| **Pending** | Not yet verified in browser or full regression |

---

## Three business modules

### A. Hiring (employer ATS)

- **Purpose:** Post jobs, receive applications, screen, shortlist, interview, offer, join.
- **Who:** Verified or trial-eligible employers with hiring workspace access.
- **Routes:** `/account/jobs`, `/account/hiring/*`, public `/jobs`.
- **Not the same as:** Talent Pool internal jobs or Staffing manpower requirements.

### B. Talent Pool (OurNear recruitment)

- **Purpose:** Admin/recruiters source, assign, progress, and optionally submit candidates to employers.
- **Who:** Staff with admin allocation permissions; recruiters with hiring workspace access.
- **Routes:** Admin `GET /admin/talent-pool-assignments`; recruiter `GET /account/talent-pool`; Talent Pool jobs `GET /account/talent-pool/jobs`.
- **Talent Pool jobs:** `listing_scope` = `talent_pool_internal` (or combined where authorized) — **not** shown on public job board when internal-only (**PHPUnit verified**).

### C. Staffing (manpower requirements)

- **Purpose:** Employer workforce requirements; admin approval before submit; allocation to recruiters via **Staffing Requirement** assignment type.
- **Who:** Employers after **Staffing access = Approved**; admin approves access separately from company verification.
- **Routes:** `GET /account/enquiries` (staffing requirements), admin employers staffing actions.
- **Not the same as:** A job application or a Talent Pool job listing.

---

## Company registration vs module access

| Concept | Stored on | Auto-granted on register? |
| --- | --- | --- |
| Company registration | `employer_profiles` + user | Yes (profile exists) |
| Email / mobile verification | `users` | No |
| OurNear / job posting approval | `employer_profiles.job_posting_approved_at` | No |
| Hiring (public post) | `EmployerPostingEligibilityService` | No |
| Talent Pool (recruiter work) | Admin allocation | No |
| Staffing access | `employer_profiles.staffing_access_status` | **No** (**PHPUnit verified**) |

### Staffing access states

`not_requested` → `pending_approval` → `approved` | `rejected` | `revoked`

- **Request:** `POST /account/company/staffing-access/request` (company user).
- **Approve / reject / revoke:** Admin `PATCH /admin/employers/{employer}/staffing-access/*` (**Implemented**; admin UI on Employers → Verification tab).
- **Audit:** `employer_staffing_access_events` table.
- **Notifications:** In-app admin alert on request; in-app employer notice on approve/reject (**PHPUnit verified** for request + approve). Email delivery **not tested** in this pass.

---

## Why OurNear was missing from the admin recruiter dropdown (2026-10-15)

**Root cause:** Recruiter options were loaded with `users.role = employer` only. OurNear’s login (`mukunda10`, user id 4) is **`candidate`** role with a linked **`employer_profiles`** row (company `ournear`, id 2). Hiring workspace access uses `canAccessHiringWorkspace()` (profile exists), but the admin allocation UI did not.

**Fix:** `App\Support\RecruitingEmployerAccounts` — users with a non-empty `employer_profiles.company_name` appear in **Candidate Allocation** recruiter lists and admin candidate assign props (**PHPUnit verified**).

**Not the same issue as:** Staffing requirement selector (still `employer_enquiries` only).

---

## Local development — account email verification (no SMTP required)

| Mechanism | When | Safeguard |
| --- | --- | --- |
| `php artisan ournear:local-verify-account-email {email\|username} [--actor=admin@…]` | `APP_ENV=local` or `testing` | Refuses `production`; audits one user |
| `POST /admin/users/{user}/local-verify-email` | Same + `users.manage` | Route 404 outside local/testing |

Does **not** change company posting approval, Staffing access, or phone verification. Does **not** mark all users verified.

**Local mail:** With `MAIL_MAILER=log`, resend-verification still “sends” — read `storage/logs/laravel.log` (`local.DEBUG` mail body). No inbox delivery.

---

## Why OurNear appeared in admin companies but not the Staffing selector

**Root cause (evidence-based):**

1. Admin **Companies / Employers** list shows **`employer_profiles`** (e.g. OurNear profile id 2, company name `ournear`).
2. **Candidate Allocation → Staffing Requirement** dropdown lists **`employer_enquiries`** (submitted staffing requirements), not companies.
3. Local data had **no staffing enquiry** tied to OurNear; only a legacy/unlinked enquiry for another company.
4. An approved company **without** a submitted requirement correctly **does not** appear in the requirement selector (**PHPUnit verified**).

**Company directory** (for context in admin allocation UI) is separate from the requirement selector (`StaffingRequirementAllocationQuery::companiesForAdminDirectory()`).

---

## Candidate Allocation — four assignment types

Admin: **Candidate Allocation** → `POST /admin/talent-pool-assignments`

**Recruiter eligibility (2026-10-15):** Any account with a named `employer_profiles` row and `canAccessHiringWorkspace()` may receive assignments — not only `users.role = employer`. Persist path uses `RecruitingEmployerAccounts::canActAsRecruiter()` (matches admin dropdown).

| Type | When to use | Required fields | Creates application? |
| --- | --- | --- | --- |
| **Talent Pool** | OurNear sourcing / screening without tying to one job | Candidate + eligible recruiter | **No** |
| **Specific Job** | Candidate tied to a job owned by recruiter’s company | Candidate + recruiter + job (`job_listings.employer_id` = recruiter profile) | **No** (optional link to existing shortlisted `application_id`) |
| **Staffing Requirement** | Manpower against a submitted enquiry | Candidate + recruiter + eligible `employer_enquiry_id` | **No** |
| **General Candidate Follow-up** | Contact / complete profile / follow-up | Candidate + recruiter only | **No** |

**Employer submission** (`POST /account/talent-pool/.../submit-to-employer`) is separate: requires a **real** linked shortlisted application; does not run on allocation alone.

| Type | Required fields | Optional | Appears for recruiter | Creates fake application? |
| --- | --- | --- | --- | --- |
| **Talent Pool** | Recruiter | Instructions, follow-up date | Yes (`/account/talent-pool`) | No |
| **Specific Job** | Recruiter + valid job | Application link when applicable | Yes | No |
| **Staffing Requirement** | Recruiter + eligible enquiry | Instructions | Yes | No |
| **General follow-up** | Recruiter | Employer, job, requirement | Yes | No |

Backend: `RecruiterTalentPoolAssignmentService` + `RecruiterTalentPoolAssignmentType` enum.

---

## Workflows (step-by-step)

### A. Admin candidate allocation

1. Sign in as admin → **Candidates → Candidate Allocation**.
2. Search/select candidate(s) → **Assign to Recruiter**.
3. Choose type (default **Talent Pool**).
4. For **Staffing Requirement**, pick an enquiry from the list; if empty, read empty-state (request access / submit requirement).
5. Save → recruiter sees **Assigned Candidates**.

### B. Recruiter Talent Pool job creation

1. Recruiter → **Talent Pool** → **Talent Pool jobs** → Create.
2. Complete standard job fields.
3. Choose **Publication destination:** Talent Pool internal and/or public (subject to employer posting rules).
4. Save — internal-only jobs stay off public `/jobs` (**PHPUnit verified**).

### C. Employer public job posting

1. Complete company profile and meet `EmployerPostingEligibilityService` rules.
2. **Jobs** workspace → Create → publish to public when eligible.
3. Distinct from Talent Pool internal listing scope.

### D. Company Staffing access request

1. Employer → **Staffing / Enquiries** (`/account/enquiries`).
2. If not approved, use **Request Staffing access** (card).
3. Status becomes **Pending approval**; requirement form hidden until approved.

### E. Admin Staffing approval / rejection

1. Admin → **Employers** → open company → **Verification** tab.
2. Review **Staffing module access** status and dates.
3. **Approve**, **Reject** (reason required), or **Revoke** when approved.

### F. Staffing requirement + allocation

1. After **Approved**, employer submits requirement on `/account/enquiries`.
2. Admin allocates recruiter with type **Staffing Requirement** and selects the enquiry.

### G. Recruiter progress updates

1. **Assigned Candidates** → open row → update progress stage, notes, follow-up/interview fields.
2. Internal notes are not exposed to candidates/employers on public surfaces.

### H. Submit to employer

1. Only when a **real linked application** exists on the assignment.
2. Uses `EmployerSubmissionService` — no fabricated application id.

---

## Permissions summary

| Action | Permission / policy |
| --- | --- |
| Admin allocation | Staff with talent-pool assignment manage permissions |
| Staffing access decision | Admin `update` on `EmployerProfile` + service guard |
| Staffing requirement submit | `staffing_access_status = approved` |
| Talent Pool job create | Recruiter hiring workspace + controller authorization |
| Public job publish | Existing employer publication policy |

---

## Local environment recovery (2026-10-14 evening)

**HTTP 500 root cause:** Pending migration `2026_10_14_210000_*` — code queried `job_listings.listing_scope` before column existed (`SQLSTATE[42S22]` in `JobListingQuery::featuredPublic()` → `PublicSiteController::home`).

**Fix:** `php artisan migrate` on `ournear_hire_local` (additive only; no reset). Verified `listing_scope` + `staffing_access_status` columns present; home and admin login return **200**.

## Verification checklist (this pass)

| Item | PHPUnit | Browser |
| --- | --- | --- |
| Local home + admin login after migrate | — | **Verified** |
| OurNear in admin Employers (id 2 `ournear`) | — | **Verified** — see evidence |
| OurNear staffing access request → admin approve | **PASS** (`EmployerModulesStaffingTest`) | **Verified** — request + pending + admin Verification approve + requirement form |
| Staffing reject + in-app notification | **PASS** | **PHPUnit only** |
| Candidate Allocation + Talent Pool assign + type labels | **14 module tests PASS** | **Verified** — Talent Pool label in admin/recruiter (not “General”) |
| All four assignment types | **PASS** per type | **Partial** — Talent Pool browser; others PHPUnit |
| Talent Pool internal job + public exclusion | **PASS** | **PHPUnit only** |
| Full suite | **1035 passed / 3 skipped / 0 failed** | — |
| `npx tsc --noEmit` + `npm run build` | — | **PASS** (gap closure) |

### Browser evidence (in repo)

- `docs/QA/evidence/2026-10-14-candidate-allocation-page.png`
- `docs/QA/evidence/2026-10-14-recruiter-assigned-candidates.png`
- `docs/QA/evidence/2026-10-14-staffing-ournear-before-request.png`
- `docs/QA/evidence/2026-10-14-staffing-ournear-pending.png`
- `docs/QA/evidence/2026-10-14-admin-ournear-staffing-pending.png`

---

## Related docs

- `docs/QA/CANDIDATE-ALLOCATION-WORKFLOW-REPORT.md`
- `docs/QA/MASTER-RECTIFICATION-REGISTER.md`
- `docs/CURRENT-STATE.md`
