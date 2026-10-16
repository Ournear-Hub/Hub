from __future__ import annotations

import pytest

from ournear_qa.fixtures.hiring import SLUG_PUBLISHED_A
from ournear_qa.journey.employer_job_publish import (
    JRN_ID,
    EmployerJobPublishContext,
    discover_job_form_defaults,
    run_employer_job_publish_lifecycle,
    step_candidate_cannot_manage_jobs,
    step_employer_b_cannot_edit,
)
from ournear_qa.journey.errors import JourneyStepError


def skip_if_account_login_throttled(err: JourneyStepError, journey_id: str) -> None:
    if "429" in err.actual and "account-login" in err.actual:
        pytest.skip(
            f"{journey_id}: blocked by account-login rate limit (5/min per email). "
            "Wait one minute between heavy combined runs."
        )



@pytest.fixture(scope="module")
def job_form_defaults():
    defaults = discover_job_form_defaults()
    if defaults is None:
        pytest.skip(
            f"{JRN_ID}: blocked prerequisite - run php artisan qa:hiring-fixtures and ensure PHP is on PATH"
        )
    return defaults


@pytest.mark.journey
@pytest.mark.p0
def test_jrn_employer_job_publish_lifecycle(
    browser_session,
    base_url,
    qa_settings,
    job_form_defaults,
) -> None:
    if not qa_settings.qa_employer_a_email or not qa_settings.qa_employer_a_password:
        pytest.skip("Set OURNEAR_QA_EMPLOYER_A_EMAIL and OURNEAR_QA_EMPLOYER_A_PASSWORD in config/local.env")

    page = browser_session.page
    try:
        ctx = run_employer_job_publish_lifecycle(
            page,
            base_url,
            qa_settings.qa_employer_a_email,
            qa_settings.qa_employer_a_password,
            job_form_defaults,
        )
    except JourneyStepError as err:
        skip_if_account_login_throttled(err, JRN_ID)
        raise

    assert "P0-06-published" in ctx.steps_completed
    assert "P0-08c-paused" in ctx.steps_completed
    assert "P0-08d-paused-not-public" in ctx.steps_completed
    assert "P0-08e-republished" in ctx.steps_completed
    assert "P0-11b-absent-from-listing" in ctx.steps_completed
    assert ctx.job_slug


@pytest.mark.journey
@pytest.mark.p0
def test_jrn_employer_job_publish_authorization(
    browser_session,
    base_url,
    qa_settings,
) -> None:
    employer_b_email = qa_settings.qa_employer_b_email or "qa-employer-b@example.test"
    employer_b_password = qa_settings.qa_employer_b_password or qa_settings.qa_employer_a_password
    if not employer_b_password:
        pytest.skip("Set OURNEAR_QA_EMPLOYER_B_PASSWORD in config/local.env")

    page = browser_session.page
    ctx = EmployerJobPublishContext(job_title="fixture", job_slug=SLUG_PUBLISHED_A)

    try:
        step_employer_b_cannot_edit(page, base_url, employer_b_email, employer_b_password, ctx)
    except JourneyStepError as err:
        skip_if_account_login_throttled(err, JRN_ID)
        raise

    if not qa_settings.qa_candidate_email or not qa_settings.qa_candidate_password:
        pytest.skip("Set OURNEAR_QA_CANDIDATE_EMAIL and OURNEAR_QA_CANDIDATE_PASSWORD for candidate isolation")

    try:
        step_candidate_cannot_manage_jobs(
            page,
            base_url,
            qa_settings.qa_candidate_email,
            qa_settings.qa_candidate_password,
            ctx,
        )
    except JourneyStepError as err:
        skip_if_account_login_throttled(err, JRN_ID)
        raise

    assert "P0-13-candidate-forbidden" in ctx.steps_completed
