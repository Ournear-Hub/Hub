"""REG-COM-001 — Admin message templates browser acceptance (Stage 6)."""

from __future__ import annotations

import json
import re
import shutil
import socket
import subprocess
import time
from pathlib import Path

import pytest
from playwright.sync_api import Page, expect

from ournear_qa.evidence import ARTIFACTS_DIR
from ournear_qa.journey.admin_application import login_admin
from ournear_qa.journey.account_auth import login_account_user

REPO_ROOT = Path(__file__).resolve().parents[3]
RUN_ID = "20261011T152900Z-reg-com-001-browser"
EVIDENCE_ROOT = ARTIFACTS_DIR / "agent2" / RUN_ID
PHP_BIN = shutil.which("php")

pytestmark = [pytest.mark.reg_com_001]


def _require_admin(qa_settings) -> tuple[str, str]:
    email = (qa_settings.qa_admin_email or "").strip()
    password = (qa_settings.qa_admin_password or "").strip()
    if not email or not password:
        pytest.skip("OURNEAR_QA_ADMIN_EMAIL / OURNEAR_QA_ADMIN_PASSWORD required")
    return email, password


def _require_mysql_and_server(base_url: str) -> None:
    if PHP_BIN is None:
        pytest.skip("PHP not on PATH")
    port_open = False
    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as sock:
        sock.settimeout(2)
        try:
            port_open = sock.connect_ex(("127.0.0.1", 3306)) == 0
        except OSError:
            port_open = False
    if not port_open:
        pytest.skip(
            "BLOCKED: MySQL not listening on 127.0.0.1:3306 (start MySQL84 as administrator)."
        )
    probe = subprocess.run(
        [PHP_BIN, str(REPO_ROOT / "scripts" / "probe_local_mysql.php")],
        cwd=REPO_ROOT,
        capture_output=True,
        text=True,
        check=False,
    )
    if probe.returncode != 0 or "CONNECTED" not in probe.stdout:
        pytest.skip(
            "BLOCKED: local MySQL unavailable (probe_local_mysql.php). "
            "Start MySQL84 as administrator before browser QA."
        )
    try:
        import urllib.request

        urllib.request.urlopen(base_url + "/", timeout=15)
    except Exception as exc:
        pytest.skip(
            f"BLOCKED: local web server not reachable at {base_url} ({exc}). "
            "Run scripts/start-local-web-server.ps1"
        )


def _shot(page: Page, name: str) -> str:
    EVIDENCE_ROOT.mkdir(parents=True, exist_ok=True)
    path = EVIDENCE_ROOT / name
    page.screenshot(path=str(path), full_page=True)
    return path.relative_to(ARTIFACTS_DIR).as_posix()


def _probe_template(template_key: str) -> dict[str, str]:
    completed = subprocess.run(
        [PHP_BIN, str(REPO_ROOT / "scripts" / "qa-message-template-probe.php"), template_key],
        cwd=REPO_ROOT,
        capture_output=True,
        text=True,
        check=False,
    )
    fields: dict[str, str] = {}
    for line in completed.stdout.splitlines():
        if "=" in line:
            key, value = line.split("=", 1)
            fields[key.strip()] = value.strip()
    return fields


def _template_section(page: Page, key_fragment: str):
    """Template rows render as Admin `Card` → <section class=\"rounded-xl ...\">."""
    return page.locator("section.rounded-xl").filter(has_text=key_fragment).first


def _filter_bar_selects(page: Page):
    return page.locator("div.grid.gap-3.md\\:grid-cols-4").first.locator("select")


def _open_message_templates(page: Page, base_url: str, qa_settings) -> None:
    _require_mysql_and_server(base_url)
    admin_email, admin_password = _require_admin(qa_settings)
    login_admin(page, base_url, admin_email, admin_password)
    page.goto(f"{base_url}/admin/message-templates", wait_until="networkidle")
    expect(page.get_by_role("heading", name="Message templates")).to_be_visible(timeout=30_000)


@pytest.fixture
def desktop_page(page: Page) -> Page:
    page.set_viewport_size({"width": 1280, "height": 900})
    return page


def test_com_001_page_load_and_communications_nav(
    desktop_page: Page, base_url: str, qa_settings
) -> None:
    _open_message_templates(desktop_page, base_url, qa_settings)
    expect(desktop_page.get_by_text("Central registry for recruitment")).to_be_visible()
    expect(desktop_page.get_by_role("link", name="Message templates")).to_be_visible()
    _shot(desktop_page, "COM-001-01-page-load.png")


def test_com_001_search_filters_and_listing(
    desktop_page: Page, base_url: str, qa_settings
) -> None:
    _open_message_templates(desktop_page, base_url, qa_settings)
    expect(desktop_page.get_by_text("Application received", exact=False).first).to_be_visible()
    search = desktop_page.get_by_role("searchbox", name="Search templates")
    search.fill("rejected")
    search.press("Enter")
    desktop_page.wait_for_load_state("networkidle")
    expect(desktop_page.get_by_text("rejected", exact=False).first).to_be_visible(timeout=15_000)
    _filter_bar_selects(desktop_page).nth(0).select_option("email")
    desktop_page.wait_for_load_state("networkidle")
    _shot(desktop_page, "COM-001-02-filters.png")


def test_com_001_create_edit_activate_preview_and_audit(
    desktop_page: Page, base_url: str, qa_settings
) -> None:
    _open_message_templates(desktop_page, base_url, qa_settings)
    unique = f"recruitment.qa.browser.{int(time.time())}.in_app"
    name = f"QA Browser Template {int(time.time())}"

    desktop_page.get_by_placeholder("Stable key (e.g. recruitment.custom.in_app)").fill(unique)
    desktop_page.get_by_placeholder("Name").fill(name)
    desktop_page.get_by_placeholder("Body").fill("Hello {{candidate_name}} for {{job_title}}.")
    desktop_page.get_by_role("button", name="Create template").click()
    desktop_page.wait_for_load_state("networkidle")

    probe = _probe_template(unique)
    assert probe.get("found") == "1", probe
    assert probe.get("channel") == "in_app"
    assert probe.get("audience") == "candidate"
    assert probe.get("is_active") == "1"

    card = _template_section(desktop_page, unique)
    card.scroll_into_view_if_needed()
    card.get_by_role("button", name="Edit").click()
    card.locator("form").locator("textarea").last.fill("Updated {{candidate_name}} — {{job_title}}.")
    card.get_by_role("button", name="Save changes").click()
    desktop_page.wait_for_load_state("networkidle")

    probe2 = _probe_template(unique)
    assert probe2.get("body_hash") != probe.get("body_hash")
    assert any(v.startswith("updated@") for k, v in probe2.items() if k.startswith("audit_"))

    card.get_by_role("button", name="Deactivate").click()
    desktop_page.wait_for_load_state("networkidle")
    assert _probe_template(unique).get("is_active") == "0"

    card.get_by_role("button", name="Activate").click()
    desktop_page.wait_for_load_state("networkidle")
    assert _probe_template(unique).get("is_active") == "1"

    with desktop_page.expect_response(
        lambda r: r.request.method == "POST" and "/admin/message-templates/preview" in r.url,
        timeout=30_000,
    ):
        card.get_by_role("button", name="Preview", exact=True).click()
    expect(desktop_page.get_by_role("heading", name="Preview (not sent)")).to_be_visible(timeout=10_000)
    expect(desktop_page.get_by_text("Sample Candidate")).to_be_visible()
    expect(desktop_page.get_by_text("Site Engineer")).to_be_visible()
    _shot(desktop_page, "COM-001-03-create-edit-preview.png")


def test_com_001_invalid_placeholder_rejected_in_ui(
    desktop_page: Page, base_url: str, qa_settings
) -> None:
    _open_message_templates(desktop_page, base_url, qa_settings)
    card = _template_section(desktop_page, "recruitment.application_rejected.in_app")
    card.get_by_role("button", name="Edit").click()
    textarea = card.locator("form").locator("textarea").last
    textarea.fill("Invalid {{secret_token}} placeholder.")
    card.get_by_role("button", name="Save changes").click()
    desktop_page.wait_for_load_state("networkidle")
    expect(card.get_by_text(re.compile(r"Unsupported variable", re.I))).to_be_visible(timeout=15_000)
    _shot(desktop_page, "COM-001-04-invalid-placeholder.png")


def test_com_001_email_preview_substitutes_supported_variables(
    desktop_page: Page, base_url: str, qa_settings
) -> None:
    _open_message_templates(desktop_page, base_url, qa_settings)
    card = _template_section(desktop_page, "recruitment.application_rejected.email")
    with desktop_page.expect_response(
        lambda r: r.request.method == "POST" and "/admin/message-templates/preview" in r.url,
        timeout=30_000,
    ):
        card.get_by_role("button", name="Preview", exact=True).click()
    expect(desktop_page.get_by_role("heading", name="Preview (not sent)")).to_be_visible(timeout=10_000)
    expect(desktop_page.get_by_text("Sample Candidate").or_(desktop_page.get_by_text("Site Engineer"))).to_be_visible()
    _shot(desktop_page, "COM-001-05-email-preview.png")


def test_com_001_system_required_templates_no_deactivate_control(
    desktop_page: Page, base_url: str, qa_settings
) -> None:
    _open_message_templates(desktop_page, base_url, qa_settings)
    system_card = _template_section(desktop_page, "recruitment.application_received.in_app")
    expect(system_card.get_by_text("System required")).to_be_visible()
    expect(system_card.get_by_role("button", name="Deactivate")).to_have_count(0)
    _shot(desktop_page, "COM-001-06-system-required.png")


def test_com_001_unauthorized_access_blocked(
    desktop_page: Page, base_url: str, qa_settings
) -> None:
    _require_mysql_and_server(base_url)
    desktop_page.context.clear_cookies()
    guest = desktop_page.request.get(f"{base_url}/admin/message-templates", max_redirects=0)
    assert guest.status in (302, 401, 403)

    employer_email = (qa_settings.qa_employer_a_email or "").strip()
    employer_password = (qa_settings.qa_employer_a_password or "").strip()
    if employer_email and employer_password:
        desktop_page.context.clear_cookies()
        login_account_user(
            desktop_page,
            base_url,
            employer_email,
            employer_password,
            journey_id="reg-com-001",
            step_id="employer-login",
        )
        denied = desktop_page.request.get(f"{base_url}/admin/message-templates")
        assert denied.status in (403, 302, 401), denied.status


def test_com_001_inactive_custom_template_preview_still_renders(
    desktop_page: Page, base_url: str, qa_settings
) -> None:
    """Inactive optional template: preview still renders sample copy (safe admin preview path)."""
    _open_message_templates(desktop_page, base_url, qa_settings)
    unique = f"recruitment.qa.inactive.{int(time.time())}.in_app"
    desktop_page.get_by_placeholder("Stable key (e.g. recruitment.custom.in_app)").fill(unique)
    desktop_page.get_by_placeholder("Name").fill("Inactive QA")
    desktop_page.get_by_placeholder("Body").fill("Inactive body {{candidate_name}}.")
    desktop_page.get_by_role("button", name="Create template").click()
    desktop_page.wait_for_load_state("networkidle")

    card = _template_section(desktop_page, unique)
    card.scroll_into_view_if_needed()
    card.get_by_role("button", name="Deactivate").click()
    desktop_page.wait_for_load_state("networkidle")
    with desktop_page.expect_response(
        lambda r: r.request.method == "POST" and "/admin/message-templates/preview" in r.url,
        timeout=30_000,
    ):
        card.get_by_role("button", name="Preview", exact=True).click()
    expect(desktop_page.get_by_role("heading", name="Preview (not sent)")).to_be_visible(timeout=10_000)
    expect(desktop_page.get_by_text("Sample Candidate")).to_be_visible(timeout=10_000)
    _shot(desktop_page, "COM-001-07-inactive-preview.png")
