# REG-PROD-001 — Employer job applicants recruitment pipeline UI

**Date:** 2026-10-12 (hiring-wide ATS extension same day)  
**Workspace:** `D:\Ournear Hub\Ournear Hire`  
**Status:** **COMPLETE (code)** — job-scoped + hiring-wide ATS pipeline; PHPUnit / browser / build **NOT RUN** (deferred by product owner)

## Summary

Employer **job applicants** (`/account/jobs/{job}/applicants`) and **hiring-wide applications** (`/account/hiring/applications`) surface the shared ATS lifecycle with stage counts and filtering. Stage resolution uses {@see `App\Support\AtsStage`} (same rules as admin), not a parallel status-only bucket model on those surfaces.

**Lifecycle shown:** Applied → Screening → Shortlisted → Interview → Selected → Offer → Joined → Closed (plus **All**).

Employer review is counted under **Screening** for employer-facing chips. Legacy query keys (`submitted`, `under_review`, `rejected`, `withdrawn`) redirect to the matching ATS bucket.

## Backend

| Area | Implementation |
| --- | --- |
| Counts (job) | `EmployerApplicationPipeline::summaryForJobAts()` |
| Counts (hiring-wide) | `EmployerApplicationPipeline::summaryForEmployerApplicationsAts()` |
| Filter (job) | `applyEmployerJobAtsStageFilter()` + eager-loaded relations for `AtsStage::resolve()` |
| Filter (hiring-wide) | `applyEmployerApplicationsAtsStageFilter()` + same eager loads |
| Controllers | `EmployerJobsController::applicants()`; `EmployerHiringWorkspaceController::applications()` |
| Actions | Unchanged — shortlist / reject / interview / messages on `Account/Employer/Applications/Show` via `AccountApplicationRecruitmentService` |
| Resume | Unchanged — `ApplicationResumeDeliveryService` + `EmployerApplicationAccess` |

## Frontend

| File | Change |
| --- | --- |
| `EmployerApplicantPipeline.tsx` | ATS helper copy, accessible stage chips, desktop stage bar |
| `Applicants/Index.tsx` | Pipeline heading + lifecycle hint |
| `RecruiterCandidateSummary.tsx` | Prefer `ats_stage_label` in list meta |
| `Applications/Show.tsx` | ATS vs application status labels |
| `Account/Hiring/Applications/Index.tsx` | Hiring-wide pipeline lifecycle hint (matches job applicants) |

## Tests authored (not run)

- `EmployerJobApplicantPipelineTest` (4 cases)
- `EmployerHiringApplicationsPipelineTest` (3 cases)
- `EmployerHiringStage25Test` — pipeline summary count **9** (ATS chips)
- Existing `EmployerApplicantCompareTest` — **not re-run**

```text
NOT RUN — deferred by product owner
php artisan test --filter=EmployerJobApplicantPipelineTest
php artisan test --filter=EmployerHiringApplicationsPipelineTest
```

## Remaining gaps

- **Talent Pool** (`/account/hiring/candidates`) intentionally uses `ApplicationStatus` talent-pool buckets (Stage 24); not ATS chips.
- Bulk stage moves / kanban drag-and-drop not in scope.
- Browser partial-role and mobile regression for REG-PROD-003 not executed in this batch.

## Issue register

**REG-PROD-001** → **COMPLETE (code)**; QA column unchanged until owner verification.
