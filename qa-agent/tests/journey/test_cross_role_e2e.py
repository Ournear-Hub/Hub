from __future__ import annotations

import pytest

from ournear_qa.journey.cross_role import JRN_ID, run_cross_role_e2e
from ournear_qa.journey.errors import JourneyStepError


def skip_if_account_login_throttled(err: JourneyStepError, journey_id: str) -> None:
    if "429" in err.actual and "account-login" in err.actual:
        pytest.skip(
            f"{journey_id}: blocked by account-login rate limit (5/min per email). "
            "Wait one minute between heavy combined runs."
        )


@pytest.mark.journey
@pytest.mark.p0
def test_jrn_cross_role_application_e2e(browser_session, base_url, qa_settings) -> None:
    if qa_settings.ournear_allow_run != "local":
        pytest.skip(f"{JRN_ID}: cross-role E2E is local-only")

    missing = [
        name
        for name, value in (
            ("OURNEAR_QA_CANDIDATE_EMAIL", qa_settings.qa_candidate_email),
            ("OURNEAR_QA_CANDIDATE_PASSWORD", qa_settings.qa_candidate_password),
            ("OURNEAR_QA_EMPLOYER_A_EMAIL", qa_settings.qa_employer_a_email),
            ("OURNEAR_QA_EMPLOYER_A_PASSWORD", qa_settings.qa_employer_a_password),
            ("OURNEAR_QA_ADMIN_EMAIL", qa_settings.qa_admin_email),
            ("OURNEAR_QA_ADMIN_PASSWORD", qa_settings.qa_admin_password),
        )
        if not value
    ]
    if missing:
        pytest.skip(f"{JRN_ID}: missing credentials in config/local.env: {', '.join(missing)}")

    from ournear_qa.journey.preflight import inspect_journey_capacity

    capacity = inspect_journey_capacity()
    if capacity and capacity.blocked_reason:
        pytest.skip(f"{JRN_ID}: preflight blocked — {capacity.blocked_reason}")
    if capacity and not capacity.cross_role_apply_feasible:
        pytest.skip(f"{JRN_ID}: apply budget or provision slots block cross-role apply")

    employer_b_email = qa_settings.qa_employer_b_email or "qa-employer-b@example.test"
    employer_b_password = qa_settings.qa_employer_b_password or qa_settings.qa_employer_a_password
    if not employer_b_password:
        pytest.skip(f"{JRN_ID}: set OURNEAR_QA_EMPLOYER_B_PASSWORD or shared employer A password")

    page = browser_session.page
    try:
        report = run_cross_role_e2e(
            page,
            base_url,
            candidate_email=qa_settings.qa_candidate_email,
            candidate_password=qa_settings.qa_candidate_password,
            employer_a_email=qa_settings.qa_employer_a_email,
            employer_a_password=qa_settings.qa_employer_a_password,
            employer_b_email=employer_b_email,
            employer_b_password=employer_b_password,
            admin_email=qa_settings.qa_admin_email,
            admin_password=qa_settings.qa_admin_password,
        )
    except JourneyStepError as err:
        skip_if_account_login_throttled(err, JRN_ID)
        if err.step_id == "D1-05-submit-blocked" and "429" in err.actual:
            pytest.skip(f"{JRN_ID}: blocked by job-applications rate limit — {err.actual}")
        if err.step_id == "D4-00-provision":
            pytest.skip(f"{JRN_ID}: {err.actual}")
        raise

    if report.blocked:
        pytest.fail(f"{JRN_ID}: blocked steps {report.blocked} — cross-role journey incomplete")

    assert report.correlation_business_id.startswith("ONT-A-")
    assert report.application_id > 0
    assert "D1-06-business-id" in report.candidate_steps
    assert "D1-07-applications-list" in report.candidate_steps
    assert "D1-08-persist-after-reload" in report.candidate_steps
    assert "D2-09-hiring-applications-list" in report.employer_steps
    assert "D3-09-persist-after-reload" in report.admin_steps
    assert report.authorization_steps == [
        "D4-auth-employer-b-isolation",
        "D4-auth-candidate-admin-denied",
        "D4-auth-employer-a-admin-denied",
    ]
    assert "C1-CAND-004-notifications-page" in report.notification_steps
    assert "C1-CAND-006-bell-link-present" in report.notification_steps
    if report.admin_status_changed:
        assert "C1-CAND-002-status-reviewing-visible" in report.notification_steps
        assert "C1-CAND-005-status-notification-present" in report.notification_steps
