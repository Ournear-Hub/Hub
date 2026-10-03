# Recruitment automation (Stage 24)

**Status:** IMPLEMENTED foundation (orchestration). Not a second matching engine.

## Architecture

```text
Existing ScreeningMatchService / jobComparison / pipeline
        ↓
RecruitmentAutomationHooks (afterCommit)
        ↓
Dispatcher: trigger → scope → conditions → actions
        ↓
Execution row (unique idempotency_key)
        ↓
TaskService / AdminAlertService / RecruitmentActivityService
```

## Files

`app/Services/RecruitmentAutomation*.php`, models, `AutomationsController`, `resources/js/Pages/Admin/Automations/Index.tsx`, migration `2026_09_28_240000_add_recruitment_automation_foundation.php`.

## Allowed actions

`recommend_screening`, `create_task`, `notify_admin` only.

The Hiring Command Center may list recent **executed** rows (match percent advisory only). Visual builder: **PLANNED**.

## Conditions

`min_match_percent`, `application_status`, `delay_hours`, `allow_insufficient_data`, `application_origin` (`guest`|`account`).

## Scheduled

Hourly via reminders command: interview approaching, joining due, replacement ageing, client follow-up, application stale, invoice due.

## Must not

Call `shortlist()`, auto reject/select/offer. Do not rebuild `ScreeningMatchService`.

## Tests

`tests/Feature/RecruitmentAutomationStage24Test.php`.
