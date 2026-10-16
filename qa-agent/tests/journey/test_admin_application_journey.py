from __future__ import annotations

import pytest

from tests.journey.conftest import skip_if_account_login_throttled
from ournear_qa.journey.errors import JourneyStepError
from ournear_qa.journey.admin_application import (
    JRN_ID,
    assert_role_cannot_access_admin_applications,
    discover_admin_journey_target,
    run_admin_application_steps,
)


@pytest.fixture(scope="module")
def admin_journey_target():
    target = discover_admin_journey_target()
    if target is None:
        pytest.skip(
            f"{JRN_ID}: blocked prerequisite - run php artisan qa:hiring-fixtures and "
            "ensure a QA candidate application exists (D1 journey)"
        )
    if not target.resume_path_set:
        pytest.skip(f"{JRN_ID}: QA application has no resume snapshot")
    return target


@pytest.mark.journey
@pytest.mark.p0
def test_jrn_admin_application_review_workflow(
    browser_session,
    base_url,
    qa_settings,
    admin_journey_target,
) -> None:
    if not qa_settings.qa_admin_email or not qa_settings.qa_admin_password:
        pytest.skip("Set OURNEAR_QA_ADMIN_EMAIL and OURNEAR_QA_ADMIN_PASSWORD in config/local.env")

    ctx = run_admin_application_steps(
        browser_session.page,
        base_url,
        qa_settings.qa_admin_email,
        qa_settings.qa_admin_password,
        admin_journey_target,
    )
    assert "D3-09-persist-after-reload" in ctx.steps_completed


@pytest.mark.journey
@pytest.mark.p0
def test_jrn_admin_application_account_role_isolation(
    browser_session,
    base_url,
    qa_settings,
    admin_journey_target,
) -> None:
    if not qa_settings.qa_candidate_email or not qa_settings.qa_candidate_password:
        pytest.skip("Set OURNEAR_QA_CANDIDATE_EMAIL and OURNEAR_QA_CANDIDATE_PASSWORD in config/local.env")

    try:
        assert_role_cannot_access_admin_applications(
            browser_session.page,
            base_url,
            qa_settings.qa_candidate_email,
            qa_settings.qa_candidate_password,
            application_id=admin_journey_target.application_id,
            business_id=admin_journey_target.business_id,
        )
    except JourneyStepError as err:
        skip_if_account_login_throttled(err, JRN_ID)
        raise


@pytest.mark.journey
@pytest.mark.p0
def test_jrn_admin_application_employer_role_isolation(
    browser_session,
    base_url,
    qa_settings,
    admin_journey_target,
) -> None:
    email = qa_settings.qa_employer_b_email or "qa-employer-b@example.test"
    password = qa_settings.qa_employer_b_password or qa_settings.qa_employer_a_password
    if not password:
        pytest.skip("Set OURNEAR_QA_EMPLOYER_B_PASSWORD or OURNEAR_QA_EMPLOYER_A_PASSWORD in config/local.env")

    try:
        assert_role_cannot_access_admin_applications(
            browser_session.page,
            base_url,
            email,
            password,
            application_id=admin_journey_target.application_id,
            business_id=admin_journey_target.business_id,
        )
    except JourneyStepError as err:
        skip_if_account_login_throttled(err, JRN_ID)
        raise
