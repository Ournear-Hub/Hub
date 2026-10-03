# Recruitment operations (ATS + staffing)

Independent of the public job board. Staff-driven.

## Screening workspace — IMPLEMENTED

`ScreeningWorkspaceService`, `ApplicationsController@screening`, stages/actions on `application_screenings`.

## Interviews — IMPLEMENTED

`InterviewService`, lifecycle statuses, scorecards, `admin.interviews.*`.

## Client (employer) submissions — IMPLEMENTED

This is the **recruitment partnership / client review** path in code: `EmployerSubmission` statuses Submitted, Under Review, Selected, Rejected, On Hold.

## Placement / offer / join — IMPLEMENTED

`PlacementService`, `PlacementStatus` Open → Offered → Accepted → Joining Confirmed → Joined (or Declined/Withdrawn).

## Employees / replacements / finance — IMPLEMENTED

`Employee` on join. `ReplacementService`. `FinanceService` invoices/payments (INR, GST fields). No Stripe.

## My Work / tasks — IMPLEMENTED

`Task`, `TaskService`, `MyWorkService`, `admin.work.index`. Tasks do not have dedicated `tasks.view` permissions.

## Hiring Command Center — IMPLEMENTED

`admin.hiring.index` orchestrates ATS, partnership (`EmployerSubmission`), staffing (placement/employee/replacement/invoice by permission), and OurNear Jobs origin (`user_id` null = guest). Reuses `ApplicationQuery` / `EmployerSubmissionQuery`. Does not replace My Work (personal assigned tasks). Interview queues use `lifecycle_status` when the column exists; otherwise `outcome` / `scheduled_at` (local MySQL may still lack Stage 6 interview columns).

## Activity

`RecruitmentActivityService` + `recruitment_activities`.

## Tests

Stage 6, Phase F3–F5, finance/replacement lifecycle tests.

See also [24-HIRING-COMMAND-CENTER.md](24-HIRING-COMMAND-CENTER.md).
