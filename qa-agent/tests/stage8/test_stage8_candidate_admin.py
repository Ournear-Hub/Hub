"""Stage 8 — candidate ↔ admin workflow."""

from __future__ import annotations

import pytest
from playwright.sync_api import Page, expect

from ournear_qa.browser.navigation import NavigationHelper
from ournear_qa.evidence import ARTIFACTS_DIR
from ournear_qa.journey.admin_application import assert_role_cannot_access_admin_applications
from ournear_qa.journey.candidate_admin_workflow import DEFAULT_BUSINESS_ID, run_candidate_admin_workflow
from ournear_qa.journey.decision_communication import resolve_stage7_target
from ournear_qa.journey.employer_resume import assert_http_forbidden, login_employer
from ournear_qa.journey.errors import JourneyStepError
from ournear_qa.journey.account_auth import login_account_user

pytestmark = [pytest.mark.stage8]

EVIDENCE_ROOT = ARTIFACTS_DIR / "stage8" / "evidence"


def skip_if_account_login_throttled(err: JourneyStepError, journey_id: str) -> None:
    if "429" in err.actual and "account-login" in err.actual:
        pytest.skip(f"{journey_id}: account-login rate limit")


@pytest.fixture
def desktop_page(page: Page):
    page.set_viewport_size({"width": 1400, "height": 900})
    return page


def _shot(page: Page, directory, name: str) -> str:
    path = directory / name
    page.screenshot(path=str(path), full_page=True)
    return path.relative_to(ARTIFACTS_DIR).as_posix()


def _require_creds(qa_settings) -> None:
    missing = [
        n
        for n, v in (
            ("OURNEAR_QA_CANDIDATE_EMAIL", qa_settings.qa_candidate_email),
            ("OURNEAR_QA_CANDIDATE_PASSWORD", qa_settings.qa_candidate_password),
            ("OURNEAR_QA_ADMIN_EMAIL", qa_settings.qa_admin_email),
            ("OURNEAR_QA_ADMIN_PASSWORD", qa_settings.qa_admin_password),
        )
        if not v
    ]
    if missing:
        pytest.skip(f"Stage 8 missing credentials: {', '.join(missing)}")


def test_stg8_candidate_admin_workflow(
    desktop_page, base_url, qa_settings, stage8_run_id, record_stage8
) -> None:
    if qa_settings.ournear_allow_run != "local":
        pytest.skip("Stage 8 local only")
    _require_creds(qa_settings)
    try:
        target = resolve_stage7_target(DEFAULT_BUSINESS_ID)
    except JourneyStepError:
        pytest.skip(f"{DEFAULT_BUSINESS_ID} not in DB")

    evidence = EVIDENCE_ROOT / stage8_run_id / "STG8-JRN"
    evidence.mkdir(parents=True, exist_ok=True)

    try:
        report = run_candidate_admin_workflow(
            desktop_page,
            base_url,
            business_id=DEFAULT_BUSINESS_ID,
            candidate_email=qa_settings.qa_candidate_email,
            candidate_password=qa_settings.qa_candidate_password,
            admin_email=qa_settings.qa_admin_email,
            admin_password=qa_settings.qa_admin_password,
        )
    except JourneyStepError as err:
        skip_if_account_login_throttled(err, "STG8")
        raise

    shot = _shot(desktop_page, evidence, "workflow-complete.png")
    app_id = report.application_id
    slug = report.job_slug

    mapping = {
        "STG8-CAND-001": ("STG8-CAND-list", ["CAN-07"], "/account/applications"),
        "STG8-CAND-004": ("STG8-CAND-filter-active", ["CAN-07"], "/account/applications?filter=active"),
        "STG8-CAND-002": ("STG8-CAND-detail-shortlisted", ["CAN-07"], f"/account/applications/{app_id}"),
        "STG8-CAND-003": ("STG8-CAND-timeline", ["CAN-07"], f"/account/applications/{app_id}"),
        "STG8-CAND-005": ("STG8-CAND-relogin", ["CAN-07"], f"/account/applications/{app_id}"),
        "STG8-ADM-001": ("STG8-ADM-drawer", ["ADM-03"], "/admin/applications"),
        "STG8-ADM-002": ("STG8-ADM-candidate-tab", ["ADM-04"], "/admin/candidates"),
        "STG8-ADM-003": ("STG8-ADM-status-interview", ["ADM-03"], "/admin/applications"),
        "STG8-CAND-006": ("STG8-CAND-post-admin-interview", ["CAN-07"], f"/account/applications/{app_id}"),
    }
    for case_id, (step, fids, route) in mapping.items():
        ok = step in report.candidate_steps or step in report.admin_steps
        record_stage8(
            case_id,
            feature_ids=fids,
            route=route,
            expected=f"Journey step {step}",
            actual="completed" if ok else "missing",
            status="PASS" if ok else "FAIL",
            evidence_rel=shot,
        )
        if not ok:
            pytest.fail(f"Missing step {step} for {case_id}")

    record_stage8(
        "STG8-CORR-001",
        feature_ids=["CAN-04", "X-03"],
        route="/account/profile",
        expected="Snapshot filename stable after profile navigation",
        actual=f"snapshot={report.snapshot_filename_before!r}; steps={report.correction_steps}",
        status="PASS" if "STG8-CORR-snapshot-unchanged" in report.correction_steps else "FAIL",
        evidence_rel=shot,
    )
    record_stage8(
        "STG8-CORR-002",
        feature_ids=["CAN-07"],
        route="/account/applications",
        expected="Candidate correction-request workflow if product supports",
        actual="No dedicated post-submit correction request UI — profile edit + immutable snapshot only",
        status="MISSING",
        notes="Not a release blocker; snapshot integrity is the supported model",
    )
    record_stage8(
        "STG8-NOTIF-001",
        feature_ids=["CAN-09"],
        route="/account/notifications",
        expected="Shortlist notification; mark read",
        actual=str(report.notification_steps),
        status="PASS"
        if "STG8-NOTIF-shortlist-present" in report.notification_steps
        or "STG8-NOTIF-mark-read" in report.notification_steps
        else "FAIL",
        evidence_rel=shot,
        notes="Stage 7 established shortlist notification; mark read optional if already read",
    )
    record_stage8(
        "STG8-NOTIF-002",
        feature_ids=["CAN-09"],
        route="email",
        expected="Email on Interview status",
        actual="No interview status mailable in RecruitmentNotificationService",
        status="NOT_TESTED",
    )


def test_stg8_auth_001_non_owner_forbidden(
    desktop_page, base_url, qa_settings, stage8_run_id, record_stage8
) -> None:
    target = resolve_stage7_target(DEFAULT_BUSINESS_ID)
    email = qa_settings.qa_employer_b_email or "qa-employer-b@example.test"
    password = qa_settings.qa_employer_b_password or qa_settings.qa_employer_a_password
    if not password:
        pytest.skip("Password required")
    evidence = EVIDENCE_ROOT / stage8_run_id / "STG8-AUTH-001"
    evidence.mkdir(parents=True, exist_ok=True)
    try:
        login_account_user(
            desktop_page, base_url, email, password, journey_id="STG8", step_id="auth-login"
        )
    except JourneyStepError as err:
        skip_if_account_login_throttled(err, "STG8")
        raise
    assert_http_forbidden(
        desktop_page,
        base_url,
        f"/account/applications/{target.application_id}",
        forbidden_token=target.business_id,
    )
    rel = _shot(desktop_page, evidence, "forbidden.png")
    record_stage8(
        "STG8-AUTH-001",
        feature_ids=["X-03"],
        route=f"/account/applications/{target.application_id}",
        expected="Non-owner denied",
        actual="403/forbidden",
        status="PASS",
        evidence_rel=rel,
    )


def test_stg8_auth_002_candidate_admin_denied(
    desktop_page, base_url, qa_settings, stage8_run_id, record_stage8
) -> None:
    if not qa_settings.qa_candidate_email or not qa_settings.qa_candidate_password:
        pytest.skip("Candidate credentials required")
    target = resolve_stage7_target(DEFAULT_BUSINESS_ID)
    evidence = EVIDENCE_ROOT / stage8_run_id / "STG8-AUTH-002"
    evidence.mkdir(parents=True, exist_ok=True)
    assert_role_cannot_access_admin_applications(
        desktop_page,
        base_url,
        qa_settings.qa_candidate_email,
        qa_settings.qa_candidate_password,
        application_id=target.application_id,
        business_id=target.business_id,
    )
    rel = _shot(desktop_page, evidence, "admin-denied.png")
    record_stage8(
        "STG8-AUTH-002",
        feature_ids=["X-03"],
        route="/admin/applications",
        expected="Candidate cannot access admin workspace",
        actual="Isolation helper passed",
        status="PASS",
        evidence_rel=rel,
    )
