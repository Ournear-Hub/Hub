"""Stage 11 — complete recruitment lifecycle (browser + registry recording)."""

from __future__ import annotations

import re

import pytest
from playwright.sync_api import Page, expect

from ournear_qa.browser.navigation import NavigationHelper
from ournear_qa.evidence import ARTIFACTS_DIR
from ournear_qa.journey.decision_communication import DEFAULT_BUSINESS_ID, resolve_stage7_target
from ournear_qa.journey.account_auth import login_account_user
from ournear_qa.journey.employer_resume import login_employer
from ournear_qa.journey.errors import JourneyStepError
from ournear_qa.journey.recruitment_lifecycle import run_lifecycle_consistency

pytestmark = [pytest.mark.stage11]

EVIDENCE = ARTIFACTS_DIR / "stage11" / "evidence"


def skip_throttle(err: JourneyStepError, label: str) -> None:
    if "429" in err.actual and "account-login" in err.actual:
        pytest.skip(f"{label}: account-login rate limit")


@pytest.fixture
def desktop_page(page: Page):
    page.set_viewport_size({"width": 1400, "height": 900})
    return page


def _shot(page: Page, directory, name: str) -> str:
    path = directory / name
    page.screenshot(path=str(path), full_page=True)
    return path.relative_to(ARTIFACTS_DIR).as_posix()


def _require_all_creds(qa_settings) -> None:
    for attr in (
        "qa_candidate_email",
        "qa_candidate_password",
        "qa_employer_a_email",
        "qa_employer_a_password",
        "qa_admin_email",
        "qa_admin_password",
    ):
        if not getattr(qa_settings, attr, None):
            pytest.skip(f"Missing {attr}")


def test_stg11_lifecycle_consistency_journey(
    desktop_page, base_url, qa_settings, stage11_run_id, record_stage11
) -> None:
    if qa_settings.ournear_allow_run != "local":
        pytest.skip("local only")
    _require_all_creds(qa_settings)
    try:
        resolve_stage7_target(DEFAULT_BUSINESS_ID)
    except JourneyStepError:
        pytest.skip(f"{DEFAULT_BUSINESS_ID} not found")

    evidence = EVIDENCE / stage11_run_id / "STG11-JRN"
    evidence.mkdir(parents=True, exist_ok=True)

    try:
        report = run_lifecycle_consistency(
            desktop_page,
            base_url,
            business_id=DEFAULT_BUSINESS_ID,
            candidate_email=qa_settings.qa_candidate_email,
            candidate_password=qa_settings.qa_candidate_password,
            employer_a_email=qa_settings.qa_employer_a_email,
            employer_a_password=qa_settings.qa_employer_a_password,
            admin_email=qa_settings.qa_admin_email,
            admin_password=qa_settings.qa_admin_password,
        )
    except JourneyStepError as err:
        skip_throttle(err, "STG11")
        raise

    shot = _shot(desktop_page, evidence, "lifecycle.png")
    app_id = report.application_id
    slug = report.job_slug

    record_stage11(
        "STG11-LCY-001",
        feature_ids=["X-01"],
        route="cross-role",
        expected=f"Correlation {report.business_id} across roles",
        actual=f"app_id={app_id} slug={slug}",
        status="PASS",
        evidence_rel=shot,
    )

    cand_ok = all(
        s in report.candidate_steps
        for s in (
            "STG11-CAND-detail-interview",
            "STG11-CAND-timeline",
            "STG11-CAND-reload",
        )
    )
    record_stage11(
        "STG11-LCY-002",
        feature_ids=["CAN-07"],
        route=f"/account/applications/{app_id}",
        expected="Interview status + timeline",
        actual=str(report.candidate_steps),
        status="PASS" if cand_ok else "FAIL",
        evidence_rel=shot,
    )

    emp_ok = "STG11-EMP-detail-interview" in report.employer_steps
    record_stage11(
        "STG11-LCY-003",
        feature_ids=["EMP-03"],
        route="/account/hiring/applications",
        expected="Employer Interview alignment",
        actual=str(report.employer_steps),
        status="PASS" if emp_ok else "FAIL",
        evidence_rel=shot,
    )

    resume_ok = "STG11-EMP-resume" in report.employer_steps
    record_stage11(
        "STG11-LCY-004",
        feature_ids=["EMP-03"],
        route=f"/account/employer-applications/{app_id}/resume",
        expected="Readable resume snapshot",
        actual="download ok" if resume_ok else "missing",
        status="PASS" if resume_ok else "FAIL",
        evidence_rel=shot,
    )

    adm_ok = "STG11-ADM-interview-tab" in report.admin_steps
    record_stage11(
        "STG11-LCY-005",
        feature_ids=["ADM-03"],
        route="/admin/applications",
        expected="Admin Interview tab row",
        actual=str(report.admin_steps),
        status="PASS" if adm_ok else "FAIL",
        evidence_rel=shot,
    )

    notif_status = "PASS" if "STG11-CAND-notifications" in report.candidate_steps else "FAIL"
    record_stage11(
        "STG11-LCY-006",
        feature_ids=["CAN-09"],
        route="/account/notifications",
        expected="Notification center reachable",
        actual=f"steps={report.candidate_steps} href={report.notification_href}",
        status=notif_status,
        evidence_rel=shot,
    )

    if not (cand_ok and emp_ok and resume_ok and adm_ok):
        pytest.fail("Lifecycle consistency checks failed")


def test_stg11_prior_apply_evidence_not_repeated(
    stage11_run_id, record_stage11
) -> None:
    record_stage11(
        "STG11-APPLY-001",
        feature_ids=["CAN-07"],
        route="/jobs/{slug}/apply",
        expected="Browser apply journey",
        actual="Prior Stage 3 canonical 20261010T073839Z-d2b6b491 — not re-run",
        status="NOT_TESTED",
        automation="reference",
        notes="OURNEAR_STAGE_3_APPLICATION_WORKFLOW_QA_REPORT.md",
    )
    record_stage11(
        "STG11-NT-002",
        feature_ids=["CAN-07"],
        route="/account/applications",
        expected="Apply reload/re-login matrix",
        actual="Covered by Stage 3 + Stage 8 STG8-CAND-005",
        status="NOT_TESTED",
        automation="reference",
    )
    record_stage11(
        "STG11-AUTH-001",
        feature_ids=["EMP-09"],
        route="/account/hiring/applications",
        expected="Employer B isolation",
        actual="Stage 10 STG10-AUTH-007–009 PASS — not re-run",
        status="NOT_TESTED",
        automation="reference",
        notes="OURNEAR_STAGE_10_SECURITY_NEGATIVE_RECOVERY_QA_REPORT.md",
    )
    record_stage11(
        "STG11-NT-001",
        feature_ids=["CAN-09"],
        route="email",
        expected="Shortlist/interview email delivery",
        actual="No local mail capture in Stage 11; in-app notifications verified in journey",
        status="NOT_TESTED",
        automation="none",
    )


def test_stg11_self_service_controls_on_correlation_fixture(
    desktop_page, base_url, qa_settings, stage11_run_id, record_stage11
) -> None:
    """Read-only UI verification on ONT-A-000051 (preserve fixture state)."""
    target = resolve_stage7_target(DEFAULT_BUSINESS_ID)
    if not qa_settings.qa_employer_a_email or not qa_settings.qa_employer_a_password:
        pytest.skip("Employer creds required")
    evidence = EVIDENCE / stage11_run_id / "STG11-MISSING"
    evidence.mkdir(parents=True, exist_ok=True)
    try:
        login_employer(desktop_page, base_url, qa_settings.qa_employer_a_email, qa_settings.qa_employer_a_password)
    except JourneyStepError as err:
        skip_throttle(err, "STG11")
        raise
    NavigationHelper(desktop_page, base_url).goto_path(
        f"/account/jobs/{target.job_slug}/applications/{target.application_id}"
    )
    expect(desktop_page.get_by_role("heading", name="Recruitment actions")).to_be_visible(timeout=15_000)
    has_shortlist = desktop_page.get_by_role("button", name="Shortlist").count() > 0
    has_reject = desktop_page.get_by_role("button", name="Reject").count() > 0
    has_schedule = desktop_page.get_by_role("button", name="Schedule interview").count() > 0
    has_messages = desktop_page.get_by_role("heading", name="Messages").count() > 0
    pipeline_actions = has_shortlist or has_reject or has_schedule
    rel = _shot(desktop_page, evidence, "employer-controls.png")

    record_stage11(
        "STG11-MISS-001",
        feature_ids=["EMP-03"],
        route=f"/account/jobs/{target.job_slug}/applications/{target.application_id}",
        expected="Employer self-service pipeline controls",
        actual=f"shortlist={has_shortlist}; reject={has_reject}; schedule={has_schedule}",
        status="PASS" if pipeline_actions else "FAIL",
        evidence_rel=rel,
        notes="Shortlist hidden when application is not eligible (e.g. Interview fixture)",
    )
    record_stage11(
        "STG11-MISS-002",
        feature_ids=["EMP-03"],
        route=f"/account/jobs/{target.job_slug}/applications/{target.application_id}",
        expected="Employer interview scheduling UI",
        actual=f"schedule={has_schedule}",
        status="PASS" if has_schedule else "FAIL",
        evidence_rel=rel,
    )
    record_stage11(
        "STG11-MISS-003",
        feature_ids=["EMP-08"],
        route=f"/account/jobs/{target.job_slug}/applications/{target.application_id}",
        expected="Employer-to-candidate messaging UI",
        actual=f"messages_section={has_messages}",
        status="PASS" if has_messages else "FAIL",
        evidence_rel=rel,
    )

    try:
        login_account_user(
            desktop_page,
            base_url,
            qa_settings.qa_candidate_email,
            qa_settings.qa_candidate_password,
            journey_id="STG11",
            step_id="missing-candidate",
        )
    except JourneyStepError as err:
        skip_throttle(err, "STG11")
        raise
    NavigationHelper(desktop_page, base_url).goto_path(f"/account/applications/{target.application_id}")
    has_interviews = desktop_page.get_by_role("heading", name="Interviews").count() > 0
    has_response = desktop_page.get_by_role("button", name="Submit response").count() > 0
    has_correction = desktop_page.get_by_role("heading", name="Correction requests").count() > 0
    rel_c = _shot(desktop_page, evidence, "candidate-controls.png")

    record_stage11(
        "STG11-MISS-004",
        feature_ids=["CAN-07"],
        route=f"/account/applications/{target.application_id}",
        expected="Candidate interview section and response when pending",
        actual=f"interviews={has_interviews}; respond={has_response}",
        status="PASS" if has_interviews else "FAIL",
        evidence_rel=rel_c,
        notes="Response button only when an interview awaits candidate response",
    )
    record_stage11(
        "STG11-MISS-005",
        feature_ids=["CAN-07"],
        route=f"/account/applications/{target.application_id}",
        expected="Correction-request workflow UI",
        actual=f"correction_section={has_correction}",
        status="PASS" if has_correction else "FAIL",
        evidence_rel=rel_c,
    )


def test_stg11_self_service_end_to_end(
    desktop_page, base_url, qa_settings, stage11_run_id, record_stage11
) -> None:
    if qa_settings.ournear_allow_run != "local":
        pytest.skip("local only")
    _require_all_creds(qa_settings)
    from ournear_qa.journey.recruitment_self_service import run_self_service_workflow

    evidence = EVIDENCE / stage11_run_id / "STG11-SELF-SERVICE"
    evidence.mkdir(parents=True, exist_ok=True)
    try:
        report = run_self_service_workflow(
            desktop_page,
            base_url,
            candidate_email=qa_settings.qa_candidate_email,
            candidate_password=qa_settings.qa_candidate_password,
            employer_a_email=qa_settings.qa_employer_a_email,
            employer_a_password=qa_settings.qa_employer_a_password,
        )
    except JourneyStepError as err:
        skip_throttle(err, "STG11-SS")
        raise
    except Exception as err:
        rel = _shot(desktop_page, evidence, "failure.png")
        for case_id in ("STG11-MISS-001", "STG11-MISS-002", "STG11-MISS-003", "STG11-MISS-004", "STG11-MISS-005"):
            record_stage11(
                case_id,
                feature_ids=["EMP-03"],
                route="self-service-journey",
                expected="End-to-end self-service workflow",
                actual=str(err),
                status="FAIL",
                evidence_rel=rel,
            )
        raise

    rel = _shot(desktop_page, evidence, "complete.png")
    steps = report.steps
    mapping = {
        "STG11-MISS-001": "STG11-SS-shortlist" in steps,
        "STG11-MISS-002": "STG11-SS-interview" in steps,
        "STG11-MISS-003": "STG11-SS-emp-message" in steps and "STG11-SS-cand-message" in steps,
        "STG11-MISS-004": "STG11-SS-cand-response" in steps,
        "STG11-MISS-005": "STG11-SS-correction" in steps,
    }
    for case_id, ok in mapping.items():
        record_stage11(
            case_id,
            feature_ids=["EMP-03" if "001" in case_id or "002" in case_id or "003" in case_id else "CAN-07"],
            route=f"/account/applications/{report.application_id}",
            expected="Mutating self-service workflow (isolated QA job)",
            actual=str(steps),
            status="PASS" if ok else "FAIL",
            evidence_rel=rel,
            notes="End-to-end run on qa-fixture-published-a-01; does not overwrite prior MISSING registry semantics for old run",
        )
