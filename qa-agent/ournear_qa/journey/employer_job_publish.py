from __future__ import annotations

import re
import shutil
import subprocess
import uuid
from dataclasses import dataclass, field
from pathlib import Path

from playwright.sync_api import Page, Response, expect

from ournear_qa.browser.forms import FormHelper
from ournear_qa.browser.navigation import NavigationHelper
from ournear_qa.journey.account_auth import login_account_user
from ournear_qa.journey.errors import JourneyStepError

REPO_ROOT = Path(__file__).resolve().parents[3]
JRN_ID = "JRN-EMPLOYER-JOB-PUBLISH-01"

# Actions exposed on Account/Employer/Jobs/Index (router.patch lifecycle).
EMPLOYER_JOB_LIFECYCLE_UI_ACTIONS = frozenset({"publish", "pause", "close"})


@dataclass(frozen=True)
class JobFormDefaults:
    industry_id: str
    job_category_id: str
    job_title_id: str
    location_id: str
    employment_type_id: str
    experience_range_id: str
    company_name: str


@dataclass
class EmployerJobPublishContext:
    job_title: str
    job_slug: str | None = None
    steps_completed: list[str] = field(default_factory=list)


def journey_job_title(suffix: str | None = None) -> str:
    token = suffix or uuid.uuid4().hex[:8]
    return f"QA Journey Publish Role {token}"


def discover_job_form_defaults(repo_root: Path = REPO_ROOT) -> JobFormDefaults | None:
    script = repo_root / "scripts" / "inspect-qa-employer-job-form-defaults.php"
    php = shutil.which("php")
    if not script.is_file() or php is None:
        return None

    completed = subprocess.run(
        [php, str(script)],
        cwd=repo_root,
        capture_output=True,
        text=True,
        check=False,
    )
    if completed.returncode != 0:
        return None

    fields: dict[str, str] = {}
    for line in completed.stdout.splitlines():
        if "=" in line:
            key, value = line.split("=", 1)
            fields[key.strip()] = value.strip()

    if fields.get("found") != "1":
        return None

    try:
        return JobFormDefaults(
            industry_id=fields["industry_id"],
            job_category_id=fields["job_category_id"],
            job_title_id=fields["job_title_id"],
            location_id=fields["location_id"],
            employment_type_id=fields["employment_type_id"],
            experience_range_id=fields["experience_range_id"],
            company_name=fields.get("company_name", ""),
        )
    except KeyError:
        return None


def _account_jobs_post(response: Response) -> bool:
    if response.request.method != "POST":
        return False
    return "/account/jobs" in response.url and "/lifecycle" not in response.url


def _account_jobs_put(response: Response) -> bool:
    return response.request.method == "PUT" and "/account/jobs/" in response.url


def _lifecycle_patch(response: Response) -> bool:
    return response.request.method == "PATCH" and "/lifecycle" in response.url


def login_employer_a(
    page: Page,
    base_url: str,
    email: str,
    password: str,
    nav: NavigationHelper | None = None,
) -> NavigationHelper:
    return login_account_user(
        page,
        base_url,
        email,
        password,
        journey_id=JRN_ID,
        step_id="employer-a-login",
        nav=nav,
    )


def step_employer_dashboard_and_jobs(
    page: Page,
    nav: NavigationHelper,
    ctx: EmployerJobPublishContext,
) -> None:
    nav.goto_path("/account/hiring")
    nav.assert_heading("Hiring Command Center")
    ctx.steps_completed.append("P0-01-hiring-dashboard")

    nav.goto_path("/account/jobs")
    nav.assert_heading("Job management")
    ctx.steps_completed.append("P0-02-jobs-index")


def step_create_validation_errors(
    page: Page,
    nav: NavigationHelper,
    forms: FormHelper,
    ctx: EmployerJobPublishContext,
) -> None:
    nav.goto_path("/account/jobs/create")
    nav.assert_heading("Create Job")

    with page.expect_response(_account_jobs_post, timeout=30_000) as response_info:
        forms.click_submit("Save draft")
    response = response_info.value
    if response.status == 422:
        ctx.steps_completed.append("P0-03-create-validation-422")
        return
    if response.status >= 400:
        raise JourneyStepError(
            "P0-03-create-validation",
            "validation response (422 or field errors)",
            f"HTTP {response.status}",
            journey_id=JRN_ID,
        )
    ctx.steps_completed.append("P0-03-create-validation")


def fill_new_job_form(
    page: Page,
    forms: FormHelper,
    defaults: JobFormDefaults,
    *,
    title: str,
    description: str = "QA journey job description for publish lifecycle.",
) -> None:
    page.locator("#title").fill(title)
    forms.select_option("Industry", defaults.industry_id)
    forms.select_option("Job category", defaults.job_category_id)
    forms.select_option("Classified job title", defaults.job_title_id)
    forms.select_option("Location", defaults.location_id)
    forms.select_option("Employment type", defaults.employment_type_id)
    forms.select_option("Experience", defaults.experience_range_id)
    forms.fill_textarea("Description", description)
    forms.fill_textarea("Requirements", "QA journey requirements.")


def step_save_draft(
    page: Page,
    nav: NavigationHelper,
    forms: FormHelper,
    defaults: JobFormDefaults,
    ctx: EmployerJobPublishContext,
) -> None:
    nav.goto_path("/account/jobs/create")
    fill_new_job_form(page, forms, defaults, title=ctx.job_title)

    with page.expect_response(_account_jobs_post, timeout=45_000) as response_info:
        forms.click_submit("Save draft")
    response = response_info.value
    if response.status >= 400:
        raise JourneyStepError(
            "P0-04-save-draft",
            "POST /account/jobs success",
            f"HTTP {response.status}",
            journey_id=JRN_ID,
        )

    nav.assert_heading("Job management")
    expect(page.get_by_text("Job saved as draft", exact=False)).to_be_visible(timeout=15_000)
    ctx.steps_completed.append("P0-04-save-draft")


def _job_card(page: Page, title: str):
    return page.locator("article").filter(has=page.get_by_role("heading", name=title, exact=True))


def step_reopen_draft_and_verify(
    page: Page,
    nav: NavigationHelper,
    forms: FormHelper,
    ctx: EmployerJobPublishContext,
) -> None:
    nav.goto_path("/account/jobs?listing=draft")
    card = _job_card(page, ctx.job_title)
    expect(card).to_be_visible(timeout=20_000)
    card.get_by_role("link", name="Edit").click()
    nav.assert_heading("Edit Job")
    expect(page.locator("#title")).to_have_value(ctx.job_title)
    ctx.steps_completed.append("P0-05-draft-persisted")


def step_publish_from_management(
    page: Page,
    nav: NavigationHelper,
    ctx: EmployerJobPublishContext,
) -> None:
    nav.goto_path("/account/jobs")
    card = _job_card(page, ctx.job_title)
    expect(card).to_be_visible(timeout=20_000)

    with page.expect_response(_lifecycle_patch, timeout=45_000) as response_info:
        card.get_by_role("button", name="Publish").click()
    response = response_info.value
    if response.status >= 400:
        raise JourneyStepError(
            "P0-06-publish",
            "PATCH lifecycle publish success",
            f"HTTP {response.status}",
            journey_id=JRN_ID,
        )
    ctx.steps_completed.append("P0-06-published")


def _slug_from_public_view_link(card) -> str:
    href = card.get_by_role("link", name="View").get_attribute("href") or ""
    match = re.search(r"/jobs/([^/?#]+)", href)
    if not match:
        raise JourneyStepError(
            "P0-06b-slug",
            "public view link with slug",
            href or "(empty)",
            journey_id=JRN_ID,
        )
    return match.group(1)


def _assert_hidden_from_public_jobs(
    page: Page,
    nav: NavigationHelper,
    ctx: EmployerJobPublishContext,
    *,
    base_url: str,
    step_id: str,
) -> None:
    if not ctx.job_slug:
        raise JourneyStepError(step_id, "job slug", "missing", journey_id=JRN_ID)

    detail_url = f"{base_url.rstrip('/')}/jobs/{ctx.job_slug}"
    detail_response = page.goto(detail_url, wait_until="domcontentloaded")
    status = detail_response.status if detail_response else 0
    if status not in (404, 410):
        raise JourneyStepError(
            step_id,
            "public job detail hidden (404/410)",
            f"HTTP {status}",
            journey_id=JRN_ID,
        )
    nav.goto_path("/jobs")
    expect(page.get_by_role("link", name=ctx.job_title, exact=True)).to_have_count(0, timeout=15_000)


def step_public_visibility(
    page: Page,
    nav: NavigationHelper,
    ctx: EmployerJobPublishContext,
) -> None:
    nav.goto_path("/account/jobs")
    card = _job_card(page, ctx.job_title)
    slug = _slug_from_public_view_link(card)
    ctx.job_slug = slug

    nav.goto_path(f"/jobs/{slug}")
    title_headings = page.get_by_role("heading", name=ctx.job_title, exact=True)
    expect(title_headings.first).to_be_visible(timeout=20_000)
    ctx.steps_completed.append("P0-07-public-detail")

    nav.goto_path("/jobs")
    expect(page.get_by_role("link", name=ctx.job_title, exact=True)).to_be_visible(timeout=20_000)
    ctx.steps_completed.append("P0-08-public-listing")


def step_pause_published_job(
    page: Page,
    nav: NavigationHelper,
    ctx: EmployerJobPublishContext,
) -> None:
    nav.goto_path("/account/jobs")
    card = _job_card(page, ctx.job_title)
    expect(card).to_be_visible(timeout=20_000)
    pause_btn = card.get_by_role("button", name="Pause")
    if pause_btn.count() == 0:
        raise JourneyStepError(
            "P0-08c-pause",
            "Pause control on published job card",
            "button not found",
            journey_id=JRN_ID,
        )
    with page.expect_response(_lifecycle_patch, timeout=45_000) as response_info:
        pause_btn.click()
    if response_info.value.status >= 400:
        raise JourneyStepError(
            "P0-08c-pause",
            "PATCH lifecycle pause success",
            f"HTTP {response_info.value.status}",
            journey_id=JRN_ID,
        )
    ctx.steps_completed.append("P0-08c-paused")


def step_assert_paused_hidden_from_public(
    page: Page,
    nav: NavigationHelper,
    ctx: EmployerJobPublishContext,
    *,
    base_url: str,
) -> None:
    _assert_hidden_from_public_jobs(
        page,
        nav,
        ctx,
        base_url=base_url,
        step_id="P0-08d-paused-not-public",
    )
    ctx.steps_completed.append("P0-08d-paused-not-public")


def step_republish_after_pause(
    page: Page,
    nav: NavigationHelper,
    ctx: EmployerJobPublishContext,
) -> None:
    step_publish_from_management(page, nav, ctx)
    ctx.steps_completed.append("P0-08e-republished")


def step_edit_published_job(
    page: Page,
    nav: NavigationHelper,
    forms: FormHelper,
    ctx: EmployerJobPublishContext,
) -> None:
    if not ctx.job_slug:
        raise JourneyStepError("P0-09-edit", "job slug", "missing", journey_id=JRN_ID)

    updated = f"{ctx.job_title} Updated"
    nav.goto_path(f"/account/jobs/{ctx.job_slug}/edit")
    nav.assert_heading("Edit Job")
    page.locator("#title").fill(updated)

    with page.expect_response(_account_jobs_put, timeout=45_000) as response_info:
        forms.click_submit("Save job")
    if response_info.value.status >= 400:
        raise JourneyStepError(
            "P0-09-edit",
            "PUT job update success",
            f"HTTP {response_info.value.status}",
            journey_id=JRN_ID,
        )

    ctx.job_title = updated
    nav.goto_path(f"/jobs/{ctx.job_slug}")
    expect(page.get_by_role("heading", name=updated, exact=True).first).to_be_visible(timeout=20_000)
    ctx.steps_completed.append("P0-09-edit-persisted")


def step_close_job(
    page: Page,
    nav: NavigationHelper,
    ctx: EmployerJobPublishContext,
    *,
    base_url: str,
) -> None:
    if not ctx.job_slug:
        raise JourneyStepError("P0-10-close", "job slug", "missing", journey_id=JRN_ID)

    nav.goto_path("/account/jobs")
    card = _job_card(page, ctx.job_title)
    with page.expect_response(_lifecycle_patch, timeout=45_000) as response_info:
        card.get_by_role("button", name="Close").click()
    if response_info.value.status >= 400:
        raise JourneyStepError(
            "P0-10-close",
            "PATCH lifecycle close success",
            f"HTTP {response_info.value.status}",
            journey_id=JRN_ID,
        )
    ctx.steps_completed.append("P0-10-closed")

    _assert_hidden_from_public_jobs(
        page,
        nav,
        ctx,
        base_url=base_url,
        step_id="P0-11-not-public-after-close",
    )
    ctx.steps_completed.append("P0-11-not-public-after-close")
    ctx.steps_completed.append("P0-11b-absent-from-listing")


def step_employer_b_cannot_edit(
    page: Page,
    base_url: str,
    email: str,
    password: str,
    ctx: EmployerJobPublishContext,
) -> None:
    if not ctx.job_slug:
        raise JourneyStepError("P0-12-isolation", "job slug", "missing", journey_id=JRN_ID)

    page.context.clear_cookies()
    login_account_user(
        page,
        base_url,
        email,
        password,
        journey_id=JRN_ID,
        step_id="employer-b-login",
    )
    page.goto(f"{base_url.rstrip('/')}/account/jobs/{ctx.job_slug}/edit", wait_until="domcontentloaded")
    expect(page.get_by_role("heading", name="403")).to_be_visible(timeout=20_000)
    ctx.steps_completed.append("P0-12-employer-b-forbidden")


def step_candidate_cannot_manage_jobs(
    page: Page,
    base_url: str,
    email: str,
    password: str,
    ctx: EmployerJobPublishContext,
) -> None:
    page.context.clear_cookies()
    login_account_user(
        page,
        base_url,
        email,
        password,
        journey_id=JRN_ID,
        step_id="candidate-login",
    )
    page.goto(f"{base_url.rstrip('/')}/account/jobs/create", wait_until="domcontentloaded")
    expect(page.get_by_role("heading", name="403")).to_be_visible(timeout=20_000)
    if ctx.job_slug:
        page.goto(f"{base_url.rstrip('/')}/account/jobs/{ctx.job_slug}/edit", wait_until="domcontentloaded")
        expect(page.get_by_role("heading", name="403")).to_be_visible(timeout=20_000)
    ctx.steps_completed.append("P0-13-candidate-forbidden")


def run_employer_job_publish_lifecycle(
    page: Page,
    base_url: str,
    employer_email: str,
    employer_password: str,
    defaults: JobFormDefaults,
    *,
    title: str | None = None,
) -> EmployerJobPublishContext:
    ctx = EmployerJobPublishContext(job_title=title or journey_job_title())
    nav = login_employer_a(page, base_url, employer_email, employer_password)
    forms = FormHelper(page)

    step_employer_dashboard_and_jobs(page, nav, ctx)
    step_create_validation_errors(page, nav, forms, ctx)
    step_save_draft(page, nav, forms, defaults, ctx)
    step_reopen_draft_and_verify(page, nav, forms, ctx)
    step_publish_from_management(page, nav, ctx)
    step_public_visibility(page, nav, ctx)
    step_pause_published_job(page, nav, ctx)
    step_assert_paused_hidden_from_public(page, nav, ctx, base_url=base_url)
    step_republish_after_pause(page, nav, ctx)
    step_public_visibility(page, nav, ctx)
    step_edit_published_job(page, nav, forms, ctx)
    step_close_job(page, nav, ctx, base_url=base_url)
    return ctx
