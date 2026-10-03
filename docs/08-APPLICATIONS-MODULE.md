# Applications module

## Statuses (exact — do not invent)

`New`, `Reviewing`, `Shortlisted`, `Interview`, `Selected`, `Placed`, `Rejected`, `Withdrawn` (`ApplicationStatus`).

Candidate-facing labels differ (`toCandidateLabel` / `toCandidateStatusLabel`).

Terminal: Selected, Placed, Rejected, Withdrawn.

## Create

`ApplicationSubmissionService` — profile apply or form apply. Dedupes by job + email/user. Stores resume snapshot. Fires admin alert, candidate mail (`RecruitmentNotificationService`), `ApplicationSubmitted` automation hook.

## Pipeline

`ApplicationPipelineService::changeStatus` — staff transitions, notes, screening mapping, `ApplicationStatusChanged` automation hook. **Shortlist:** `shortlist()` — human only.

## Screening

- Record: `ApplicationScreening`, `ScreeningDecision`, `ScreeningStage`, `ScreeningAction`.
- Match: `ScreeningMatchService::evaluate` — factor list, percent only if ≥2 comparable fields, disclaimer constant.
- Comparison: `ScreeningWorkspaceService::jobComparison`.
- UI: `ApplicationsController` workspace variants + `ApplicationWorkspacePage` / `ApplicationDrawer`.

## Interviews

`InterviewService`, `application_interviews`, scorecards/templates. Reminders via `RecruitmentReminderService`.

## Employer path

Only shortlisted (or screening shortlisted) can submit (`isEligibleForEmployerSubmission`). Then `EmployerSubmissionService`.

## Permissions

`applications.view` / `applications.manage`. Account candidates see own applications only.

Staff Command Center (`admin.hiring.index`) includes ATS/Jobs application counts only when those permissions are present.

## Tests

`ApplicationsWorkspaceTest`, `ScreeningShortlistTest`, `PhaseF3ScreeningWorkspaceTest`, `JobApplicationTest`, Stage 24 automation tests, `RecruiterHiringCommandCenterStage26Test`.

## Do not

Replace match formula. Auto-transition to Selected/Placed from automation.
