from __future__ import annotations

import re
import shutil
import subprocess
from dataclasses import dataclass, field
from pathlib import Path

from playwright.sync_api import Page, expect

from ournear_qa.browser.navigation import NavigationHelper
from ournear_qa.journey.account_auth import login_account_user
from ournear_qa.journey.errors import JourneyStepError

REPO_ROOT = Path(__file__).resolve().parents[3]
JRN_ID = "JRN-EMPLOYER-RESUME-01"
_PDF_MARKER = b"QA readable resume"


@dataclass
class EmployerJourneyTarget:
    application_id: int
    business_id: str
    job_slug: str
    job_title: str
    resume_path_set: bool


@dataclass
class EmployerResumeJourneyContext:
    target: EmployerJourneyTarget
    steps_completed: list[str] = field(default_factory=list)


def discover_employer_journey_target(repo_root: Path = REPO_ROOT) -> EmployerJourneyTarget | None:
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
    job_slug = fields.get("job_slug", "")
    job_title = fields.get("job_title", job_slug)
    if not business_id or not job_slug:
        return None

    return EmployerJourneyTarget(
        application_id=application_id,
        business_id=business_id,
        job_slug=job_slug,
        job_title=job_title,
        resume_path_set=fields.get("resume_path_set") == "yes",
    )


def _resume_region(page: Page):
    region = page.get_by_role("region", name="Applicant resume overview")
    if region.count() > 0:
        return region.first
    return page.locator("section").filter(has=page.get_by_role("heading", name="Resume")).first


def pdf_bytes_are_readable(body: bytes) -> bool:
    if not body.startswith(b"%PDF"):
        return False
    if b"%%EOF" not in body:
        return False
    page_markers = len(re.findall(rb"/Type\s*/Page\b", body))
    if page_markers < 1:
        return False
    return _PDF_MARKER in body


def login_employer(
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
        step_id="D2-01-login-dashboard",
        nav=nav,
    )


def _application_show_path(job_slug: str, application_id: int) -> str:
    return f"/account/jobs/{job_slug}/applications/{application_id}"


def _resume_path(application_id: int) -> str:
    return f"/account/employer-applications/{application_id}/resume"


def _download_bytes_from_event(page: Page, trigger) -> bytes | None:
    with page.expect_download(timeout=30_000) as download_info:
        trigger()
    path = download_info.value.path()
    if not path:
        return None
    return Path(path).read_bytes()


def _open_inline_resume(page: Page, view_link, inline_nav_url: str, resume_path: str) -> None:
    try:
        body = _download_bytes_from_event(page, lambda: view_link.first.click())
        if body and pdf_bytes_are_readable(body):
            return
    except Exception:
        pass

    try:
        with page.expect_popup(timeout=5_000) as popup_info:
            view_link.first.click()
        popup = popup_info.value
        popup.wait_for_load_state("load", timeout=30_000)
        if resume_path not in popup.url:
            raise JourneyStepError(
                "D2-06-inline-resume",
                f"popup resume URL contains {resume_path}",
                popup.url,
                journey_id=JRN_ID,
            )
        popup.close()
        return
    except JourneyStepError:
        raise
    except Exception:
        pass

    try:
        def _goto_inline() -> None:
            try:
                page.goto(inline_nav_url, wait_until="commit", timeout=30_000)
            except Exception as exc:
                if "Download is starting" not in str(exc):
                    raise

        body = _download_bytes_from_event(page, _goto_inline)
        if body and pdf_bytes_are_readable(body):
            return
    except Exception:
        pass

    raise JourneyStepError(
        "D2-06-inline-resume",
        "readable PDF via View, popup, or inline navigation",
        "no readable bytes",
        journey_id=JRN_ID,
    )


def assert_http_forbidden(page: Page, base_url: str, path: str, *, forbidden_token: str) -> None:
    response = page.goto(f"{base_url.rstrip('/')}{path}", wait_until="domcontentloaded")
    status = response.status if response else 0
    body = page.locator("body").inner_text()
    if forbidden_token and forbidden_token in body:
        raise JourneyStepError(
            "D2-isolation-leak",
            "forbidden response must not expose owner application id",
            "business id visible on forbidden page",
            journey_id=JRN_ID,
        )
    if status == 403:
        return
    if page.get_by_role("heading", name="403").count() > 0:
        return
    if re.search(r"\b403\b|Forbidden|not authorized|Unauthorized", body, re.I):
        return
    raise JourneyStepError(
        "D2-isolation-forbidden",
        "403 or forbidden UX",
        f"status={status} url={page.url}",
        journey_id=JRN_ID,
    )


def run_employer_a_resume_steps(
    page: Page,
    base_url: str,
    email: str,
    password: str,
    target: EmployerJourneyTarget,
) -> EmployerResumeJourneyContext:
    ctx = EmployerResumeJourneyContext(target=target)
    nav = login_employer(page, base_url, email, password)
    ctx.steps_completed.append("D2-01-login-dashboard")

    nav.goto_path(f"/account/jobs/{target.job_slug}/applicants")
    expect(page.get_by_role("heading", name=target.job_title).first).to_be_visible(timeout=30_000)
    ctx.steps_completed.append("D2-02-applicants-list")

    view_app = page.get_by_role("link", name="View application")
    if view_app.count() == 0:
        raise JourneyStepError(
            "D2-03-locate-application",
            "View application link on applicants list",
            "not found",
            journey_id=JRN_ID,
        )
    view_app.first.click()
    page.wait_for_url(f"**/applications/{target.application_id}**", timeout=30_000)
    ctx.steps_completed.append("D2-03-open-application")

    expect(page.get_by_text("Application ID").first).to_be_visible(timeout=15_000)
    expect(page.get_by_text(target.business_id).first).to_be_visible(timeout=15_000)
    ctx.steps_completed.append("D2-04-application-detail")

    resume_card = _resume_region(page)
    expect(resume_card).to_be_visible(timeout=15_000)
    view_resume = resume_card.get_by_role("link", name="View")
    download_resume = resume_card.get_by_role("link", name="Download")
    if view_resume.count() == 0 or download_resume.count() == 0:
        raise JourneyStepError(
            "D2-05-resume-controls",
            "View and Download resume links",
            "missing on detail page",
            journey_id=JRN_ID,
        )

    resume_path = _resume_path(target.application_id)
    inline_href = view_resume.first.get_attribute("href") or ""
    download_href = download_resume.first.get_attribute("href") or ""
    if resume_path not in inline_href or resume_path not in download_href:
        raise JourneyStepError(
            "D2-05-resume-controls",
            f"resume links reference {resume_path}",
            f"inline={inline_href!r} download={download_href!r}",
            journey_id=JRN_ID,
        )

    detail_url = page.url
    inline_nav_url = f"{base_url.rstrip('/')}{resume_path}?disposition=inline"
    _open_inline_resume(page, view_resume, inline_nav_url, resume_path)
    ctx.steps_completed.append("D2-06-inline-resume")

    if page.url != detail_url:
        page.goto(detail_url, wait_until="domcontentloaded")
        expect(page.get_by_text(target.business_id).first).to_be_visible(timeout=15_000)
        resume_card = _resume_region(page)
        download_resume = resume_card.get_by_role("link", name="Download")

    with page.expect_download(timeout=30_000) as download_info:
        download_resume.first.click()
    download = download_info.value
    download_path = download.path()
    if not download_path:
        raise JourneyStepError(
            "D2-07-download-resume",
            "download path from UI Download",
            "empty path",
            journey_id=JRN_ID,
        )
    download_bytes = Path(download_path).read_bytes()
    if not pdf_bytes_are_readable(download_bytes):
        raise JourneyStepError(
            "D2-07-download-resume",
            "readable PDF from UI Download",
            "bytes invalid",
            journey_id=JRN_ID,
        )
    ctx.steps_completed.append("D2-07-download-resume")

    page.reload(wait_until="domcontentloaded")
    expect(page.get_by_text(target.business_id).first).to_be_visible(timeout=15_000)
    resume_card = _resume_region(page)
    expect(resume_card.get_by_role("link", name="View")).to_be_visible(timeout=15_000)
    ctx.steps_completed.append("D2-08-persist-after-reload")

    nav.goto_path("/account/hiring/applications")
    expect(page.get_by_role("heading", name="Applications").first).to_be_visible(timeout=30_000)
    expect(page.get_by_text(target.business_id).first).to_be_visible(timeout=15_000)
    ctx.steps_completed.append("D2-09-hiring-applications-list")

    return ctx


def run_employer_b_isolation_steps(
    page: Page,
    base_url: str,
    email: str,
    password: str,
    target: EmployerJourneyTarget,
) -> None:
    login_employer(page, base_url, email, password)

    nav = NavigationHelper(page, base_url)
    nav.goto_path("/account/hiring/applications")
    body = page.locator("body").inner_text()
    if target.business_id in body:
        raise JourneyStepError(
            "D2-10-b-list-isolation",
            "Employer B hiring list must not show Employer A application id",
            "business id visible",
            journey_id=JRN_ID,
        )

    assert_http_forbidden(
        page,
        base_url,
        _application_show_path(target.job_slug, target.application_id),
        forbidden_token=target.business_id,
    )
    assert_http_forbidden(
        page,
        base_url,
        _resume_path(target.application_id),
        forbidden_token=target.business_id,
    )
