from __future__ import annotations

import pytest

from ournear_qa.journey.errors import JourneyStepError
from ournear_qa.journey.lr_register_account_home import (
    JRN_ID,
    run_account_home_spot_check,
    run_register_spot_check,
)

from tests.journey.conftest import skip_if_account_login_throttled


@pytest.mark.journey
def test_jrn_lr_register_page_spot_check(browser_session, base_url, request) -> None:
    run_id = request.config._ournear_qa_run_id  # type: ignore[attr-defined]
    report = run_register_spot_check(browser_session.page, base_url, run_id=run_id)
    assert "/register" in report.routes_checked
    assert len(report.viewports_checked) == 2
    assert len(report.evidence_paths) == 2


@pytest.mark.journey
def test_jrn_lr_account_home_spot_check(browser_session, base_url, qa_settings, request) -> None:
    if not qa_settings.qa_candidate_email or not qa_settings.qa_candidate_password:
        pytest.skip("Set OURNEAR_QA_CANDIDATE_EMAIL and OURNEAR_QA_CANDIDATE_PASSWORD in config/local.env")

    run_id = request.config._ournear_qa_run_id  # type: ignore[attr-defined]
    try:
        report = run_account_home_spot_check(
            browser_session.page,
            base_url,
            qa_settings.qa_candidate_email,
            qa_settings.qa_candidate_password,
            run_id=run_id,
        )
    except JourneyStepError as err:
        skip_if_account_login_throttled(err, JRN_ID)
        raise

    assert "/account" in report.routes_checked
    assert len(report.viewports_checked) == 2
    assert len(report.evidence_paths) == 2
