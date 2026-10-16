"""Execute companion manual cases with real browser evidence (not a substitute for full human UX review)."""

from __future__ import annotations

import re
from pathlib import Path

import pytest
from playwright.sync_api import Page, expect

from ournear_qa.browser.forms import FormHelper
from ournear_qa.browser.navigation import NavigationHelper
from ournear_qa.evidence import ARTIFACTS_DIR

MANUAL_EVIDENCE_ROOT = ARTIFACTS_DIR / "manual" / "evidence"


def _evidence_dir(qa_run_id: str, case_id: str) -> Path:
    path = MANUAL_EVIDENCE_ROOT / qa_run_id / case_id
    path.mkdir(parents=True, exist_ok=True)
    return path


def _shot(page: Page, directory: Path, name: str) -> str:
    target = directory / name
    page.screenshot(path=str(target), full_page=True)
    return target.relative_to(ARTIFACTS_DIR).as_posix()


def _login_candidate(page: Page, base_url: str, qa_settings) -> None:
    if not qa_settings.qa_candidate_email or not qa_settings.qa_candidate_password:
        pytest.skip("Set OURNEAR_QA_CANDIDATE_* in config/local.env")
    nav = NavigationHelper(page, base_url)
    forms = FormHelper(page)
    nav.goto_path("/login")
    forms.fill_field("Username or Email", qa_settings.qa_candidate_email)
    forms.fill_field("Password", qa_settings.qa_candidate_password)
    forms.click_submit("Log in")
    nav.wait_for_path("**/account**")


@pytest.mark.manual
def test_man_can_01_dashboard_guidance(page, base_url, qa_settings, request) -> None:
    """MAN-CAN-01-DASHBOARD-GUIDANCE — dashboard next-action vs completion after profile save."""
    run_id = request.config._ournear_qa_run_id  # type: ignore[attr-defined]
    evidence = _evidence_dir(run_id, "MAN-CAN-01-DASHBOARD-GUIDANCE")
    _login_candidate(page, base_url, qa_settings)

    nav = NavigationHelper(page, base_url)
    nav.goto_path("/account")
    nav.assert_heading("Dashboard")

    completion_before = page.get_by_text(re.compile(r"\d+% profile complete")).inner_text()
    next_title_before = page.locator("#candidate-next-action").inner_text()
    _shot(page, evidence, "01-dashboard-before.png")

    next_href = page.locator("section[aria-labelledby='candidate-next-action']").get_by_role("link").get_attribute(
        "href"
    ) or "/account/profile"
    page.goto(f"{base_url}{next_href}" if next_href.startswith("/") else next_href)
    page.wait_for_load_state("networkidle")

    marker = f"MAN-CAN-01 evidence run {run_id[:8]}"
    if page.locator("#bio").count() > 0 and page.locator("#bio").is_visible():
        page.locator("#bio").fill(marker)
    elif page.locator('input[name="location"], #location').first.is_visible():
        page.locator('input[name="location"], #location').first.fill(marker[:80])
    else:
        page.get_by_role("button", name="Edit Profile").click()
        page.get_by_role("button", name="Edit").first.click()
        if page.locator("#bio").is_visible():
            page.locator("#bio").fill(marker)
        else:
            page.get_by_label("Location").fill(marker[:80])
    page.get_by_role("button", name="Save", exact=True).click()
    page.wait_for_load_state("networkidle")
    _shot(page, evidence, "02-profile-after-save.png")

    page.goto(f"{base_url}/account")
    page.wait_for_load_state("networkidle")
    nav.assert_heading("Dashboard")

    completion_after = page.get_by_text(re.compile(r"\d+% profile complete")).inner_text()
    next_title_after = page.locator("#candidate-next-action").inner_text()
    _shot(page, evidence, "03-dashboard-after-reload.png")

    assert marker in page.content() or completion_after != completion_before or next_title_after != next_title_before, (
        "Expected completion or next-action to reflect bio save"
    )
    if next_title_before.strip().lower().startswith("complete: bio"):
        assert next_title_after.strip().lower() != next_title_before.strip().lower(), (
            "Next action should advance after completing bio"
        )


@pytest.mark.manual
def test_man_pub_02_jobs_search(page, base_url, request) -> None:
    """MAN-PUB-02-JOBS-SEARCH — keyword and company filter on public jobs index."""
    run_id = request.config._ournear_qa_run_id  # type: ignore[attr-defined]
    evidence = _evidence_dir(run_id, "MAN-PUB-02-JOBS-SEARCH")
    page.set_viewport_size({"width": 1400, "height": 900})
    nav = NavigationHelper(page, base_url)
    nav.goto_path("/jobs")
    page.wait_for_load_state("networkidle")
    _shot(page, evidence, "01-jobs-index.png")

    keyword = page.locator('input[name="keyword"]')
    expect(keyword).to_be_visible()
    keyword.fill("QA Fixture Published Role")
    page.get_by_role("button", name="Search").click()
    page.wait_for_load_state("networkidle")
    _shot(page, evidence, "02-keyword-filter.png")
    expect(page.get_by_role("heading", name="No matching roles")).not_to_be_visible()
    expect(page.locator('a[href*="/jobs/qa-fixture-published-a"]').first).to_be_attached()

    page.get_by_role("button", name="Search").click()
    page.wait_for_load_state("networkidle")

    company_select = page.locator("#job-company")
    expect(company_select).to_be_visible()
    company_value = "QA Hiring Co A"
    if company_select.locator(f'option[value="{company_value}"]').count() == 0:
        company_value = None
        for index in range(company_select.locator("option").count()):
            value = company_select.locator("option").nth(index).get_attribute("value") or ""
            if value.strip():
                company_value = value
                break
    if not company_value:
        pytest.skip("No company options in filter dropdown — fixtures may lack company labels")

    company_select.select_option(company_value)
    page.get_by_role("button", name="Search").click()
    page.wait_for_load_state("networkidle")
    _shot(page, evidence, "03-company-filter-ui.png")
    expect(page.locator("#job-company")).to_have_value(company_value)
    expect(page.get_by_role("heading", name="No matching roles")).not_to_be_visible()

    expect(page.get_by_text(re.compile(r"\d+ open job", re.I)).first).to_be_visible()
    job_link = page.locator('a[href^="/jobs/"]').filter(has_not_text="Jobs").first
    expect(job_link).to_be_attached()
    job_href = job_link.get_attribute("href")
    assert job_href
    page.goto(f"{base_url}{job_href}")
    page.wait_for_load_state("networkidle")
    _shot(page, evidence, "04-job-detail.png")

    page.set_viewport_size({"width": 390, "height": 844})
    nav.goto_path("/jobs")
    page.wait_for_load_state("networkidle")
    expect(page.locator("#job-company")).to_be_visible()
    _shot(page, evidence, "05-mobile-company-filter-visible.png")


@pytest.mark.manual
def test_man_can_02_profile_reload(page, base_url, qa_settings, request) -> None:
    """MAN-CAN-02-PROFILE-RELOAD — personal and professional saves persist after reload."""
    run_id = request.config._ournear_qa_run_id  # type: ignore[attr-defined]
    evidence = _evidence_dir(run_id, "MAN-CAN-02-PROFILE-RELOAD")
    _login_candidate(page, base_url, qa_settings)

    nav = NavigationHelper(page, base_url)
    nav.goto_path("/account/profile")
    page.wait_for_load_state("networkidle")
    completion_locator = page.get_by_text(re.compile(r"\d+%(\s*complete)?", re.I))
    expect(completion_locator.first).to_be_visible()
    completion_before = completion_locator.first.inner_text()
    _shot(page, evidence, "01-profile-before.png")

    page.goto(f"{base_url}/account/profile#about")
    page.wait_for_load_state("networkidle")
    bio_marker = f"MAN-CAN-02 bio {run_id[:8]}"
    page.locator("#bio").wait_for(state="visible")
    page.locator("#bio").fill(bio_marker)
    page.get_by_role("button", name="Save", exact=True).click()
    page.wait_for_load_state("networkidle")
    _shot(page, evidence, "02-personal-after-save.png")
    expect(page.locator("#bio")).to_have_value(bio_marker)

    page.reload(wait_until="networkidle")
    _shot(page, evidence, "03-personal-after-reload.png")
    expect(page.get_by_text(bio_marker)).to_be_visible()

    page.goto(f"{base_url}/account/profile#professional")
    page.wait_for_load_state("networkidle")
    page.locator("#current_designation").wait_for(state="visible")
    designation_marker = f"QA Designation {run_id[:8]}"
    page.locator("#current_designation").fill(designation_marker)
    page.locator("form").filter(has=page.locator("#current_designation")).get_by_role("button", name="Save").click()
    page.wait_for_load_state("networkidle")
    _shot(page, evidence, "04-professional-after-save.png")
    expect(page.locator("#current_designation")).to_have_value(designation_marker)

    page.reload(wait_until="networkidle")
    page.locator("#current_designation").wait_for(state="visible")
    _shot(page, evidence, "05-professional-after-reload.png")
    expect(page.locator("#current_designation")).to_have_value(designation_marker)

    nav.goto_path("/account/profile")
    page.wait_for_load_state("networkidle")
    expect(page.get_by_role("definition").filter(has_text=designation_marker)).to_be_visible()
    expect(completion_locator.first).to_be_visible()
    completion_after = completion_locator.first.inner_text()
    before_pct = int(re.search(r"(\d+)", completion_before).group(1))
    after_pct = int(re.search(r"(\d+)", completion_after).group(1))
    assert after_pct >= before_pct, "Profile completion should not regress after saves"
    assert bio_marker in page.content(), "Bio marker should remain on profile hub after reload"


@pytest.mark.manual
def test_man_pub_02_jobs_company_pagination(page, base_url, request) -> None:
    """PUB-02 — company filter retained across pagination (fixture-backed)."""
    run_id = request.config._ournear_qa_run_id  # type: ignore[attr-defined]
    evidence = _evidence_dir(run_id, "MAN-PUB-02-JOBS-PAGINATION")
    page.set_viewport_size({"width": 1400, "height": 900})
    nav = NavigationHelper(page, base_url)
    nav.goto_path("/jobs")
    page.wait_for_load_state("networkidle")

    company_value = "QA Hiring Co A"
    company_select = page.locator("#job-company")
    expect(company_select).to_be_visible()
    if company_select.locator(f'option[value="{company_value}"]').count() == 0:
        pytest.skip("QA Hiring Co A not in company filter — run php artisan qa:hiring-fixtures")

    company_select.select_option(company_value)
    page.get_by_role("button", name="Search").click()
    page.wait_for_load_state("networkidle")
    _shot(page, evidence, "01-company-filter-page1.png")

    expect(page.locator("#job-company")).to_have_value(company_value)
    total_line = page.get_by_text(re.compile(r"\d+ open jobs", re.I)).first
    expect(total_line).to_be_visible()
    total_text = total_line.inner_text()
    match = re.search(r"(\d+)\s+open jobs", total_text, re.I)
    assert match and int(match.group(1)) >= 10, (
        f"Expected >=10 open jobs for {company_value} to paginate; got {total_text!r}"
    )

    cards = page.locator('[aria-label="Job results"] article[role="button"]')
    assert cards.count() > 0
    for index in range(cards.count()):
        expect(cards.nth(index).get_by_role("link", name=company_value)).to_be_attached()

    page_two = page.get_by_role("navigation", name="Pagination").get_by_role("link", name="2")
    expect(page_two).to_be_visible()
    page_two.click()
    page.wait_for_load_state("networkidle")
    _shot(page, evidence, "02-company-filter-page2.png")

    expect(page.locator("#job-company")).to_have_value(company_value)
    expect(page).to_have_url(re.compile(r"[?&]company="))
    cards_page2 = page.locator('[aria-label="Job results"] article[role="button"]')
    assert cards_page2.count() > 0
    for index in range(cards_page2.count()):
        expect(cards_page2.nth(index).get_by_role("link", name=company_value)).to_be_attached()

    page.get_by_role("button", name="Clear filters").click()
    page.wait_for_load_state("networkidle")
    _shot(page, evidence, "03-after-clear-filters.png")
    expect(page.locator("#job-company")).to_have_value("")
