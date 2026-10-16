"""Stage 11 — read-only cross-role lifecycle consistency for correlation fixture."""

from __future__ import annotations

import re
from dataclasses import dataclass, field
from pathlib import Path

from playwright.sync_api import Page, expect

from ournear_qa.browser.navigation import NavigationHelper
from ournear_qa.journey.account_auth import login_account_user
from ournear_qa.journey.admin_application import _admin_applications_url, _drawer, login_admin
from ournear_qa.journey.decision_communication import DEFAULT_BUSINESS_ID, resolve_stage7_target
from ournear_qa.journey.employer_resume import (
    _application_show_path,
    login_employer,
    pdf_bytes_are_readable,
)
from ournear_qa.journey.errors import JourneyStepError

JRN_ID = "JRN-RECRUITMENT-LIFECYCLE-01"


@dataclass
class LifecycleConsistencyReport:
    business_id: str
    application_id: int
    job_slug: str
    candidate_steps: list[str] = field(default_factory=list)
    employer_steps: list[str] = field(default_factory=list)
    admin_steps: list[str] = field(default_factory=list)
    notification_href: str | None = None


def run_lifecycle_consistency(
    page: Page,
    base_url: str,
    *,
    business_id: str = DEFAULT_BUSINESS_ID,
    candidate_email: str,
    candidate_password: str,
    employer_a_email: str,
    employer_a_password: str,
    admin_email: str,
    admin_password: str,
) -> LifecycleConsistencyReport:
    target = resolve_stage7_target(business_id)
    report = LifecycleConsistencyReport(
        business_id=target.business_id,
        application_id=target.application_id,
        job_slug=target.job_slug,
    )
    nav = NavigationHelper(page, base_url)

    login_account_user(
        page,
        base_url,
        candidate_email,
        candidate_password,
        journey_id=JRN_ID,
        step_id="STG11-candidate-login",
    )
    nav.goto_path("/account/applications")
    expect(page.get_by_text(target.business_id).first).to_be_visible(timeout=30_000)
    report.candidate_steps.append("STG11-CAND-list")

    nav.goto_path(f"/account/applications/{target.application_id}")
    expect(page.get_by_text(target.business_id).first).to_be_visible(timeout=15_000)
    expect(page.get_by_text("Interview").first).to_be_visible(timeout=15_000)
    report.candidate_steps.append("STG11-CAND-detail-interview")

    expect(page.get_by_role("heading", name="Timeline")).to_be_visible(timeout=15_000)
    body = page.locator("body").inner_text()
    if not re.search(r"submitted|review|shortlist|interview", body, re.I):
        raise JourneyStepError(
            "STG11-CAND-timeline",
            "timeline mentions lifecycle events",
            "no matching timeline copy",
            journey_id=JRN_ID,
        )
    report.candidate_steps.append("STG11-CAND-timeline")

    page.reload(wait_until="domcontentloaded")
    expect(page.get_by_text("Interview").first).to_be_visible(timeout=15_000)
    report.candidate_steps.append("STG11-CAND-reload")

    login_employer(page, base_url, employer_a_email, employer_a_password)
    nav.goto_path("/account/hiring/applications")
    expect(page.get_by_text(target.business_id).first).to_be_visible(timeout=30_000)
    report.employer_steps.append("STG11-EMP-hiring-list")

    show = _application_show_path(target.job_slug, target.application_id)
    nav.goto_path(show)
    expect(page.get_by_text(target.business_id).first).to_be_visible(timeout=15_000)
    expect(page.get_by_text("Interview").first).to_be_visible(timeout=15_000)
    report.employer_steps.append("STG11-EMP-detail-interview")

    with page.expect_download(timeout=30_000) as download_info:
        page.get_by_role("link", name="Download").click()
    dl_path = download_info.value.path()
    if not dl_path or not pdf_bytes_are_readable(Path(dl_path).read_bytes()):
        raise JourneyStepError(
            "STG11-EMP-resume",
            "readable resume PDF",
            "download invalid",
            journey_id=JRN_ID,
        )
    report.employer_steps.append("STG11-EMP-resume")

    login_admin(page, base_url, admin_email, admin_password)
    nav.goto_path(_admin_applications_url(target.application_id, target.business_id))
    drawer = _drawer(page)
    expect(drawer).to_be_visible(timeout=30_000)
    drawer_text = drawer.inner_text()
    if target.business_id not in drawer_text:
        raise JourneyStepError(
            "STG11-ADM-drawer",
            f"drawer shows {target.business_id}",
            "business id missing",
            journey_id=JRN_ID,
        )
    if "Interview" not in drawer_text:
        raise JourneyStepError(
            "STG11-ADM-status",
            "admin drawer reflects Interview status",
            drawer_text[:200],
            journey_id=JRN_ID,
        )
    report.admin_steps.append("STG11-ADM-drawer-interview")

    drawer.get_by_role("tab", name="Interview").click()
    expect(drawer.get_by_text(re.compile(r"Interview #\d+"))).to_be_visible(timeout=15_000)
    report.admin_steps.append("STG11-ADM-interview-tab")

    login_account_user(
        page,
        base_url,
        candidate_email,
        candidate_password,
        journey_id=JRN_ID,
        step_id="STG11-candidate-notifications",
    )
    nav.goto_path("/account/notifications")
    expect(page.get_by_role("heading", name="Notification center")).to_be_visible(timeout=15_000)
    report.candidate_steps.append("STG11-CAND-notifications")

    interview_link = page.get_by_role("link", name=re.compile(r"interview|application", re.I))
    if interview_link.count() > 0:
        href = interview_link.first.get_attribute("href") or ""
        report.notification_href = href
        if str(target.application_id) in href or target.business_id in href:
            report.candidate_steps.append("STG11-CAND-notif-link")
        else:
            report.candidate_steps.append("STG11-CAND-notif-present-no-deep-link-match")
    else:
        report.candidate_steps.append("STG11-CAND-notif-none-listed")

    return report
