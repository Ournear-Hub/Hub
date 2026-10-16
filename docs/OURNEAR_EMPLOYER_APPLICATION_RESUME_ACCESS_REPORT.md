# Employer applicant resume access — implementation report

**Date:** 2026-10-12  
**Scope:** Account employer preview/download of **application resume snapshots** only (not company profile, not candidate library management).

## Confirmed gaps and root causes

| Gap | Root cause |
| --- | --- |
| Employer UI offered View/Download when DB had `resume_path` but file missing on storage | `EmployerVisibleCandidate` set `has_resume` from DB column only; candidate-facing `CandidateApplicationViewService` already checked `Storage::disk('resumes')->exists()` |
| Duplicated stream logic across employer/admin/candidate controllers | Three copies with slightly different headers and no shared path sanitization |
| Legacy rows referencing library-relative paths | Some snapshots may exist only on `candidate_files` after historical storage; employer route checked **`resumes` disk only** |
| Path traversal risk | No normalization rejected `..` or absolute paths in `resume_path` |

**Not an application defect:** Authorization via job ownership + recruitment submission ownership was already enforced on the employer route; snapshots remain immutable per `ApplicationSubmissionService::storeSnapshot()`.

## Implementation

### Service

- `App\Services\ApplicationResumeDeliveryService` — resolve snapshot (resumes disk first, then `candidate_files` fallback), sanitize relative paths, stream with MIME, safe filename, `Cache-Control: private, no-store`, `X-Content-Type-Options: nosniff`, inline PDF preview when `?disposition=inline`.
- `App\Support\ApplicationResumeSnapshot` — value object for resolved disk/path/extension.

### Routes (unchanged)

- `GET account/employer-applications/{application}/resume` → `account.employer.applications.resume` (`EmployerJobsController::resume`).

### Controllers updated

- `EmployerJobsController::resume` — `EmployerApplicationAccess` + submission ownership; delegates to delivery service.
- `Admin\ApplicationsController::resume` — same service (behavior parity).
- `Account\CandidateApplicationsController::resume` — same service.

### Employer UI data

- `EmployerVisibleCandidate::fromApplication()` — `has_resume` / `resume_url` align with **storage availability** (matches candidate detail resume projection pattern).

### Security

- Server-side employer authorization unchanged in intent; explicit `EmployerApplicationAccess::managesApplication()` check.
- No public disk exposure; private storage only.
- Rejects traversal paths; 404 when file missing (no internal path leakage).

### Historical snapshot rule

- Delivery always reads **`applications.resume_path`** snapshot reference only; does not substitute current candidate library resume.

## Automated tests

**File:** `tests/Feature/EmployerApplicantResumeAccessTest.php`  
**Status:** **Added, not run — deferred to consolidated testing phase.**

Covers: authorized download/preview, cross-employer forbidden, candidate forbidden on employer route, missing file 404, path traversal, legacy disk fallback, `has_resume` accuracy, snapshot disk preference, safe filename.

## Register

- **REG-SEC-002** regression note updated (employer resume delivery reliability).
- Release verdict remains **`insufficient_evidence`**.

## Remaining limitations

- Browser QA not re-run in this pass.
- No migration or file moves for legacy paths; fallback read-only on `candidate_files` when `resumes` miss.
- Full employer self-service action matrix (shortlist/interview/message) out of scope — see REG-PROD-001.

## Verification not performed (per development pass)

- PHPUnit / Pest / Playwright / `npm run build` / `tsc` — **not executed**.
- No commit, push, or deploy.
