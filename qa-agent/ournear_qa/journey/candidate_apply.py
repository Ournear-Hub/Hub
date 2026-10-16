from __future__ import annotations

import re
import shutil
import subprocess
from dataclasses import dataclass, field
from pathlib import Path

from playwright.sync_api import Page, Response, expect

from ournear_qa.browser.navigation import NavigationHelper
from ournear_qa.journey.account_auth import login_account_user
from ournear_qa.fixtures.hiring import QA_FIXTURE_SLUG_PREFIX, SLUG_PUBLISHED_A, TITLE_PUBLISHED_A

REPO_ROOT = Path(__file__).resolve().parents[3]
JRN_ID = "JRN-CANDIDATE-APPLY-01"


from ournear_qa.journey.errors import JourneyStepError  # re-export for conftest/tests

@dataclass
class ApplyJourneyContext:
    job_slug: str
    job_title: str
    business_id: str | None = None
    steps_completed: list[str] = field(default_factory=list)


def discover_qa_apply_target(
    *,
    override_slug: str | None = None,
    repo_root: Path = REPO_ROOT,
) -> tuple[str, str] | None:
    """Return (slug, title) for an unapplied published qa-fixture job, or None."""
    if override_slug:
        return override_slug, override_slug  # title filled by caller when configured

    script = repo_root / "scripts" / "inspect-qa-apply-eligibility.php"
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

    in_open_section = False
    for line in completed.stdout.splitlines():
        if line.startswith("--- all published"):
            in_open_section = True
            continue
        if line.startswith("job id=") and "business_id=" in line:
            in_open_section = False
        if not in_open_section:
            continue
        match = re.search(r"slug=(\S+)\s+employer=\S+\s+title=(.+)$", line)
        if not match:
            continue
        slug, title = match.group(1), match.group(2).strip()
        if slug.startswith(QA_FIXTURE_SLUG_PREFIX):
            return slug, title
    return None


def login_candidate(
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
        step_id="D1-01-login-dashboard",
        nav=nav,
    )


def provision_journey_apply_target(repo_root: Path = REPO_ROOT) -> tuple[str, str] | None:
    script = repo_root / "scripts" / "qa-provision-journey-apply-job.php"
    php = shutil.which("php")
    if not script.is_file() or php is None:
        return None
    completed = subprocess.run(
        [php, str(script)], cwd=repo_root, capture_output=True, text=True, check=False
    )
    if completed.returncode != 0:
        return None
    slug = title = ""
    for line in completed.stdout.splitlines():
        if line.startswith("slug="):
            slug = line.split("=", 1)[1].strip()
        if line.startswith("title="):
            title = line.split("=", 1)[1].strip()
    if slug and title:
        return slug, title
    return None


def job_show_url_pattern(job_slug: str) -> re.Pattern[str]:
    """Job detail URL only — must not match `/jobs/{slug}/apply`."""
    return re.compile(rf".*/jobs/{re.escape(job_slug)}/?$")


def _apply_post_response(page: Page, job_slug: str) -> Response:
    apply_path = f"/jobs/{job_slug}/apply"

    def is_apply_post(response: Response) -> bool:
        if response.request.method != "POST":
            return False
        return response.url.rstrip("/").endswith(apply_path)

    with page.expect_response(is_apply_post, timeout=30_000) as response_info:
        page.get_by_role("button", name="Submit application").click()
    return response_info.value


def open_job_detail(page: Page, base_url: str, job_title: str, job_slug: str) -> None:
    nav = NavigationHelper(page, base_url)
    nav.goto_path(f"/jobs/{job_slug}")
    expect(page.get_by_role("heading", name=job_title).first).to_be_visible(timeout=30_000)


def run_new_application_steps(
    page: Page,
    base_url: str,
    email: str,
    password: str,
    job_slug: str,
    job_title: str,
) -> ApplyJourneyContext:
    ctx = ApplyJourneyContext(job_slug=job_slug, job_title=job_title)
    nav = login_candidate(page, base_url, email, password)
    ctx.steps_completed.append("D1-01-login-dashboard")

    open_job_detail(page, base_url, job_title, job_slug)
    ctx.steps_completed.append("D1-02-job-detail-open")

    apply_link = page.get_by_role("link", name="Apply")
    if apply_link.count() == 0:
        raise JourneyStepError("D1-03-apply-available", "Apply link visible", "missing Apply control")
    apply_link.first.click()
    try:
        page.wait_for_url(f"**/jobs/{job_slug}/apply**", timeout=30_000)
    except Exception:
        nav.goto_path(f"/jobs/{job_slug}/apply")
    ctx.steps_completed.append("D1-03-apply-page")

    expect(page.get_by_text("Ready to include with your application.")).to_be_visible(timeout=15_000)
    ctx.steps_completed.append("D1-04-resume-ready")

    post_response = _apply_post_response(page, job_slug)
    if post_response.status == 429:
        retry_after = post_response.headers.get("retry-after", "")
        detail = "HTTP 429 Too Many Requests (throttle:job-applications)"
        if retry_after:
            detail = f"{detail}; Retry-After={retry_after}"
        raise JourneyStepError(
            "D1-05-submit-blocked",
            "application POST accepted (not rate limited)",
            detail,
        )
    if post_response.status >= 400:
        raise JourneyStepError(
            "D1-05-submit-blocked",
            "application POST redirect/success (<400)",
            f"HTTP {post_response.status}",
        )

    page.wait_for_url(job_show_url_pattern(job_slug), timeout=30_000)
    expect(page.get_by_text("Application submitted")).to_be_visible(timeout=15_000)
    ctx.steps_completed.append("D1-05-submit-confirmation")

    body = page.locator("body").inner_text()
    match = re.search(r"ONT-A-\d{6}", body)
    ctx.business_id = match.group(0) if match else None
    if not ctx.business_id:
        raise JourneyStepError("D1-06-business-id", "ONT-A-* on confirmation", "not found in page text")
    ctx.steps_completed.append("D1-06-business-id")

    nav.goto_path("/account/applications")
    # List rows show job title, not public business_id (REG-PROD-001 / Personal Dashboard parity).
    title_loc = page.get_by_role("heading", name=job_title, level=2)
    if title_loc.count() == 0:
        expect(page.get_by_text(job_title, exact=False).first).to_be_visible(timeout=15_000)
    else:
        expect(title_loc.first).to_be_visible(timeout=15_000)
    ctx.steps_completed.append("D1-07-applications-list")

    page.reload(wait_until="domcontentloaded")
    if title_loc.count() == 0:
        expect(page.get_by_text(job_title, exact=False).first).to_be_visible(timeout=15_000)
    else:
        expect(title_loc.first).to_be_visible(timeout=15_000)
    ctx.steps_completed.append("D1-08-persist-after-reload")

    page.goto(f"{base_url.rstrip('/')}/jobs/{job_slug}/apply", wait_until="domcontentloaded")
    if f"/jobs/{job_slug}/apply" in page.url:
        raise JourneyStepError("D1-09-duplicate-guard", f"redirect away from apply when applied", page.url)
    expect(page.get_by_text("Applied", exact=True).first).to_be_visible(timeout=15_000)
    ctx.steps_completed.append("D1-09-duplicate-guard")

    return ctx


def run_guest_intended_application_steps(
    page: Page,
    base_url: str,
    email: str,
    password: str,
    job_slug: str,
    job_title: str,
) -> ApplyJourneyContext:
    """Guest opens apply → login → intended apply URL → submit → persist → duplicate guard."""
    from ournear_qa.browser.forms import FormHelper

    ctx = ApplyJourneyContext(job_slug=job_slug, job_title=job_title)
    nav = NavigationHelper(page, base_url)
    page.context.clear_cookies()
    page.goto(f"{base_url.rstrip('/')}/jobs/{job_slug}/apply", wait_until="networkidle")
    if "/login" not in page.url:
        raise JourneyStepError("G1-01-guest-apply-login", "redirect to /login", page.url)
    ctx.steps_completed.append("G1-01-guest-apply-login")

    forms = FormHelper(page)
    forms.fill_account_login(email)
    forms.fill_field("Password", password)
    with page.expect_response(
        lambda response: response.request.method == "POST" and response.url.rstrip("/").endswith("/login"),
        timeout=30_000,
    ):
        forms.click_login_submit()
    page.wait_for_url(re.compile(rf".*/jobs/{re.escape(job_slug)}/apply"), timeout=30_000)
    ctx.steps_completed.append("G1-02-intended-apply-landing")

    expect(
        page.get_by_role("heading", name=re.compile(re.escape(job_title), re.I)).first
    ).to_be_visible(timeout=20_000)
    expect(page.get_by_text("Ready to include with your application.")).to_be_visible(timeout=15_000)
    ctx.steps_completed.append("G1-03-resume-ready")

    post_response = _apply_post_response(page, job_slug)
    if post_response.status >= 400:
        raise JourneyStepError(
            "G1-04-submit",
            "application POST success",
            f"HTTP {post_response.status}",
        )
    page.wait_for_url(job_show_url_pattern(job_slug), timeout=30_000)
    expect(page.get_by_text("Application submitted")).to_be_visible(timeout=15_000)
    ctx.steps_completed.append("G1-04-submit-confirmation")

    body = page.locator("body").inner_text()
    match = re.search(r"ONT-A-\d{6}", body)
    ctx.business_id = match.group(0) if match else None
    if not ctx.business_id:
        raise JourneyStepError("G1-05-business-id", "ONT-A-* on confirmation", "not found")
    ctx.steps_completed.append("G1-05-business-id")

    nav.goto_path("/account/applications")
    expect(page.get_by_text(ctx.business_id)).to_be_visible(timeout=15_000)
    page.reload(wait_until="domcontentloaded")
    expect(page.get_by_text(ctx.business_id)).to_be_visible(timeout=15_000)
    ctx.steps_completed.append("G1-06-persist-after-reload")

    page.goto(f"{base_url.rstrip('/')}/jobs/{job_slug}/apply", wait_until="domcontentloaded")
    if f"/jobs/{job_slug}/apply" in page.url:
        raise JourneyStepError("G1-07-duplicate-guard", "redirect away from apply when applied", page.url)
    expect(page.get_by_text("Applied", exact=True).first).to_be_visible(timeout=15_000)
    ctx.steps_completed.append("G1-07-duplicate-guard")

    return ctx


def run_duplicate_guard_steps(page: Page, base_url: str, email: str, password: str) -> None:
    """Uses fixture job A (already applied by qa:hiring-fixtures) — no new submission."""
    nav = login_candidate(page, base_url, email, password)
    nav.goto_path(f"/jobs/{SLUG_PUBLISHED_A}")
    expect(page.get_by_text("Applied", exact=True).first).to_be_visible(timeout=15_000)

    fixture_row = page.locator("li").filter(
        has=page.locator(f'a[href*="/jobs/{SLUG_PUBLISHED_A}"]'),
    )
    nav.goto_path("/account/applications")
    page.wait_for_load_state("domcontentloaded")
    page_hrefs = page.locator('nav[aria-label="Pagination"] a[href*="page="]').evaluate_all(
        "els => [...new Set(els.map(e => e.getAttribute('href')).filter(Boolean))]",
    )
    if not page_hrefs:
        page_hrefs = [page.url]
    for href in page_hrefs:
        if href.startswith("http"):
            page.goto(href, wait_until="domcontentloaded")
        else:
            nav.goto_path(href)
        if fixture_row.count() > 0:
            break
    expect(fixture_row.first).to_be_visible(timeout=15_000)
    page.reload(wait_until="domcontentloaded")
    expect(fixture_row.first).to_be_visible(timeout=15_000)

    page.goto(f"{base_url.rstrip('/')}/jobs/{SLUG_PUBLISHED_A}/apply", wait_until="domcontentloaded")
    assert f"/jobs/{SLUG_PUBLISHED_A}/apply" not in page.url
    expect(page.get_by_text("Applied", exact=True).first).to_be_visible(timeout=15_000)
