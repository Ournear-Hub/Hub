# REG-PROD-008 — Public jobs company / employer filtering

**Date:** 2026-10-11  
**Workspace:** `D:\Ournear Hub\Ournear Hire`

## Root cause

1. **Navigation gap:** Company profile, company directory cards, and home company tiles linked to plain `/jobs`, so the employer filter was never applied when users clicked “Browse jobs” or “View all jobs” — the backend already supported `?company=` and `?employer=` via `JobListingQuery`.
2. **Label matching edge cases:** Company-name filtering used case-insensitive exact match without `TRIM()` on stored names, so whitespace mismatches between the Company select label and database values could yield empty results.
3. **QA regression assertion:** Combined keyword + company test required a specific slug on page 1 while pagination fixtures put other matching jobs first (flaky failure, not a filter logic bug).

## Implementation

### Backend (`App\Support\JobListingQuery`)

- Existing `applyEmployerFilter()` (profile id / business id / name; unknown ids → zero results) and `applyCompanyNameFilter()` retained.
- Company-name SQL now uses `LOWER(TRIM(...))` on `company_name`, `project`, user `name`, and `business_id` for parity with public card labels.

### Public payloads

- `public_jobs_url` on company payloads: `route('jobs.index', ['employer' => $id])` from `UnifiedProfilePresenter`, `PublicSiteController::home`, and `PublicPlatformController::companies`.

### Frontend

- `resources/js/utils/publicJobsUrl.ts` — helper for employer-scoped jobs URL.
- `Companies/Show.tsx`, `PublicCompanyCard.tsx` — “Browse jobs” / “View all jobs” use filtered URL.
- Public jobs board (`Jobs/Index.tsx`, `JobsSearchPanel.tsx`) unchanged in contract: `company` query param + server-side `employer` → `filters.company` for pagination.

## Tests (PHPUnit)

| Suite | Result |
| --- | --- |
| `PublicJobsCompanyFilterTest` | **5/5 PASS** |
| `QaDefectRegressionTest` (company/employer cases) | **10/10 PASS** |
| `PhaseEJobCardsClassificationTest` | **PASS** (re-run) |

## Frontend checks

- `npm run typecheck` — **PASS**
- `npm run build` — **PASS**

## Limitations

- Independent browser QA (MAN-PUB-02 company pagination journey) not re-run in this batch.
- Company filter dropdown still uses public **company name** strings; deep links prefer canonical **`?employer={profileId}`**.
