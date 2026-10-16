from __future__ import annotations

import json
import re
import shutil
import subprocess
import tempfile
import time
from pathlib import Path

import pytest
from playwright.sync_api import Page, expect

from ournear_qa.journey.admin_application import login_admin
from ournear_qa.journey.account_auth import login_account_user

REPO_ROOT = Path(__file__).resolve().parents[3]
RUN_ID = "20261011T154500Z-reg-md-001-browser"
ENTITY_SLUGS = [
    "industries",
    "categories",
    "job-titles",
    "locations",
    "employment-types",
    "experience-ranges",
    "qualifications",
    "job-statuses",
    "application-statuses",
    "rejection-reasons",
]


def _upload_env_blocked(body: str, status: int) -> bool:
    lower = body.lower()
    return (
        "unable to create a temporary file" in lower
        or "failed to upload" in lower
        or (status == 419 and "page expired" in lower)
    )


def _require_admin(qa_settings) -> tuple[str, str]:
    email = (qa_settings.qa_admin_email or "").strip()
    password = (qa_settings.qa_admin_password or "").strip()
    if not email or not password:
        pytest.skip("OURNEAR_QA_ADMIN_EMAIL / OURNEAR_QA_ADMIN_PASSWORD required")
    return email, password


def _build_industry_workbook(name: str) -> Path:
    php = shutil.which("php")
    script = REPO_ROOT / "tests" / "bin" / "generate-master-data-xlsx.php"
    if php is None or not script.is_file():
        pytest.skip("PHP workbook generator unavailable")
    out = Path(tempfile.gettempdir()) / f"reg-md-001-{name.replace(' ', '-')}.xlsx"
    rows = json.dumps(
        [
            {
                "id": "",
                "name": name,
                "description": "REG-MD-001 browser QA",
                "sort_order": "88",
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
        pytest.skip(f"Workbook generation failed: {completed.stderr}")
    return out


def _read_xlsx_headers(path: Path) -> list[str]:
    php = shutil.which("php")
    script = REPO_ROOT / "scripts" / "qa-read-xlsx-headers.php"
    completed = subprocess.run(
        [php, str(script), str(path)],
        cwd=REPO_ROOT,
        capture_output=True,
        text=True,
        check=False,
    )
    if completed.returncode != 0:
        raise AssertionError(completed.stderr or completed.stdout)
    payload = json.loads(completed.stdout)
    return [str(h).strip().lower() for h in payload.get("headers", []) if str(h).strip()]


@pytest.fixture
def desktop_page(page: Page) -> Page:
    page.set_viewport_size({"width": 1280, "height": 900})
    return page


@pytest.mark.reg_md_001
def test_md001_a_navigation_panel_and_export_content(desktop_page: Page, base_url: str, qa_settings) -> None:
    admin_email, admin_password = _require_admin(qa_settings)
    login_admin(desktop_page, base_url, admin_email, admin_password)
    desktop_page.goto(f"{base_url}/admin/master-data?tab=industries", wait_until="networkidle")

    expect(desktop_page.get_by_role("heading", name="Master Data")).to_be_visible(timeout=30_000)
    expect(desktop_page.get_by_text("Excel import / export")).to_be_visible()
    expect(desktop_page.get_by_role("link", name="Download template")).to_be_visible()
    expect(desktop_page.get_by_role("link", name="Export industries")).to_be_visible()

    export_resp = desktop_page.request.get(f"{base_url}/admin/master-data/industries/excel/export")
    assert export_resp.ok
    assert "spreadsheet" in (export_resp.headers.get("content-type") or "")

    export_path = Path(tempfile.gettempdir()) / "reg-md-001-export-industries.xlsx"
    export_path.write_bytes(export_resp.body())
    headers = _read_xlsx_headers(export_path)
    for expected in ("id", "name", "description", "sort_order", "is_active"):
        assert expected in headers, headers

    template_resp = desktop_page.request.get(f"{base_url}/admin/master-data/locations/excel/template")
    assert template_resp.ok
    template_path = Path(tempfile.gettempdir()) / "reg-md-001-template-locations.xlsx"
    template_path.write_bytes(template_resp.body())
    template_headers = _read_xlsx_headers(template_path)
    assert "id" in template_headers and "name" in template_headers


@pytest.mark.reg_md_001
def test_md001_a_template_export_all_entity_types(desktop_page: Page, base_url: str, qa_settings) -> None:
    admin_email, admin_password = _require_admin(qa_settings)
    login_admin(desktop_page, base_url, admin_email, admin_password)

    for slug in ENTITY_SLUGS:
        template = desktop_page.request.get(f"{base_url}/admin/master-data/{slug}/excel/template")
        assert template.ok, slug
        export = desktop_page.request.get(f"{base_url}/admin/master-data/{slug}/excel/export")
        assert export.ok, slug


@pytest.mark.reg_md_001
def test_md001_b_ui_upload_preview_confirm_or_env_block(desktop_page: Page, base_url: str, qa_settings) -> None:
    admin_email, admin_password = _require_admin(qa_settings)
    unique = f"REG-MD-001 Industry {int(time.time())}"
    workbook = _build_industry_workbook(unique)

    login_admin(desktop_page, base_url, admin_email, admin_password)
    desktop_page.goto(f"{base_url}/admin/master-data?tab=industries", wait_until="networkidle")

    desktop_page.locator("#master-data-import-file").set_input_files(str(workbook))
    desktop_page.get_by_role("button", name="Preview import").click()
    desktop_page.wait_for_load_state("networkidle")

    if desktop_page.get_by_text("failed to upload", exact=False).count() > 0:
        pytest.skip(
            "BLOCKED: browser multipart upload — PHP UPLOAD_ERR_NO_TMP_DIR on Windows artisan serve "
            "(see qa-agent/artifacts/agent2/20261011T120000Z-c3-verify/environment_upload_findings.md)"
        )

    expect(desktop_page.get_by_text("Preview:", exact=False)).to_be_visible(timeout=30_000)
    expect(desktop_page.get_by_text("1 create", exact=False)).to_be_visible()
    desktop_page.get_by_role("button", name="Confirm import").click()
    desktop_page.wait_for_load_state("networkidle")
    expect(desktop_page.get_by_text("Import complete:", exact=False)).to_be_visible(timeout=30_000)
    desktop_page.reload(wait_until="networkidle")
    expect(desktop_page.get_by_text(unique)).to_be_visible(timeout=30_000)

    workbook2 = _build_industry_workbook(unique)
    desktop_page.locator("#master-data-import-file").set_input_files(str(workbook2))
    desktop_page.get_by_role("button", name="Preview import").click()
    desktop_page.wait_for_load_state("networkidle")
    expect(desktop_page.get_by_text("unchanged", exact=False).first).to_be_visible(timeout=30_000)


@pytest.mark.reg_md_001
def test_md001_d_invalid_file_ui_feedback(desktop_page: Page, base_url: str, qa_settings) -> None:
    admin_email, admin_password = _require_admin(qa_settings)
    bad = Path(tempfile.gettempdir()) / "reg-md-001-bad.txt"
    bad.write_text("not an excel file", encoding="utf-8")

    login_admin(desktop_page, base_url, admin_email, admin_password)
    desktop_page.goto(f"{base_url}/admin/master-data?tab=industries", wait_until="networkidle")

    desktop_page.locator("#master-data-import-file").set_input_files(str(bad))
    desktop_page.get_by_role("button", name="Preview import").click()
    desktop_page.wait_for_load_state("networkidle")

    if desktop_page.get_by_text("failed to upload", exact=False).count() > 0:
        pytest.skip("BLOCKED: invalid-file UI test blocked by PHP multipart temp dir (same as C3)")

    expect(
        desktop_page.get_by_text("must be a file of type", exact=False).or_(
            desktop_page.get_by_text("Upload an Excel workbook", exact=False)
        )
    ).to_be_visible(timeout=30_000)


@pytest.mark.reg_md_001
def test_md001_c_unauthenticated_and_unauthorized(desktop_page: Page, base_url: str, qa_settings) -> None:
    desktop_page.context.clear_cookies()
    for path in (
        "/admin/master-data/industries/excel/template",
        "/admin/master-data/industries/excel/export",
    ):
        resp = desktop_page.request.get(f"{base_url}{path}", max_redirects=0)
        assert resp.status in (401, 403, 302), f"{path} -> {resp.status}"

    employer_email = (qa_settings.qa_employer_a_email or "").strip()
    employer_password = (qa_settings.qa_employer_a_password or "").strip()
    if not employer_email or not employer_password:
        pytest.skip("Employer QA creds not configured")

    desktop_page.context.clear_cookies()
    login_account_user(
        desktop_page,
        base_url,
        employer_email,
        employer_password,
        journey_id="reg-md-001",
        step_id="employer-login",
    )
    denied = desktop_page.request.get(f"{base_url}/admin/master-data/industries/excel/export")
    assert denied.status in (403, 302, 401), denied.status
