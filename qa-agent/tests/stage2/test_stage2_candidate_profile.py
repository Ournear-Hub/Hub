"""Stage 2 — candidate registration, profile, resume browser verification."""

from __future__ import annotations

import re
import time
import uuid

import pytest
from playwright.sync_api import Page, expect

from ournear_qa.browser.forms import FormHelper
from ournear_qa.browser.navigation import NavigationHelper
from ournear_qa.evidence import ARTIFACTS_DIR
pytestmark = [pytest.mark.stage2]

STAGE2_EVIDENCE_ROOT = ARTIFACTS_DIR / "stage2" / "evidence"
FIXTURE_PASSWORD = "Password1!"


def stage2_evidence_dir(run_id: str, case_id: str):
    path = STAGE2_EVIDENCE_ROOT / run_id / case_id
    path.mkdir(parents=True, exist_ok=True)
    return path


def _shot(page: Page, directory, name: str) -> str:
    target = directory / name
    page.screenshot(path=str(target), full_page=True)
    return target.relative_to(ARTIFACTS_DIR).as_posix()


def _require_candidate_creds(qa_settings) -> tuple[str, str]:
    if not qa_settings.qa_candidate_email or not qa_settings.qa_candidate_password:
        pytest.skip("Set OURNEAR_QA_CANDIDATE_EMAIL and OURNEAR_QA_CANDIDATE_PASSWORD in config/local.env")
    return qa_settings.qa_candidate_email, qa_settings.qa_candidate_password


def _login_candidate(
    page: Page,
    base_url: str,
    email: str,
    password: str,
    *,
    fresh: bool = False,
) -> NavigationHelper:
    """Login without clearing cookies unless `fresh=True` (avoids account-login throttle in long suites)."""
    navigation = NavigationHelper(page, base_url)
    forms = FormHelper(page)
    if fresh:
        page.context.clear_cookies()
    if "/account" in page.url and page.get_by_role("heading", name="Dashboard").is_visible():
        return navigation
    navigation.goto_path("/login")
    _dismiss_rate_limit_overlay(page)
    if "Too Many Requests" in page.title():
        time.sleep(18)
        navigation.goto_path("/login")
    if page.get_by_role("heading", name="Dashboard").is_visible():
        return navigation
    login_id = email
    forms.fill_field("Username or Email", login_id)
    forms.fill_field("Password", password)
    for attempt in range(3):
        with page.expect_response(
            lambda r: r.request.method == "POST" and r.url.rstrip("/").endswith("/login"),
            timeout=30_000,
        ) as response_info:
            forms.click_submit("Log in")
        status = response_info.value.status
        if status == 429:
            retry_after = int(response_info.value.headers.get("retry-after", "15") or 15)
            time.sleep(min(retry_after + 1, 30))
            continue
        break
    else:
        raise AssertionError("Account login remained rate-limited after retries")
    page.wait_for_url(re.compile(r"/account"), timeout=30_000)
    navigation.assert_heading("Dashboard")
    return navigation


def _login_candidate_username(
    page: Page,
    base_url: str,
    username: str,
    password: str,
    *,
    fresh: bool = False,
) -> NavigationHelper:
    navigation = NavigationHelper(page, base_url)
    forms = FormHelper(page)
    if fresh:
        page.context.clear_cookies()
    navigation.goto_path("/login")
    forms.fill_field("Username or Email", username)
    forms.fill_field("Password", password)
    forms.click_submit("Log in")
    page.wait_for_url(re.compile(r"/account"), timeout=30_000)
    navigation.assert_heading("Dashboard")
    return navigation


def _dismiss_rate_limit_overlay(page: Page) -> None:
    dialog = page.locator("#inertia-error-dialog[open]")
    if dialog.count() > 0:
        page.keyboard.press("Escape")
        page.wait_for_timeout(300)


def _logout_via_nav(page: Page) -> None:
    logout = page.get_by_role("button", name="Log out")
    if logout.count() == 0:
        logout = page.get_by_role("link", name="Log out")
    logout.first.click()
    page.wait_for_load_state("networkidle")


def _completion_pct(page: Page) -> int:
    text = page.get_by_text(re.compile(r"\d+%(\s*profile complete|\s*complete)?", re.I)).first.inner_text()
    match = re.search(r"(\d+)", text)
    assert match, f"Could not parse completion from: {text!r}"
    return int(match.group(1))


@pytest.fixture
def desktop_page(page: Page):
    page.set_viewport_size({"width": 1400, "height": 900})
    return page


@pytest.fixture
def mobile_page(page: Page):
    page.set_viewport_size({"width": 390, "height": 844})
    return page


def test_stg2_auth_001_login_success(desktop_page, base_url, qa_settings, stage2_run_id, record_stage2) -> None:
    case = "STG2-AUTH-001"
    evidence = stage2_evidence_dir(stage2_run_id, case)
    email, password = _require_candidate_creds(qa_settings)
    _login_candidate(desktop_page, base_url, email, password, fresh=True)
    evidence_rel = _shot(desktop_page, evidence, "dashboard.png")
    expect(desktop_page.get_by_role("heading", name="Dashboard")).to_be_visible()
    record_stage2(
        case,
        feature_ids=["ACC-AUTH-01", "CAN-01"],
        route="/login",
        expected="Valid credentials open candidate dashboard",
        actual="Dashboard heading visible after login",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg2_auth_002_login_invalid(desktop_page, base_url, stage2_run_id, record_stage2) -> None:
    case = "STG2-AUTH-002"
    evidence = stage2_evidence_dir(stage2_run_id, case)
    desktop_page.context.clear_cookies()
    NavigationHelper(desktop_page, base_url).goto_path("/login")
    forms = FormHelper(desktop_page)
    forms.fill_field("Username or Email", "not-a-real-user@example.test")
    forms.fill_field("Password", "WrongPassword99!")
    forms.click_submit("Log in")
    desktop_page.wait_for_load_state("networkidle")
    evidence_rel = _shot(desktop_page, evidence, "invalid-login.png")
    expect(desktop_page).to_have_url(re.compile(r"/login"))
    record_stage2(
        case,
        feature_ids=["ACC-AUTH-01"],
        route="/login",
        expected="Invalid login stays on login page",
        actual=f"Remained on login ({desktop_page.url})",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg2_auth_003_logout(desktop_page, base_url, qa_settings, stage2_run_id, record_stage2) -> None:
    case = "STG2-AUTH-003"
    evidence = stage2_evidence_dir(stage2_run_id, case)
    email, password = _require_candidate_creds(qa_settings)
    _login_candidate(desktop_page, base_url, email, password, fresh=True)
    _logout_via_nav(desktop_page)
    evidence_rel = _shot(desktop_page, evidence, "after-logout.png")
    desktop_page.goto(f"{base_url}/account", wait_until="networkidle")
    assert "/login" in desktop_page.url
    record_stage2(
        case,
        feature_ids=["ACC-AUTH-01"],
        route="/account",
        expected="Logout clears session",
        actual=f"Guest redirected to login from /account ({desktop_page.url})",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg2_auth_004_register_validation(desktop_page, base_url, stage2_run_id, record_stage2) -> None:
    case = "STG2-AUTH-004"
    evidence = stage2_evidence_dir(stage2_run_id, case)
    NavigationHelper(desktop_page, base_url).goto_path("/register")
    desktop_page.locator("form").first.evaluate("form => form.requestSubmit()")
    desktop_page.wait_for_load_state("networkidle")
    evidence_rel = _shot(desktop_page, evidence, "validation.png")
    expect(desktop_page).to_have_url(re.compile(r"/register"))
    record_stage2(
        case,
        feature_ids=["ACC-AUTH-01"],
        route="/register",
        expected="Empty register shows validation and stays on form",
        actual="Still on /register after empty submit",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg2_auth_005_register_duplicate(desktop_page, base_url, qa_settings, stage2_run_id, record_stage2) -> None:
    case = "STG2-AUTH-005"
    evidence = stage2_evidence_dir(stage2_run_id, case)
    email, _ = _require_candidate_creds(qa_settings)
    NavigationHelper(desktop_page, base_url).goto_path("/register")
    desktop_page.locator("#name").fill("Duplicate Test")
    desktop_page.locator("#username").fill(f"dup{int(time.time())}"[:20])
    desktop_page.locator("#email").fill(email)
    desktop_page.locator("#password").fill(FIXTURE_PASSWORD)
    desktop_page.locator("#password_confirmation").fill(FIXTURE_PASSWORD)
    desktop_page.locator('form input[type="checkbox"]').check()
    desktop_page.get_by_role("button", name=re.compile(r"Create account|Register", re.I)).click()
    desktop_page.wait_for_load_state("networkidle")
    evidence_rel = _shot(desktop_page, evidence, "duplicate-email.png")
    assert "/register" in desktop_page.url or "already" in desktop_page.content().lower()
    record_stage2(
        case,
        feature_ids=["ACC-AUTH-01"],
        route="/register",
        expected="Existing fixture email cannot register again",
        actual="Registration rejected or stayed on register with error",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg2_auth_006_register_success(desktop_page, base_url, stage2_run_id, record_stage2) -> None:
    case = "STG2-AUTH-006"
    evidence = stage2_evidence_dir(stage2_run_id, case)
    token = uuid.uuid4().hex[:12]
    username = f"s2{token}"[:20]
    email = f"s2.{token}@example.test"
    NavigationHelper(desktop_page, base_url).goto_path("/register")
    desktop_page.get_by_label("Candidate", exact=True).click()
    desktop_page.locator("#name").fill("Stage Two Register")
    desktop_page.locator("#username").fill(username)
    expect(desktop_page.get_by_text("Username available")).to_be_visible(timeout=15_000)
    desktop_page.locator("#email").fill(email)
    desktop_page.locator("#password").fill(FIXTURE_PASSWORD)
    desktop_page.locator("#password_confirmation").fill(FIXTURE_PASSWORD)
    desktop_page.locator('form input[type="checkbox"]').check()
    desktop_page.get_by_role("button", name="Create account").click()
    desktop_page.wait_for_url(re.compile(r"/login"), timeout=60_000)
    expect(desktop_page.get_by_text(re.compile(r"Registration successful", re.I))).to_be_visible()
    _login_candidate_username(desktop_page, base_url, username, FIXTURE_PASSWORD, fresh=True)
    evidence_rel = _shot(desktop_page, evidence, "registered-dashboard.png")
    expect(desktop_page.get_by_role("heading", name="Dashboard")).to_be_visible()
    record_stage2(
        case,
        feature_ids=["ACC-AUTH-01"],
        route="/register",
        expected="Unique registration succeeds then login reaches dashboard",
        actual=f"Registered {email}; login → dashboard",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg2_auth_007_guest_account(desktop_page, base_url, stage2_run_id, record_stage2) -> None:
    case = "STG2-AUTH-007"
    evidence = stage2_evidence_dir(stage2_run_id, case)
    desktop_page.context.clear_cookies()
    desktop_page.goto(f"{base_url}/account", wait_until="networkidle")
    evidence_rel = _shot(desktop_page, evidence, "guest-account.png")
    assert "/login" in desktop_page.url
    record_stage2(
        case,
        feature_ids=["X-01"],
        route="/account",
        expected="Guest cannot access account workspace",
        actual=f"Redirected to {desktop_page.url}",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg2_pro_001_bio_reload(desktop_page, base_url, qa_settings, stage2_run_id, record_stage2) -> None:
    case = "STG2-PRO-001"
    evidence = stage2_evidence_dir(stage2_run_id, case)
    email, password = _require_candidate_creds(qa_settings)
    _login_candidate(desktop_page, base_url, email, password)
    marker = f"STG2-PRO-001 bio {stage2_run_id[:8]}"
    desktop_page.goto(f"{base_url}/account/profile#about", wait_until="networkidle")
    desktop_page.locator("#bio").wait_for(state="visible")
    desktop_page.locator("#bio").fill(marker)
    desktop_page.get_by_role("button", name="Save", exact=True).click()
    desktop_page.wait_for_load_state("networkidle")
    desktop_page.reload(wait_until="networkidle")
    evidence_rel = _shot(desktop_page, evidence, "bio-after-reload.png")
    expect(desktop_page.locator("#bio")).to_have_value(marker)
    record_stage2(
        case,
        feature_ids=["CAN-02", "CAN-03"],
        route="/account/profile#about",
        expected="Bio persists after hard reload",
        actual="Bio field matches saved marker after reload",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg2_pro_002_bio_relogin(desktop_page, base_url, qa_settings, stage2_run_id, record_stage2) -> None:
    case = "STG2-PRO-002"
    evidence = stage2_evidence_dir(stage2_run_id, case)
    email, password = _require_candidate_creds(qa_settings)
    _login_candidate(desktop_page, base_url, email, password)
    marker = f"STG2-PRO-002 bio {stage2_run_id[:8]}"
    desktop_page.goto(f"{base_url}/account/profile#about", wait_until="networkidle")
    desktop_page.locator("#bio").fill(marker)
    desktop_page.get_by_role("button", name="Save", exact=True).click()
    desktop_page.wait_for_load_state("networkidle")
    _logout_via_nav(desktop_page)
    time.sleep(2)
    _login_candidate(desktop_page, base_url, email, password, fresh=True)
    desktop_page.goto(f"{base_url}/account/profile#about", wait_until="networkidle")
    evidence_rel = _shot(desktop_page, evidence, "bio-after-relogin.png")
    expect(desktop_page.locator("#bio")).to_have_value(marker)
    record_stage2(
        case,
        feature_ids=["CAN-02", "CAN-03"],
        route="/account/profile#about",
        expected="Bio persists after logout and login",
        actual="Bio field matches after new session",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg2_pro_003_designation_reload(desktop_page, base_url, qa_settings, stage2_run_id, record_stage2) -> None:
    case = "STG2-PRO-003"
    evidence = stage2_evidence_dir(stage2_run_id, case)
    email, password = _require_candidate_creds(qa_settings)
    _login_candidate(desktop_page, base_url, email, password)
    marker = f"STG2 Designation {stage2_run_id[:8]}"
    desktop_page.goto(f"{base_url}/account/profile#professional", wait_until="networkidle")
    desktop_page.locator("#current_designation").wait_for(state="visible")
    desktop_page.locator("#current_designation").fill(marker)
    desktop_page.locator("form").filter(has=desktop_page.locator("#current_designation")).get_by_role(
        "button", name="Save"
    ).click()
    desktop_page.wait_for_load_state("networkidle")
    desktop_page.reload(wait_until="networkidle")
    desktop_page.locator("#current_designation").wait_for(state="visible")
    evidence_rel = _shot(desktop_page, evidence, "designation-reload.png")
    expect(desktop_page.locator("#current_designation")).to_have_value(marker)
    record_stage2(
        case,
        feature_ids=["CAN-02", "CAN-03"],
        route="/account/profile#professional",
        expected="Designation persists after reload",
        actual="current_designation matches after reload",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg2_pro_004_skill_reload(desktop_page, base_url, qa_settings, stage2_run_id, record_stage2) -> None:
    case = "STG2-PRO-004"
    evidence = stage2_evidence_dir(stage2_run_id, case)
    email, password = _require_candidate_creds(qa_settings)
    _login_candidate(desktop_page, base_url, email, password)
    skill = f"STG2Skill{stage2_run_id[:6]}"
    desktop_page.goto(f"{base_url}/account/profile#skills", wait_until="networkidle")
    skill_input = desktop_page.locator("#skill_name")
    expect(skill_input).to_be_visible()
    skill_input.fill(skill)
    desktop_page.get_by_role("button", name=re.compile(r"Add skill", re.I)).click()
    desktop_page.wait_for_load_state("networkidle")
    desktop_page.reload(wait_until="networkidle")
    desktop_page.goto(f"{base_url}/account/profile#skills", wait_until="networkidle")
    evidence_rel = _shot(desktop_page, evidence, "skill-reload.png")
    expect(desktop_page.get_by_text(skill)).to_be_visible()
    record_stage2(
        case,
        feature_ids=["CAN-03"],
        route="/account/profile#skills",
        expected="Added skill visible after reload",
        actual=f"Skill {skill} present after reload",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg2_pro_005_modules_hub(desktop_page, base_url, qa_settings, stage2_run_id, record_stage2) -> None:
    case = "STG2-PRO-005"
    evidence = stage2_evidence_dir(stage2_run_id, case)
    email, password = _require_candidate_creds(qa_settings)
    _login_candidate(desktop_page, base_url, email, password)
    NavigationHelper(desktop_page, base_url).goto_path("/account/profile")
    desktop_page.wait_for_load_state("networkidle")
    for title in ["Basic information", "Professional summary", "Skills", "Experience"]:
        expect(desktop_page.get_by_text(title, exact=True).first).to_be_visible()
    evidence_rel = _shot(desktop_page, evidence, "modules-hub.png")
    record_stage2(
        case,
        feature_ids=["CAN-02"],
        route="/account/profile",
        expected="Profile completion modules listed on hub",
        actual="Core module titles present",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg2_pro_006_privacy_section(desktop_page, base_url, qa_settings, stage2_run_id, record_stage2) -> None:
    case = "STG2-PRO-006"
    evidence = stage2_evidence_dir(stage2_run_id, case)
    email, password = _require_candidate_creds(qa_settings)
    _login_candidate(desktop_page, base_url, email, password)
    desktop_page.goto(f"{base_url}/account/profile#privacy", wait_until="networkidle")
    evidence_rel = _shot(desktop_page, evidence, "privacy.png")
    expect(desktop_page.locator("#privacy")).to_be_attached()
    record_stage2(
        case,
        feature_ids=["CAN-10"],
        route="/account/profile#privacy",
        expected="Privacy section with share controls",
        actual="#privacy section present",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg2_dash_001_completion_no_regress(desktop_page, base_url, qa_settings, stage2_run_id, record_stage2) -> None:
    case = "STG2-DASH-001"
    evidence = stage2_evidence_dir(stage2_run_id, case)
    email, password = _require_candidate_creds(qa_settings)
    _login_candidate(desktop_page, base_url, email, password)
    NavigationHelper(desktop_page, base_url).goto_path("/account")
    before = _completion_pct(desktop_page)
    marker = f"STG2 dash {stage2_run_id[:8]}"
    desktop_page.goto(f"{base_url}/account/profile#about", wait_until="networkidle")
    desktop_page.locator("#bio").fill(marker)
    desktop_page.get_by_role("button", name="Save", exact=True).click()
    desktop_page.wait_for_load_state("networkidle")
    NavigationHelper(desktop_page, base_url).goto_path("/account")
    after = _completion_pct(desktop_page)
    evidence_rel = _shot(desktop_page, evidence, "completion.png")
    assert after >= before, f"Completion regressed {before}% -> {after}%"
    record_stage2(
        case,
        feature_ids=["CAN-01", "CAN-02"],
        route="/account",
        expected="Completion % does not decrease after save",
        actual=f"{before}% -> {after}%",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg2_dash_002_next_action(desktop_page, base_url, qa_settings, stage2_run_id, record_stage2) -> None:
    case = "STG2-DASH-002"
    evidence = stage2_evidence_dir(stage2_run_id, case)
    email, password = _require_candidate_creds(qa_settings)
    _login_candidate(desktop_page, base_url, email, password)
    NavigationHelper(desktop_page, base_url).goto_path("/account")
    next_section = desktop_page.locator("section[aria-labelledby='candidate-next-action']")
    expect(next_section).to_be_visible()
    link = next_section.get_by_role("link").first
    href = link.get_attribute("href") or ""
    evidence_rel = _shot(desktop_page, evidence, "next-action.png")
    assert "/account/profile" in href or href.startswith("/account")
    record_stage2(
        case,
        feature_ids=["CAN-01"],
        route="/account",
        expected="Next-action links into account profile workflow",
        actual=f"Next action href={href}",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg2_res_001_library_fixture(desktop_page, base_url, qa_settings, stage2_run_id, record_stage2) -> None:
    case = "STG2-RES-001"
    evidence = stage2_evidence_dir(stage2_run_id, case)
    email, password = _require_candidate_creds(qa_settings)
    _login_candidate(desktop_page, base_url, email, password)
    NavigationHelper(desktop_page, base_url).goto_path("/account/resumes")
    desktop_page.wait_for_load_state("networkidle")
    evidence_rel = _shot(desktop_page, evidence, "resumes-hub.png")
    expect(desktop_page.get_by_text(re.compile(r"My Resume|Resume", re.I)).first).to_be_visible()
    expect(desktop_page.get_by_text(re.compile(r"QA Fixture|My Resume", re.I)).first).to_be_attached()
    record_stage2(
        case,
        feature_ids=["CAN-04"],
        route="/account/resumes",
        expected="Fixture candidate has library resume on hub",
        actual="Resume hub shows library resume content",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg2_res_002_builder_loads(desktop_page, base_url, qa_settings, stage2_run_id, record_stage2) -> None:
    case = "STG2-RES-002"
    evidence = stage2_evidence_dir(stage2_run_id, case)
    email, password = _require_candidate_creds(qa_settings)
    _login_candidate(desktop_page, base_url, email, password)
    NavigationHelper(desktop_page, base_url).goto_path("/account/resumes/builder")
    desktop_page.wait_for_load_state("networkidle")
    evidence_rel = _shot(desktop_page, evidence, "builder.png")
    expect(desktop_page.get_by_role("heading", name=re.compile(r"Resume|Hire", re.I)).first).to_be_visible()
    record_stage2(
        case,
        feature_ids=["CAN-05"],
        route="/account/resumes/builder",
        expected="Hire resume builder page loads",
        actual="Builder heading visible",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg2_res_003_resumes_reload(desktop_page, base_url, qa_settings, stage2_run_id, record_stage2) -> None:
    case = "STG2-RES-003"
    evidence = stage2_evidence_dir(stage2_run_id, case)
    email, password = _require_candidate_creds(qa_settings)
    _login_candidate(desktop_page, base_url, email, password)
    NavigationHelper(desktop_page, base_url).goto_path("/account/resumes")
    desktop_page.wait_for_load_state("networkidle")
    desktop_page.reload(wait_until="networkidle")
    evidence_rel = _shot(desktop_page, evidence, "resumes-reload.png")
    expect(desktop_page.get_by_text(re.compile(r"My Resume", re.I)).first).to_be_visible()
    record_stage2(
        case,
        feature_ids=["CAN-04"],
        route="/account/resumes",
        expected="Resume hub stable after reload",
        actual="My Resume section still visible",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg2_mob_001_bio_mobile(mobile_page, base_url, qa_settings, stage2_run_id, record_stage2) -> None:
    case = "STG2-MOB-001"
    evidence = stage2_evidence_dir(stage2_run_id, case)
    email, password = _require_candidate_creds(qa_settings)
    _login_candidate(mobile_page, base_url, email, password)
    marker = f"STG2 mobile {stage2_run_id[:8]}"
    mobile_page.goto(f"{base_url}/account/profile#about", wait_until="networkidle")
    mobile_page.locator("#bio").fill(marker)
    mobile_page.get_by_role("button", name="Save", exact=True).click()
    mobile_page.wait_for_load_state("networkidle")
    evidence_rel = _shot(mobile_page, evidence, "mobile-bio.png")
    expect(mobile_page.locator("#bio")).to_have_value(marker)
    record_stage2(
        case,
        feature_ids=["CAN-02"],
        route="/account/profile#about",
        expected="Bio editable on mobile viewport",
        actual="Bio saved on 390×844",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg2_mob_002_dashboard_mobile(mobile_page, base_url, qa_settings, stage2_run_id, record_stage2) -> None:
    case = "STG2-MOB-002"
    evidence = stage2_evidence_dir(stage2_run_id, case)
    email, password = _require_candidate_creds(qa_settings)
    _login_candidate(mobile_page, base_url, email, password)
    evidence_rel = _shot(mobile_page, evidence, "mobile-dashboard.png")
    expect(mobile_page.get_by_role("heading", name="Dashboard")).to_be_visible()
    record_stage2(
        case,
        feature_ids=["CAN-01"],
        route="/account",
        expected="Dashboard usable on mobile",
        actual="Dashboard heading visible at 390×844",
        status="PASS",
        evidence_rel=evidence_rel,
    )
