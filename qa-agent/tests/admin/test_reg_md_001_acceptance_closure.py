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

REPO_ROOT = Path(__file__).resolve().parents[3]
RUN_ID = "20261011T094500Z-reg-md-001-acceptance-closure"


@pytest.fixture
def desktop_page(page: Page) -> Page:
    page.set_viewport_size({"width": 1280, "height": 900})
    return page


def _admin(qa_settings) -> tuple[str, str]:
    email = (qa_settings.qa_admin_email or "").strip()
    password = (qa_settings.qa_admin_password or "").strip()
    if not email or not password:
        pytest.skip("Admin QA credentials required")
    return email, password


def _workbook(type_slug: str, rows: list[dict]) -> Path:
    php = shutil.which("php")
    script = REPO_ROOT / "tests" / "bin" / "generate-master-data-xlsx.php"
    out = Path(tempfile.gettempdir()) / f"reg-md-acc-{type_slug}-{int(time.time())}.xlsx"
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


def _excel_panel(page: Page):
    return page.locator("section").filter(has=page.get_by_role("heading", name="Excel import / export"))


def _expect_preview_rejected_rows(page: Page) -> None:
    expect(_excel_panel(page).get_by_text(re.compile(r"\d+ rejected", re.I))).to_be_visible(timeout=30_000)


def _skip_if_upload_blocked(page: Page) -> None:
    if page.get_by_text("failed to upload", exact=False).count() > 0:
        pytest.skip(
            "BLOCKED: multipart upload — use scripts/start-local-web-server.ps1"
        )


@pytest.mark.reg_md_001_acceptance
def test_md001_guest_preview_and_confirm_denied(desktop_page: Page, base_url: str) -> None:
    desktop_page.context.clear_cookies()
    for path in (
        "/admin/master-data/industries/excel/preview",
        "/admin/master-data/industries/excel/confirm",
    ):
        resp = desktop_page.request.post(f"{base_url}{path}", max_redirects=0)
        assert resp.status in (401, 403, 302, 419), f"{path} -> {resp.status}"


@pytest.mark.reg_md_001_acceptance
def test_md001_invalid_foreign_key_and_missing_headers_ui(
    desktop_page: Page, base_url: str, qa_settings
) -> None:
    admin_email, admin_password = _admin(qa_settings)
    login_admin(desktop_page, base_url, admin_email, admin_password)

    bad_fk = _workbook(
        "categories",
        [
            {
                "id": "",
                "name": f"REG-MD Bad FK {int(time.time())}",
                "description": "",
                "industry_name": "Industry That Does Not Exist 99999",
                "sort_order": "1",
                "is_active": "yes",
            }
        ],
    )
    desktop_page.goto(f"{base_url}/admin/master-data?tab=categories", wait_until="networkidle")
    desktop_page.locator("#master-data-import-file").set_input_files(str(bad_fk))
    desktop_page.get_by_role("button", name="Preview import").click()
    desktop_page.wait_for_load_state("networkidle")
    _skip_if_upload_blocked(desktop_page)
    _expect_preview_rejected_rows(desktop_page)

    php = shutil.which("php")
    bad_headers = Path(tempfile.gettempdir()) / f"reg-md-bad-hdr-{int(time.time())}.xlsx"
    subprocess.run(
        [php, str(REPO_ROOT / "tests" / "bin" / "generate-master-data-xlsx-wrong-headers.php"), str(bad_headers)],
        cwd=REPO_ROOT,
        check=True,
    )
    desktop_page.goto(f"{base_url}/admin/master-data?tab=industries", wait_until="networkidle")
    desktop_page.locator("#master-data-import-file").set_input_files(str(bad_headers))
    desktop_page.get_by_role("button", name="Preview import").click()
    desktop_page.wait_for_load_state("networkidle")
    _skip_if_upload_blocked(desktop_page)
    expect(
        desktop_page.get_by_text("header", exact=False).or_(desktop_page.get_by_text("column", exact=False))
    ).to_be_visible(timeout=30_000)


@pytest.mark.reg_md_001_acceptance
def test_md001_industry_update_via_ui(desktop_page: Page, base_url: str, qa_settings) -> None:
    admin_email, admin_password = _admin(qa_settings)
    login_admin(desktop_page, base_url, admin_email, admin_password)

    unique = f"REG-MD Update {int(time.time())}"
    desc_v1 = "REG-MD browser update v1"
    create_wb = _workbook(
        "industries",
        [{"id": "", "name": unique, "description": desc_v1, "sort_order": "77", "is_active": "yes"}],
    )
    desktop_page.goto(f"{base_url}/admin/master-data?tab=industries", wait_until="networkidle")
    desktop_page.locator("#master-data-import-file").set_input_files(str(create_wb))
    desktop_page.get_by_role("button", name="Preview import").click()
    desktop_page.wait_for_load_state("networkidle")
    _skip_if_upload_blocked(desktop_page)
    desktop_page.get_by_role("button", name="Confirm import").click()
    desktop_page.wait_for_load_state("networkidle")

    php = shutil.which("php")
    id_out = subprocess.run(
        [php, str(REPO_ROOT / "scripts" / "qa-master-data-id-by-name.php"), "industries", unique],
        cwd=REPO_ROOT,
        capture_output=True,
        text=True,
        check=False,
    )
    assert id_out.returncode == 0, id_out.stderr
    record_id = id_out.stdout.strip()

    desc_v2 = "REG-MD browser update v2"
    update_wb = _workbook(
        "industries",
        [{"id": record_id, "name": unique, "description": desc_v2, "sort_order": "78", "is_active": "yes"}],
    )
    desktop_page.locator("#master-data-import-file").set_input_files(str(update_wb))
    desktop_page.get_by_role("button", name="Preview import").click()
    desktop_page.wait_for_load_state("networkidle")
    expect(desktop_page.get_by_text("1 update", exact=False)).to_be_visible(timeout=30_000)
    desktop_page.get_by_role("button", name="Confirm import").click()
    desktop_page.wait_for_load_state("networkidle")
    expect(desktop_page.get_by_text("1 updated", exact=False)).to_be_visible(timeout=30_000)
    export_resp = desktop_page.request.get(f"{base_url}/admin/master-data/industries/excel/export")
    assert export_resp.ok
    export_path = Path(tempfile.gettempdir()) / f"reg-md-export-{int(time.time())}.xlsx"
    export_path.write_bytes(export_resp.body())
    field_out = subprocess.run(
        [php, str(REPO_ROOT / "scripts" / "qa-read-master-export-row.php"), str(export_path), record_id, "description"],
        cwd=REPO_ROOT,
        capture_output=True,
        text=True,
        check=False,
    )
    assert field_out.returncode == 0, field_out.stderr
    assert field_out.stdout.strip() == desc_v2
    desktop_page.reload(wait_until="networkidle")
    expect(desktop_page.get_by_text(unique)).to_be_visible(timeout=30_000)


CREATABLE_PREVIEW_SLUGS = (
    "locations",
    "employment-types",
    "experience-ranges",
    "qualifications",
    "rejection-reasons",
)


@pytest.mark.reg_md_001_acceptance
def test_md001_empty_name_preview_rejected_for_remaining_creatable_types(
    desktop_page: Page, base_url: str, qa_settings
) -> None:
    admin_email, admin_password = _admin(qa_settings)
    login_admin(desktop_page, base_url, admin_email, admin_password)

    for slug in CREATABLE_PREVIEW_SLUGS:
        wb = _workbook(slug, [{"id": "", "name": "", "description": "", "sort_order": "1", "is_active": "yes"}])
        desktop_page.goto(f"{base_url}/admin/master-data?tab={slug}", wait_until="networkidle")
        desktop_page.locator("#master-data-import-file").set_input_files(str(wb))
        desktop_page.get_by_role("button", name="Preview import").click()
        desktop_page.wait_for_load_state("networkidle")
        _skip_if_upload_blocked(desktop_page)
        _expect_preview_rejected_rows(desktop_page)


@pytest.mark.reg_md_001_acceptance
def test_md001_update_only_entity_rejects_create_in_preview(
    desktop_page: Page, base_url: str, qa_settings
) -> None:
    admin_email, admin_password = _admin(qa_settings)
    login_admin(desktop_page, base_url, admin_email, admin_password)

    for slug in ("job-statuses", "application-statuses"):
        wb = _workbook(
            slug,
            [
                {
                    "id": "",
                    "code": "should-not-create",
                    "name": "Should Not Create",
                    "description": "",
                    "sort_order": "1",
                    "is_active": "yes",
                }
            ],
        )
        desktop_page.goto(f"{base_url}/admin/master-data?tab={slug}", wait_until="networkidle")
        desktop_page.locator("#master-data-import-file").set_input_files(str(wb))
        desktop_page.get_by_role("button", name="Preview import").click()
        desktop_page.wait_for_load_state("networkidle")
        _skip_if_upload_blocked(desktop_page)
        _expect_preview_rejected_rows(desktop_page)
