# Phase G1 — Employer application pipeline UI

**Date:** 2026-10-11  
**Agent:** 1 (development only)  
**Status:** **PHASE G1 IMPLEMENTED — AWAITING AGENT 2 QA**  
**Commit / push / deploy:** **None**

---

## 1. Inspection summary (gaps vs existing)

| Area | Before | Gap? |
| --- | --- | --- |
| Per-job applicants (`/account/jobs/{slug}/applicants`) | Pipeline tabs, search, detail links | **No** — already complete |
| Application detail (`Show.tsx`) | Shortlist, reject, interview, messages, corrections, resume, timeline | **Partial** — ATS/pipeline labels and empty-action guidance missing |
| Hiring-wide applications (`/account/hiring/applications`) | List + status filter only | **Yes** — no pipeline stage strip |
| Backend workflows | `EmployerApplicationWorkflowService`, routes, policies | **No** — reused |
| Self-service tests | `EmployerSelfServiceRecruitmentTest`, `EmployerHiringStage25Test` | **No** — extended assertions |

Stage 5/6/10 “MISSING employer pipeline UI” referred mainly to **lack of independent browser proof** and **hiring-wide pipeline visibility**, not absence of per-job detail actions.

---

## 2. Files changed

| File | Why |
| --- | --- |
| `app/Support/EmployerApplicationPipeline.php` | `summaryForEmployerApplications`, `applyJobStageFilter`, `stageLabelForApplication` |
| `app/Support/EmployerVisibleCandidate.php` | Expose `ats_stage`, `ats_stage_label`, `pipeline_stage_label` |
| `app/Http/Controllers/Account/EmployerHiringWorkspaceController.php` | Stage filter + `pipeline_summary` on hiring applications index |
| `resources/js/Components/Account/Recruiter/EmployerHiringApplicationsPipeline.tsx` | Pipeline nav for `/account/hiring/applications` |
| `resources/js/Pages/Account/Hiring/Applications/Index.tsx` | Render pipeline strip |
| `resources/js/Components/Account/Recruiter/RecruiterCandidateSummary.tsx` | Show stage in meta line |
| `resources/js/Pages/Account/Employer/Applications/Show.tsx` | ATS/pipeline fields, flash/errors, action hints, read-only copy |
| `tests/Feature/EmployerHiringStage25Test.php` | Assert new Inertia props |
| `docs/OURNEAR_RELEASE_ISSUE_REGISTER.md` | REG-PROD-001 update |

**Unchanged:** Workflow controllers, policies, C1/C2 QA, public F1/F2, admin F3 sidebar.

---

## 3. Authorization

- No new routes or mutations.
- Stage filters apply only to `$profile->applications()` (existing employer scope).
- Detail page still uses `EmployerApplicationAccess` / job policies via existing controllers.
- Employers cannot use generic status PATCH (still 405/404 per existing tests).

---

## 4. Validation (Agent 1)

| Command | Result |
| --- | --- |
| `php artisan test --filter=EmployerHiringStage25Test` | **8 passed** (68 assertions) |
| `php artisan test --filter=EmployerSelfServiceRecruitmentTest` | **10 passed** (35 assertions) |
| `npm run typecheck` | **PASS** |
| `npm run build` | **PASS** (~99s) |

**Note:** PHPUnit uses the project test harness (in-memory DB). Live MySQL/browser journeys remain **REG-ENV-001** blocked per Agent 2 handoff — not re-run here.

---

## 5. Agent 2 browser scenarios

1. **Employer A** — `/account/hiring/applications`: pipeline chips filter list; counts match applications.
2. **Job applicants** — `/account/jobs/{slug}/applicants`: regression on existing pipeline tabs.
3. **Application detail** — open fixture application: ATS stage + pipeline bucket visible; shortlist/reject/interview when eligible; message thread; resume view/download.
4. **Employer B** — empty hiring applications; forbidden on Employer A application.
5. **Mobile** — horizontal scroll on pipeline chips; touch targets on actions.
6. **Terminal status** — confirm “no further self-service actions” copy when buttons hidden.

---

## 6. Remaining gaps

- Employer cannot arbitrary-change status to every ATS stage (by design — staff pipeline + self-service actions only).
- Offer/joined employer self-service UI not expanded in this batch (no new backend).
- Public “Employer Not Verified” label — **REG-PHP-003** partial per Agent 2.

---

*End of Phase G1 report.*
