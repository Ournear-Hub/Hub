"""REG-PROD-001 — Employer self-service reject on a fresh application (browser)."""

from __future__ import annotations

import re
import shutil
import socket
import subprocess
from pathlib import Path

import pytest
from playwright.sync_api import Page, expect

from ournear_qa.evidence import ARTIFACTS_DIR
from ournear_qa.journey.candidate_apply import provision_journey_apply_target, run_new_application_steps
from ournear_qa.journey.employer_resume import login_employer
from ournear_qa.journey.errors import JourneyStepError

REPO_ROOT = Path(__file__).resolve().parents[3]
RUN_ID = "20261014T120000Z-reg-prod-001-browser"
EVIDENCE = ARTIFACTS_DIR / "agent2" / RUN_ID

pytestmark = [pytest.mark.reg_prod_001]


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
        urllib.request.urlopen(base_url.rstrip("/") + "/", timeout=15)
        return True
    except Exception:
        return False


def _probe_application_status(job_slug: str, business_id: str) -> str:
    php = shutil.which("php")
    if php is None:
        return ""
    completed = subprocess.run(
        [
            php,
            str(REPO_ROOT / "scripts" / "qa-application-status-probe.php"),
            job_slug,
            business_id,
        ],
        cwd=REPO_ROOT,
        capture_output=True,
        text=True,
        check=False,
    )
    for line in completed.stdout.splitlines():
        if line.startswith("status="):
            return line.split("=", 1)[1].strip()
    return ""


@pytest.fixture
def desktop_page(page: Page) -> Page:
    page.set_viewport_size({"width": 1280, "height": 900})
    return page


def test_reg_prod_001_employer_reject_persists_after_reload(
    desktop_page: Page, base_url: str, qa_settings
) -> None:
    if qa_settings.ournear_allow_run != "local":
        pytest.skip("Set OURNEAR_ALLOW_RUN=local")
    if not _mysql_up():
        pytest.skip("BLOCKED: MySQL not on 3306")
    if not _server_up(base_url):
        pytest.skip("BLOCKED: start scripts/start-local-web-server.ps1")

    employer_email = (qa_settings.qa_employer_a_email or "").strip()
    employer_password = (qa_settings.qa_employer_a_password or "").strip()
    candidate_email = (qa_settings.qa_candidate_email or "").strip()
    candidate_password = (qa_settings.qa_candidate_password or "").strip()
    if not employer_email or not employer_password or not candidate_email or not candidate_password:
        pytest.skip("QA employer + candidate credentials required")

    target = provision_journey_apply_target()
    if target is None:
        pytest.skip("BLOCKED: no unapplied journey job — run qa-provision-journey-apply-job.php")
    job_slug, job_title = target

    try:
        ctx = run_new_application_steps(
            desktop_page,
            base_url,
            candidate_email,
            candidate_password,
            job_slug,
            job_title,
        )
    except JourneyStepError as err:
        if "429" in err.actual:
            pytest.skip(f"rate limited: {err}")
        raise
    assert ctx.business_id, "expected ONT-A-* from apply confirmation"
    EVIDENCE.mkdir(parents=True, exist_ok=True)
    desktop_page.screenshot(path=str(EVIDENCE / "01-candidate-applied.png"), full_page=True)

    login_employer(desktop_page, base_url, employer_email, employer_password)
    desktop_page.goto(
        f"{base_url.rstrip('/')}/account/jobs/{job_slug}/applicants",
        wait_until="networkidle",
    )
    expect(desktop_page.get_by_text(ctx.business_id, exact=False).first).to_be_visible(timeout=20_000)
    app_link = desktop_page.get_by_role("link", name="View application").first
    href = app_link.get_attribute("href") or ""
    assert "/applications/" in href
    app_link.click()
    desktop_page.wait_for_load_state("networkidle")

    expect(desktop_page.get_by_role("heading", name="Recruitment actions")).to_be_visible(timeout=15_000)
    desktop_page.get_by_role("button", name="Reject").click()
    reason_select = desktop_page.locator("form").filter(has_text="Confirm reject").locator("select").first
    options = reason_select.locator("option")
    reason_value = None
    for i in range(options.count()):
        value = options.nth(i).get_attribute("value") or ""
        if value.strip():
            reason_value = value
            break
    assert reason_value, "No rejection reasons in master data"
    reason_select.select_option(reason_value)
    reject_form = desktop_page.locator("form").filter(has_text="Rejection reason")
    reject_form.locator("textarea").fill("REG-PROD-001 browser reject QA")
    with desktop_page.expect_response(
        lambda r: r.request.method == "POST" and "/reject" in r.url and job_slug in r.url,
        timeout=30_000,
    ) as reject_response_info:
        desktop_page.get_by_role("button", name="Confirm reject").click()
    assert reject_response_info.value.status < 400
    desktop_page.wait_for_load_state("networkidle")
    desktop_page.screenshot(path=str(EVIDENCE / "02-after-reject.png"), full_page=True)

    desktop_page.reload(wait_until="networkidle")
    expect(desktop_page.get_by_text("Rejected", exact=False).first).to_be_visible(timeout=15_000)
    expect(desktop_page.get_by_role("button", name="Reject")).to_have_count(0)
    db_status = _probe_application_status(job_slug, ctx.business_id)
    if db_status:
        assert db_status == "Rejected", f"expected Rejected, got {db_status!r}"
    desktop_page.screenshot(path=str(EVIDENCE / "03-after-reload.png"), full_page=True)
