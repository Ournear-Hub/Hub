from __future__ import annotations

import tempfile
import time
from pathlib import Path

import pytest
from playwright.sync_api import Page, expect

from ournear_qa.journey.account_auth import login_account_user

REPO_ROOT = Path(__file__).resolve().parents[3]

MINIMAL_PNG = bytes.fromhex(
    "89504e470d0a1a0a0000000d49484452000000010000000108060000001f15c489"
    "0000000a49444154789c6300010000050001000d0a2db40000000049454e44ae426082"
)


@pytest.fixture
def desktop_page(page: Page) -> Page:
    page.set_viewport_size({"width": 1280, "height": 900})
    return page


def _employer_creds(qa_settings) -> tuple[str, str]:
    email = (qa_settings.qa_employer_a_email or "").strip()
    password = (qa_settings.qa_employer_a_password or "").strip()
    if not email or not password:
        pytest.skip("Employer A QA credentials required")
    return email, password


def _employer_b_creds(qa_settings) -> tuple[str, str]:
    email = (qa_settings.qa_employer_b_email or "").strip()
    password = (qa_settings.qa_employer_b_password or "").strip()
    if not email or not password:
        pytest.skip("Employer B QA credentials required")
    return email, password


@pytest.mark.c3_upload
def test_c3_company_logo_upload_persist_and_invalid_pdf(desktop_page: Page, base_url: str, qa_settings) -> None:
    email, password = _employer_creds(qa_settings)
    login_account_user(desktop_page, base_url, email, password, journey_id="c3", step_id="employer-login")
    desktop_page.goto(f"{base_url}/account/profile", wait_until="networkidle")
    desktop_page.get_by_role("button", name="Edit company profile").click()

    png = Path(tempfile.gettempdir()) / f"c3-logo-{int(time.time())}.png"
    png.write_bytes(MINIMAL_PNG)
    desktop_page.locator("#employer-logo").set_input_files(str(png))
    desktop_page.get_by_role("button", name="Save").click()
    desktop_page.wait_for_load_state("networkidle")

    logo_img = desktop_page.locator("#basic img")

    expect(logo_img).to_be_visible(timeout=30_000)

    desktop_page.reload(wait_until="networkidle")
    desktop_page.get_by_role("button", name="Edit company profile").click()
    expect(logo_img).to_be_visible(timeout=30_000)

    pdf = Path(tempfile.gettempdir()) / "c3-invalid-logo.pdf"
    pdf.write_bytes(b"%PDF-1.4 invalid")
    desktop_page.locator("#employer-logo").set_input_files(str(pdf))
    desktop_page.get_by_role("button", name="Save").click()
    desktop_page.wait_for_load_state("networkidle")
    expect(desktop_page.get_by_text("photo", exact=False).or_(desktop_page.get_by_text("image", exact=False))).to_be_visible(
        timeout=15_000
    )
    expect(logo_img).to_be_visible()


@pytest.mark.c3_upload
def test_c3_company_document_upload_list_and_cross_employer_denied(
    desktop_page: Page, base_url: str, qa_settings
) -> None:
    email_a, password_a = _employer_creds(qa_settings)
    title = f"C3 Browser Doc {int(time.time())}"
    pdf = Path(tempfile.gettempdir()) / "c3-company-doc.pdf"
    pdf.write_bytes(b"%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF")

    login_account_user(desktop_page, base_url, email_a, password_a, journey_id="c3", step_id="employer-a")
    desktop_page.goto(f"{base_url}/account/company-documents", wait_until="networkidle")
    desktop_page.locator("#company-document-title").fill(title)
    desktop_page.locator("#company-document-file").set_input_files(str(pdf))
    desktop_page.get_by_role("button", name="Upload document").click()
    desktop_page.wait_for_load_state("networkidle")

    expect(desktop_page.get_by_text(title)).to_be_visible(timeout=30_000)
    desktop_page.reload(wait_until="networkidle")
    expect(desktop_page.get_by_text(title)).to_be_visible(timeout=30_000)

    href = desktop_page.get_by_role("link", name="Download").first.get_attribute("href")
    assert href and "/account/company-documents/" in href

    email_b, password_b = _employer_b_creds(qa_settings)
    desktop_page.context.clear_cookies()
    login_account_user(desktop_page, base_url, email_b, password_b, journey_id="c3", step_id="employer-b")
    denied = desktop_page.request.get(f"{base_url.rstrip('/')}{href}")
    assert denied.status in (403, 404, 302), denied.status
