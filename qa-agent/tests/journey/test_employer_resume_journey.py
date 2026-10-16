from __future__ import annotations

import pytest

from ournear_qa.journey.errors import JourneyStepError
from ournear_qa.journey.employer_resume import (
    JRN_ID,
    discover_employer_journey_target,
    run_employer_a_resume_steps,
    run_employer_b_isolation_steps,
)


def skip_if_account_login_throttled(err: JourneyStepError, journey_id: str) -> None:
    if "429" in err.actual and "account-login" in err.actual:
        pytest.skip(
            f"{journey_id}: blocked by account-login rate limit (5/min per email). "
            "Wait one minute between heavy combined runs."
        )



@pytest.fixture(scope="module")
def employer_journey_target():
    target = discover_employer_journey_target()
    if target is None:
        pytest.skip(
            f"{JRN_ID}: blocked prerequisite - run php artisan qa:hiring-fixtures and "
            "ensure a QA candidate application exists (JRN-CANDIDATE-APPLY-01 or fixtures)"
        )
    if not target.resume_path_set:
        pytest.skip(f"{JRN_ID}: application has no resume snapshot on disk")
    return target


@pytest.mark.journey
@pytest.mark.p0
def test_jrn_employer_resume_owner_workflow(
    browser_session,
    base_url,
    qa_settings,
    employer_journey_target,
) -> None:
    if not qa_settings.qa_employer_a_email or not qa_settings.qa_employer_a_password:
        pytest.skip("Set OURNEAR_QA_EMPLOYER_A_EMAIL and OURNEAR_QA_EMPLOYER_A_PASSWORD in config/local.env")

    try:
        ctx = run_employer_a_resume_steps(
            browser_session.page,
            base_url,
            qa_settings.qa_employer_a_email,
            qa_settings.qa_employer_a_password,
            employer_journey_target,
        )
    except JourneyStepError as err:
        skip_if_account_login_throttled(err, JRN_ID)
        raise
    assert "D2-09-hiring-applications-list" in ctx.steps_completed


@pytest.mark.journey
@pytest.mark.p0
def test_jrn_employer_resume_employer_b_isolation(
    browser_session,
    base_url,
    qa_settings,
    employer_journey_target,
) -> None:
    email = qa_settings.qa_employer_b_email or "qa-employer-b@example.test"
    password = qa_settings.qa_employer_b_password or qa_settings.qa_employer_a_password
    if not password:
        pytest.skip(
            "Set OURNEAR_QA_EMPLOYER_B_PASSWORD (or shared OURNEAR_QA_EMPLOYER_A_PASSWORD) in config/local.env"
        )

    try:
        run_employer_b_isolation_steps(
            browser_session.page,
            base_url,
            email,
            password,
            employer_journey_target,
        )
    except JourneyStepError as err:
        skip_if_account_login_throttled(err, JRN_ID)
        raise
