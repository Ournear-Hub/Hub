# Phase E — Job cards and job classifications

**Date:** 2026-10-11  
**Agent:** 1 (development)  
**Status:** **PHASE E IMPLEMENTED — AWAITING AGENT 2 QA**  
**Commit / push / deploy:** **None**

---

## 1. Inspection — already working

| Area | Finding |
| --- | --- |
| Canonical UI | `resources/js/Components/Public/JobCard.tsx`, `JobDetail.tsx`, `JobActions.tsx` |
| Pages | `Home.tsx`, `Jobs/Index.tsx`, `Jobs/Show.tsx` — cards, split pane, similar jobs |
| Master data | Industry, JobCategory, JobTitle, Location, EmploymentType, ExperienceRange on `Job`; admin/employer `JobForm.tsx` with hierarchy validation |
| Admin filters | `JobListingQuery::adminIndex()` — ID-based master filters |
| Job create/edit | `StoreJobRequest` / `UpdateJobRequest` + `ValidatesJobMasterData`; string sync on save |
| Search / company | Keyword, company, employer query params, pagination — unchanged |
| Policies | Unverified publish limits, apply eligibility via `PublicJobStateService` — not modified |

**Out of scope (unchanged):** job-level skills/qualifications master data; Saved Jobs / Talent Pool card unification; employer “not verified” public label (REG-PHP-003 product question); Master Data module redesign.

---

## 2. Genuine gaps addressed

1. **Public payload vs master data** — `Job::toPublicCard()` used raw denormalized columns, so FK-only jobs could show blank industry/category/location on cards and detail.
2. **Public filters** — `/jobs` filters matched legacy string columns only; FK-classified jobs could not be found by industry/category name.
3. **Filter option lists** — `publicFilterOptions()` used distinct legacy columns only; master-only values missing from dropdowns.
4. **Card UI** — `PublicJobCard` carried industry/category but `JobCard` did not surface them; featured badge only on detail.

---

## 3. Files changed

| File | Change |
| --- | --- |
| `app/Models/Job.php` | `toPublicCard()` uses `display*()` helpers; added `displayPublicCategory()` |
| `app/Support/JobListingQuery.php` | FK-aware public filters; display-based filter options; shared `publicJobRelations()` |
| `app/Http/Controllers/PublicSiteController.php` | Homepage latest jobs eager-load classifications |
| `resources/js/Components/Public/JobCard.tsx` | Industry · category line; Featured badge |
| `tests/Feature/PhaseEJobCardsClassificationTest.php` | **Added** — FK-only job card + filter regression |
| `docs/OURNEAR_PHASE_E_JOB_CARDS_CLASSIFICATIONS_REPORT.md` | This report |
| `docs/OURNEAR_RELEASE_ISSUE_REGISTER.md` | REG-PROD-008 row |

---

## 4. Reused components and services

- `JobCard`, `JobDetail`, `JobsSearchPanel`, `PublicJobStateService`, `JobListingQuery`, `MasterDataService`, existing `JobForm` and request validation — no duplicate card or classification system.

---

## 5. Validation

| Command | Result |
| --- | --- |
| `php artisan test --filter=PhaseEJobCardsClassificationTest` | **PASS** — 1 test, 36 assertions |
| `php artisan test --filter=QaDefectRegressionTest` | **PASS** — 8/8 |
| `php artisan test --filter=PublicWebsiteTest` | **PASS** — 7/7 |
| `php artisan test --filter=JobsWorkspaceTest` | **PASS** — 4/4 |
| `npm run typecheck` | **PASS** |
| `npm run build` | **PASS** |

**Environment:** User prompt notes **REG-ENV-001** may be open on some machines; PHPUnit here uses the project test harness (not live MySQL QA). No browser verification by Agent 1.

---

## 6. Remaining gaps / risks

- Public filters still use **name** query params (not master IDs) — consistent with existing `JobsSearchPanel`; inactive master rows follow existing job FK rules.
- Legacy V1 `category` column remains distinct from classified job category (admin behavior preserved).
- Full PHPUnit suite and responsive browser matrix not re-run.
- Phase **G1**, **C3**, **F** items remain **AWAITING QA**; release verdict stays **`insufficient_evidence`**.

---

## 7. Agent 2 — browser scenarios

1. **Home** — open roles / featured cards show industry · category when job has master data; Featured badge when `featured=true`.
2. **`/jobs`** — filter by Industry and Category for a FK-classified job; result list and split-pane detail match.
3. **Mobile list row** — classification line visible; tap navigates to correct job detail.
4. **Job detail** — industry/category align with card; apply/save actions unchanged for guest and candidate.
5. **Legacy-only job** (no FK) — card still shows legacy strings; filters still work.
6. **Company filter + pagination** — regression with industry filter combined (REG-PHP-002 style).

---

*End of Phase E report.*
