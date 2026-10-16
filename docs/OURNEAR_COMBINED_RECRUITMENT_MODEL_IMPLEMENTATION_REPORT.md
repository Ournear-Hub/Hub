# OurNear Hire — Combined Recruitment Model Implementation Report

**Date:** 2026-10-10 (local)  
**Scope:** Employer self-service + candidate response workflows alongside existing admin-managed recruitment.  
**Release gate:** **`insufficient_evidence`** (unchanged — no staging verification; full post-change PHPUnit suite not re-run in this session).

---

## 1. Executive summary

Stage 11 documented **five MISSING** capabilities blocking a combined OurNear-managed + employer/candidate self-service model. This implementation adds those capabilities on the **existing** Laravel source of truth (`Application`, `ApplicationInterview`, `ApplicationPipelineService`, `InterviewService`, `MessageService` / conversations) without replacing admin ATS controls.

| Capability | Stage 11 | After implementation (local) |
|------------|----------|------------------------------|
| Employer shortlist / reject / interview actions | MISSING | **Implemented** — account routes + UI on employer application detail |
| Employer-side interview scheduling | MISSING | **Implemented** — reuses `InterviewService::schedule()` via pipeline |
| Employer → candidate messaging | MISSING | **Implemented** — application-scoped conversations; admin-only restriction lifted for authorized parties |
| Candidate interview accept/decline | MISSING | **Implemented** — `candidate_response` on interview (distinct from application status) |
| Candidate correction requests | MISSING | **Implemented** — `application_correction_requests` table + lifecycle |

Admin-managed recruitment continues to use existing `admin/applications/*` and `ApplicationPolicy::update` (staff `ApplicationsManage`).

---

## 2. Before / after feature matrix

| Area | Before | After | Roles |
|------|--------|-------|-------|
| Employer pipeline actions | Read-only copy on employer application page | POST shortlist, reject (with reason), schedule interview | Employer owning job (`EmployerApplicationAccess`) |
| Interviews | Admin + pipeline only | Same `application_interviews` rows; employer can schedule | Employer (own job), admin unchanged |
| Messaging | `MessageService` admin recipients only | `startRecruitmentApplicationMessage` + thread on application context | Employer ↔ candidate on owned application |
| Interview response | N/A | Accept/decline stored on interview; accept may confirm lifecycle | Candidate (own application) |
| Corrections | Profile edit only; snapshot immutable | Structured request + employer acknowledge/resolve | Candidate submit; employer (or admin) update status |
| Authorization | `ApplicationPolicy::update` staff-only | Added `manageAsEmployer`, `respondToInterview`, `submitCorrectionRequest` | See policies |

---

## 3. Migrations

**`2026_10_10_120000_add_recruitment_self_service_support.php`**

- `application_interviews`: `candidate_response`, `candidate_responded_at`, `candidate_response_note` — models **interview attendance response**, not application ATS status.
- `application_correction_requests`: persisted correction/clarification requests with status workflow.

Applied locally: `php artisan migrate --force` → **DONE**.

---

## 4. Key files changed / added

**Backend**

- `app/Support/EmployerApplicationAccess.php`
- `app/Services/EmployerApplicationWorkflowService.php`
- `app/Services/RecruitmentApplicationMessagingService.php`
- `app/Services/ApplicationCorrectionRequestService.php`
- `app/Services/AccountApplicationRecruitmentService.php`
- `app/Http/Controllers/Account/EmployerApplicationWorkflowController.php`
- `app/Http/Controllers/Account/CandidateApplicationsController.php` (interview response, messages, corrections)
- `app/Http/Controllers/Account/EmployerJobsController.php` (Inertia `recruitment` payload)
- `app/Services/MessageService.php` — `startRecruitmentApplicationMessage`
- `app/Services/InterviewService.php` — `recordCandidateResponse`
- `app/Services/EmployerNotificationService.php` — employer in-app alerts for response, message, correction
- `app/Policies/ApplicationPolicy.php`
- `app/Enums/CandidateInterviewResponse.php`, `ApplicationCorrectionRequestStatus.php`
- `app/Enums/RecruitmentActivityType.php` — candidate interview + correction activity types
- `app/Enums/EmployerNotificationEvent.php` — new employer events
- `app/Models/ApplicationCorrectionRequest.php`, `ApplicationInterview.php`, `Application.php`
- `routes/account.php` — employer and candidate workflow routes

**Frontend**

- `resources/js/Pages/Account/Employer/Applications/Show.tsx` — recruitment actions, interview form, messages, corrections
- `resources/js/Pages/Account/Applications/Show.tsx` — interviews, response form, messages, correction requests

**Tests**

- `tests/Feature/EmployerSelfServiceRecruitmentTest.php` (new)
- `tests/Feature/EmployerHiringStage25Test.php` (updated expectations)

---

## 5. Routes (account)

| Method | Route | Name |
|--------|-------|------|
| POST | `account/jobs/{job}/applications/{application}/shortlist` | `account.employer.jobs.application.shortlist` |
| POST | `account/jobs/{job}/applications/{application}/reject` | `account.employer.jobs.application.reject` |
| POST | `account/jobs/{job}/applications/{application}/interview` | `account.employer.jobs.application.interview` |
| POST | `account/jobs/{job}/applications/{application}/messages` | `account.employer.jobs.application.messages` |
| PATCH | `account/jobs/{job}/applications/{application}/correction-requests/{correctionRequest}` | `account.employer.jobs.application.correction-requests.update` |
| POST | `account/applications/{application}/interviews/{interview}/response` | `account.applications.interviews.response` |
| POST | `account/applications/{application}/correction-requests` | `account.applications.correction-requests.store` |
| POST | `account/applications/{application}/messages` | `account.applications.messages.store` |

Legacy invalid status PUT/PATCH on application detail remains **405/404** (`EmployerHiringStage25Test`).

---

## 6. Behavior notes

- **Single source of truth:** Employer and admin actions both call `ApplicationPipelineService` / `InterviewService`; no duplicate application records.
- **Reject:** Uses existing `markNotSuitable()` (screening + `ApplicationStatus::Rejected`) with active `rejection_reasons`.
- **Interview response:** Stored on `application_interviews.candidate_response`; accepting may set lifecycle **Confirmed** when allowed; application status unchanged on decline-only response.
- **Messaging:** In-app only; stored in `conversations` / `messages` with `ConversationContextType::Application`. Email delivery **NOT_TESTED** (unchanged).
- **Corrections:** Do not mutate application snapshot fields; employer can acknowledge or resolve with optional note.

---

## 7. Automated test evidence (this session)

| Command | Exit | Result |
|---------|------|--------|
| `php artisan migrate --force` | 0 | Migration applied |
| `php artisan test --filter EmployerSelfServiceRecruitmentTest` | 0 | **6 passed**, 20 assertions (~82s) |
| `php artisan test --filter EmployerHiringStage25Test` | 0 | **8 passed**, 58 assertions (~81s) |
| `npx tsc --noEmit` | 0 | Pass (~318s) |
| `npm run build` | 0 | Pass (~6m 18s) |

**Not run in this session:** Full `php artisan test` (714-test Stage 12 baseline), Playwright / qa-agent Stage 11 re-run, browser walkthrough with screenshots.

---

## 8. Browser / manual UI testing

**Status:** **NOT_RUN** in this session (PHPUnit + Inertia props cover core authorization and persistence paths). Recommended manual checks:

1. Employer A → job application → shortlist → reload → status Shortlisted.
2. Schedule interview → candidate application → accept/decline → employer view shows response.
3. Employer message → candidate reply → thread visible both sides.
4. Candidate correction request → employer acknowledge/resolve.
5. Admin application workspace → same interview/status/timeline after employer actions.

---

## 9. Admin-managed model preservation

- Admin routes (`routes/admin.php`) and `ApplicationsController` unchanged for pipeline/interview CRUD.
- Staff still require `ApplicationsManage` for `ApplicationPolicy::update`.
- Employer actions gated by job ownership only.

**Coexistence risk (open):** Both admin and employer can act on the same application; no new locking — product rules should define precedence for conflicting actions (not implemented).

---

## 10. Profile and email (Phase 4)

**Verdict unchanged:** **`PASS_WITH_COVERAGE_GAPS`** per `docs/OURNEAR_PROFILE_FIELD_AUDIT_AND_REPAIR_REPORT.md`.

No additional profile-field browser coverage or email dispatch/delivery verification was performed in this pass. Do not claim verified email delivery.

---

## 11. Defects

| ID | Severity | Status |
|----|----------|--------|
| — | — | No product defects filed from focused tests |

---

## 12. Launch-critical risks and next actions

1. **Re-run full PHPUnit** (`php artisan test`) and compare to Stage 12 baseline (714 passed).
2. **Re-run qa-agent Stage 11** journey; update registry MISSING → PASS where evidenced.
3. **Staging verification** (explicitly not authorized here) before changing release gate.
4. **Browser evidence** for five capabilities and admin regression on fixture `ONT-A-000051`.
5. **Profile/email gaps** from audit backlog (photo upload, master-data selects, privacy toggles, employer locations).
6. **Product decision:** conflict resolution when admin and employer both change pipeline state.

---

## 13. Dependencies / unresolved product decisions

- Employer final hiring decision vs OurNear sourcing (engagement model) — documented in charter; not encoded in new code beyond existing pipeline rules.
- Whether declined interviews should trigger admin alerts or auto-cancel — currently activity + optional employer notification on accept/decline response only via `notifyInterviewCandidateResponded`.

---

*Preserves Stage 10–12 QA reports and canonical run IDs; this document adds post-Stage-12 implementation evidence only.*
