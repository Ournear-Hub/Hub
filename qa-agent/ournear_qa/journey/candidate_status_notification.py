"""Candidate verification after admin status change (C1 extension)."""

from __future__ import annotations

from playwright.sync_api import Page, expect

from ournear_qa.browser.navigation import NavigationHelper
from ournear_qa.journey.account_auth import login_account_user
from ournear_qa.journey.errors import JourneyStepError

JRN_ID = "JRN-CROSS-ROLE-01"


def run_candidate_status_and_notification_steps(
    page: Page,
    base_url: str,
    email: str,
    password: str,
    *,
    application_id: int,
    business_id: str,
    expect_reviewing: bool,
) -> list[str]:
    steps: list[str] = []
    login_account_user(
        page,
        base_url,
        email,
        password,
        journey_id=JRN_ID,
        step_id="C1-candidate-post-status-login",
    )

    nav = NavigationHelper(page, base_url)
    nav.goto_path(f"/account/applications/{application_id}")
    expect(page.get_by_text(business_id).first).to_be_visible(timeout=30_000)
    steps.append("C1-CAND-001-application-detail")

    if expect_reviewing:
        expect(page.get_by_text("Under review", exact=False).first).to_be_visible(timeout=15_000)
        steps.append("C1-CAND-002-status-reviewing-visible")

    page.reload(wait_until="domcontentloaded")
    expect(page.get_by_text(business_id).first).to_be_visible(timeout=15_000)
    if expect_reviewing:
        expect(page.get_by_text("Under review", exact=False).first).to_be_visible(timeout=15_000)
    steps.append("C1-CAND-003-detail-reload")

    nav.goto_path("/account/notifications")
    expect(page.get_by_role("heading", name="Notification center")).to_be_visible(timeout=30_000)
    steps.append("C1-CAND-004-notifications-page")

    if expect_reviewing:
        body = page.locator("main").inner_text()
        if "being reviewed" not in body.lower() and business_id not in body:
            raise JourneyStepError(
                "C1-CAND-005-notification-body",
                "in-app notification mentioning review or application id",
                body[:500],
                journey_id=JRN_ID,
            )
        steps.append("C1-CAND-005-status-notification-present")

    nav.goto_path("/account")
    bell = page.get_by_role("link", name="Notifications")
    if bell.count() == 0:
        bell = page.locator('a[href="/account/notifications"]')
    expect(bell.first).to_be_visible(timeout=15_000)
    steps.append("C1-CAND-006-bell-link-present")

    return steps
