from __future__ import annotations

import pytest

from ournear_qa.journey.errors import JourneyStepError
from ournear_qa.journey.candidate_apply import (
    JRN_ID,
    discover_qa_apply_target,
    provision_journey_apply_target,
    run_duplicate_guard_steps,
    run_new_application_steps,
)


def skip_if_account_login_throttled(err: JourneyStepError, journey_id: str) -> None:
    if "429" in err.actual and "account-login" in err.actual:
        pytest.skip(
            f"{journey_id}: blocked by account-login rate limit (5/min per email). "
            "Wait one minute between heavy combined runs."
        )


@pytest.mark.journey
@pytest.mark.p0
def test_jrn_candidate_apply_new_application(browser_session, base_url, qa_settings) -> None:
    if not qa_settings.qa_candidate_email or not qa_settings.qa_candidate_password:
        pytest.skip("Set OURNEAR_QA_CANDIDATE_EMAIL and OURNEAR_QA_CANDIDATE_PASSWORD in config/local.env")

    target = provision_journey_apply_target()
    if target is None:
        target = discover_qa_apply_target()
    if target is None:
        pytest.skip(
            f"{JRN_ID}: blocked prerequisite - run php artisan qa:hiring-fixtures then "
            "scripts/qa-provision-journey-apply-job.php"
        )

    slug, title = target

    page = browser_session.page
    try:
        ctx = run_new_application_steps(
            page,
            base_url,
            qa_settings.qa_candidate_email,
            qa_settings.qa_candidate_password,
            slug,
            title,
        )
    except JourneyStepError as err:
        skip_if_account_login_throttled(err, JRN_ID)
        if err.step_id == "D1-05-submit-blocked" and "429" in err.actual:
            pytest.skip(
                f"{JRN_ID}: blocked by job-applications rate limit (8/hour per candidate). "
                "Wait for the window before re-running new-apply."
            )
        raise
    assert ctx.business_id
    assert "D1-09-duplicate-guard" in ctx.steps_completed


@pytest.mark.journey
@pytest.mark.p0
def test_jrn_candidate_apply_duplicate_and_persistence(browser_session, base_url, qa_settings) -> None:
    if not qa_settings.qa_candidate_email or not qa_settings.qa_candidate_password:
        pytest.skip("Set OURNEAR_QA_CANDIDATE_EMAIL and OURNEAR_QA_CANDIDATE_PASSWORD in config/local.env")

    try:
        run_duplicate_guard_steps(
            browser_session.page,
            base_url,
            qa_settings.qa_candidate_email,
            qa_settings.qa_candidate_password,
        )
    except JourneyStepError as err:
        skip_if_account_login_throttled(err, JRN_ID)
        raise
