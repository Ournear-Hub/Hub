from __future__ import annotations

import re
import shutil
import subprocess
from pathlib import Path

import pytest
from playwright.sync_api import Page, expect

from ournear_qa.browser.forms import FormHelper
from ournear_qa.browser.navigation import NavigationHelper
from ournear_qa.journey.account_auth import login_account_user
from ournear_qa.journey.employer_job_publish import (
    _account_jobs_post,
    _job_card,
    _lifecycle_patch,
    discover_job_form_defaults,
    fill_new_job_form,
)

REPO_ROOT = Path(__file__).resolve().parents[3]
RUN_ID = "20261011T104600Z-reg-php-003-browser"
FIXTURE_SLUG = "qa-fixture-published-a"
PHP_BIN = shutil.which("php")


def _run_php_script(script_name: str, *args: str) -> subprocess.CompletedProcess[str]:
    if PHP_BIN is None:
        pytest.skip("PHP executable not on PATH")
    script = REPO_ROOT / "scripts" / script_name
    return subprocess.run(
        [PHP_BIN, str(script), *args],
        cwd=REPO_ROOT,
        capture_output=True,
        text=True,
        check=False,
    )


@pytest.fixture
def desktop_page(page: Page) -> Page:
    page.set_viewport_size({"width": 1400, "height": 900})
    return page


def _employer(qa_settings) -> tuple[str, str]:
    email = (qa_settings.qa_employer_a_email or "").strip()
    password = (qa_settings.qa_employer_a_password or "").strip()
    if not email or not password:
        pytest.skip("Employer A QA credentials required")
    return email, password


@pytest.mark.reg_php_003
def test_reg_php_003_public_unverified_label_on_job_detail(desktop_page: Page, base_url: str) -> None:
    desktop_page.goto(f"{base_url}/jobs/{FIXTURE_SLUG}", wait_until="networkidle")
    expect(
        desktop_page.locator("p.text-xs.font-medium.uppercase").filter(has_text="Employer Not Verified")
    ).to_be_visible(timeout=30_000)


@pytest.mark.reg_php_003
def test_reg_php_003_public_unverified_label_on_jobs_listing(desktop_page: Page, base_url: str) -> None:
    desktop_page.goto(f"{base_url}/jobs?keyword=QA+Fixture+Published+Role", wait_until="networkidle")
    results = desktop_page.get_by_label("Job results")
    card = results.get_by_role("link", name="QA Fixture Published Role (Employer A)", exact=True)
    expect(card).to_be_visible(timeout=30_000)
    expect(results.get_by_text("Employer Not Verified", exact=False).first).to_be_visible(timeout=30_000)


@pytest.mark.reg_php_003
def test_reg_php_003_second_publish_blocked_for_unverified_employer(
    desktop_page: Page, base_url: str, qa_settings
) -> None:
    email, password = _employer(qa_settings)
    login_account_user(desktop_page, base_url, email, password, journey_id="reg-php-003", step_id="employer")
    nav = NavigationHelper(desktop_page, base_url)
    forms = FormHelper(desktop_page)
    defaults = discover_job_form_defaults(REPO_ROOT)
    if defaults is None:
        pytest.skip("Job form defaults script unavailable")
    title = f"REG-PHP-003 Block {int(__import__('time').time())}"
    nav.goto_path("/account/jobs/create")
    fill_new_job_form(desktop_page, forms, defaults, title=title)
    with desktop_page.expect_response(_account_jobs_post, timeout=45_000) as response_info:
        forms.click_submit("Save draft")
    assert response_info.value.status < 400, response_info.value.status
    nav.goto_path("/account/jobs")
    nav.assert_heading("Job management")
    card = _job_card(desktop_page, title)
    expect(card).to_be_visible(timeout=20_000)
    publish = card.get_by_role("button", name="Publish")
    if publish.count() == 0:
        expect(
            desktop_page.get_by_text("one job", exact=False).or_(desktop_page.get_by_text("Verify", exact=False))
        ).to_be_visible()
        return
    with desktop_page.expect_response(_lifecycle_patch, timeout=45_000) as response_info:
        publish.click()
    if response_info.value.status >= 400:
        return
    desktop_page.wait_for_load_state("networkidle")
    body = desktop_page.content().lower()
    assert "one job" in body or "verify" in body or "posting" in body


@pytest.mark.reg_php_003
def test_reg_php_003_trial_expiry_hides_job_from_public(desktop_page: Page, base_url: str) -> None:
    try:
        php = _run_php_script("qa-reg-php-003-expire-trial.php", FIXTURE_SLUG)
        assert php.returncode == 0, php.stderr
        assert "visible=no" in php.stdout

        resp = desktop_page.request.get(f"{base_url}/jobs/{FIXTURE_SLUG}")
        assert resp.status in (404, 410), resp.status
    finally:
        if PHP_BIN is not None:
            subprocess.run(
                [PHP_BIN, str(REPO_ROOT / "scripts" / "qa-reg-php-003-restore-published-a.php")],
                cwd=REPO_ROOT,
                check=False,
            )
