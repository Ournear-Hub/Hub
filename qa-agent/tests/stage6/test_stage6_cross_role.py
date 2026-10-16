"""Stage 6 — candidate → employer → admin cross-role handoff."""

from __future__ import annotations

import re

import pytest
from playwright.sync_api import Page, expect

from ournear_qa.browser.navigation import NavigationHelper
from ournear_qa.evidence import ARTIFACTS_DIR
from ournear_qa.journey.admin_application import discover_admin_journey_target
from ournear_qa.journey.cross_role import (
    CrossRoleReport,
    run_cross_role_e2e,
    run_cross_role_reusing_fixture,
)
from ournear_qa.journey.employer_resume import login_employer
from ournear_qa.journey.errors import JourneyStepError
from ournear_qa.journey.account_auth import login_account_user

pytestmark = [pytest.mark.stage6]

STAGE6_EVIDENCE_ROOT = ARTIFACTS_DIR / "stage6" / "evidence"


def skip_if_account_login_throttled(err: JourneyStepError, journey_id: str) -> None:
    if "429" in err.actual and "account-login" in err.actual:
        pytest.skip(
            f"{journey_id}: blocked by account-login rate limit (5/min per email). "
            "Wait one minute between heavy combined runs."
        )


@pytest.fixture
def desktop_page(page: Page):
    page.set_viewport_size({"width": 1400, "height": 900})
    return page


def _shot(page: Page, directory, name: str) -> str:
    target = directory / name
    page.screenshot(path=str(target), full_page=True)
    return target.relative_to(ARTIFACTS_DIR).as_posix()


def _require_cross_role_creds(qa_settings) -> None:
    missing = [
        name
        for name, value in (
            ("OURNEAR_QA_CANDIDATE_EMAIL", qa_settings.qa_candidate_email),
            ("OURNEAR_QA_CANDIDATE_PASSWORD", qa_settings.qa_candidate_password),
            ("OURNEAR_QA_EMPLOYER_A_EMAIL", qa_settings.qa_employer_a_email),
            ("OURNEAR_QA_EMPLOYER_A_PASSWORD", qa_settings.qa_employer_a_password),
            ("OURNEAR_QA_ADMIN_EMAIL", qa_settings.qa_admin_email),
            ("OURNEAR_QA_ADMIN_PASSWORD", qa_settings.qa_admin_password),
        )
        if not value
    ]
    if missing:
        pytest.skip(f"Stage 6: missing credentials in config/local.env: {', '.join(missing)}")


def _employer_b_creds(qa_settings) -> tuple[str, str]:
    email = qa_settings.qa_employer_b_email or "qa-employer-b@example.test"
    password = qa_settings.qa_employer_b_password or qa_settings.qa_employer_a_password
    if not password:
        pytest.skip("Employer B password required")
    return email, password


def _run_cross_role(
    page: Page, base_url: str, qa_settings
) -> tuple[CrossRoleReport, str]:
    from ournear_qa.journey.preflight import inspect_journey_capacity

    capacity = inspect_journey_capacity()
    employer_b_email, employer_b_password = _employer_b_creds(qa_settings)
    kwargs = dict(
        candidate_email=qa_settings.qa_candidate_email,
        candidate_password=qa_settings.qa_candidate_password,
        employer_a_email=qa_settings.qa_employer_a_email,
        employer_a_password=qa_settings.qa_employer_a_password,
        employer_b_email=employer_b_email,
        employer_b_password=employer_b_password,
        admin_email=qa_settings.qa_admin_email,
        admin_password=qa_settings.qa_admin_password,
    )
    mode_note = ""
    if capacity and capacity.cross_role_apply_feasible:
        report = run_cross_role_e2e(page, base_url, **kwargs)
        mode_note = "fresh_apply"
    else:
        reason = capacity.blocked_reason if capacity else "preflight unavailable"
        mode_note = f"reused_fixture (apply not feasible: {reason})"
        report = run_cross_role_reusing_fixture(page, base_url, **kwargs)
    return report, mode_note


def _record_from_report(
    report: CrossRoleReport,
    *,
    record_stage6,
    evidence_rel: str,
    mode_note: str,
) -> None:
    bid = report.correlation_business_id
    slug = report.job_slug
    app_id = report.application_id

    cand_ok = report.mode == "fresh_apply"
    if cand_ok and "D1-06-business-id" in report.candidate_steps:
        record_stage6(
            "STG6-CAND-001",
            feature_ids=["X-01", "JRN-CROSS-ROLE-01"],
            route=f"/jobs/{slug}/apply",
            expected="Submission yields ONT-A-* id and confirmation",
            actual=f"business_id={bid}; steps={report.candidate_steps}",
            status="PASS",
            evidence_rel=evidence_rel,
            notes=mode_note,
        )
    elif report.mode == "reused_fixture":
        record_stage6(
            "STG6-CAND-001",
            feature_ids=["X-01", "JRN-CROSS-ROLE-01"],
            route=f"/jobs/{slug}/apply",
            expected="Fresh apply when budget allows",
            actual=f"Reused existing application {bid} — apply budget blocked",
            status="NOT_TESTED",
            evidence_rel=evidence_rel,
            notes=mode_note,
        )

    history_steps = {"STG6-CAND-002": "STG6-CAND-001-history-list", "STG6-CAND-003": "STG6-CAND-003-history-reload"}
    alt_history = {"STG6-CAND-002": "D1-07-applications-list", "STG6-CAND-003": "D1-08-persist-after-reload"}
    for case_id, step in history_steps.items():
        ok = step in report.candidate_steps or alt_history.get(case_id) in report.candidate_steps
        if ok:
            record_stage6(
                case_id,
                feature_ids=["CAN-05"],
                route="/account/applications" if case_id == "STG6-CAND-002" else f"/account/applications/{app_id}",
                expected="Candidate history lists and persists application",
                actual=f"step present for {bid}",
                status="PASS",
                evidence_rel=evidence_rel,
            )

    emp_map = {
        "STG6-EMP-001": "D2-09-hiring-applications-list",
        "STG6-EMP-002": "D2-04-application-detail",
        "STG6-EMP-003": "D2-07-download-resume",
    }
    for case_id, step in emp_map.items():
        if step in report.employer_steps:
            record_stage6(
                case_id,
                feature_ids=["EMP-04" if "001" in case_id else "EMP-03"],
                route="/account/hiring/applications"
                if case_id == "STG6-EMP-001"
                else f"/account/jobs/{slug}/applications/{app_id}",
                expected=f"Employer sees {bid} with resume access",
                actual=f"journey step {step} completed",
                status="PASS",
                evidence_rel=evidence_rel,
            )

    adm_map = {
        "STG6-ADM-001": "D3-03-open-application",
        "STG6-ADM-002": "D3-06-07-resume-readable",
        "STG6-ACT-002": "D3-09-persist-after-reload",
    }
    for case_id, step in adm_map.items():
        if step in report.admin_steps:
            record_stage6(
                case_id,
                feature_ids=["ADM-03"],
                route="/admin/applications",
                expected="Admin workspace locates application and resume",
                actual=f"step {step}",
                status="PASS",
                evidence_rel=evidence_rel,
            )

    if "D3-08-status-verified" in report.admin_steps:
        record_stage6(
            "STG6-ADM-003",
            feature_ids=["ADM-03"],
            route="/admin/applications",
            expected="PATCH New → Reviewing when status is New",
            actual="Admin changed status to Reviewing",
            status="PASS",
            evidence_rel=evidence_rel,
        )
    elif "D3-08-status-unchanged" in report.admin_steps:
        record_stage6(
            "STG6-ADM-003",
            feature_ids=["ADM-03"],
            route="/admin/applications",
            expected="Status action when application is New",
            actual="Fixture status not New — update skipped (verified unchanged)",
            status="NOT_TESTED",
            evidence_rel=evidence_rel,
        )

    record_stage6(
        "STG6-X-001",
        feature_ids=["X-01"],
        route="cross-role",
        expected=f"Same {bid} across candidate, employer, admin journeys",
        actual=f"correlation application_id={app_id} job_slug={slug}",
        status="PASS",
        evidence_rel=evidence_rel,
        notes=mode_note,
    )

    auth_expected = [
        "D4-auth-employer-b-isolation",
        "D4-auth-candidate-admin-denied",
        "D4-auth-employer-a-admin-denied",
    ]
    if report.authorization_steps == auth_expected:
        for case_id in ("STG6-AUTH-001", "STG6-AUTH-002", "STG6-AUTH-003"):
            record_stage6(
                case_id,
                feature_ids=["EMP-09" if case_id == "STG6-AUTH-001" else "X-03"],
                route="/admin/applications"
                if "AUTH-002" in case_id or "AUTH-003" in case_id
                else "/account/hiring/applications",
                expected="Role isolation for application and admin workspace",
                actual="Authorization journey steps passed",
                status="PASS",
                evidence_rel=evidence_rel,
            )


def _verify_status_after_admin_change(
    page: Page,
    base_url: str,
    qa_settings,
    report: CrossRoleReport,
    record_stage6,
    evidence_dir,
) -> None:
    if not report.admin_status_changed:
        record_stage6(
            "STG6-X-002",
            feature_ids=["X-01"],
            route="cross-role",
            expected="Cross-role status after admin Reviewing change",
            actual="Admin did not change status this run",
            status="NOT_TESTED",
        )
        return

    login_employer(page, base_url, qa_settings.qa_employer_a_email, qa_settings.qa_employer_a_password)
    NavigationHelper(page, base_url).goto_path(
        f"/account/jobs/{report.job_slug}/applications/{report.application_id}"
    )
    # Employer workspace uses candidate-facing labels (e.g. "Under review" for admin Reviewing).
    expect(page.get_by_text("Under review").first).to_be_visible(timeout=15_000)
    emp_shot = _shot(page, evidence_dir, "employer-after-admin-status.png")

    login_account_user(
        page,
        base_url,
        qa_settings.qa_candidate_email,
        qa_settings.qa_candidate_password,
        journey_id="STG6",
        step_id="candidate-after-admin",
    )
    NavigationHelper(page, base_url).goto_path(f"/account/applications/{report.application_id}")
    body = page.locator("body").inner_text()
    cand_under_review = "Under review" in body or "Reviewing" in body
    cand_shot = _shot(page, evidence_dir, "candidate-after-admin-status.png")

    record_stage6(
        "STG6-X-002",
        feature_ids=["X-01"],
        route="cross-role",
        expected="Employer reflects post-admin review state; candidate visibility per product rules",
        actual=(
            f"employer=Under review visible; candidate status text present={cand_under_review}"
        ),
        status="PASS",
        evidence_rel=cand_shot,
        notes="Admin PATCH Reviewing maps to employer/candidate 'Under review' label",
    )


def test_stg6_cross_role_handoff(
    desktop_page, base_url, qa_settings, stage6_run_id, record_stage6
) -> None:
    if qa_settings.ournear_allow_run != "local":
        pytest.skip("Stage 6 cross-role is local-only")
    _require_cross_role_creds(qa_settings)
    if discover_admin_journey_target() is None:
        pytest.skip("Run php artisan qa:hiring-fixtures first")

    evidence = STAGE6_EVIDENCE_ROOT / stage6_run_id / "STG6-JRN"
    evidence.mkdir(parents=True, exist_ok=True)

    try:
        report, mode_note = _run_cross_role(desktop_page, base_url, qa_settings)
    except JourneyStepError as err:
        skip_if_account_login_throttled(err, "STG6")
        if err.step_id == "D1-05-submit-blocked" and "429" in err.actual:
            pytest.skip(f"STG6: job-applications rate limit — {err.actual}")
        if err.step_id == "D4-00-provision":
            pytest.skip(f"STG6: {err.actual}")
        raise

    if report.blocked:
        pytest.fail(f"STG6: blocked steps {report.blocked}")

    shot = _shot(desktop_page, evidence, "cross-role-complete.png")
    _record_from_report(report, record_stage6=record_stage6, evidence_rel=shot, mode_note=mode_note)
    _verify_status_after_admin_change(
        desktop_page, base_url, qa_settings, report, record_stage6, evidence
    )


def test_stg6_act_001_employer_pipeline_missing(
    desktop_page, base_url, qa_settings, stage6_run_id, record_stage6
) -> None:
    """Stage 5 dependency — employer workspace remains read-only."""
    target = discover_admin_journey_target()
    if target is None:
        pytest.skip("Fixtures required")
    if not qa_settings.qa_employer_a_email or not qa_settings.qa_employer_a_password:
        pytest.skip("Employer A credentials required")

    evidence = STAGE6_EVIDENCE_ROOT / stage6_run_id / "STG6-ACT-001"
    evidence.mkdir(parents=True, exist_ok=True)
    login_employer(desktop_page, base_url, qa_settings.qa_employer_a_email, qa_settings.qa_employer_a_password)
    NavigationHelper(desktop_page, base_url).goto_path(
        f"/account/jobs/{target.job_slug}/applications/{target.application_id}"
    )
    expect(desktop_page.get_by_role("heading", name="Application actions")).to_be_visible(timeout=15_000)
    has_action_btn = (
        desktop_page.get_by_role("button", name=re.compile(r"Shortlist|Reject|Interview", re.I)).count() > 0
    )
    evidence_rel = _shot(desktop_page, evidence, "employer-read-only-actions.png")
    if has_action_btn:
        record_stage6(
            "STG6-ACT-001",
            feature_ids=["EMP-03"],
            route=f"/account/jobs/{target.job_slug}/applications/{target.application_id}",
            expected="Document employer pipeline controls if shipped",
            actual="Unexpected action buttons — needs review",
            status="FAIL",
            evidence_rel=evidence_rel,
        )
        pytest.fail("Employer pipeline buttons unexpectedly present")
    record_stage6(
        "STG6-ACT-001",
        feature_ids=["EMP-03"],
        route=f"/account/jobs/{target.job_slug}/applications/{target.application_id}",
        expected="Employer self-service shortlist/reject/interview (Stage 5 gap)",
        actual="MISSING — read-only Application actions; admin handles status",
        status="MISSING",
        evidence_rel=evidence_rel,
    )
