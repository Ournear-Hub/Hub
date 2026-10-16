"""Stage 9 — employer ↔ candidate workflow (read-heavy + admin interview)."""

from __future__ import annotations

import re
from dataclasses import dataclass, field
from datetime import datetime, timedelta, timezone
from pathlib import Path

from playwright.sync_api import Page, Response, expect

from ournear_qa.browser.navigation import NavigationHelper
from ournear_qa.journey.account_auth import login_account_user
from ournear_qa.journey.admin_application import (
    _admin_applications_url,
    _drawer,
    login_admin,
)
from ournear_qa.journey.decision_communication import DEFAULT_BUSINESS_ID, resolve_stage7_target
from ournear_qa.journey.employer_resume import (
    _application_show_path,
    _resume_path,
    login_employer,
    pdf_bytes_are_readable,
)
from ournear_qa.journey.errors import JourneyStepError

JRN_ID = "JRN-EMPLOYER-CANDIDATE-01"


@dataclass
class EmployerCandidateReport:
    business_id: str
    application_id: int
    job_slug: str
    employer_steps: list[str] = field(default_factory=list)
    admin_steps: list[str] = field(default_factory=list)
    candidate_steps: list[str] = field(default_factory=list)
    interview_scheduled: bool = False


def _schedule_admin_interview_if_needed(page: Page, application_id: int) -> bool:
    drawer = _drawer(page)
    drawer.get_by_role("tab", name="Interview").click()
    body = drawer.inner_text()
    if "Interview #1" in body or re.search(r"Interview #\d+", body):
        return False

    tomorrow = (datetime.now(timezone.utc) + timedelta(days=2)).strftime("%Y-%m-%dT10:30")
    drawer.get_by_label("Interview time").fill(tomorrow)
    drawer.get_by_label("Interview mode").select_option("phone")
    drawer.get_by_placeholder("Interviewer name").fill("Stage 9 QA Recruiter")
    drawer.get_by_placeholder("Location or link").fill("https://meet.example.test/stage9")

    def is_interview_put(response: Response) -> bool:
        return (
            response.request.method == "PUT"
            and f"/admin/applications/{application_id}/interview" in response.url
        )

    with page.expect_response(is_interview_put, timeout=30_000) as response_info:
        drawer.get_by_role("button", name="Schedule interview").click()
    if response_info.value.status >= 400:
        raise JourneyStepError(
            "STG9-admin-interview",
            "PUT interview success",
            f"HTTP {response_info.value.status}",
            journey_id=JRN_ID,
        )
    expect(drawer.get_by_text(re.compile(r"Interview #\d+"))).to_be_visible(timeout=15_000)
    return True


def run_employer_candidate_workflow(
    page: Page,
    base_url: str,
    *,
    business_id: str,
    employer_a_email: str,
    employer_a_password: str,
    admin_email: str,
    admin_password: str,
    candidate_email: str,
    candidate_password: str,
) -> EmployerCandidateReport:
    target = resolve_stage7_target(business_id)
    report = EmployerCandidateReport(
        business_id=target.business_id,
        application_id=target.application_id,
        job_slug=target.job_slug,
    )
    nav = NavigationHelper(page, base_url)

    login_employer(page, base_url, employer_a_email, employer_a_password)
    nav.goto_path("/account/hiring/applications")
    expect(page.get_by_text(target.business_id).first).to_be_visible(timeout=30_000)
    report.employer_steps.append("STG9-EMP-hiring-list")

    show = _application_show_path(target.job_slug, target.application_id)
    nav.goto_path(show)
    expect(page.get_by_text(target.business_id).first).to_be_visible(timeout=15_000)
    expect(page.get_by_text("Interview").first).to_be_visible(timeout=15_000)
    report.employer_steps.append("STG9-EMP-detail-interview")

    with page.expect_download(timeout=30_000) as download_info:
        page.get_by_role("link", name="Download").click()
    path = download_info.value.path()
    if not path or not pdf_bytes_are_readable(Path(path).read_bytes()):
        raise JourneyStepError(
            "STG9-emp-resume",
            "readable resume PDF",
            "download invalid",
            journey_id=JRN_ID,
        )
    report.employer_steps.append("STG9-EMP-resume")

    page.reload(wait_until="domcontentloaded")
    expect(page.get_by_text("Interview").first).to_be_visible(timeout=15_000)
    report.employer_steps.append("STG9-EMP-reload")

    login_admin(page, base_url, admin_email, admin_password)
    nav.goto_path(_admin_applications_url(target.application_id, target.business_id))
    expect(_drawer(page)).to_be_visible(timeout=30_000)
    report.admin_steps.append("STG9-ADM-drawer")

    report.interview_scheduled = _schedule_admin_interview_if_needed(page, target.application_id)
    report.admin_steps.append(
        "STG9-ADM-interview-scheduled" if report.interview_scheduled else "STG9-ADM-interview-existing"
    )

    page.reload(wait_until="domcontentloaded")
    _drawer(page).get_by_role("tab", name="Interview").click()
    expect(_drawer(page).get_by_text(re.compile(r"Interview #\d+"))).to_be_visible(timeout=15_000)
    report.admin_steps.append("STG9-ADM-interview-persist")

    login_account_user(
        page,
        base_url,
        candidate_email,
        candidate_password,
        journey_id=JRN_ID,
        step_id="STG9-candidate",
    )
    nav.goto_path(f"/account/applications/{target.application_id}")
    expect(page.get_by_text("Interview").first).to_be_visible(timeout=15_000)
    report.candidate_steps.append("STG9-CAND-status-interview")

    nav.goto_path("/account/notifications")
    expect(page.get_by_role("heading", name="Notification center")).to_be_visible(timeout=15_000)
    notif_body = page.locator("main").inner_text()
    if report.interview_scheduled and re.search(r"interview", notif_body, re.I):
        report.candidate_steps.append("STG9-CAND-interview-notification")
    elif not report.interview_scheduled:
        report.candidate_steps.append("STG9-CAND-notifications-checked")

    return report
