from __future__ import annotations

import pytest

from ournear_qa.journey.errors import JourneyStepError
from ournear_qa.journey.fe103_employer_detail_header import (
    JRN_ID,
    discover_fe103_targets,
    run_fe103_candidate_application_show,
    run_fe103_employer_detail_header_qa,
)

from tests.journey.conftest import skip_if_account_login_throttled


@pytest.fixture(scope="module")
def fe103_targets():
    targets = discover_fe103_targets()
    if targets is None:
        pytest.skip(
            f"{JRN_ID}: run php artisan qa:hiring-fixtures and ensure QA candidate application exists"
        )
    return targets


@pytest.mark.journey
def test_jrn_fe103_employer_detail_header_browser(
    browser_session,
    base_url,
    qa_settings,
    fe103_targets,
    request,
) -> None:
    if not qa_settings.qa_employer_a_email or not qa_settings.qa_employer_a_password:
        pytest.skip("Set OURNEAR_QA_EMPLOYER_A_EMAIL and OURNEAR_QA_EMPLOYER_A_PASSWORD in config/local.env")

    if fe103_targets.submission_id is None:
        pytest.fail(
            f"{JRN_ID}: no EmployerSubmission for QA Employer A — "
            "submission detail (/account/submissions/{id}) cannot be browser-verified. "
            "Create a submission via admin shortlisted submit workflow for the fixture application."
        )

    run_id = request.config._ournear_qa_run_id  # type: ignore[attr-defined]
    try:
        report = run_fe103_employer_detail_header_qa(
            browser_session.page,
            base_url,
            qa_settings.qa_employer_a_email,
            qa_settings.qa_employer_a_password,
            fe103_targets,
            run_id=run_id,
            include_submission=True,
        )
    except JourneyStepError as err:
        skip_if_account_login_throttled(err, JRN_ID)
        raise

    assert fe103_targets.job_slug in report.routes_checked[0]
    assert "/account/submissions/" in "".join(report.routes_checked)
    assert len(report.viewports_checked) == 3
    assert report.evidence_paths


@pytest.mark.journey
def test_jrn_fe103_candidate_application_detail_mobile(
    browser_session,
    base_url,
    qa_settings,
    fe103_targets,
    request,
) -> None:
    if not qa_settings.qa_candidate_email or not qa_settings.qa_candidate_password:
        pytest.skip("Set OURNEAR_QA_CANDIDATE_* in config/local.env")

    run_id = request.config._ournear_qa_run_id  # type: ignore[attr-defined]
    try:
        run_fe103_candidate_application_show(
            browser_session.page,
            base_url,
            qa_settings.qa_candidate_email,
            qa_settings.qa_candidate_password,
            fe103_targets,
            run_id=run_id,
        )
    except JourneyStepError as err:
        skip_if_account_login_throttled(err, JRN_ID)
        raise
