"""Stage 7 — admin decision → employer visibility → candidate notification loop."""

from __future__ import annotations

import re
from dataclasses import dataclass, field
from pathlib import Path

from playwright.sync_api import Page, Response, expect

from ournear_qa.browser.navigation import NavigationHelper
from ournear_qa.journey.account_auth import login_account_user
from ournear_qa.journey.admin_application import (
    AdminJourneyTarget,
    _admin_applications_url,
    _drawer,
    login_admin,
)
from ournear_qa.journey.cross_role import resolve_application_by_business_id
from ournear_qa.journey.employer_resume import login_employer
from ournear_qa.journey.errors import JourneyStepError

REPO_ROOT = Path(__file__).resolve().parents[3]
JRN_ID = "JRN-DECISION-COMM-01"
DEFAULT_BUSINESS_ID = "ONT-A-000051"


@dataclass
class DecisionCommunicationReport:
    business_id: str
    application_id: int
    job_slug: str
    admin_steps: list[str] = field(default_factory=list)
    employer_steps: list[str] = field(default_factory=list)
    candidate_steps: list[str] = field(default_factory=list)
    shortlist_performed: bool = False
    prior_status: str = ""


def resolve_stage7_target(business_id: str = DEFAULT_BUSINESS_ID) -> AdminJourneyTarget:
    resolved = resolve_application_by_business_id(business_id)
    if resolved is None:
        raise JourneyStepError(
            "STG7-resolve",
            f"application {business_id}",
            "inspect script not found",
            journey_id=JRN_ID,
        )
    return resolved


def _admin_shortlist_in_drawer(page: Page, target: AdminJourneyTarget) -> bool:
    """Returns True if shortlist POST ran; False if already shortlisted."""
    drawer = _drawer(page)
    drawer.get_by_role("tab", name="Overview").click()
    status_select = drawer.get_by_role("combobox", name="Application status")
    current = status_select.input_value()
    if current == "Shortlisted":
        return False

    drawer.get_by_role("tab", name="Screening").click()
    shortlist_btn = drawer.get_by_role("button", name="Shortlist", exact=True)
    expect(shortlist_btn).to_be_visible(timeout=15_000)
    if shortlist_btn.is_disabled():
        raise JourneyStepError(
            "STG7-admin-shortlist",
            "enabled Shortlist control",
            f"disabled; status={current}",
            journey_id=JRN_ID,
        )

    shortlist_btn.click()
    confirm = page.get_by_role("dialog", name="Shortlist this candidate?")
    expect(confirm).to_be_visible(timeout=15_000)

    def is_shortlist_post(response: Response) -> bool:
        return (
            response.request.method == "POST"
            and f"/admin/applications/{target.application_id}/shortlist" in response.url
        )

    with page.expect_response(is_shortlist_post, timeout=30_000) as response_info:
        confirm.get_by_role("button", name="Shortlist", exact=True).click()
    response = response_info.value
    if response.status >= 400:
        raise JourneyStepError(
            "STG7-admin-shortlist",
            "POST shortlist success",
            f"HTTP {response.status}",
            journey_id=JRN_ID,
        )

    drawer.get_by_role("tab", name="Overview").click()
    status_select = drawer.get_by_role("combobox", name="Application status")
    expect(status_select).to_have_value("Shortlisted", timeout=15_000)
    page.reload(wait_until="domcontentloaded")
    expect(_drawer(page)).to_be_visible(timeout=30_000)
    _drawer(page).get_by_role("tab", name="Overview").click()
    expect(_drawer(page).get_by_role("combobox", name="Application status")).to_have_value(
        "Shortlisted", timeout=15_000
    )
    return True


def run_decision_communication_loop(
    page: Page,
    base_url: str,
    *,
    business_id: str,
    admin_email: str,
    admin_password: str,
    employer_a_email: str,
    employer_a_password: str,
    candidate_email: str,
    candidate_password: str,
) -> DecisionCommunicationReport:
    target = resolve_stage7_target(business_id)
    report = DecisionCommunicationReport(
        business_id=target.business_id,
        application_id=target.application_id,
        job_slug=target.job_slug,
        prior_status=target.status,
    )

    login_admin(page, base_url, admin_email, admin_password)
    report.admin_steps.append("STG7-ADM-login")

    nav = NavigationHelper(page, base_url)
    nav.goto_path(_admin_applications_url(target.application_id, target.business_id))
    expect(_drawer(page)).to_be_visible(timeout=30_000)
    report.admin_steps.append("STG7-ADM-drawer")

    report.shortlist_performed = _admin_shortlist_in_drawer(page, target)
    report.admin_steps.append(
        "STG7-ADM-shortlist" if report.shortlist_performed else "STG7-ADM-already-shortlisted"
    )

    login_employer(page, base_url, employer_a_email, employer_a_password)
    show_path = f"/account/jobs/{target.job_slug}/applications/{target.application_id}"
    nav.goto_path(show_path)
    expect(page.get_by_text(target.business_id).first).to_be_visible(timeout=15_000)
    expect(page.get_by_text("Shortlisted").first).to_be_visible(timeout=15_000)
    page.reload(wait_until="domcontentloaded")
    expect(page.get_by_text("Shortlisted").first).to_be_visible(timeout=15_000)
    report.employer_steps.append("STG7-EMP-detail-shortlisted")

    nav.goto_path("/account/notifications")
    expect(page.get_by_role("heading", name="Notification center")).to_be_visible(timeout=15_000)
    report.employer_steps.append("STG7-EMP-notifications-index")

    login_account_user(
        page,
        base_url,
        candidate_email,
        candidate_password,
        journey_id=JRN_ID,
        step_id="STG7-CAND-login",
    )
    nav.goto_path("/account/notifications")
    expect(page.get_by_role("heading", name="Notification center")).to_be_visible(timeout=30_000)
    body = page.locator("main").inner_text()
    if not re.search(r"shortlisted", body, re.I):
        raise JourneyStepError(
            "STG7-cand-notification",
            "in-app shortlisted notification visible",
            "no shortlisted text on /account/notifications",
            journey_id=JRN_ID,
        )
    report.candidate_steps.append("STG7-CAND-notification-visible")

    open_btn = page.get_by_role("button", name="Open related page").first
    open_btn.click()
    page.wait_for_url("**/account/applications**", timeout=30_000)
    report.candidate_steps.append("STG7-CAND-notification-deeplink")

    nav.goto_path(f"/account/applications/{target.application_id}")
    expect(page.get_by_text("Shortlisted").first).to_be_visible(timeout=15_000)
    page.reload(wait_until="domcontentloaded")
    expect(page.get_by_text("Shortlisted").first).to_be_visible(timeout=15_000)
    report.candidate_steps.append("STG7-CAND-detail-timeline")

    return report
