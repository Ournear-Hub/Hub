"""Employer job applicant compare (REG-BRW-004)."""

from __future__ import annotations

import re
import shutil
import subprocess
from dataclasses import dataclass, field
from pathlib import Path

from playwright.sync_api import Page, expect

from ournear_qa.browser.navigation import NavigationHelper
from ournear_qa.journey.employer_resume import login_employer
from ournear_qa.journey.errors import JourneyStepError

JRN_ID = "JRN-EMPLOYER-APPLICANT-COMPARE-01"
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


def _ensure_compare_job() -> tuple[str, int] | None:
    provision = _run_php_script("qa-provision-screening-compare.php")
    if provision is not None and provision.returncode != 0:
        return None

    completed = _run_php_script("inspect-qa-screening-compare-job.php")
    if completed is None or completed.returncode != 0:
        return None

    fields: dict[str, str] = {}
    for line in completed.stdout.splitlines():
        if "=" in line:
            key, value = line.split("=", 1)
            fields[key.strip()] = value.strip()

    slug = fields.get("job_slug", "").strip()
    try:
        count = int(fields.get("applications_count", "0"))
    except ValueError:
        count = 0

    if not slug or count < 2:
        return None

    return slug, count


@dataclass
class EmployerApplicantCompareReport:
    job_slug: str
    selected_names: list[str] = field(default_factory=list)
    steps: list[str] = field(default_factory=list)


def run_employer_applicant_compare(
    page: Page,
    base_url: str,
    employer_email: str,
    employer_password: str,
    *,
    evidence_dir: Path | None = None,
    viewport_label: str = "desktop",
) -> EmployerApplicantCompareReport:
    job_info = _ensure_compare_job()
    if job_info is None:
        raise JourneyStepError(
            "E4-01-provision-job",
            "employer-owned job with >= 2 applicants",
            "qa-provision-screening-compare.php / inspect script failed",
            journey_id=JRN_ID,
        )

    job_slug, _ = job_info
    report = EmployerApplicantCompareReport(job_slug=job_slug)

    login_employer(page, base_url, employer_email, employer_password)
    report.steps.append("E4-01-employer-login")

    nav = NavigationHelper(page, base_url)
    nav.goto_path(f"/account/jobs/{job_slug}/applicants")
    expect(page.get_by_text("Applicants for", exact=False)).to_be_visible(timeout=30_000)
    report.steps.append("E4-02-applicants-list")

    compare_boxes = page.locator('input[type="checkbox"][data-compare-candidate-name]')
    count = compare_boxes.count()
    if count < 2:
        raise JourneyStepError(
            "E4-03-eligible-applicants",
            "at least two applicants with compare checkboxes",
            f"count={count}",
            journey_id=JRN_ID,
        )

    for index in range(min(2, count)):
        box = compare_boxes.nth(index)
        name = (box.get_attribute("data-compare-candidate-name") or "").strip()
        if not name:
            label = box.get_attribute("aria-label") or f"applicant-{index}"
            name = label.removeprefix("Compare applicant ").removeprefix("Compare ").strip()
        report.selected_names.append(name)
        box.check()
    report.steps.append("E4-04-two-selected")

    compare_btn = page.get_by_role("button", name=re.compile(r"Compare selected", re.I))
    expect(compare_btn).to_be_enabled(timeout=10_000)
    compare_btn.click()
    report.steps.append("E4-05-open-compare")

    panel = page.locator("#employer-applicant-compare-panel")
    expect(panel).to_be_visible(timeout=15_000)
    panel.scroll_into_view_if_needed()
    expect(panel.get_by_role("heading", name=re.compile(r"Compare applicants for", re.I))).to_be_visible()

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
    report.steps.append("E4-06-compare-names-visible")

    if evidence_dir is not None:
        evidence_dir.mkdir(parents=True, exist_ok=True)
        page.screenshot(
            path=str(evidence_dir / f"employer-applicant-compare-{viewport_label}.png"),
            full_page=True,
        )

    page.get_by_role("button", name="Hide comparison").click()
    expect(panel).not_to_be_visible(timeout=10_000)
    report.steps.append("E4-07-hide-compare")

    expect(page).to_have_url(re.compile(re.escape(f"/account/jobs/{job_slug}/applicants")), timeout=15_000)
    report.steps.append("E4-08-job-context-preserved")

    return report
