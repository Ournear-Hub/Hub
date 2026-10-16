"""Stage 8 — candidate ↔ admin application visibility and updates."""

from __future__ import annotations

import re
from dataclasses import dataclass, field

from playwright.sync_api import Page, Response, expect

from ournear_qa.browser.navigation import NavigationHelper
from ournear_qa.journey.account_auth import login_account_user
from ournear_qa.journey.admin_application import (
    _admin_applications_url,
    _drawer,
    login_admin,
)
from ournear_qa.journey.decision_communication import DEFAULT_BUSINESS_ID, resolve_stage7_target
from ournear_qa.journey.errors import JourneyStepError

JRN_ID = "JRN-CANDIDATE-ADMIN-01"


@dataclass
class CandidateAdminReport:
    business_id: str
    application_id: int
    job_slug: str
    candidate_steps: list[str] = field(default_factory=list)
    admin_steps: list[str] = field(default_factory=list)
    correction_steps: list[str] = field(default_factory=list)
    notification_steps: list[str] = field(default_factory=list)
    status_update_performed: bool = False
    snapshot_filename_before: str = ""


def run_candidate_admin_workflow(
    page: Page,
    base_url: str,
    *,
    business_id: str,
    candidate_email: str,
    candidate_password: str,
    admin_email: str,
    admin_password: str,
) -> CandidateAdminReport:
    target = resolve_stage7_target(business_id)
    report = CandidateAdminReport(
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
        step_id="STG8-candidate-login",
    )
    nav.goto_path("/account/applications")
    expect(page.get_by_text(target.business_id).first).to_be_visible(timeout=30_000)
    report.candidate_steps.append("STG8-CAND-list")

    nav.goto_path("/account/applications?filter=active")
    expect(page.get_by_text(target.business_id).first).to_be_visible(timeout=15_000)
    report.candidate_steps.append("STG8-CAND-filter-active")

    nav.goto_path(f"/account/applications/{target.application_id}")
    expect(page.get_by_text("Shortlisted").first).to_be_visible(timeout=15_000)
    expect(page.get_by_text(target.business_id).first).to_be_visible(timeout=15_000)
    snapshot_heading = page.get_by_role("heading", name="Resume used")
    expect(snapshot_heading).to_be_visible(timeout=15_000)
    snapshot_section = snapshot_heading.locator("xpath=ancestor::section[1]")
    report.snapshot_filename_before = snapshot_section.locator("p.text-sm.text-ink").first.inner_text().strip()
    report.candidate_steps.append("STG8-CAND-detail-shortlisted")

    expect(page.get_by_role("heading", name="Timeline")).to_be_visible(timeout=15_000)
    timeline = page.locator("body").inner_text()
    if "Shortlisted" not in timeline:
        raise JourneyStepError(
            "STG8-candidate-timeline",
            "timeline includes Shortlisted",
            "Shortlisted not in body",
            journey_id=JRN_ID,
        )
    report.candidate_steps.append("STG8-CAND-timeline")

    page.reload(wait_until="domcontentloaded")
    expect(page.get_by_text("Shortlisted").first).to_be_visible(timeout=15_000)
    report.candidate_steps.append("STG8-CAND-reload")

    page.get_by_role("button", name="Log out").click()
    page.wait_for_url("**/", timeout=30_000)
    login_account_user(
        page,
        base_url,
        candidate_email,
        candidate_password,
        journey_id=JRN_ID,
        step_id="STG8-candidate-relogin",
    )
    nav.goto_path(f"/account/applications/{target.application_id}")
    expect(page.get_by_text("Shortlisted").first).to_be_visible(timeout=15_000)
    report.candidate_steps.append("STG8-CAND-relogin")

    login_admin(page, base_url, admin_email, admin_password)
    report.admin_steps.append("STG8-ADM-login")
    nav.goto_path(_admin_applications_url(target.application_id, target.business_id))
    drawer = _drawer(page)
    expect(drawer).to_be_visible(timeout=30_000)
    report.admin_steps.append("STG8-ADM-drawer")

    candidate_link = drawer.get_by_role("link", name="Candidate")
    expect(candidate_link).to_be_visible(timeout=10_000)
    href = candidate_link.get_attribute("href") or ""
    if "admin/candidates" not in href:
        raise JourneyStepError(
            "STG8-admin-candidate-link",
            "admin candidates workspace href",
            href,
            journey_id=JRN_ID,
        )
    report.admin_steps.append("STG8-ADM-candidate-link")

    drawer.get_by_role("tab", name="Candidate").click()
    expect(drawer.get_by_text(re.compile(r"QA Candidate|qa-candidate@", re.I)).first).to_be_visible(
        timeout=15_000
    )
    report.admin_steps.append("STG8-ADM-candidate-tab")

    drawer.get_by_role("tab", name="Overview").click()
    status_select = drawer.get_by_role("combobox", name="Application status")
    current = status_select.input_value()
    if current != "Interview":

        def is_status_patch(response: Response) -> bool:
            return (
                response.request.method == "PATCH"
                and f"/admin/applications/{target.application_id}/status" in response.url
            )

        with page.expect_response(is_status_patch, timeout=30_000) as response_info:
            status_select.select_option("Interview")
        if response_info.value.status >= 400:
            raise JourneyStepError(
                "STG8-admin-status-interview",
                "PATCH Interview",
                f"HTTP {response_info.value.status}",
                journey_id=JRN_ID,
            )
        report.status_update_performed = True
    report.admin_steps.append("STG8-ADM-status-interview")

    page.reload(wait_until="domcontentloaded")
    _drawer(page).get_by_role("tab", name="Overview").click()
    expect(_drawer(page).get_by_role("combobox", name="Application status")).to_have_value(
        "Interview", timeout=15_000
    )
    report.admin_steps.append("STG8-ADM-persist-reload")

    login_account_user(
        page,
        base_url,
        candidate_email,
        candidate_password,
        journey_id=JRN_ID,
        step_id="STG8-candidate-after-admin",
    )
    nav.goto_path(f"/account/applications/{target.application_id}")
    expect(page.get_by_text("Interview").first).to_be_visible(timeout=15_000)
    report.candidate_steps.append("STG8-CAND-post-admin-interview")

    nav.goto_path("/account/notifications")
    expect(page.get_by_role("heading", name="Notification center")).to_be_visible(timeout=15_000)
    if page.get_by_text("shortlisted", exact=False).count() > 0:
        report.notification_steps.append("STG8-NOTIF-shortlist-present")
    mark_read = page.get_by_role("button", name="Mark read").first
    if mark_read.is_visible():
        mark_read.click()
        page.wait_for_timeout(500)
        report.notification_steps.append("STG8-NOTIF-mark-read")
    page.reload(wait_until="domcontentloaded")
    report.notification_steps.append("STG8-NOTIF-reload")

    nav.goto_path("/account/profile")
    expect(page.get_by_role("heading", name=re.compile(r"Profile|Resume|Hire", re.I)).first).to_be_visible(
        timeout=30_000
    )
    report.correction_steps.append("STG8-CORR-profile-edit-reachable")

    nav.goto_path(f"/account/applications/{target.application_id}")
    snapshot_section = page.get_by_role("heading", name="Resume used").locator("xpath=ancestor::section[1]")
    after_name = snapshot_section.locator("p.text-sm.text-ink").first.inner_text().strip()
    if after_name != report.snapshot_filename_before:
        raise JourneyStepError(
            "STG8-snapshot-integrity",
            "application resume snapshot unchanged after profile navigation",
            f"before={report.snapshot_filename_before!r} after={after_name!r}",
            journey_id=JRN_ID,
        )
    report.correction_steps.append("STG8-CORR-snapshot-unchanged")

    return report
