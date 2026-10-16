"""Employer/candidate self-service recruitment on QA fixture job (not ONT-A-000051)."""

from __future__ import annotations

import re
from dataclasses import dataclass, field

from playwright.sync_api import Page, expect

from ournear_qa.browser.navigation import NavigationHelper
from ournear_qa.journey.account_auth import login_account_user
from ournear_qa.journey.candidate_apply import (
    discover_qa_apply_target,
    provision_journey_apply_target,
    run_new_application_steps,
)
from ournear_qa.journey.employer_resume import login_employer

QA_SELF_SERVICE_JOB_SLUG = "qa-fixture-published-a-01"  # legacy default; runtime uses provision/discover


@dataclass
class SelfServiceReport:
    job_slug: str = QA_SELF_SERVICE_JOB_SLUG
    application_id: int | None = None
    steps: list[str] = field(default_factory=list)


def run_self_service_workflow(
    page: Page,
    base_url: str,
    *,
    candidate_email: str,
    candidate_password: str,
    employer_a_email: str,
    employer_a_password: str,
) -> SelfServiceReport:
    report = SelfServiceReport()
    nav = NavigationHelper(page, base_url)

    job_slug = QA_SELF_SERVICE_JOB_SLUG
    job_title = job_slug
    provisioned = provision_journey_apply_target() or discover_qa_apply_target()
    if provisioned is not None:
        job_slug, job_title = provisioned
    report.job_slug = job_slug

    login_employer(page, base_url, employer_a_email, employer_a_password)
    nav.goto_path(f"/account/jobs/{job_slug}/applicants")
    report.steps.append("STG11-SS-emp-applicants")

    applicant_link = page.locator(f'a[href*="/applications/"]').first
    if applicant_link.count() == 0:
        run_new_application_steps(
            page,
            base_url,
            candidate_email,
            candidate_password,
            job_slug,
            job_title,
        )
        report.steps.append("STG11-SS-candidate-applied")
        login_employer(page, base_url, employer_a_email, employer_a_password)
        nav.goto_path(f"/account/jobs/{job_slug}/applicants")

    href = page.locator(f'a[href*="/account/jobs/{job_slug}/applications/"]').first.get_attribute(
        "href"
    )
    if not href:
        raise RuntimeError("No application found for self-service job")
    app_id = int(href.rstrip("/").split("/")[-1])
    report.application_id = app_id
    nav.goto_path(f"/account/jobs/{job_slug}/applications/{app_id}")
    report.steps.append("STG11-SS-emp-detail")

    expect(page.get_by_role("heading", name="Recruitment actions")).to_be_visible(timeout=15_000)
    shortlist = page.get_by_role("button", name="Shortlist")
    if shortlist.count() and shortlist.is_enabled():
        page.once("dialog", lambda d: d.accept())
        shortlist.click()
        page.wait_for_load_state("networkidle")
        report.steps.append("STG11-SS-shortlist")

    page.get_by_role("button", name="Schedule interview").click()
    interview_form = page.locator("form").filter(has=page.locator('input[type="datetime-local"]'))
    interview_form.locator('input[type="datetime-local"]').fill("2030-06-15T10:00")
    interview_form.get_by_role("button", name="Schedule", exact=True).click()
    page.wait_for_load_state("networkidle")
    report.steps.append("STG11-SS-interview")

    page.locator("textarea[placeholder*='Message the candidate']").fill("Self-service QA message.")
    page.get_by_role("button", name="Send message").click()
    page.wait_for_load_state("networkidle")
    report.steps.append("STG11-SS-emp-message")

    login_account_user(
        page,
        base_url,
        candidate_email,
        candidate_password,
        journey_id="STG11",
        step_id="ss-candidate",
    )
    nav.goto_path(f"/account/applications/{app_id}")
    expect(page.get_by_role("heading", name="Interviews")).to_be_visible(timeout=15_000)
    report.steps.append("STG11-SS-cand-interviews")

    if page.get_by_role("button", name="Submit response").count():
        page.locator("select").first.select_option("accepted")
        page.get_by_role("button", name="Submit response").click()
        page.wait_for_load_state("networkidle")
        report.steps.append("STG11-SS-cand-response")

    correction = page.locator("textarea[placeholder*='corrected']")
    if correction.count():
        correction.fill("Please confirm my application snapshot qualification.")
        page.get_by_role("button", name="Submit request").click()
        page.wait_for_load_state("networkidle")
        report.steps.append("STG11-SS-correction")

    page.locator("textarea[placeholder*='Message the employer']").fill("Thanks for the update.")
    page.get_by_role("button", name="Send message").nth(0).click()
    page.wait_for_load_state("networkidle")
    report.steps.append("STG11-SS-cand-message")

    login_employer(page, base_url, employer_a_email, employer_a_password)
    nav.goto_path(f"/account/jobs/{job_slug}/applications/{app_id}")
    ack = page.get_by_role("button", name="Acknowledge")
    if ack.count():
        ack.first.click()
        page.wait_for_load_state("networkidle")
        report.steps.append("STG11-SS-correction-ack")

    return report
