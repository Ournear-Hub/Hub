"""REG-PROD-002 — Admin Email delivery settings browser acceptance."""

from __future__ import annotations

import re
import socket
from pathlib import Path

import pytest
from playwright.sync_api import Page, expect

from ournear_qa.evidence import ARTIFACTS_DIR
from ournear_qa.journey.admin_application import login_admin

RUN_ID = "20261011T163000Z-reg-prod-002-browser"
EVIDENCE = ARTIFACTS_DIR / "agent2" / RUN_ID
TEST_SMTP_HOST = "smtp.example.test"

pytestmark = [pytest.mark.reg_prod_002]


def _mysql_up() -> bool:
    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as sock:
        sock.settimeout(2)
        try:
            return sock.connect_ex(("127.0.0.1", 3306)) == 0
        except OSError:
            return False


def _server_up(base_url: str) -> bool:
    import urllib.request

    try:
        urllib.request.urlopen(base_url.rstrip("/") + "/", timeout=8)
        return True
    except Exception:
        return False


def _require_admin(qa_settings) -> tuple[str, str]:
    email = (qa_settings.qa_admin_email or "").strip()
    password = (qa_settings.qa_admin_password or "").strip()
    if not email or not password:
        pytest.skip("OURNEAR_QA_ADMIN_EMAIL / OURNEAR_QA_ADMIN_PASSWORD required")
    return email, password


def _smtp_host_input(page: Page):
    """SMTP host field: wrapped in <label><span>SMTP host</span><input /></label> (no name attr)."""
    return page.get_by_label(re.compile(r"^SMTP host$", re.I))


@pytest.fixture
def desktop_page(page: Page) -> Page:
    page.set_viewport_size({"width": 1280, "height": 900})
    return page


def test_reg_prod_002_email_delivery_tab_smoke(
    desktop_page: Page, base_url: str, qa_settings
) -> None:
    if qa_settings.ournear_allow_run != "local":
        pytest.skip("Set OURNEAR_ALLOW_RUN=local for this browser acceptance")
    if not _mysql_up():
        pytest.skip("BLOCKED: MySQL84 not listening on 3306")
    if not _server_up(base_url):
        pytest.skip("BLOCKED: start scripts/start-local-web-server.ps1")

    admin_email, admin_password = _require_admin(qa_settings)
    login_admin(desktop_page, base_url, admin_email, admin_password)
    settings_url = f"{base_url.rstrip('/')}/admin/settings?tab=email"
    desktop_page.goto(settings_url, wait_until="networkidle")

    expect(desktop_page.get_by_role("heading", name=re.compile("Settings", re.I))).to_be_visible(timeout=30_000)
    expect(desktop_page.get_by_text("Email delivery", exact=False).first).to_be_visible()
    host_input = _smtp_host_input(desktop_page)
    expect(host_input).to_be_visible()
    expect(desktop_page.locator('input[type="password"]').first).to_be_visible()

    EVIDENCE.mkdir(parents=True, exist_ok=True)
    desktop_page.screenshot(path=str(EVIDENCE / "email-delivery-tab.png"), full_page=True)

    host_input.fill(TEST_SMTP_HOST)
    save_button = desktop_page.get_by_role("button", name="Save email settings", exact=True)
    with desktop_page.expect_response(
        lambda r: r.request.method == "PUT" and "/admin/settings/email" in r.url,
        timeout=30_000,
    ) as save_response_info:
        save_button.click()
    save_response = save_response_info.value
    assert save_response.status < 400, f"Save failed HTTP {save_response.status}"
    desktop_page.wait_for_load_state("networkidle")

    expect(host_input).to_have_value(TEST_SMTP_HOST, timeout=15_000)
    desktop_page.screenshot(path=str(EVIDENCE / "email-delivery-after-save.png"), full_page=True)

    desktop_page.reload(wait_until="networkidle")
    expect(_smtp_host_input(desktop_page)).to_have_value(TEST_SMTP_HOST, timeout=15_000)
    desktop_page.screenshot(path=str(EVIDENCE / "email-delivery-after-reload.png"), full_page=True)

    validate_button = desktop_page.get_by_role("button", name=re.compile(r"^Validate configuration$", re.I))
    with desktop_page.expect_response(
        lambda r: r.request.method == "POST" and "/admin/settings/email/validate" in r.url,
        timeout=30_000,
    ):
        validate_button.click()
    expect(
        desktop_page.locator("section").filter(has_text="Diagnostics").locator("p.bg-surface").last
    ).to_contain_text(
        re.compile(
            r"Configuration valid|Admin mail delivery|SMTP port is required|SMTP username",
            re.I,
        ),
        timeout=20_000,
    )

    preview_button = desktop_page.get_by_role("button", name=re.compile(r"^Preview verification email$", re.I))
    with desktop_page.expect_response(
        lambda r: r.request.method == "POST" and "/admin/settings/email/preview-verification" in r.url,
        timeout=30_000,
    ):
        preview_button.click()
    expect(
        desktop_page.get_by_text(
            re.compile(r"Verification anchor|Verification link was not detected|Link rendering check", re.I)
        ).first
    ).to_be_visible(timeout=20_000)
