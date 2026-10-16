from __future__ import annotations

import re
import shutil
import subprocess
from dataclasses import dataclass, field
from pathlib import Path

from urllib.parse import urlparse

from playwright.sync_api import Page, Response, expect

from ournear_qa.browser.forms import FormHelper
from ournear_qa.browser.navigation import NavigationHelper
from ournear_qa.journey.account_auth import login_account_user
from ournear_qa.journey.errors import JourneyStepError
from ournear_qa.journey.employer_resume import pdf_bytes_are_readable

REPO_ROOT = Path(__file__).resolve().parents[3]
JRN_ID = "JRN-ADMIN-APPLICATION-01"
_STATUS_REVIEWING = "Reviewing"


@dataclass
class AdminJourneyTarget:
    application_id: int
    business_id: str
    job_slug: str
    job_title: str
    resume_path_set: bool
    status: str


@dataclass
class AdminApplicationJourneyContext:
    target: AdminJourneyTarget
    steps_completed: list[str] = field(default_factory=list)
    status_changed: bool = False


def discover_admin_journey_target(repo_root: Path = REPO_ROOT) -> AdminJourneyTarget | None:
    script = repo_root / "scripts" / "inspect-qa-employer-journey-target.php"
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
    if completed.returncode not in (0, 2):
        return None

    fields: dict[str, str] = {}
    for line in completed.stdout.splitlines():
        if "=" in line:
            key, value = line.split("=", 1)
            fields[key.strip()] = value.strip()

    if fields.get("found") != "1":
        return None

    try:
        application_id = int(fields["application_id"])
    except (KeyError, ValueError):
        return None

    business_id = fields.get("business_id", "")
    if not business_id:
        return None

    return AdminJourneyTarget(
        application_id=application_id,
        business_id=business_id,
        job_slug=fields.get("job_slug", ""),
        job_title=fields.get("job_title", fields.get("job_slug", "")),
        resume_path_set=fields.get("resume_path_set") == "yes",
        status=fields.get("status", "New"),
    )


def _is_authenticated_admin_url(url: str) -> bool:
    path = urlparse(url).path
    if path in ("/admin", "/admin/"):
        return True
    if path.startswith("/admin/") and not path.startswith(
        ("/admin/login", "/admin/forgot-password", "/admin/reset-password")
    ):
        return True
    return False


def login_admin(
    page: Page,
    base_url: str,
    email: str,
    password: str,
    nav: NavigationHelper | None = None,
) -> NavigationHelper:
    navigation = nav or NavigationHelper(page, base_url)
    forms = FormHelper(page)
    page.context.clear_cookies()
    navigation.goto_path("/admin/login")
    forms.fill_field("Email", email)
    forms.fill_field("Password", password)
    with page.expect_response(
        lambda response: response.request.method == "POST" and response.url.rstrip("/").endswith("/admin/login"),
        timeout=30_000,
    ) as login_response_info:
        forms.click_submit("Log in")
    login_response = login_response_info.value
    if login_response.status >= 400:
        raise JourneyStepError(
            "D3-01-admin-login",
            "admin login POST success",
            f"HTTP {login_response.status}",
            journey_id=JRN_ID,
        )
    page.wait_for_url(lambda url: _is_authenticated_admin_url(url), timeout=30_000)
    navigation.assert_heading("Operations dashboard")
    return navigation


def _admin_applications_url(application_id: int, business_id: str) -> str:
    from urllib.parse import quote

    return f"/admin/applications?search={quote(business_id)}&application={application_id}"


def _admin_resume_path(application_id: int) -> str:
    return f"/admin/applications/{application_id}/resume"


def _drawer(page: Page):
    return page.get_by_role("dialog").last


def _open_application_drawer(page: Page, base_url: str, target: AdminJourneyTarget) -> None:
    nav = NavigationHelper(page, base_url)
    nav.goto_path(_admin_applications_url(target.application_id, target.business_id))
    drawer = _drawer(page)
    expect(drawer).to_be_visible(timeout=30_000)
    expect(drawer.get_by_role("heading", name=target.business_id)).to_be_visible(timeout=15_000)


def _verify_admin_resume(page: Page, base_url: str, application_id: int) -> None:
    drawer = _drawer(page)
    drawer.get_by_role("tab", name="Resume").click()
    view_link = drawer.get_by_role("link", name="View Resume")
    download_link = drawer.get_by_role("link", name="Download Resume")
    expect(view_link).to_be_visible(timeout=15_000)
    expect(download_link).to_be_visible(timeout=15_000)

    resume_path = _admin_resume_path(application_id)
    if resume_path not in (view_link.get_attribute("href") or ""):
        raise JourneyStepError(
            "D3-06-resume-links",
            f"View Resume href contains {resume_path}",
            view_link.get_attribute("href") or "",
            journey_id=JRN_ID,
        )

    with page.expect_download(timeout=30_000) as download_info:
        download_link.click()
    path = download_info.value.path()
    if not path:
        raise JourneyStepError(
            "D3-07-resume-download",
            "download path from admin Download Resume",
            "empty",
            journey_id=JRN_ID,
        )
    if not pdf_bytes_are_readable(Path(path).read_bytes()):
        raise JourneyStepError(
            "D3-07-resume-download",
            "readable synthetic PDF",
            "bytes invalid",
            journey_id=JRN_ID,
        )


def _maybe_update_status(page: Page, target: AdminJourneyTarget) -> bool:
    drawer = _drawer(page)
    drawer.get_by_role("tab", name="Overview").click()
    status_select = drawer.get_by_role("combobox", name="Application status")

    if target.status != "New":
        expect(status_select).to_have_value(target.status, timeout=15_000)
        return False

    def is_status_patch(response: Response) -> bool:
        return (
            response.request.method == "PATCH"
            and f"/admin/applications/{target.application_id}/status" in response.url
        )

    with page.expect_response(is_status_patch, timeout=30_000) as response_info:
        status_select.select_option(_STATUS_REVIEWING)
    response = response_info.value
    if response.status >= 400:
        raise JourneyStepError(
            "D3-08-status-update",
            "PATCH status success",
            f"HTTP {response.status}",
            journey_id=JRN_ID,
        )
    expect(status_select).to_have_value(_STATUS_REVIEWING, timeout=15_000)
    return True


def run_admin_application_steps(
    page: Page,
    base_url: str,
    email: str,
    password: str,
    target: AdminJourneyTarget,
) -> AdminApplicationJourneyContext:
    ctx = AdminApplicationJourneyContext(target=target)
    login_admin(page, base_url, email, password)
    ctx.steps_completed.append("D3-01-admin-login")

    nav = NavigationHelper(page, base_url)
    nav.goto_path("/admin/applications")
    expect(page.get_by_role("heading", name="Applications")).to_be_visible(timeout=30_000)
    ctx.steps_completed.append("D3-02-applications-index")

    _open_application_drawer(page, base_url, target)
    ctx.steps_completed.append("D3-03-open-application")

    drawer = _drawer(page)
    drawer.get_by_role("tab", name="Overview").click()
    expect(drawer.get_by_text("Application ID", exact=True)).to_be_visible(timeout=15_000)
    expect(drawer.locator("dd").filter(has_text=target.business_id).first).to_be_visible(timeout=15_000)
    ctx.steps_completed.append("D3-04-application-reference")

    if not target.resume_path_set:
        raise JourneyStepError(
            "D3-05-resume-prerequisite",
            "resume snapshot on QA application",
            "resume_path_set=no",
            journey_id=JRN_ID,
        )
    _verify_admin_resume(page, base_url, target.application_id)
    ctx.steps_completed.append("D3-06-07-resume-readable")

    ctx.status_changed = _maybe_update_status(page, target)
    ctx.steps_completed.append("D3-08-status-verified" if ctx.status_changed else "D3-08-status-unchanged")

    page.reload(wait_until="domcontentloaded")
    expect(_drawer(page)).to_be_visible(timeout=30_000)
    status_select = _drawer(page).get_by_role("combobox", name="Application status")
    expected_status = _STATUS_REVIEWING if ctx.status_changed else target.status
    expect(status_select).to_have_value(expected_status, timeout=15_000)
    ctx.steps_completed.append("D3-09-persist-after-reload")

    return ctx


def assert_role_cannot_access_admin_applications(
    page: Page,
    base_url: str,
    email: str,
    password: str,
    *,
    application_id: int,
    business_id: str,
) -> None:
    nav = NavigationHelper(page, base_url)
    login_account_user(
        page,
        base_url,
        email,
        password,
        journey_id=JRN_ID,
        step_id="D3-isolation-account-login",
        nav=nav,
    )

    response = page.goto(f"{base_url.rstrip('/')}/admin/applications", wait_until="domcontentloaded")
    status = response.status if response else 0
    body = page.locator("body").inner_text()
    if business_id in body:
        raise JourneyStepError(
            "D3-isolation-leak",
            "forbidden admin workspace must not expose application id",
            "business id in body",
            journey_id=JRN_ID,
        )
    if status == 403 or page.get_by_role("heading", name="403").count() > 0:
        pass
    elif re.search(r"\b403\b|Forbidden", body, re.I):
        pass
    elif "/admin/login" in page.url:
        pass
    else:
        raise JourneyStepError(
            "D3-isolation-admin-index",
            "403, forbidden, or admin login redirect",
            f"status={status} url={page.url}",
            journey_id=JRN_ID,
        )

    resume_response = page.goto(
        f"{base_url.rstrip('/')}{_admin_resume_path(application_id)}",
        wait_until="domcontentloaded",
    )
    resume_status = resume_response.status if resume_response else 0
    resume_body = page.locator("body").inner_text()
    if business_id in resume_body:
        raise JourneyStepError(
            "D3-isolation-leak",
            "forbidden admin resume must not expose application id",
            "business id in body",
            journey_id=JRN_ID,
        )
    if resume_status not in (403, 401) and not re.search(r"\b403\b|Forbidden|login", resume_body, re.I):
        raise JourneyStepError(
            "D3-isolation-admin-resume",
            "403 or login for admin resume URL",
            f"status={resume_status} url={page.url}",
            journey_id=JRN_ID,
        )
