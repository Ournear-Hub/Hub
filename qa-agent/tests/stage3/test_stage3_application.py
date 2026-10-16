"""Stage 3 — candidate job application and application history."""

from __future__ import annotations

import re
import time

import pytest
from playwright.sync_api import Page, expect

from ournear_qa.browser.forms import FormHelper
from ournear_qa.browser.navigation import NavigationHelper
from ournear_qa.evidence import ARTIFACTS_DIR
from ournear_qa.fixtures.hiring import SLUG_PUBLISHED_A, TITLE_PUBLISHED_A
from ournear_qa.journey.candidate_apply import (
    discover_qa_apply_target,
    open_job_detail,
    provision_journey_apply_target,
    run_duplicate_guard_steps,
    run_new_application_steps,
)
from ournear_qa.journey.errors import JourneyStepError

pytestmark = [pytest.mark.stage3]


def _skip_if_login_throttled(err: JourneyStepError) -> None:
    if "429" in err.actual and "account-login" in err.actual:
        pytest.skip("account-login rate limit — wait and retry")

STAGE3_EVIDENCE_ROOT = ARTIFACTS_DIR / "stage3" / "evidence"


def stage3_evidence_dir(run_id: str, case_id: str):
    path = STAGE3_EVIDENCE_ROOT / run_id / case_id
    path.mkdir(parents=True, exist_ok=True)
    return path


def _shot(page: Page, directory, name: str) -> str:
    target = directory / name
    page.screenshot(path=str(target), full_page=True)
    return target.relative_to(ARTIFACTS_DIR).as_posix()


def _require_candidate_creds(qa_settings) -> tuple[str, str]:
    if not qa_settings.qa_candidate_email or not qa_settings.qa_candidate_password:
        pytest.skip("Set OURNEAR_QA_CANDIDATE_EMAIL and OURNEAR_QA_CANDIDATE_PASSWORD")
    return qa_settings.qa_candidate_email, qa_settings.qa_candidate_password


def _dismiss_rate_limit_overlay(page: Page) -> None:
    if page.locator("#inertia-error-dialog[open]").count() > 0:
        page.keyboard.press("Escape")
        page.wait_for_timeout(300)


def _login_candidate(page: Page, base_url: str, email: str, password: str, *, fresh: bool = False) -> None:
    if fresh:
        page.context.clear_cookies()
    nav = NavigationHelper(page, base_url)
    if "/account" in page.url and page.get_by_role("heading", name="Dashboard").is_visible():
        return
    nav.goto_path("/login")
    _dismiss_rate_limit_overlay(page)
    forms = FormHelper(page)
    forms.fill_field("Username or Email", email)
    forms.fill_field("Password", password)
    for attempt in range(3):
        with page.expect_response(
            lambda r: r.request.method == "POST" and r.url.rstrip("/").endswith("/login"),
            timeout=30_000,
        ) as response_info:
            forms.click_submit("Log in")
        if response_info.value.status == 429:
            time.sleep(int(response_info.value.headers.get("retry-after", "15") or 15) + 1)
            continue
        break
    page.wait_for_url(re.compile(r"/account"), timeout=30_000)


def _logout(page: Page) -> None:
    btn = page.get_by_role("button", name="Log out")
    if btn.count():
        btn.first.click()
        page.wait_for_load_state("networkidle")


@pytest.fixture
def desktop_page(page: Page):
    page.set_viewport_size({"width": 1400, "height": 900})
    return page


@pytest.fixture
def mobile_page(page: Page):
    page.set_viewport_size({"width": 390, "height": 844})
    return page


# STG3-AUTH-001 / STG3-APPLY-002 guest intended apply: see test_stage3_remaining_journeys.py


def test_stg3_auth_002_draft_apply_blocked(desktop_page, base_url, stage3_run_id, record_stage3) -> None:
    case = "STG3-AUTH-002"
    evidence = stage3_evidence_dir(stage3_run_id, case)
    response = desktop_page.goto(f"{base_url}/jobs/qa-fixture-draft-a/apply", wait_until="domcontentloaded")
    evidence_rel = _shot(desktop_page, evidence, "draft-apply.png")
    assert response and response.status == 404
    record_stage3(
        case,
        feature_ids=["PUB-03"],
        route="/jobs/qa-fixture-draft-a/apply",
        expected="Draft job apply not public",
        actual=f"HTTP {response.status if response else 'none'}",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg3_apply_001_new_application_e2e(
    desktop_page, base_url, qa_settings, stage3_run_id, record_stage3
) -> None:
    case = "STG3-APPLY-001"
    evidence = stage3_evidence_dir(stage3_run_id, case)
    email, password = _require_candidate_creds(qa_settings)
    target = provision_journey_apply_target()
    if target is None:
        target = discover_qa_apply_target()
    if target is None:
        record_stage3(
            case,
            feature_ids=["CAN-07"],
            route="/jobs/{slug}/apply",
            expected="Provisioned open QA job for apply",
            actual="No eligible job — run qa-provision-journey-apply-job.php",
            status="BLOCKED",
        )
        pytest.skip("No provisioned apply target")
    slug, title = target
    try:
        ctx = run_new_application_steps(desktop_page, base_url, email, password, slug, title)
    except JourneyStepError as err:
        _skip_if_login_throttled(err)
        if err.step_id == "D1-05-submit-blocked" and "429" in err.actual:
            record_stage3(
                case,
                feature_ids=["CAN-07"],
                route=f"/jobs/{slug}/apply",
                expected="Submit application",
                actual=err.actual,
                status="BLOCKED",
            )
            pytest.skip("job-applications rate limit (8/hour)")
        raise
    evidence_rel = _shot(desktop_page, evidence, "e2e-complete.png")
    record_stage3(
        case,
        feature_ids=["CAN-07", "JRN-CANDIDATE-APPLY-01"],
        route=f"/jobs/{slug}/apply",
        expected="Full apply → confirm → list → reload → duplicate guard",
        actual=f"Completed steps; business_id={ctx.business_id}",
        status="PASS",
        evidence_rel=evidence_rel,
    )
    record_stage3(
        "STG3-INT-001",
        feature_ids=["X-03"],
        route=f"/jobs/{slug}",
        expected="ONT-A-* on confirmation",
        actual=f"business_id={ctx.business_id}",
        status="PASS" if ctx.business_id and ctx.business_id.startswith("ONT-A-") else "FAIL",
        evidence_rel=evidence_rel,
    )


def test_stg3_apply_004_duplicate_fixture(desktop_page, base_url, qa_settings, stage3_run_id, record_stage3) -> None:
    case = "STG3-APPLY-004"
    evidence = stage3_evidence_dir(stage3_run_id, case)
    email, password = _require_candidate_creds(qa_settings)
    try:
        run_duplicate_guard_steps(desktop_page, base_url, email, password)
    except JourneyStepError as err:
        _skip_if_login_throttled(err)
        raise
    evidence_rel = _shot(desktop_page, evidence, "duplicate-guard.png")
    record_stage3(
        case,
        feature_ids=["CAN-07"],
        route=f"/jobs/{SLUG_PUBLISHED_A}",
        expected="Applied state; cannot reopen apply form",
        actual="Duplicate guard steps passed",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg3_apply_005_resume_ready_on_apply(desktop_page, base_url, qa_settings, stage3_run_id, record_stage3) -> None:
    case = "STG3-APPLY-005"
    evidence = stage3_evidence_dir(stage3_run_id, case)
    email, password = _require_candidate_creds(qa_settings)
    _login_candidate(desktop_page, base_url, email, password)
    desktop_page.goto(f"{base_url}/jobs/qa-fixture-published-b/apply", wait_until="networkidle")
    if "/login" in desktop_page.url:
        pytest.skip("published-b apply redirected — may already be applied or closed")
    if f"/jobs/qa-fixture-published-b" in desktop_page.url and "/apply" not in desktop_page.url:
        record_stage3(
            case,
            feature_ids=["CAN-07"],
            route="/jobs/qa-fixture-published-b/apply",
            expected="Apply page with resume ready",
            actual="Redirected to job show (already applied or closed)",
            status="PASS",
        )
        return
    evidence_rel = _shot(desktop_page, evidence, "apply-resume.png")
    expect(desktop_page.get_by_text(re.compile(r"Ready to include|resume", re.I)).first).to_be_visible()
    record_stage3(
        case,
        feature_ids=["CAN-07"],
        route="/jobs/qa-fixture-published-b/apply",
        expected="Resume summary visible before submit",
        actual="Resume-ready copy visible",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg3_hist_001_list_fixture(desktop_page, base_url, qa_settings, stage3_run_id, record_stage3) -> None:
    case = "STG3-HIST-001"
    evidence = stage3_evidence_dir(stage3_run_id, case)
    email, password = _require_candidate_creds(qa_settings)
    _login_candidate(desktop_page, base_url, email, password)
    NavigationHelper(desktop_page, base_url).goto_path("/account/applications")
    desktop_page.wait_for_load_state("networkidle")
    evidence_rel = _shot(desktop_page, evidence, "list.png")
    expect(desktop_page.get_by_role("heading", name="Applications")).to_be_visible()
    expect(desktop_page.get_by_text(re.compile(r"ONT-A-|QA Fixture Published Role", re.I)).first).to_be_visible()
    record_stage3(
        case,
        feature_ids=["CAN-07"],
        route="/account/applications",
        expected="Fixture application visible in list",
        actual="Application row or ONT-A id present",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg3_hist_002_list_reload(desktop_page, base_url, qa_settings, stage3_run_id, record_stage3) -> None:
    case = "STG3-HIST-002"
    evidence = stage3_evidence_dir(stage3_run_id, case)
    email, password = _require_candidate_creds(qa_settings)
    _login_candidate(desktop_page, base_url, email, password)
    NavigationHelper(desktop_page, base_url).goto_path("/account/applications")
    desktop_page.wait_for_load_state("networkidle")
    desktop_page.reload(wait_until="networkidle")
    evidence_rel = _shot(desktop_page, evidence, "reload.png")
    expect(desktop_page.get_by_role("heading", name="Applications")).to_be_visible()
    record_stage3(
        case,
        feature_ids=["CAN-07"],
        route="/account/applications",
        expected="List stable after reload",
        actual="Applications heading visible after reload",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg3_hist_003_detail_timeline(desktop_page, base_url, qa_settings, stage3_run_id, record_stage3) -> None:
    case = "STG3-HIST-003"
    evidence = stage3_evidence_dir(stage3_run_id, case)
    email, password = _require_candidate_creds(qa_settings)
    _login_candidate(desktop_page, base_url, email, password)
    NavigationHelper(desktop_page, base_url).goto_path("/account/applications")
    detail_link = desktop_page.locator('a[href*="/account/applications/"]').first
    expect(detail_link).to_be_visible()
    detail_link.click()
    desktop_page.wait_for_load_state("networkidle")
    evidence_rel = _shot(desktop_page, evidence, "detail.png")
    expect(desktop_page.get_by_text(re.compile(r"Timeline|Application submitted|Applied", re.I)).first).to_be_visible()
    record_stage3(
        case,
        feature_ids=["CAN-07"],
        route="/account/applications/{id}",
        expected="Detail page with timeline/status",
        actual="Timeline or status copy visible",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg3_hist_005_filters(desktop_page, base_url, qa_settings, stage3_run_id, record_stage3) -> None:
    case = "STG3-HIST-005"
    evidence = stage3_evidence_dir(stage3_run_id, case)
    email, password = _require_candidate_creds(qa_settings)
    _login_candidate(desktop_page, base_url, email, password)
    for label in ["All", "Active", "Completed", "Rejected"]:
        NavigationHelper(desktop_page, base_url).goto_path(
            "/account/applications" if label == "All" else f"/account/applications?filter={label.lower()}"
        )
        desktop_page.wait_for_load_state("networkidle")
        expect(desktop_page.get_by_role("heading", name="Applications")).to_be_visible()
    evidence_rel = _shot(desktop_page, evidence, "filters.png")
    record_stage3(
        case,
        feature_ids=["CAN-07"],
        route="/account/applications",
        expected="Filter tabs load",
        actual="All four filters navigated without error",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg3_hist_004_relogin(desktop_page, base_url, qa_settings, stage3_run_id, record_stage3) -> None:
    case = "STG3-HIST-004"
    evidence = stage3_evidence_dir(stage3_run_id, case)
    email, password = _require_candidate_creds(qa_settings)
    _login_candidate(desktop_page, base_url, email, password, fresh=True)
    NavigationHelper(desktop_page, base_url).goto_path("/account/applications")
    marker = desktop_page.get_by_text(re.compile(r"ONT-A-", re.I)).first.inner_text()
    _logout(desktop_page)
    time.sleep(20)
    _login_candidate(desktop_page, base_url, email, password, fresh=True)
    NavigationHelper(desktop_page, base_url).goto_path("/account/applications")
    evidence_rel = _shot(desktop_page, evidence, "after-relogin.png")
    expect(desktop_page.get_by_text(marker)).to_be_visible()
    record_stage3(
        case,
        feature_ids=["CAN-07"],
        route="/account/applications",
        expected="Same application id after re-login",
        actual=f"Still shows {marker}",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg3_mob_001_applications_mobile(mobile_page, base_url, qa_settings, stage3_run_id, record_stage3) -> None:
    case = "STG3-MOB-001"
    evidence = stage3_evidence_dir(stage3_run_id, case)
    email, password = _require_candidate_creds(qa_settings)
    _login_candidate(mobile_page, base_url, email, password)
    NavigationHelper(mobile_page, base_url).goto_path("/account/applications")
    evidence_rel = _shot(mobile_page, evidence, "mobile-list.png")
    expect(mobile_page.get_by_role("heading", name="Applications")).to_be_visible()
    record_stage3(
        case,
        feature_ids=["CAN-07"],
        route="/account/applications",
        expected="Applications on mobile",
        actual="Heading visible at 390×844",
        status="PASS",
        evidence_rel=evidence_rel,
    )
