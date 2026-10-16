"""Stage 7 — admin decision and candidate communication loop."""

from __future__ import annotations

import re

import pytest
from playwright.sync_api import Page, expect

from ournear_qa.browser.navigation import NavigationHelper
from ournear_qa.evidence import ARTIFACTS_DIR
from ournear_qa.journey.decision_communication import (
    DEFAULT_BUSINESS_ID,
    resolve_stage7_target,
    run_decision_communication_loop,
)
from ournear_qa.journey.employer_resume import assert_http_forbidden, login_employer
from ournear_qa.journey.errors import JourneyStepError
from ournear_qa.journey.account_auth import login_account_user

pytestmark = [pytest.mark.stage7]

STAGE7_EVIDENCE_ROOT = ARTIFACTS_DIR / "stage7" / "evidence"


def skip_if_account_login_throttled(err: JourneyStepError, journey_id: str) -> None:
    if "429" in err.actual and "account-login" in err.actual:
        pytest.skip(f"{journey_id}: account-login rate limit — wait and retry")


@pytest.fixture
def desktop_page(page: Page):
    page.set_viewport_size({"width": 1400, "height": 900})
    return page


def _shot(page: Page, directory, name: str) -> str:
    target = directory / name
    page.screenshot(path=str(target), full_page=True)
    return target.relative_to(ARTIFACTS_DIR).as_posix()


def _require_creds(qa_settings) -> None:
    missing = [
        n
        for n, v in (
            ("OURNEAR_QA_ADMIN_EMAIL", qa_settings.qa_admin_email),
            ("OURNEAR_QA_ADMIN_PASSWORD", qa_settings.qa_admin_password),
            ("OURNEAR_QA_EMPLOYER_A_EMAIL", qa_settings.qa_employer_a_email),
            ("OURNEAR_QA_EMPLOYER_A_PASSWORD", qa_settings.qa_employer_a_password),
            ("OURNEAR_QA_CANDIDATE_EMAIL", qa_settings.qa_candidate_email),
            ("OURNEAR_QA_CANDIDATE_PASSWORD", qa_settings.qa_candidate_password),
        )
        if not v
    ]
    if missing:
        pytest.skip(f"Stage 7 missing credentials: {', '.join(missing)}")


def test_stg7_decision_communication_loop(
    desktop_page, base_url, qa_settings, stage7_run_id, record_stage7
) -> None:
    if qa_settings.ournear_allow_run != "local":
        pytest.skip("Stage 7 is local-only")
    _require_creds(qa_settings)
    try:
        resolve_stage7_target(DEFAULT_BUSINESS_ID)
    except JourneyStepError:
        pytest.skip(f"Application {DEFAULT_BUSINESS_ID} not found in local DB")

    evidence = STAGE7_EVIDENCE_ROOT / stage7_run_id / "STG7-JRN"
    evidence.mkdir(parents=True, exist_ok=True)

    try:
        report = run_decision_communication_loop(
            desktop_page,
            base_url,
            business_id=DEFAULT_BUSINESS_ID,
            admin_email=qa_settings.qa_admin_email,
            admin_password=qa_settings.qa_admin_password,
            employer_a_email=qa_settings.qa_employer_a_email,
            employer_a_password=qa_settings.qa_employer_a_password,
            candidate_email=qa_settings.qa_candidate_email,
            candidate_password=qa_settings.qa_candidate_password,
        )
    except JourneyStepError as err:
        skip_if_account_login_throttled(err, "STG7")
        raise

    shot = _shot(desktop_page, evidence, "decision-loop-complete.png")
    slug = report.job_slug
    app_id = report.application_id
    bid = report.business_id

    admin_shortlist = report.shortlist_performed
    record_stage7(
        "STG7-ADM-001",
        feature_ids=["ADM-03"],
        route="/admin/applications",
        expected="Admin shortlists ONT-A-* with confirmation",
        actual="shortlist_performed="
        + str(admin_shortlist)
        + f"; prior_status={report.prior_status}",
        status="PASS",
        evidence_rel=shot,
    )
    record_stage7(
        "STG7-ADM-002",
        feature_ids=["ADM-03"],
        route="/admin/applications",
        expected="Shortlisted persists after reload",
        actual="STG7-ADM-shortlist or already-shortlisted with reload check",
        status="PASS" if "STG7-ADM-shortlist" in report.admin_steps or "STG7-ADM-already-shortlisted" in report.admin_steps else "FAIL",
        evidence_rel=shot,
    )

    record_stage7(
        "STG7-EMP-001",
        feature_ids=["EMP-03"],
        route=f"/account/jobs/{slug}/applications/{app_id}",
        expected="Employer detail shows Shortlisted",
        actual="STG7-EMP-detail-shortlisted" if "STG7-EMP-detail-shortlisted" in report.employer_steps else "missing step",
        status="PASS" if "STG7-EMP-detail-shortlisted" in report.employer_steps else "FAIL",
        evidence_rel=shot,
    )
    record_stage7(
        "STG7-EMP-002",
        feature_ids=["EMP-08"],
        route="/account/notifications",
        expected="Employer notification center loads",
        actual="index reachable",
        status="PASS" if "STG7-EMP-notifications-index" in report.employer_steps else "FAIL",
        evidence_rel=shot,
    )

    record_stage7(
        "STG7-CAND-001",
        feature_ids=["CAN-09"],
        route="/account/notifications",
        expected="Candidate in-app shortlisted notification",
        actual="visible on notifications index",
        status="PASS" if "STG7-CAND-notification-visible" in report.candidate_steps else "FAIL",
        evidence_rel=shot,
    )
    record_stage7(
        "STG7-CAND-003",
        feature_ids=["CAN-09"],
        route="/account/notifications",
        expected="Open related page → applications",
        actual="deeplink step",
        status="PASS" if "STG7-CAND-notification-deeplink" in report.candidate_steps else "FAIL",
        evidence_rel=shot,
    )
    record_stage7(
        "STG7-CAND-002",
        feature_ids=["CAN-05"],
        route=f"/account/applications/{app_id}",
        expected="Candidate detail/timeline Shortlisted",
        actual="detail step",
        status="PASS" if "STG7-CAND-detail-timeline" in report.candidate_steps else "FAIL",
        evidence_rel=shot,
    )
    record_stage7(
        "STG7-NOTIF-001",
        feature_ids=["CAN-09"],
        route="/account/notifications",
        expected="Notification body references shortlist for job",
        actual=f"shortlisted copy present for {bid}",
        status="PASS" if "STG7-CAND-notification-visible" in report.candidate_steps else "FAIL",
        evidence_rel=shot,
    )
    record_stage7(
        "STG7-EMAIL-001",
        feature_ids=["CAN-09"],
        route="email",
        expected="Email on shortlist if product sends",
        actual="RecruitmentNotificationService queues mail only for Rejected/Selected/Interview — shortlist is in-app only",
        status="NOT_TESTED",
        notes="No mailable for Shortlisted; log/array mail not asserted in this run",
    )
    record_stage7(
        "STG7-X-001",
        feature_ids=["X-01"],
        route="cross-role",
        expected=f"Shortlisted consistent for {bid}",
        actual=f"employer+candidate steps completed; application_id={app_id}",
        status="PASS",
        evidence_rel=shot,
    )


def test_stg7_emp_act_001_employer_decision_missing(
    desktop_page, base_url, qa_settings, stage7_run_id, record_stage7
) -> None:
    target = resolve_stage7_target(DEFAULT_BUSINESS_ID)
    if not qa_settings.qa_employer_a_email or not qa_settings.qa_employer_a_password:
        pytest.skip("Employer credentials required")
    evidence = STAGE7_EVIDENCE_ROOT / stage7_run_id / "STG7-EMP-ACT-001"
    evidence.mkdir(parents=True, exist_ok=True)
    login_employer(desktop_page, base_url, qa_settings.qa_employer_a_email, qa_settings.qa_employer_a_password)
    NavigationHelper(desktop_page, base_url).goto_path(
        f"/account/jobs/{target.job_slug}/applications/{target.application_id}"
    )
    expect(desktop_page.get_by_role("heading", name="Application actions")).to_be_visible(timeout=15_000)
    has_btn = desktop_page.get_by_role("button", name=re.compile(r"Shortlist|Reject|Interview", re.I)).count() > 0
    rel = _shot(desktop_page, evidence, "employer-read-only.png")
    record_stage7(
        "STG7-EMP-ACT-001",
        feature_ids=["EMP-03"],
        route=f"/account/jobs/{target.job_slug}/applications/{target.application_id}",
        expected="Employer decision/response controls if implemented",
        actual="MISSING — read-only Application actions (Stage 5/6 dependency)",
        status="MISSING" if not has_btn else "FAIL",
        evidence_rel=rel,
    )
    if has_btn:
        pytest.fail("Unexpected employer action buttons")


def test_stg7_auth_001_other_candidate_forbidden(
    desktop_page, base_url, qa_settings, stage7_run_id, record_stage7
) -> None:
    target = resolve_stage7_target(DEFAULT_BUSINESS_ID)
    email = qa_settings.qa_employer_b_email or "qa-employer-b@example.test"
    password = qa_settings.qa_employer_b_password or qa_settings.qa_employer_a_password
    if not password:
        pytest.skip("Need account password for isolation check")
    evidence = STAGE7_EVIDENCE_ROOT / stage7_run_id / "STG7-AUTH-001"
    evidence.mkdir(parents=True, exist_ok=True)
    try:
        login_account_user(
            desktop_page,
            base_url,
            email,
            password,
            journey_id="STG7",
            step_id="other-account-login",
        )
    except JourneyStepError as err:
        skip_if_account_login_throttled(err, "STG7")
        raise
    assert_http_forbidden(
        desktop_page,
        base_url,
        f"/account/applications/{target.application_id}",
        forbidden_token=target.business_id,
    )
    rel = _shot(desktop_page, evidence, "other-candidate-forbidden.png")
    record_stage7(
        "STG7-AUTH-001",
        feature_ids=["X-03"],
        route=f"/account/applications/{target.application_id}",
        expected="Non-owner account cannot open candidate application",
        actual="403/forbidden on direct URL (employer B used as non-owner account)",
        status="PASS",
        evidence_rel=rel,
        notes="Candidate-vs-candidate isolation covered by ApplicationExperienceTest; this verifies direct URL denial",
    )
