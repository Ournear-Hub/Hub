from __future__ import annotations

import json
import shutil
import subprocess
import tempfile
from pathlib import Path

import pytest
from playwright.sync_api import Page, expect

from ournear_qa.journey.admin_application import login_admin

REPO_ROOT = Path(__file__).resolve().parents[3]


@pytest.fixture
def desktop_page(page: Page) -> Page:
    page.set_viewport_size({"width": 1280, "height": 900})
    return page


def _require_admin_creds(qa_settings) -> tuple[str, str]:
    email = (qa_settings.qa_admin_email or "").strip()
    password = (qa_settings.qa_admin_password or "").strip()
    if not email or not password:
        pytest.skip("OURNEAR_QA_ADMIN_EMAIL / OURNEAR_QA_ADMIN_PASSWORD required")
    return email, password


def _build_industry_workbook(name: str) -> Path:
    php = shutil.which("php")
    script = REPO_ROOT / "tests" / "bin" / "generate-master-data-xlsx.php"
    if php is None or not script.is_file():
        pytest.skip("PHP CLI and generate-master-data-xlsx.php required")

    out = Path(tempfile.gettempdir()) / f"md-excel-{name.replace(' ', '-')}.xlsx"
    rows = json.dumps(
        [
            {
                "id": "",
                "name": name,
                "description": "Browser QA import",
                "sort_order": "77",
                "is_active": "yes",
            }
        ]
    )
    completed = subprocess.run(
        [php, str(script), "industries", str(out), rows],
        cwd=REPO_ROOT,
        capture_output=True,
        text=True,
        check=False,
    )
    if completed.returncode != 0 or not out.is_file():
        pytest.skip(f"Could not generate workbook: {completed.stderr or completed.stdout}")
    return out


@pytest.mark.phase5c
def test_p5c_master_data_excel_panel_and_downloads(desktop_page: Page, base_url: str, qa_settings) -> None:
    email, password = _require_admin_creds(qa_settings)
    login_admin(desktop_page, base_url, email, password)
    desktop_page.goto(f"{base_url}/admin/master-data?tab=industries", wait_until="networkidle")

    expect(desktop_page.get_by_role("heading", name="Master Data")).to_be_visible(timeout=30_000)
    expect(desktop_page.get_by_text("Excel import / export")).to_be_visible()

    template_resp = desktop_page.request.get(f"{base_url}/admin/master-data/industries/excel/template")
    assert template_resp.ok, template_resp.status
    assert "spreadsheet" in (template_resp.headers.get("content-type") or "")

    export_resp = desktop_page.request.get(f"{base_url}/admin/master-data/industries/excel/export")
    assert export_resp.ok, export_resp.status
    assert "spreadsheet" in (export_resp.headers.get("content-type") or "")


@pytest.mark.phase5c
def test_p5c_master_data_excel_import_preview_confirm(desktop_page: Page, base_url: str, qa_settings) -> None:
    email, password = _require_admin_creds(qa_settings)
    unique_name = "P5C Browser Excel Industry"
    workbook = _build_industry_workbook(unique_name)

    login_admin(desktop_page, base_url, email, password)
    desktop_page.goto(f"{base_url}/admin/master-data?tab=industries", wait_until="networkidle")

    desktop_page.locator("#master-data-import-file").set_input_files(str(workbook))
    desktop_page.get_by_role("button", name="Preview import").click()
    desktop_page.wait_for_load_state("networkidle")
    page_text = desktop_page.content().lower()
    if "unable to create a temporary file" in page_text or "failed to upload" in page_text:
        pytest.skip("PHP multipart upload blocked in local env (UPLOAD_ERR_NO_TMP_DIR; see C3 upload findings)")

    expect(desktop_page.get_by_text("Preview:", exact=False)).to_be_visible(timeout=30_000)
    expect(desktop_page.get_by_text("1 create", exact=False)).to_be_visible()
    desktop_page.get_by_role("button", name="Confirm import").click()
    desktop_page.wait_for_load_state("networkidle")

    expect(desktop_page.get_by_text("Import complete:", exact=False)).to_be_visible(timeout=30_000)
    desktop_page.reload(wait_until="networkidle")
    expect(desktop_page.get_by_text(unique_name)).to_be_visible(timeout=30_000)

    desktop_page.locator("#master-data-import-file").set_input_files(str(workbook))
    desktop_page.get_by_role("button", name="Preview import").click()
    desktop_page.wait_for_load_state("networkidle")
    expect(desktop_page.get_by_text("0 create", exact=False)).to_be_visible(timeout=30_000)
    expect(desktop_page.get_by_text("1 unchanged", exact=False)).to_be_visible(timeout=30_000)


@pytest.mark.phase5c
def test_p5c_master_data_excel_invalid_upload_shows_error(desktop_page: Page, base_url: str, qa_settings) -> None:
    email, password = _require_admin_creds(qa_settings)
    bad = Path(tempfile.gettempdir()) / "bad-master-upload.txt"
    bad.write_text("not excel", encoding="utf-8")

    login_admin(desktop_page, base_url, email, password)
    desktop_page.goto(f"{base_url}/admin/master-data?tab=industries", wait_until="networkidle")

    desktop_page.locator("#master-data-import-file").set_input_files(str(bad))
    desktop_page.get_by_role("button", name="Preview import").click()
    desktop_page.wait_for_load_state("networkidle")
    page_text = desktop_page.content().lower()
    if "unable to create a temporary file" in page_text or "failed to upload" in page_text:
        pytest.skip("PHP multipart upload blocked in local env (UPLOAD_ERR_NO_TMP_DIR; see C3 upload findings)")

    expect(
        desktop_page.get_by_text("must be a file of type", exact=False).or_(
            desktop_page.get_by_text("Upload an Excel workbook", exact=False)
        )
    ).to_be_visible(timeout=30_000)
    expect(desktop_page.get_by_role("button", name="Confirm import")).to_have_count(0)
