from __future__ import annotations

from ournear_qa.journey.preflight import JourneyCapacityReport, parse_kv_stdout


def test_parse_capacity_stdout_sample() -> None:
    stdout = """
fixtures_ready=yes
journey_apply_provision_slots=12
next_eligible_slug=qa-fixture-journey-apply-25
provision_would_succeed=yes
candidate_applications_last_60m=7
apply_hourly_limit=8
apply_submit_budget_remaining=1
cross_role_apply_feasible=yes
new_apply_test_feasible=yes
"""
    fields = parse_kv_stdout(stdout.strip())
    report = JourneyCapacityReport(
        fixtures_ready=True,
        journey_apply_provision_slots=int(fields["journey_apply_provision_slots"]),
        next_eligible_slug=fields["next_eligible_slug"],
        provision_would_succeed=True,
        candidate_applications_last_60m=7,
        apply_hourly_limit=8,
        apply_submit_budget_remaining=1,
        cross_role_apply_feasible=True,
        new_apply_test_feasible=True,
        raw=fields,
    )
    assert report.blocked_reason is None
    assert report.journey_apply_provision_slots == 12


def test_blocked_reason_when_apply_budget_exhausted() -> None:
    report = JourneyCapacityReport(
        fixtures_ready=True,
        journey_apply_provision_slots=5,
        next_eligible_slug="qa-fixture-journey-apply-30",
        provision_would_succeed=True,
        candidate_applications_last_60m=8,
        apply_hourly_limit=8,
        apply_submit_budget_remaining=0,
        cross_role_apply_feasible=False,
        new_apply_test_feasible=False,
        raw={},
    )
    assert report.blocked_reason is not None
    assert "job-applications" in report.blocked_reason
