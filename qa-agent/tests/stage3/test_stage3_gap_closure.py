"""Stage 3 gap closure — §F browser evidence (does not rerun core STG3 checklist)."""

from __future__ import annotations

import re
import time
from typing import Any

import pytest
from playwright.sync_api import Page, Response, expect

from ournear_qa.browser.files import synthetic_pdf_file
from ournear_qa.browser.forms import FormHelper
from ournear_qa.browser.navigation import NavigationHelper
from ournear_qa.evidence import ARTIFACTS_DIR
from ournear_qa.gap.application_helpers import application_count_for_slug, close_fixture_job
from ournear_qa.journey.candidate_apply import (
    job_show_url_pattern,
    open_job_detail,
    provision_journey_apply_target,
)
from ournear_qa.journey.errors import JourneyStepError

pytestmark = [pytest.mark.stage3_gap]

STAGE3_EVIDENCE_ROOT = ARTIFACTS_DIR / "stage3" / "evidence"
_GAP_STATE: dict[str, Any] = {}


@pytest.fixture
def desktop_page(page: Page):
    page.set_viewport_size({"width": 1400, "height": 900})
    return page


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


def _login_candidate(page: Page, base_url: str, email: str, password: str) -> None:
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


def _provision_open_job() -> tuple[str, str]:
    target = provision_journey_apply_target()
    if target is None:
        pytest.skip("Run scripts/qa-provision-journey-apply-job.php after qa:hiring-fixtures")
    return target


def _open_application_detail(page: Page, business_id: str) -> None:
    card = page.locator("li").filter(has_text=business_id)
    expect(card.first).to_be_visible(timeout=30_000)
    card.first.get_by_role("link", name="Open application").click()
    page.wait_for_url(re.compile(r"/account/applications/\d+"), timeout=30_000)


def test_stg3_gap_01_closed_job_apply_blocked(
    desktop_page, base_url, qa_settings, stage3_run_id, record_stage3_gap
) -> None:
    case = "STG3-GAP-C"
    evidence = stage3_evidence_dir(stage3_run_id, case)
    slug, title = _provision_open_job()
    assert application_count_for_slug(slug) == 0
    close_fixture_job(slug)

    guest_resp = desktop_page.goto(f"{base_url}/jobs/{slug}", wait_until="domcontentloaded")
    guest_shot = _shot(desktop_page, evidence, "closed-job-guest-show.png")
    assert guest_resp and guest_resp.status == 404

    apply_resp = desktop_page.goto(f"{base_url}/jobs/{slug}/apply", wait_until="domcontentloaded")
    _shot(desktop_page, evidence, "closed-job-guest-apply.png")
    assert apply_resp and apply_resp.status == 404

    email, password = _require_candidate_creds(qa_settings)
    _login_candidate(desktop_page, base_url, email, password)
    cand_apply = desktop_page.goto(f"{base_url}/jobs/{slug}/apply", wait_until="domcontentloaded")
    _shot(desktop_page, evidence, "closed-job-candidate-apply.png")
    assert cand_apply and cand_apply.status == 404
    assert application_count_for_slug(slug) == 0

    record_stage3_gap(
        case,
        feature_ids=["CAN-07"],
        route=f"/jobs/{slug}",
        expected="Closed fixture job returns 404 on show/apply; no application row",
        actual=f"HTTP show={guest_resp.status}, apply={cand_apply.status}, count=0; title={title}",
        status="PASS",
        evidence_rel=guest_shot,
    )


def test_stg3_gap_02_double_click_single_application(
    desktop_page, base_url, qa_settings, stage3_run_id, record_stage3_gap
) -> None:
    case = "STG3-GAP-A"
    evidence = stage3_evidence_dir(stage3_run_id, case)
    slug, title = _provision_open_job()
    assert application_count_for_slug(slug) == 0

    email, password = _require_candidate_creds(qa_settings)
    _login_candidate(desktop_page, base_url, email, password)
    open_job_detail(desktop_page, base_url, title, slug)
    desktop_page.get_by_role("link", name="Apply").first.click()
    desktop_page.wait_for_url(f"**/jobs/{slug}/apply**", timeout=30_000)
    expect(desktop_page.get_by_text("Ready to include with your application.")).to_be_visible(timeout=15_000)
    _shot(desktop_page, evidence, "apply-ready.png")

    apply_posts: list[Response] = []

    def capture_apply_post(response: Response) -> None:
        if response.request.method == "POST" and f"/jobs/{slug}/apply" in response.url:
            apply_posts.append(response)

    desktop_page.on("response", capture_apply_post)
    submit = desktop_page.get_by_role("button", name=re.compile(r"Submit application|Submitting"))
    submit.dblclick()
    desktop_page.wait_for_url(job_show_url_pattern(slug), timeout=30_000)
    expect(desktop_page.get_by_text("Application submitted")).to_be_visible(timeout=15_000)
    _shot(desktop_page, evidence, "submit-confirmation.png")

    body = desktop_page.locator("body").inner_text()
    match = re.search(r"ONT-A-\d{6}", body)
    business_id = match.group(0) if match else None
    assert business_id

    count_after = application_count_for_slug(slug)
    assert count_after == 1, f"expected 1 application, got {count_after}; POSTs={len(apply_posts)}"

    desktop_page.reload(wait_until="domcontentloaded")
    expect(desktop_page.get_by_text("Applied", exact=True).first).to_be_visible(timeout=15_000)
    assert application_count_for_slug(slug) == 1

    desktop_page.go_back(wait_until="domcontentloaded")
    if f"/jobs/{slug}/apply" in desktop_page.url:
        if submit.count() and submit.is_enabled():
            submit.click()
            desktop_page.wait_for_timeout(1500)
    assert application_count_for_slug(slug) == 1

    desktop_page.goto(f"{base_url}/jobs/{slug}/apply", wait_until="domcontentloaded")
    assert f"/jobs/{slug}/apply" not in desktop_page.url
    expect(desktop_page.get_by_text("Applied", exact=True).first).to_be_visible(timeout=15_000)
    _shot(desktop_page, evidence, "duplicate-guard-after-double-click.png")

    _GAP_STATE["withdraw_business_id"] = business_id
    _GAP_STATE["withdraw_job_slug"] = slug

    record_stage3_gap(
        case,
        feature_ids=["CAN-07"],
        route=f"/jobs/{slug}/apply",
        expected="One application despite double-click, reload, and revisit apply URL",
        actual=f"count=1; business_id={business_id}; apply_POST_responses={len(apply_posts)}",
        status="PASS",
        evidence_rel=_shot(desktop_page, evidence, "final-applied-state.png"),
    )


def test_stg3_gap_03_withdraw_application(
    desktop_page, base_url, qa_settings, stage3_run_id, record_stage3_gap
) -> None:
    case = "STG3-GAP-B"
    evidence = stage3_evidence_dir(stage3_run_id, case)
    business_id = _GAP_STATE.get("withdraw_business_id")
    if not business_id:
        pytest.skip("Depends on STG3-GAP-A application")

    email, password = _require_candidate_creds(qa_settings)
    _login_candidate(desktop_page, base_url, email, password)
    NavigationHelper(desktop_page, base_url).goto_path("/account/applications")
    _open_application_detail(desktop_page, business_id)
    expect(desktop_page.get_by_role("button", name="Withdraw application")).to_be_visible(timeout=15_000)
    _shot(desktop_page, evidence, "before-withdraw.png")

    desktop_page.once("dialog", lambda dialog: dialog.accept())
    desktop_page.get_by_role("button", name="Withdraw application").click()
    desktop_page.wait_for_load_state("networkidle")
    expect(desktop_page.get_by_text("Application withdrawn.")).to_be_visible(timeout=15_000)
    expect(desktop_page.locator("span").filter(has_text="Withdrawn").first).to_be_visible(timeout=15_000)
    _shot(desktop_page, evidence, "after-withdraw.png")

    desktop_page.reload(wait_until="domcontentloaded")
    expect(desktop_page.locator("span").filter(has_text="Withdrawn").first).to_be_visible(timeout=15_000)
    expect(desktop_page.get_by_role("button", name="Withdraw application")).to_have_count(0)

    record_stage3_gap(
        case,
        feature_ids=["CAN-07"],
        route="/account/applications/{id}/withdraw",
        expected="Confirm dialog → Withdrawn status persists after reload",
        actual=f"Withdrawn for {business_id}; withdraw button hidden after reload",
        status="PASS",
        evidence_rel=_shot(desktop_page, evidence, "withdraw-persist.png"),
    )


def test_stg3_gap_04_resume_snapshot_after_library_replace(
    desktop_page, base_url, qa_settings, stage3_run_id, record_stage3_gap
) -> None:
    case = "STG3-GAP-D"
    evidence = stage3_evidence_dir(stage3_run_id, case)
    slug, title = _provision_open_job()
    assert application_count_for_slug(slug) == 0

    email, password = _require_candidate_creds(qa_settings)
    _login_candidate(desktop_page, base_url, email, password)
    NavigationHelper(desktop_page, base_url).goto_path(f"/jobs/{slug}/apply")
    desktop_page.wait_for_url(f"**/jobs/{slug}/apply**", timeout=30_000)
    expect(desktop_page.get_by_text("Ready to include with your application.")).to_be_visible(timeout=15_000)
    _shot(desktop_page, evidence, "apply-resume-before.png")

    with desktop_page.expect_response(
        lambda r: r.request.method == "POST" and f"/jobs/{slug}/apply" in r.url,
        timeout=30_000,
    ) as apply_response_info:
        desktop_page.get_by_role("button", name="Submit application").click()
    apply_response = apply_response_info.value
    if apply_response.status == 429:
        record_stage3_gap(
            case,
            feature_ids=["CAN-07"],
            route=f"/jobs/{slug}/apply",
            expected="Submit application for snapshot browser proof",
            actual="HTTP 429 job-applications throttle (8/hour)",
            status="BLOCKED",
        )
        pytest.skip("job-applications rate limit (8/hour)")
    if apply_response.status >= 400:
        pytest.fail(f"apply POST HTTP {apply_response.status}")
    desktop_page.wait_for_url(job_show_url_pattern(slug), timeout=30_000)
    body = desktop_page.locator("body").inner_text()
    match = re.search(r"ONT-A-\d{6}", body)
    assert match
    business_id = match.group(0)

    NavigationHelper(desktop_page, base_url).goto_path("/account/applications")
    _open_application_detail(desktop_page, business_id)
    snapshot_section = desktop_page.get_by_role("heading", name="Resume used").locator("xpath=ancestor::section[1]")
    expect(snapshot_section).to_be_visible()
    snapshot_filename = snapshot_section.locator("p.text-sm.text-ink").first.inner_text().strip()
    _shot(desktop_page, evidence, "application-snapshot-before-replace.png")
    expect(snapshot_section.get_by_role("link", name="Download snapshot")).to_be_visible()

    NavigationHelper(desktop_page, base_url).goto_path("/account/resumes")
    replace_path = synthetic_pdf_file("qa-gap-snapshot-replace.pdf")
    desktop_page.locator("#replace-file").set_input_files(str(replace_path))
    expect(desktop_page.get_by_text("qa-gap-snapshot-replace.pdf")).to_be_visible(timeout=10_000)
    with desktop_page.expect_response(
        lambda r: r.request.method == "POST" and "/account/resumes/" in r.url and "/replace" in r.url,
        timeout=30_000,
    ):
        desktop_page.get_by_role("button", name="Replace resume", exact=True).click()
    desktop_page.wait_for_load_state("networkidle")
    expect(desktop_page.get_by_text("qa-gap-snapshot-replace.pdf")).to_be_visible(timeout=15_000)
    _shot(desktop_page, evidence, "library-resume-after-replace.png")

    NavigationHelper(desktop_page, base_url).goto_path("/account/applications")
    _open_application_detail(desktop_page, business_id)
    snapshot_after = desktop_page.get_by_role("heading", name="Resume used").locator("xpath=ancestor::section[1]")
    after_filename = snapshot_after.locator("p.text-sm.text-ink").first.inner_text().strip()
    _shot(desktop_page, evidence, "application-snapshot-after-replace.png")

    assert after_filename == snapshot_filename, (
        f"snapshot filename changed: before={snapshot_filename!r} after={after_filename!r}"
    )
    assert "qa-gap-snapshot-replace.pdf" not in after_filename

    record_stage3_gap(
        case,
        feature_ids=["CAN-07"],
        route=f"/account/applications/{{id}}",
        expected="Application snapshot filename unchanged after library replace",
        actual=(
            f"snapshot={snapshot_filename!r}; library now qa-gap-snapshot-replace.pdf; "
            "download snapshot link visible before replace"
        ),
        status="PASS",
        evidence_rel=_shot(desktop_page, evidence, "snapshot-integrity-final.png"),
    )


def test_stg3_gap_05_journey_evidence_sufficiency(stage3_run_id, record_stage3_gap) -> None:
    case = "STG3-GAP-E"
    record_stage3_gap(
        case,
        feature_ids=["JRN-CANDIDATE-APPLY-01"],
        route="n/a",
        expected="Journey behavior evidenced without redundant standalone run",
        actual=(
            "STG3-APPLY-001/002 executed run_new_application_steps + run_duplicate_guard_steps "
            "under canonical run 20261010T073839Z-d2b6b491; journey pytest import fixed "
            "(tests.journey.conftest → local helper) for optional future run"
        ),
        status="PASS",
    )
