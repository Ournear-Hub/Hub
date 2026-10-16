"""Stage 4 — employer registration, profile, dashboard, job lifecycle."""

from __future__ import annotations

import re
import time
import uuid
from typing import Any

import pytest
from playwright.sync_api import Page, expect

from ournear_qa.browser.forms import FormHelper
from ournear_qa.browser.navigation import NavigationHelper
from ournear_qa.evidence import ARTIFACTS_DIR
from ournear_qa.journey.employer_job_publish import (
    discover_job_form_defaults,
    journey_job_title,
    run_employer_job_publish_lifecycle,
    step_candidate_cannot_manage_jobs,
    step_employer_b_cannot_edit,
    EmployerJobPublishContext,
)
from ournear_qa.journey.errors import JourneyStepError

pytestmark = [pytest.mark.stage4]

STAGE4_EVIDENCE_ROOT = ARTIFACTS_DIR / "stage4" / "evidence"
FIXTURE_PASSWORD = "Password1!"
_LIFECYCLE_CTX: dict[str, Any] = {}

STEP_TO_CASE: dict[str, tuple[str, str]] = {
    "P0-03-create-validation-422": ("STG4-JOB-001", "/account/jobs/create"),
    "P0-03-create-validation": ("STG4-JOB-001", "/account/jobs/create"),
    "P0-04-save-draft": ("STG4-JOB-002", "/account/jobs/create"),
    "P0-05-draft-persisted": ("STG4-JOB-003", "/account/jobs/{slug}/edit"),
    "P0-06-published": ("STG4-JOB-004", "/account/jobs"),
    "P0-08-public-listing": ("STG4-JOB-005", "/jobs/{slug}"),
    "P0-08d-paused-not-public": ("STG4-JOB-006", "/account/jobs"),
    "P0-08e-republished": ("STG4-JOB-007", "/account/jobs"),
    "P0-09-edit-persisted": ("STG4-JOB-008", "/account/jobs/{slug}/edit"),
    "P0-11b-absent-from-listing": ("STG4-JOB-009", "/account/jobs"),
}


@pytest.fixture
def desktop_page(page: Page):
    page.set_viewport_size({"width": 1400, "height": 900})
    return page


def stage4_evidence_dir(run_id: str, case_id: str):
    path = STAGE4_EVIDENCE_ROOT / run_id / case_id
    path.mkdir(parents=True, exist_ok=True)
    return path


def _shot(page: Page, directory, name: str) -> str:
    target = directory / name
    page.screenshot(path=str(target), full_page=True)
    return target.relative_to(ARTIFACTS_DIR).as_posix()


def _employer_creds(qa_settings) -> tuple[str, str]:
    if not qa_settings.qa_employer_a_email or not qa_settings.qa_employer_a_password:
        pytest.skip("Set OURNEAR_QA_EMPLOYER_A_EMAIL and OURNEAR_QA_EMPLOYER_A_PASSWORD")
    return qa_settings.qa_employer_a_email, qa_settings.qa_employer_a_password


def _dismiss_rate_limit_overlay(page: Page) -> None:
    if page.locator("#inertia-error-dialog[open]").count() > 0:
        page.keyboard.press("Escape")
        page.wait_for_timeout(300)


def _login_employer(
    page: Page,
    base_url: str,
    email: str,
    password: str,
    *,
    fresh: bool = False,
) -> NavigationHelper:
    if fresh:
        page.context.clear_cookies()
    nav = NavigationHelper(page, base_url)
    if "/account" in page.url and page.get_by_text(re.compile(r"Own jobs|Dashboard", re.I)).count():
        return nav
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
    return nav


def _logout(page: Page) -> None:
    btn = page.get_by_role("button", name="Log out")
    if btn.count():
        btn.first.click()
        page.wait_for_load_state("networkidle")


def _skip_if_login_throttled(err: JourneyStepError) -> None:
    if "429" in err.actual and "account-login" in err.actual:
        pytest.skip("account-login rate limit — wait and retry")


def test_stg4_auth_001_employer_login(desktop_page, base_url, qa_settings, stage4_run_id, record_stage4) -> None:
    case = "STG4-AUTH-001"
    evidence = stage4_evidence_dir(stage4_run_id, case)
    email, password = _employer_creds(qa_settings)
    _login_employer(desktop_page, base_url, email, password, fresh=True)
    evidence_rel = _shot(desktop_page, evidence, "dashboard.png")
    expect(desktop_page.get_by_text("Own jobs").first).to_be_visible(timeout=15_000)
    record_stage4(
        case,
        feature_ids=["ACC-AUTH-01", "EMP-02"],
        route="/login",
        expected="Employer login → dashboard with employer metrics",
        actual="Own jobs card visible on /account",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg4_auth_002_invalid_login(desktop_page, base_url, stage4_run_id, record_stage4) -> None:
    case = "STG4-AUTH-002"
    evidence = stage4_evidence_dir(stage4_run_id, case)
    desktop_page.context.clear_cookies()
    NavigationHelper(desktop_page, base_url).goto_path("/login")
    forms = FormHelper(desktop_page)
    forms.fill_field("Username or Email", "not-an-employer@example.test")
    forms.fill_field("Password", "WrongPassword9!")
    forms.click_submit("Log in")
    desktop_page.wait_for_load_state("networkidle")
    evidence_rel = _shot(desktop_page, evidence, "invalid-login.png")
    assert "/login" in desktop_page.url
    record_stage4(
        case,
        feature_ids=["ACC-AUTH-01"],
        route="/login",
        expected="Invalid credentials stay on login",
        actual=f"URL {desktop_page.url}",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg4_auth_003_logout(desktop_page, base_url, qa_settings, stage4_run_id, record_stage4) -> None:
    case = "STG4-AUTH-003"
    evidence = stage4_evidence_dir(stage4_run_id, case)
    email, password = _employer_creds(qa_settings)
    _login_employer(desktop_page, base_url, email, password)
    _logout(desktop_page)
    desktop_page.goto(f"{base_url}/account", wait_until="domcontentloaded")
    evidence_rel = _shot(desktop_page, evidence, "after-logout.png")
    assert "/login" in desktop_page.url
    record_stage4(
        case,
        feature_ids=["ACC-AUTH-01"],
        route="/account",
        expected="Logout clears session",
        actual="Guest redirected to login from /account",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg4_auth_004_guest_account(desktop_page, base_url, stage4_run_id, record_stage4) -> None:
    case = "STG4-AUTH-004"
    evidence = stage4_evidence_dir(stage4_run_id, case)
    desktop_page.context.clear_cookies()
    desktop_page.goto(f"{base_url}/account", wait_until="domcontentloaded")
    evidence_rel = _shot(desktop_page, evidence, "guest-account.png")
    assert "/login" in desktop_page.url
    record_stage4(
        case,
        feature_ids=["X-01"],
        route="/account",
        expected="Guest cannot access account",
        actual=f"Redirected to {desktop_page.url}",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg4_auth_005_register_duplicate(desktop_page, base_url, qa_settings, stage4_run_id, record_stage4) -> None:
    case = "STG4-AUTH-005"
    evidence = stage4_evidence_dir(stage4_run_id, case)
    email, _ = _employer_creds(qa_settings)
    NavigationHelper(desktop_page, base_url).goto_path("/register")
    desktop_page.get_by_label("Employer", exact=True).click()
    desktop_page.locator("#name").fill("Dup Employer")
    desktop_page.locator("#username").fill(f"dup{int(time.time())}"[:20])
    desktop_page.locator("#email").fill(email)
    desktop_page.locator("#password").fill(FIXTURE_PASSWORD)
    desktop_page.locator("#password_confirmation").fill(FIXTURE_PASSWORD)
    desktop_page.locator('form input[type="checkbox"]').check()
    desktop_page.get_by_role("button", name="Create account").click()
    desktop_page.wait_for_load_state("networkidle")
    evidence_rel = _shot(desktop_page, evidence, "duplicate-email.png")
    assert "/register" in desktop_page.url or "already" in desktop_page.content().lower()
    record_stage4(
        case,
        feature_ids=["ACC-AUTH-01"],
        route="/register",
        expected="Duplicate employer email rejected",
        actual="Stayed on register or showed duplicate error",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg4_auth_006_register_employer_success(desktop_page, base_url, stage4_run_id, record_stage4) -> None:
    case = "STG4-AUTH-006"
    evidence = stage4_evidence_dir(stage4_run_id, case)
    token = uuid.uuid4().hex[:12]
    username = f"e4{token}"[:20]
    email = f"e4.{token}@example.test"
    NavigationHelper(desktop_page, base_url).goto_path("/register")
    desktop_page.get_by_label("Employer", exact=True).click()
    desktop_page.locator("#name").fill("Stage Four Employer Co")
    desktop_page.locator("#username").fill(username)
    expect(desktop_page.get_by_text("Username available")).to_be_visible(timeout=15_000)
    desktop_page.locator("#email").fill(email)
    desktop_page.locator("#password").fill(FIXTURE_PASSWORD)
    desktop_page.locator("#password_confirmation").fill(FIXTURE_PASSWORD)
    desktop_page.locator('form input[type="checkbox"]').check()
    desktop_page.get_by_role("button", name="Create account").click()
    desktop_page.wait_for_url(re.compile(r"/login"), timeout=60_000)
    expect(desktop_page.get_by_text(re.compile(r"Registration successful", re.I))).to_be_visible()
    _login_employer(desktop_page, base_url, username, FIXTURE_PASSWORD, fresh=True)
    evidence_rel = _shot(desktop_page, evidence, "employer-registered.png")
    expect(desktop_page.get_by_text(re.compile(r"Own jobs|Complete company profile", re.I)).first).to_be_visible(
        timeout=15_000
    )
    record_stage4(
        case,
        feature_ids=["ACC-AUTH-01"],
        route="/register",
        expected="Employer registration → login → employer dashboard shell",
        actual=f"Registered {email}; username login OK",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg4_prof_001_company_name_persist(
    desktop_page, base_url, qa_settings, stage4_run_id, record_stage4
) -> None:
    case = "STG4-PROF-001"
    evidence = stage4_evidence_dir(stage4_run_id, case)
    email, password = _employer_creds(qa_settings)
    _login_employer(desktop_page, base_url, email, password, fresh=True)
    marker = f"QA Co {stage4_run_id[:8]}"
    NavigationHelper(desktop_page, base_url).goto_path("/account/profile")
    expect(desktop_page.get_by_role("heading", name=re.compile(r"Company Profile|Profile", re.I)).first).to_be_visible(
        timeout=15_000
    )
    edit_btn = desktop_page.get_by_role("button", name="Edit company profile")
    edit_btn.first.click()
    company_field = desktop_page.get_by_label("Company name", exact=True)
    company_field.wait_for(state="visible", timeout=15_000)
    company_field.fill(marker)
    desktop_page.get_by_role("button", name="Save", exact=True).click()
    desktop_page.wait_for_load_state("networkidle")
    _shot(desktop_page, evidence, "saved.png")
    desktop_page.reload(wait_until="domcontentloaded")
    desktop_page.get_by_role("button", name="Edit company profile").first.click()
    expect(desktop_page.get_by_label("Company name", exact=True)).to_have_value(marker, timeout=15_000)
    evidence_rel = _shot(desktop_page, evidence, "after-reload.png")
    record_stage4(
        case,
        feature_ids=["EMP-07"],
        route="/account/profile",
        expected="Company name persists after reload",
        actual=f"company_name={marker!r}",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg4_dash_001_dashboard_cards(desktop_page, base_url, qa_settings, stage4_run_id, record_stage4) -> None:
    case = "STG4-DASH-001"
    evidence = stage4_evidence_dir(stage4_run_id, case)
    email, password = _employer_creds(qa_settings)
    _login_employer(desktop_page, base_url, email, password)
    for label in ("Own jobs", "Applicants on your jobs", "Submitted candidates", "Own enquiries"):
        expect(desktop_page.get_by_text(label).first).to_be_visible(timeout=15_000)
    desktop_page.get_by_text("Own jobs").first.click()
    desktop_page.wait_for_url(re.compile(r"/account/jobs"), timeout=30_000)
    evidence_rel = _shot(desktop_page, evidence, "jobs-from-dashboard.png")
    record_stage4(
        case,
        feature_ids=["EMP-01", "EMP-02"],
        route="/account",
        expected="Dashboard cards visible; jobs link navigates",
        actual="All four metric labels visible; Own jobs → /account/jobs",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg4_dash_002_hiring_command_center(
    desktop_page, base_url, qa_settings, stage4_run_id, record_stage4
) -> None:
    case = "STG4-DASH-002"
    evidence = stage4_evidence_dir(stage4_run_id, case)
    email, password = _employer_creds(qa_settings)
    _login_employer(desktop_page, base_url, email, password)
    NavigationHelper(desktop_page, base_url).goto_path("/account/hiring")
    NavigationHelper(desktop_page, base_url).assert_heading("Hiring Command Center")
    evidence_rel = _shot(desktop_page, evidence, "hiring-center.png")
    record_stage4(
        case,
        feature_ids=["EMP-01"],
        route="/account/hiring",
        expected="Hiring Command Center loads for employer A",
        actual="Heading visible",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg4_job_lifecycle_and_record_cases(
    desktop_page, base_url, qa_settings, stage4_run_id, record_stage4
) -> None:
    evidence = stage4_evidence_dir(stage4_run_id, "STG4-JOB-LIFE")
    email, password = _employer_creds(qa_settings)
    defaults = discover_job_form_defaults()
    if defaults is None:
        for case_id in (
            "STG4-JOB-001",
            "STG4-JOB-002",
            "STG4-JOB-003",
            "STG4-JOB-004",
            "STG4-JOB-005",
            "STG4-JOB-006",
            "STG4-JOB-007",
            "STG4-JOB-008",
            "STG4-JOB-009",
        ):
            record_stage4(
                case_id,
                feature_ids=["EMP-02"],
                route="/account/jobs",
                expected="Job lifecycle browser proof",
                actual="Blocked — run qa:hiring-fixtures and inspect-qa-employer-job-form-defaults.php",
                status="BLOCKED",
            )
        pytest.skip("Job form defaults unavailable")

    title = journey_job_title("stg4")
    try:
        ctx = run_employer_job_publish_lifecycle(
            desktop_page, base_url, email, password, defaults, title=title
        )
    except JourneyStepError as err:
        _skip_if_login_throttled(err)
        raise

    _LIFECYCLE_CTX["job_slug"] = ctx.job_slug
    _LIFECYCLE_CTX["job_title"] = ctx.job_title
    evidence_rel = _shot(desktop_page, evidence, "lifecycle-complete.png")

    recorded: set[str] = set()
    for step in ctx.steps_completed:
        if step not in STEP_TO_CASE:
            continue
        case_id, route_tpl = STEP_TO_CASE[step]
        if case_id in recorded:
            continue
        recorded.add(case_id)
        route = route_tpl.replace("{slug}", ctx.job_slug or "")
        record_stage4(
            case_id,
            feature_ids=["EMP-02", "JRN-EMPLOYER-JOB-PUBLISH-01"],
            route=route,
            expected=f"Journey step {step} completed",
            actual=f"steps include {step}; slug={ctx.job_slug}",
            status="PASS",
            evidence_rel=evidence_rel,
        )

    for cid, _route in STEP_TO_CASE.values():
        if cid not in recorded:
            record_stage4(
                cid,
                feature_ids=["EMP-02"],
                route="/account/jobs",
                expected="Lifecycle step recorded",
                actual=f"Missing step mapping; completed={ctx.steps_completed}",
                status="FAIL",
            )


def test_stg4_iso_001_employer_b_forbidden(
    desktop_page, base_url, qa_settings, stage4_run_id, record_stage4
) -> None:
    case = "STG4-ISO-001"
    evidence = stage4_evidence_dir(stage4_run_id, case)
    slug = _LIFECYCLE_CTX.get("job_slug")
    if not slug:
        record_stage4(
            case,
            feature_ids=["EMP-09"],
            route="/account/jobs/{slug}/edit",
            expected="Employer B forbidden on A job edit URL",
            actual="Blocked — lifecycle test did not run",
            status="BLOCKED",
        )
        pytest.skip("Depends on lifecycle job slug")

    employer_b_email = qa_settings.qa_employer_b_email or "qa-employer-b@example.test"
    employer_b_password = qa_settings.qa_employer_b_password or qa_settings.qa_employer_a_password
    if not employer_b_password:
        pytest.skip("Set OURNEAR_QA_EMPLOYER_B_PASSWORD")

    ctx = EmployerJobPublishContext(job_title="iso", job_slug=str(slug))
    try:
        step_employer_b_cannot_edit(
            desktop_page, base_url, employer_b_email, employer_b_password, ctx
        )
    except JourneyStepError as err:
        _skip_if_login_throttled(err)
        raise
    evidence_rel = _shot(desktop_page, evidence, "employer-b-403.png")
    record_stage4(
        case,
        feature_ids=["EMP-09", "X-03"],
        route=f"/account/jobs/{slug}/edit",
        expected="Employer B sees 403 on Employer A job edit",
        actual="403 heading visible",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg4_iso_002_candidate_forbidden(
    desktop_page, base_url, qa_settings, stage4_run_id, record_stage4
) -> None:
    case = "STG4-ISO-002"
    evidence = stage4_evidence_dir(stage4_run_id, case)
    if not qa_settings.qa_candidate_email or not qa_settings.qa_candidate_password:
        pytest.skip("Candidate credentials required")
    slug = _LIFECYCLE_CTX.get("job_slug")
    ctx = EmployerJobPublishContext(job_title="iso", job_slug=str(slug) if slug else None)
    try:
        step_candidate_cannot_manage_jobs(
            desktop_page,
            base_url,
            qa_settings.qa_candidate_email,
            qa_settings.qa_candidate_password,
            ctx,
        )
    except JourneyStepError as err:
        _skip_if_login_throttled(err)
        raise
    evidence_rel = _shot(desktop_page, evidence, "candidate-403.png")
    record_stage4(
        case,
        feature_ids=["X-03"],
        route="/account/jobs/create",
        expected="Candidate cannot open employer job create/edit",
        actual="403 on create and edit URLs",
        status="PASS",
        evidence_rel=evidence_rel,
    )
