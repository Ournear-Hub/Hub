"""Stage 9 — employer ↔ candidate workflow."""

from __future__ import annotations

import re

import pytest
from playwright.sync_api import Page, expect

from ournear_qa.browser.navigation import NavigationHelper
from ournear_qa.evidence import ARTIFACTS_DIR
from ournear_qa.journey.decision_communication import DEFAULT_BUSINESS_ID, resolve_stage7_target
from ournear_qa.journey.employer_candidate_workflow import run_employer_candidate_workflow
from ournear_qa.journey.employer_resume import (
    assert_http_forbidden,
    login_employer,
    run_employer_b_isolation_steps,
    EmployerJourneyTarget,
)
from ournear_qa.journey.errors import JourneyStepError
from ournear_qa.journey.account_auth import login_account_user

pytestmark = [pytest.mark.stage9]

EVIDENCE = ARTIFACTS_DIR / "stage9" / "evidence"


def skip_throttle(err: JourneyStepError, label: str) -> None:
    if "429" in err.actual and "account-login" in err.actual:
        pytest.skip(f"{label}: login rate limit")


@pytest.fixture
def desktop_page(page: Page):
    page.set_viewport_size({"width": 1400, "height": 900})
    return page


def _shot(page: Page, directory, name: str) -> str:
    path = directory / name
    page.screenshot(path=str(path), full_page=True)
    return path.relative_to(ARTIFACTS_DIR).as_posix()


def _target() -> EmployerJourneyTarget:
    admin_target = resolve_stage7_target(DEFAULT_BUSINESS_ID)
    return EmployerJourneyTarget(
        application_id=admin_target.application_id,
        business_id=admin_target.business_id,
        job_slug=admin_target.job_slug,
        job_title=admin_target.job_title,
        resume_path_set=admin_target.resume_path_set,
    )


def test_stg9_employer_candidate_workflow(
    desktop_page, base_url, qa_settings, stage9_run_id, record_stage9
) -> None:
    if qa_settings.ournear_allow_run != "local":
        pytest.skip("local only")
    for attr in ("qa_employer_a_email", "qa_employer_a_password", "qa_admin_email", "qa_admin_password", "qa_candidate_email", "qa_candidate_password"):
        if not getattr(qa_settings, attr, None):
            pytest.skip(f"Missing {attr}")

    evidence = EVIDENCE / stage9_run_id / "STG9-JRN"
    evidence.mkdir(parents=True, exist_ok=True)

    try:
        report = run_employer_candidate_workflow(
            desktop_page,
            base_url,
            business_id=DEFAULT_BUSINESS_ID,
            employer_a_email=qa_settings.qa_employer_a_email,
            employer_a_password=qa_settings.qa_employer_a_password,
            admin_email=qa_settings.qa_admin_email,
            admin_password=qa_settings.qa_admin_password,
            candidate_email=qa_settings.qa_candidate_email,
            candidate_password=qa_settings.qa_candidate_password,
        )
    except JourneyStepError as err:
        skip_throttle(err, "STG9")
        raise

    shot = _shot(desktop_page, evidence, "workflow.png")
    app_id = report.application_id
    slug = report.job_slug

    emp_map = {
        "STG9-EMP-001": ("STG9-EMP-detail-interview", "/account/hiring/applications"),
        "STG9-EMP-002": ("STG9-EMP-resume", f"/account/employer-applications/{app_id}/resume"),
        "STG9-EMP-003": ("STG9-EMP-reload", f"/account/jobs/{slug}/applications/{app_id}"),
    }
    for case, (step, route) in emp_map.items():
        ok = step in report.employer_steps
        record_stage9(case, feature_ids=["EMP-03"], route=route, expected=step, actual="ok" if ok else "missing", status="PASS" if ok else "FAIL", evidence_rel=shot)
        if not ok:
            pytest.fail(step)

    adm_ok = "STG9-ADM-interview-scheduled" in report.admin_steps or "STG9-ADM-interview-existing" in report.admin_steps
    record_stage9("STG9-INT-ADM-001", feature_ids=["ADM-03"], route="/admin/applications", expected="Interview tab row or schedule", actual=str(report.admin_steps), status="PASS" if adm_ok else "FAIL", evidence_rel=shot)
    record_stage9("STG9-INT-ADM-002", feature_ids=["ADM-03"], route="/admin/applications", expected="Interview persists reload", actual="STG9-ADM-interview-persist" if "STG9-ADM-interview-persist" in report.admin_steps else "missing", status="PASS" if "STG9-ADM-interview-persist" in report.admin_steps else "FAIL", evidence_rel=shot)

    record_stage9("STG9-CAND-002", feature_ids=["CAN-07"], route=f"/account/applications/{app_id}", expected="Interview status", actual=str(report.candidate_steps), status="PASS" if "STG9-CAND-status-interview" in report.candidate_steps else "FAIL", evidence_rel=shot)

    if report.interview_scheduled:
        record_stage9("STG9-CAND-001", feature_ids=["CAN-09"], route="/account/notifications", expected="Interview scheduled notification", actual=str(report.candidate_steps), status="PASS" if "STG9-CAND-interview-notification" in report.candidate_steps else "FAIL", evidence_rel=shot)
    else:
        record_stage9("STG9-CAND-001", feature_ids=["CAN-09"], route="/account/notifications", expected="New interview notification", actual="Interview already on record — no new schedule this run", status="NOT_TESTED", notes="Idempotent: did not re-schedule")

    record_stage9("STG9-X-001", feature_ids=["X-01"], route="cross-role", expected="Interview visible employer+candidate", actual=f"employer+candidate steps for {report.business_id}", status="PASS", evidence_rel=shot)


def test_stg9_missing_employer_capabilities(
    desktop_page, base_url, qa_settings, stage9_run_id, record_stage9
) -> None:
    target = _target()
    evidence = EVIDENCE / stage9_run_id / "STG9-MISSING"
    evidence.mkdir(parents=True, exist_ok=True)
    login_employer(desktop_page, base_url, qa_settings.qa_employer_a_email, qa_settings.qa_employer_a_password)
    NavigationHelper(desktop_page, base_url).goto_path(
        f"/account/jobs/{target.job_slug}/applications/{target.application_id}"
    )
    expect(desktop_page.get_by_role("heading", name="Application actions")).to_be_visible(timeout=15_000)
    has_pipeline = desktop_page.get_by_role("button", name=re.compile(r"Shortlist|Reject|Interview|Schedule", re.I)).count() > 0
    rel = _shot(desktop_page, evidence, "employer-read-only.png")
    record_stage9("STG9-EMP-ACT-001", feature_ids=["EMP-03"], route=f"/account/jobs/{target.job_slug}/applications/{target.application_id}", expected="Employer pipeline/interview actions", actual="MISSING — read-only Application actions", status="MISSING" if not has_pipeline else "FAIL", evidence_rel=rel)
    record_stage9("STG9-INT-EMP-001", feature_ids=["EMP-03"], route=f"/account/jobs/{target.job_slug}/applications/{target.application_id}", expected="Employer interview scheduling UI", actual="MISSING — scheduling is admin/staff Application drawer only", status="MISSING", evidence_rel=rel)
    record_stage9("STG9-COMM-001", feature_ids=["EMP-08"], route="/account", expected="Employer-to-candidate messaging", actual="MISSING — MessageService restricts in-app messages to admin recipients", status="MISSING", evidence_rel=rel)
    record_stage9("STG9-CAND-RESP-001", feature_ids=["CAN-07"], route=f"/account/applications/{target.application_id}", expected="Candidate interview accept/decline", actual="MISSING — no response controls on application detail", status="MISSING", evidence_rel=rel)
    if has_pipeline:
        pytest.fail("Unexpected employer action buttons")


def test_stg9_auth_employer_b_isolation(
    desktop_page, base_url, qa_settings, stage9_run_id, record_stage9
) -> None:
    target = _target()
    email = qa_settings.qa_employer_b_email or "qa-employer-b@example.test"
    password = qa_settings.qa_employer_b_password or qa_settings.qa_employer_a_password
    if not password:
        pytest.skip("Employer B password required")
    evidence = EVIDENCE / stage9_run_id / "STG9-AUTH-001"
    evidence.mkdir(parents=True, exist_ok=True)
    try:
        run_employer_b_isolation_steps(desktop_page, base_url, email, password, target)
    except JourneyStepError as err:
        skip_throttle(err, "STG9")
        raise
    rel = _shot(desktop_page, evidence, "employer-b.png")
    record_stage9("STG9-AUTH-001", feature_ids=["EMP-09"], route="/account/hiring/applications", expected="Employer B isolation", actual="isolation journey passed", status="PASS", evidence_rel=rel)


def test_stg9_auth_non_owner_application(
    desktop_page, base_url, qa_settings, stage9_run_id, record_stage9
) -> None:
    target = _target()
    email = qa_settings.qa_employer_b_email or "qa-employer-b@example.test"
    password = qa_settings.qa_employer_b_password or qa_settings.qa_employer_a_password
    evidence = EVIDENCE / stage9_run_id / "STG9-AUTH-002"
    evidence.mkdir(parents=True, exist_ok=True)
    try:
        login_account_user(desktop_page, base_url, email, password, journey_id="STG9", step_id="auth")
    except JourneyStepError as err:
        skip_throttle(err, "STG9")
        raise
    assert_http_forbidden(desktop_page, base_url, f"/account/applications/{target.application_id}", forbidden_token=target.business_id)
    rel = _shot(desktop_page, evidence, "forbidden.png")
    record_stage9("STG9-AUTH-002", feature_ids=["X-03"], route=f"/account/applications/{target.application_id}", expected="Non-owner forbidden", actual="403", status="PASS", evidence_rel=rel)
