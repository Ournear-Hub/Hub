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
RUN_NOTE = "20261011T091800Z-upload-c3-reg-md-ext"


@pytest.fixture
def desktop_page(page: Page) -> Page:
    page.set_viewport_size({"width": 1280, "height": 900})
    return page


def _laravel_form_post(page: Page, url: str, form_data: dict[str, str]) -> dict:
    """POST with Laravel CSRF using the page's cookie jar (APIRequest alone often 419s)."""
    return page.evaluate(
        """async ({ url, formData }) => {
            const body = new URLSearchParams(formData);
            const match = document.cookie.split('; ').find((row) => row.startsWith('XSRF-TOKEN='));
            const xsrf = match ? decodeURIComponent(match.split('=').slice(1).join('=')) : '';
            const response = await fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-XSRF-TOKEN': xsrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body,
            });
            return { status: response.status, text: await response.text() };
        }""",
        {"url": url, "formData": form_data},
    )


def _admin(qa_settings) -> tuple[str, str]:
    email = (qa_settings.qa_admin_email or "").strip()
    password = (qa_settings.qa_admin_password or "").strip()
    if not email or not password:
        pytest.skip("Admin QA credentials required")
    return email, password


def _workbook(type_slug: str, rows: list[dict]) -> Path:
    php = shutil.which("php")
    script = REPO_ROOT / "tests" / "bin" / "generate-master-data-xlsx.php"
    out = Path(tempfile.gettempdir()) / f"reg-md-ext-{type_slug}-{int(time.time())}.xlsx"
    completed = subprocess.run(
        [php, str(script), type_slug, str(out), json.dumps(rows)],
        cwd=REPO_ROOT,
        capture_output=True,
        text=True,
        check=False,
    )
    if completed.returncode != 0:
        pytest.skip(completed.stderr)
    return out


def _ui_import_industry_like(page: Page, base_url: str, tab: str, workbook: Path, unique_marker: str) -> None:
    page.goto(f"{base_url}/admin/master-data?tab={tab}", wait_until="networkidle")
    page.locator("#master-data-import-file").set_input_files(str(workbook))
    page.get_by_role("button", name="Preview import").click()
    page.wait_for_load_state("networkidle")
    if page.get_by_text("failed to upload", exact=False).count() > 0:
        pytest.fail("BLOCKED: multipart upload — use scripts/start-local-web-server.ps1")
    expect(page.get_by_text("Preview:", exact=False)).to_be_visible(timeout=30_000)
    page.get_by_role("button", name="Confirm import").click()
    page.wait_for_load_state("networkidle")
    expect(page.get_by_text("Import complete:", exact=False)).to_be_visible(timeout=30_000)
    page.reload(wait_until="networkidle")
    expect(page.get_by_text(unique_marker)).to_be_visible(timeout=30_000)


@pytest.mark.reg_md_001_ext
def test_reg_md_ext_categories_and_job_titles_hierarchical(desktop_page: Page, base_url: str, qa_settings) -> None:
    admin_email, admin_password = _admin(qa_settings)
    login_admin(desktop_page, base_url, admin_email, admin_password)

    cat_name = f"REG-MD Cat {int(time.time())}"
    cat_wb = _workbook(
        "categories",
        [
            {
                "id": "",
                "name": cat_name,
                "description": "",
                "industry_name": "Mining",
                "sort_order": "12",
                "is_active": "yes",
            }
        ],
    )
    _ui_import_industry_like(desktop_page, base_url, "categories", cat_wb, cat_name)

    title_name = f"REG-MD Title {int(time.time())}"
    title_wb = _workbook(
        "job-titles",
        [
            {
                "id": "",
                "name": title_name,
                "description": "",
                "job_category_name": cat_name,
                "sort_order": "3",
                "is_active": "yes",
            }
        ],
    )
    _ui_import_industry_like(desktop_page, base_url, "job-titles", title_wb, title_name)

    title_wb2 = _workbook(
        "job-titles",
        [
            {
                "id": "",
                "name": title_name,
                "description": "",
                "job_category_name": cat_name,
                "sort_order": "3",
                "is_active": "yes",
            }
        ],
    )
    desktop_page.goto(f"{base_url}/admin/master-data?tab=job-titles", wait_until="networkidle")
    desktop_page.locator("#master-data-import-file").set_input_files(str(title_wb2))
    desktop_page.get_by_role("button", name="Preview import").click()
    desktop_page.wait_for_load_state("networkidle")
    expect(desktop_page.get_by_text("1 unchanged", exact=False)).to_be_visible(timeout=30_000)


@pytest.mark.reg_md_001_ext
def test_reg_md_ext_token_lifecycle_http(desktop_page: Page, base_url: str, qa_settings) -> None:
    admin_email, admin_password = _admin(qa_settings)
    login_admin(desktop_page, base_url, admin_email, admin_password)

    unique = f"REG-MD Token Row {int(time.time())}"
    wb = _workbook(
        "industries",
        [{"id": "", "name": unique, "description": "", "sort_order": "1", "is_active": "yes"}],
    )
    desktop_page.goto(f"{base_url}/admin/master-data?tab=industries", wait_until="networkidle")
    desktop_page.locator("#master-data-import-file").set_input_files(str(wb))
    desktop_page.get_by_role("button", name="Preview import").click()
    desktop_page.wait_for_load_state("networkidle")
    php = shutil.which("php")
    token_out = subprocess.run(
        [php, str(REPO_ROOT / "scripts" / "qa-latest-master-import-token.php"), "industries", admin_email],
        cwd=REPO_ROOT,
        capture_output=True,
        text=True,
        check=False,
    )
    assert token_out.returncode == 0, token_out.stderr
    token = token_out.stdout.strip()

    confirm_url = f"{base_url}/admin/master-data/industries/excel/confirm"
    dup = _laravel_form_post(desktop_page, confirm_url, {"token": token})
    assert dup["status"] in (200, 302)

    dup2 = _laravel_form_post(desktop_page, confirm_url, {"token": token})
    assert dup2["status"] in (302, 422)

    wb2 = _workbook(
        "industries",
        [{"id": "", "name": f"{unique} B", "description": "", "sort_order": "2", "is_active": "yes"}],
    )
    desktop_page.locator("#master-data-import-file").set_input_files(str(wb2))
    desktop_page.get_by_role("button", name="Preview import").click()
    desktop_page.wait_for_load_state("networkidle")
    token_b_out = subprocess.run(
        [php, str(REPO_ROOT / "scripts" / "qa-latest-master-import-token.php"), "industries", admin_email],
        cwd=REPO_ROOT,
        capture_output=True,
        text=True,
        check=False,
    )
    token_b = token_b_out.stdout.strip()
    assert len(token_b) == 64

    employer_email = (qa_settings.qa_employer_a_email or "").strip()
    if employer_email:
        subprocess.run(
            [
                php,
                str(REPO_ROOT / "scripts" / "qa-master-data-preview-fixture.php"),
                "reassign-user",
                token_b,
                employer_email,
            ],
            cwd=REPO_ROOT,
            check=True,
        )
        cross = _laravel_form_post(desktop_page, confirm_url, {"token": token_b})
        assert cross["status"] in (302, 422)

    wb3 = _workbook(
        "industries",
        [{"id": "", "name": f"{unique} Expire", "description": "", "sort_order": "9", "is_active": "yes"}],
    )
    desktop_page.locator("#master-data-import-file").set_input_files(str(wb3))
    desktop_page.get_by_role("button", name="Preview import").click()
    desktop_page.wait_for_load_state("networkidle")
    token_c_out = subprocess.run(
        [php, str(REPO_ROOT / "scripts" / "qa-latest-master-import-token.php"), "industries", admin_email],
        cwd=REPO_ROOT,
        capture_output=True,
        text=True,
        check=False,
    )
    token_c = token_c_out.stdout.strip()
    assert len(token_c) == 64
    subprocess.run(
        [php, str(REPO_ROOT / "scripts" / "qa-master-data-preview-fixture.php"), "expire", token_c],
        cwd=REPO_ROOT,
        check=True,
    )
    expired = _laravel_form_post(desktop_page, confirm_url, {"token": token_c})
    assert expired["status"] in (302, 422)

    bad = _laravel_form_post(desktop_page, confirm_url, {"token": "0" * 64})
    assert bad["status"] in (302, 422)


@pytest.mark.reg_md_001_ext
def test_reg_md_ext_upload_size_and_row_boundaries(desktop_page: Page, base_url: str, qa_settings) -> None:
    admin_email, admin_password = _admin(qa_settings)
    login_admin(desktop_page, base_url, admin_email, admin_password)

    oversize = Path(tempfile.gettempdir()) / "reg-md-oversize.xlsx"
    oversize.write_bytes(b"0" * (5121 * 1024))
    desktop_page.goto(f"{base_url}/admin/master-data?tab=industries", wait_until="networkidle")
    desktop_page.locator("#master-data-import-file").set_input_files(str(oversize))
    desktop_page.get_by_role("button", name="Preview import").click()
    desktop_page.wait_for_load_state("networkidle")
    over_rejected = (
        desktop_page.get_by_text("5 MB", exact=False).count() > 0
        or desktop_page.get_by_text("too large", exact=False).count() > 0
        or desktop_page.locator("text=Preview:").count() == 0
    )
    assert over_rejected

    php = shutil.which("php")
    big = Path(tempfile.gettempdir()) / "reg-md-2001-rows.xlsx"
    completed = subprocess.run(
        [php, str(REPO_ROOT / "tests" / "bin" / "generate-master-data-xlsx-rows.php"), "industries", str(big), "2001"],
        cwd=REPO_ROOT,
        capture_output=True,
        text=True,
        check=False,
    )
    if completed.returncode != 0:
        pytest.skip("Could not generate 2001-row workbook")
    desktop_page.locator("#master-data-import-file").set_input_files(str(big))
    desktop_page.get_by_role("button", name="Preview import").click()
    desktop_page.wait_for_load_state("networkidle")
    expect(desktop_page.get_by_text("2000", exact=False).or_(desktop_page.get_by_text("limit", exact=False))).to_be_visible(
        timeout=30_000
    )
