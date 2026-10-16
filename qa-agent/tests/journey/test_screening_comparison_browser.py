from __future__ import annotations

import pytest

from ournear_qa.evidence import ARTIFACTS_DIR
from ournear_qa.journey.errors import JourneyStepError
from ournear_qa.journey.screening_comparison import JRN_ID, run_screening_comparison


@pytest.mark.journey
def test_jrn_screening_comparison(browser_session, base_url, qa_settings, request) -> None:
    if qa_settings.ournear_allow_run != "local":
        pytest.skip(f"{JRN_ID}: local only")

    if not qa_settings.qa_admin_email or not qa_settings.qa_admin_password:
        pytest.skip(f"{JRN_ID}: admin credentials required")

    run_id = getattr(request.config, "_ournear_qa_run_id", "adhoc")
    evidence = ARTIFACTS_DIR / "c2-screening" / run_id

    page = browser_session.page
    viewports = [
        ("desktop", {"width": 1440, "height": 900}),
        ("mobile", {"width": 390, "height": 844}),
    ]

    for label, size in viewports:
        page.set_viewport_size(size)
        try:
            report = run_screening_comparison(
                page,
                base_url,
                qa_settings.qa_admin_email,
                qa_settings.qa_admin_password,
                evidence_dir=evidence if label == "desktop" else None,
                viewport_label=label,
            )
        except JourneyStepError as err:
            pytest.fail(f"{JRN_ID} ({label}): {err.step_id} — {err.actual}")

        assert len(report.selected_names) >= 2
        assert "C2-07-compare-data-visible" in report.steps
