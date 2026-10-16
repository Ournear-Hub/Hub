"""Stage 10 — security, negative cases, recovery (browser)."""

from __future__ import annotations

import re

import pytest
from playwright.sync_api import Page, expect

from ournear_qa.browser.navigation import NavigationHelper
from ournear_qa.evidence import ARTIFACTS_DIR
from ournear_qa.journey.admin_application import assert_role_cannot_access_admin_applications
from ournear_qa.journey.decision_communication import DEFAULT_BUSINESS_ID, resolve_stage7_target
from ournear_qa.journey.employer_resume import (
    EmployerJourneyTarget,
    assert_http_forbidden,
    login_employer,
    run_employer_b_isolation_steps,
)
from ournear_qa.journey.errors import JourneyStepError
from ournear_qa.journey.account_auth import login_account_user

pytestmark = [pytest.mark.stage10]

EVIDENCE = ARTIFACTS_DIR / "stage10" / "evidence"


def skip_throttle(err: JourneyStepError, label: str) -> None:
    if "429" in err.actual and "account-login" in err.actual:
        pytest.skip(f"{label}: account-login rate limit — wait cooldown")


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


def _logout_via_nav(page: Page) -> None:
    logout = page.get_by_role("button", name="Log out")
    if logout.count() == 0:
        logout = page.get_by_role("link", name="Log out")
    logout.first.click()
    page.wait_for_load_state("networkidle")


def test_stg10_guest_protected_routes(
    desktop_page, base_url, stage10_run_id, record_stage10
) -> None:
    evidence = EVIDENCE / stage10_run_id / "STG10-GUEST"
    evidence.mkdir(parents=True, exist_ok=True)
    desktop_page.context.clear_cookies()

    checks = [
        ("STG10-AUTH-001", "/account", "/login"),
        ("STG10-AUTH-002", "/admin", "/admin/login"),
        ("STG10-AUTH-003", "/account/hiring", "/login"),
    ]
    for case_id, path, expect_fragment in checks:
        desktop_page.goto(f"{base_url.rstrip('/')}{path}", wait_until="networkidle")
        rel = _shot(desktop_page, evidence, f"{case_id}.png")
        ok = expect_fragment in desktop_page.url
        record_stage10(
            case_id,
            feature_ids=["X-01"],
            route=path,
            expected=f"Redirect guest to {expect_fragment}",
            actual=desktop_page.url,
            status="PASS" if ok else "FAIL",
            evidence_rel=rel,
        )
        assert ok, f"{case_id} url={desktop_page.url}"


def test_stg10_logout_blocks_profile(
    desktop_page, base_url, qa_settings, stage10_run_id, record_stage10
) -> None:
    if not qa_settings.qa_candidate_email or not qa_settings.qa_candidate_password:
        pytest.skip("Candidate creds required")
    evidence = EVIDENCE / stage10_run_id / "STG10-AUTH-004"
    evidence.mkdir(parents=True, exist_ok=True)
    try:
        login_account_user(
            desktop_page,
            base_url,
            qa_settings.qa_candidate_email,
            qa_settings.qa_candidate_password,
            journey_id="STG10",
            step_id="login",
        )
    except JourneyStepError as err:
        skip_throttle(err, "STG10")
        raise
    _logout_via_nav(desktop_page)
    desktop_page.goto(f"{base_url}/account/profile", wait_until="networkidle")
    rel = _shot(desktop_page, evidence, "after-logout.png")
    ok = "/login" in desktop_page.url
    record_stage10(
        "STG10-AUTH-004",
        feature_ids=["ACC-AUTH-01"],
        route="/account/profile",
        expected="Logout clears session; profile requires login",
        actual=desktop_page.url,
        status="PASS" if ok else "FAIL",
        evidence_rel=rel,
    )
    assert ok


def test_stg10_account_users_denied_admin(
    desktop_page, base_url, qa_settings, stage10_run_id, record_stage10
) -> None:
    target = _target()
    evidence = EVIDENCE / stage10_run_id / "STG10-ADMIN-DENY"
    evidence.mkdir(parents=True, exist_ok=True)

    pairs = [
        ("STG10-AUTH-005", qa_settings.qa_candidate_email, qa_settings.qa_candidate_password),
        ("STG10-AUTH-006", qa_settings.qa_employer_a_email, qa_settings.qa_employer_a_password),
    ]
    for case_id, email, password in pairs:
        if not email or not password:
            record_stage10(
                case_id,
                feature_ids=["X-03"],
                route="/admin/applications",
                expected="Account role denied admin workspace",
                actual="credentials missing",
                status="NOT_TESTED",
            )
            continue
        desktop_page.context.clear_cookies()
        try:
            assert_role_cannot_access_admin_applications(
                desktop_page,
                base_url,
                email,
                password,
                application_id=target.application_id,
                business_id=target.business_id,
            )
        except JourneyStepError as err:
            skip_throttle(err, "STG10")
            raise
        rel = _shot(desktop_page, evidence, f"{case_id}.png")
        record_stage10(
            case_id,
            feature_ids=["X-03"],
            route="/admin/applications",
            expected="No admin application leak for account member",
            actual="admin access denied",
            status="PASS",
            evidence_rel=rel,
        )


def test_stg10_employer_b_isolation(
    desktop_page, base_url, qa_settings, stage10_run_id, record_stage10
) -> None:
    target = _target()
    email = qa_settings.qa_employer_b_email or "qa-employer-b@example.test"
    password = qa_settings.qa_employer_b_password or qa_settings.qa_employer_a_password
    if not password:
        pytest.skip("Employer B password required")
    evidence = EVIDENCE / stage10_run_id / "STG10-EMP-B"
    evidence.mkdir(parents=True, exist_ok=True)
    try:
        run_employer_b_isolation_steps(desktop_page, base_url, email, password, target)
    except JourneyStepError as err:
        skip_throttle(err, "STG10")
        raise
    rel = _shot(desktop_page, evidence, "isolation.png")
    for case_id, route in (
        ("STG10-AUTH-007", "/account/hiring/applications"),
        ("STG10-AUTH-008", f"/account/jobs/{target.job_slug}/applications/{target.application_id}"),
        ("STG10-AUTH-009", f"/account/employer-applications/{target.application_id}/resume"),
    ):
        record_stage10(
            case_id,
            feature_ids=["EMP-09"],
            route=route,
            expected="Employer B cannot access Employer A resources",
            actual="isolation journey passed",
            status="PASS",
            evidence_rel=rel,
        )


def test_stg10_guest_resume_forbidden(
    desktop_page, base_url, stage10_run_id, record_stage10
) -> None:
    target = _target()
    evidence = EVIDENCE / stage10_run_id / "STG10-GUEST-RESUME"
    evidence.mkdir(parents=True, exist_ok=True)
    desktop_page.context.clear_cookies()
    path = f"/account/employer-applications/{target.application_id}/resume"
    response = desktop_page.goto(f"{base_url.rstrip('/')}{path}", wait_until="domcontentloaded")
    status = response.status if response else 0
    body = desktop_page.locator("body").inner_text()
    rel = _shot(desktop_page, evidence, "guest-resume.png")
    denied = (
        status == 403
        or "/login" in desktop_page.url
        or re.search(r"\b403\b|Forbidden|not authorized", body, re.I)
    )
    leak = target.business_id in body and "/login" not in desktop_page.url
    record_stage10(
        "STG10-AUTH-010",
        feature_ids=["X-03"],
        route=path,
        expected="Guest cannot access private resume (403 or login redirect)",
        actual=f"status={status} url={desktop_page.url}",
        status="FAIL" if leak or not denied else "PASS",
        evidence_rel=rel,
    )
    assert denied and not leak


def test_stg10_non_owner_application_forbidden(
    desktop_page, base_url, qa_settings, stage10_run_id, record_stage10
) -> None:
    target = _target()
    email = qa_settings.qa_employer_b_email or "qa-employer-b@example.test"
    password = qa_settings.qa_employer_b_password or qa_settings.qa_employer_a_password
    if not password:
        pytest.skip("Employer B password required")
    evidence = EVIDENCE / stage10_run_id / "STG10-AUTH-011"
    evidence.mkdir(parents=True, exist_ok=True)
    try:
        login_account_user(desktop_page, base_url, email, password, journey_id="STG10", step_id="login-b")
    except JourneyStepError as err:
        skip_throttle(err, "STG10")
        raise
    path = f"/account/applications/{target.application_id}"
    assert_http_forbidden(desktop_page, base_url, path, forbidden_token=target.business_id)
    rel = _shot(desktop_page, evidence, "non-owner-app.png")
    record_stage10(
        "STG10-AUTH-011",
        feature_ids=["X-03"],
        route=path,
        expected="Non-owner account member forbidden",
        actual="403/forbidden",
        status="PASS",
        evidence_rel=rel,
    )


def test_stg10_known_employer_pipeline_missing(
    desktop_page, base_url, qa_settings, stage10_run_id, record_stage10
) -> None:
    target = _target()
    if not qa_settings.qa_employer_a_email or not qa_settings.qa_employer_a_password:
        pytest.skip("Employer A creds required")
    evidence = EVIDENCE / stage10_run_id / "STG10-KNOWN"
    evidence.mkdir(parents=True, exist_ok=True)
    try:
        login_employer(desktop_page, base_url, qa_settings.qa_employer_a_email, qa_settings.qa_employer_a_password)
    except JourneyStepError as err:
        skip_throttle(err, "STG10")
        raise
    NavigationHelper(desktop_page, base_url).goto_path(
        f"/account/jobs/{target.job_slug}/applications/{target.application_id}"
    )
    expect(desktop_page.get_by_role("heading", name="Application actions")).to_be_visible(timeout=15_000)
    has_pipeline = desktop_page.get_by_role("button", name=re.compile(r"Shortlist|Reject|Interview|Schedule", re.I)).count() > 0
    rel = _shot(desktop_page, evidence, "read-only.png")
    record_stage10(
        "STG10-KNOWN-001",
        feature_ids=["EMP-03"],
        route=f"/account/jobs/{target.job_slug}/applications/{target.application_id}",
        expected="Employer pipeline self-service (known gap)",
        actual="MISSING — read-only Application actions" if not has_pipeline else "Unexpected action buttons",
        status="MISSING" if not has_pipeline else "FAIL",
        evidence_rel=rel,
    )
    if has_pipeline:
        pytest.fail("Employer pipeline buttons unexpectedly present")
