"""Admin screening multi-candidate comparison (C2)."""

from __future__ import annotations

import re
import shutil
import subprocess
from dataclasses import dataclass, field
from pathlib import Path

from playwright.sync_api import Page, expect

from ournear_qa.browser.navigation import NavigationHelper
from ournear_qa.evidence import ARTIFACTS_DIR
from ournear_qa.journey.admin_application import login_admin
from ournear_qa.journey.errors import JourneyStepError

JRN_ID = "JRN-SCREENING-COMPARE-01"
REPO_ROOT = Path(__file__).resolve().parents[3]


def _run_php_script(name: str) -> subprocess.CompletedProcess[str] | None:
    php = shutil.which("php")
    script = REPO_ROOT / "scripts" / name
    if php is None or not script.is_file():
        return None
    return subprocess.run(
        [php, str(script)],
        cwd=REPO_ROOT,
        capture_output=True,
        text=True,
        check=False,
    )


def _preferred_screening_job_id() -> str | None:
    provision = _run_php_script("qa-provision-screening-compare.php")
    if provision is not None and provision.returncode != 0:
        return None

    completed = _run_php_script("inspect-qa-screening-compare-job.php")
    if completed is None or completed.returncode != 0:
        return None
    for line in completed.stdout.splitlines():
        if line.startswith("job_id="):
            return line.split("=", 1)[1].strip()
    return None


@dataclass
class ScreeningComparisonReport:
    job_id: str
    selected_names: list[str] = field(default_factory=list)
    steps: list[str] = field(default_factory=list)


def run_screening_comparison(
    page: Page,
    base_url: str,
    admin_email: str,
    admin_password: str,
    *,
    evidence_dir: Path | None = None,
    viewport_label: str = "desktop",
) -> ScreeningComparisonReport:
    report = ScreeningComparisonReport(job_id="")
    login_admin(page, base_url, admin_email, admin_password)
    report.steps.append("C2-01-admin-login")

    nav = NavigationHelper(page, base_url)
    nav.goto_path("/admin/screening")
    expect(page.get_by_role("heading", name="Screening")).to_be_visible(timeout=30_000)
    report.steps.append("C2-02-screening-index")

    job_select = page.locator("#screening-job")
    expect(job_select).to_be_visible(timeout=15_000)

    job_value = _preferred_screening_job_id() or ""
    if not job_value:
        raise JourneyStepError(
            "C2-03-job-select",
            "job with at least two applications (inspect script)",
            "inspect-qa-screening-compare-job.php returned none",
            journey_id=JRN_ID,
        )

    nav.goto_path(f"/admin/screening?job_id={job_value}")
    expect(page.get_by_role("heading", name="Screening")).to_be_visible(timeout=30_000)
    job_select = page.locator("#screening-job")
    expect(job_select).to_have_value(job_value, timeout=15_000)
    report.job_id = job_value
    report.steps.append("C2-03-job-selected")
    compare_boxes = page.locator('input[type="checkbox"][data-compare-candidate-name]')
    count = compare_boxes.count()
    if count < 2:
        raise JourneyStepError(
            "C2-04-eligible-applicants",
            "at least two applicants with compare checkboxes",
            f"count={count}",
            journey_id=JRN_ID,
        )

    for index in range(min(2, count)):
        box = compare_boxes.nth(index)
        name = (box.get_attribute("data-compare-candidate-name") or "").strip()
        if not name:
            label = box.get_attribute("aria-label") or f"candidate-{index}"
            name = label.removeprefix("Compare candidate ").removeprefix("Compare ").strip()
        report.selected_names.append(name)
        box.check()
    report.steps.append("C2-05-two-selected")

    compare_btn = page.get_by_role("button", name=re.compile(r"Compare selected", re.I))
    expect(compare_btn).to_be_enabled(timeout=10_000)
    compare_btn.click()
    report.steps.append("C2-06-open-compare")

    panel = page.locator("#screening-compare-panel")
    expect(panel).to_be_visible(timeout=15_000)
    panel.scroll_into_view_if_needed()
    expect(panel.get_by_role("heading", name="Compare candidates for this job")).to_be_visible()
    for name in report.selected_names:
        candidate_label = panel.locator("[data-compare-candidate]").get_by_text(name, exact=True)
        header = panel.get_by_role("columnheader", name=name, exact=True)
        if candidate_label.count() > 0:
            candidate_label.first.scroll_into_view_if_needed()
            expect(candidate_label.first).to_be_visible(timeout=10_000)
            continue
        assert header.count() > 0, f"compare label for {name!r}"
        header.first.scroll_into_view_if_needed()
        expect(header.first).to_be_visible(timeout=10_000)
    expect(panel.get_by_text("Application ID", exact=True)).to_be_visible()
    report.steps.append("C2-07-compare-data-visible")

    if evidence_dir is not None:
        evidence_dir.mkdir(parents=True, exist_ok=True)
        page.screenshot(path=str(evidence_dir / f"screening-compare-{viewport_label}.png"), full_page=True)

    page.get_by_role("button", name="Hide comparison").click()
    expect(panel).not_to_be_visible(timeout=10_000)
    report.steps.append("C2-08-hide-compare")

    expect(job_select).to_have_value(job_value, timeout=10_000)
    report.steps.append("C2-09-job-context-preserved")

    return report
