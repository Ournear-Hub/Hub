from __future__ import annotations

import shutil
import subprocess
from dataclasses import dataclass
from pathlib import Path

from playwright.sync_api import Page, expect

from ournear_qa.browser.session import BrowserSession
from ournear_qa.evidence import ARTIFACTS_DIR
from ournear_qa.journey.account_auth import login_account_user
from ournear_qa.journey.errors import JourneyStepError

REPO_ROOT = Path(__file__).resolve().parents[3]
JRN_ID = "JRN-FE103-DETAIL-HEADER-01"

FE103_VIEWPORTS: list[tuple[str, int, int]] = [
    ("desktop", 1440, 900),
    ("tablet", 768, 1024),
    ("mobile", 390, 844),
]


@dataclass
class Fe103Targets:
    application_id: int
    business_id: str
    job_slug: str
    job_title: str
    candidate_name: str
    resume_path_set: bool
    submission_id: int | None = None
    submission_business_id: str | None = None


@dataclass
class Fe103RunReport:
    targets: Fe103Targets
    viewports_checked: list[str]
    routes_checked: list[str]
    evidence_paths: list[str]
    timeline_visible_on_application: bool | None
    timeline_visible_on_submission: bool | None


def discover_fe103_targets(repo_root: Path = REPO_ROOT) -> Fe103Targets | None:
    script = repo_root / "scripts" / "inspect-qa-fe103-detail-targets.php"
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

    if fields.get("application_found") != "1":
        return None

    try:
        application_id = int(fields["application_id"])
    except (KeyError, ValueError):
        return None

    job_slug = fields.get("job_slug", "")
    job_title = fields.get("job_title", job_slug)
    business_id = fields.get("business_id", "")
    candidate_name = fields.get("candidate_name", "")
    if not job_slug or not business_id or not candidate_name:
        return None

    submission_id: int | None = None
    submission_business_id: str | None = None
    if fields.get("submission_found") == "1":
        try:
            submission_id = int(fields["submission_id"])
            submission_business_id = fields.get("submission_business_id") or None
        except (KeyError, ValueError):
            submission_id = None

    return Fe103Targets(
        application_id=application_id,
        business_id=business_id,
        job_slug=job_slug,
        job_title=job_title,
        candidate_name=candidate_name,
        resume_path_set=fields.get("resume_path_set") == "yes",
        submission_id=submission_id,
        submission_business_id=submission_business_id,
    )


def _evidence_dir(run_id: str) -> Path:
    path = ARTIFACTS_DIR / "fe103" / run_id
    path.mkdir(parents=True, exist_ok=True)
    return path


def _shot(page: Page, directory: Path, name: str) -> str:
    target = directory / name
    page.screenshot(path=str(target), full_page=True)
    return target.relative_to(ARTIFACTS_DIR).as_posix()


def _assert_diagnostics(session: BrowserSession, step_id: str) -> None:
    if session.page_errors:
        raise JourneyStepError(
            step_id,
            "no uncaught page errors",
            session.page_errors[0].message,
            journey_id=JRN_ID,
        )
    if session.console_errors:
        raise JourneyStepError(
            step_id,
            "no console errors",
            session.console_errors[0].text,
            journey_id=JRN_ID,
        )
    failures = session.actionable_request_failures()
    if failures:
        first = failures[0]
        raise JourneyStepError(
            step_id,
            "no failed network requests",
            f"{first.method} {first.url} — {first.failure}",
            journey_id=JRN_ID,
        )
    bad_http = [r for r in session.http_error_responses() if r.status >= 500]
    if bad_http:
        first = bad_http[0]
        raise JourneyStepError(
            step_id,
            "no HTTP 5xx responses",
            f"{first.status} {first.url}",
            journey_id=JRN_ID,
        )


def _assert_no_horizontal_overflow(page: Page, step_id: str) -> None:
    overflow = page.evaluate(
        """() => {
            const doc = document.documentElement;
            const body = document.body;
            const width = Math.max(doc.scrollWidth, body ? body.scrollWidth : 0);
            const client = doc.clientWidth;
            return { scrollWidth: width, clientWidth: client, delta: width - client };
        }"""
    )
    if overflow.get("delta", 0) > 8:
        raise JourneyStepError(
            step_id,
            "no material horizontal overflow",
            str(overflow),
            journey_id=JRN_ID,
        )


def _assert_employer_application_detail(page: Page, targets: Fe103Targets, step_id: str) -> bool | None:
    expect(page.get_by_role("banner").first).to_be_visible(timeout=15_000)
    expect(page.get_by_role("heading", level=1, name=targets.job_title).first).to_be_visible(timeout=15_000)
    expect(page.get_by_role("heading", level=2, name=targets.candidate_name).first).to_be_visible(timeout=15_000)
    expect(page.locator("main").get_by_text(targets.business_id).first).to_be_visible(timeout=15_000)
    expect(page.locator("main").get_by_text("Application ID").first).to_be_visible(timeout=15_000)

    resume_heading = page.get_by_role("heading", name="Resume")
    if resume_heading.count() > 0:
        expect(resume_heading.first).to_be_visible(timeout=10_000)

    timeline = page.get_by_role("region", name="Application timeline")
    if timeline.count() == 0:
        timeline = page.get_by_label("Application timeline")
    if timeline.count() == 0:
        return None
    expect(timeline.first).to_be_visible(timeout=10_000)
    return True


def _assert_employer_submission_detail(page: Page, targets: Fe103Targets, step_id: str) -> bool | None:
    expect(
        page.locator("main").get_by_text("Candidate submission", exact=True).first
    ).to_be_visible(timeout=15_000)
    expect(page.get_by_role("heading", level=2, name=targets.candidate_name).first).to_be_visible(timeout=15_000)
    if targets.submission_business_id:
        expect(page.locator("main").get_by_text(targets.submission_business_id).first).to_be_visible(
            timeout=15_000
        )

    timeline = page.get_by_label("Submission timeline")
    if timeline.count() == 0:
        return None
    expect(timeline.first).to_be_visible(timeout=10_000)
    return True


def run_fe103_employer_detail_header_qa(
    page: Page,
    base_url: str,
    email: str,
    password: str,
    targets: Fe103Targets,
    *,
    run_id: str,
    include_submission: bool = True,
) -> Fe103RunReport:
    session = BrowserSession.attach(page)
    evidence = _evidence_dir(run_id)
    viewports_checked: list[str] = []
    routes_checked: list[str] = []
    evidence_paths: list[str] = []
    timeline_app: bool | None = None
    timeline_sub: bool | None = None

    login_account_user(
        page,
        base_url,
        email,
        password,
        journey_id=JRN_ID,
        step_id="FE103-login",
    )

    app_path = f"/account/jobs/{targets.job_slug}/applications/{targets.application_id}"
    sub_path = (
        f"/account/submissions/{targets.submission_id}"
        if include_submission and targets.submission_id
        else None
    )

    for label, width, height in FE103_VIEWPORTS:
        page.set_viewport_size({"width": width, "height": height})
        viewports_checked.append(f"{label} ({width}x{height})")

        page.goto(f"{base_url.rstrip('/')}{app_path}", wait_until="domcontentloaded")
        page.wait_for_load_state("networkidle", timeout=60_000)
        routes_checked.append(app_path)
        timeline_app = _assert_employer_application_detail(page, targets, f"FE103-app-{label}")
        if label == "desktop":
            page.keyboard.press("Tab")
            has_focus_ring = page.evaluate(
                """() => {
                    const el = document.activeElement;
                    if (!el || el === document.body) return false;
                    const style = window.getComputedStyle(el);
                    return style.outlineStyle !== 'none' || style.boxShadow !== 'none';
                }"""
            )
            if not has_focus_ring:
                raise JourneyStepError(
                    f"FE103-a11y-focus-{label}",
                    "visible focus indicator on first Tab stop",
                    f"activeElement={page.evaluate('() => document.activeElement?.outerHTML?.slice(0,120)')}",
                    journey_id=JRN_ID,
                )
        _assert_no_horizontal_overflow(page, f"FE103-overflow-app-{label}")
        _assert_diagnostics(session, f"FE103-diagnostics-app-{label}")
        evidence_paths.append(_shot(page, evidence, f"employer-application-{label}.png"))

        if sub_path:
            page.goto(f"{base_url.rstrip('/')}{sub_path}", wait_until="domcontentloaded")
            page.wait_for_load_state("networkidle", timeout=60_000)
            if sub_path not in routes_checked:
                routes_checked.append(sub_path)
            timeline_sub = _assert_employer_submission_detail(page, targets, f"FE103-sub-{label}")
            _assert_no_horizontal_overflow(page, f"FE103-overflow-sub-{label}")
            _assert_diagnostics(session, f"FE103-diagnostics-sub-{label}")
            evidence_paths.append(_shot(page, evidence, f"employer-submission-{label}.png"))

    return Fe103RunReport(
        targets=targets,
        viewports_checked=viewports_checked,
        routes_checked=list(dict.fromkeys(routes_checked)),
        evidence_paths=evidence_paths,
        timeline_visible_on_application=timeline_app,
        timeline_visible_on_submission=timeline_sub,
    )


def run_fe103_candidate_application_show(
    page: Page,
    base_url: str,
    email: str,
    password: str,
    targets: Fe103Targets,
    *,
    run_id: str,
) -> None:
    session = BrowserSession.attach(page)
    evidence = _evidence_dir(run_id)
    login_account_user(
        page,
        base_url,
        email,
        password,
        journey_id=JRN_ID,
        step_id="FE103-candidate-login",
    )
    path = f"/account/applications/{targets.application_id}"
    page.set_viewport_size({"width": 390, "height": 844})
    page.goto(f"{base_url.rstrip('/')}{path}", wait_until="domcontentloaded")
    page.wait_for_load_state("networkidle", timeout=60_000)
    expect(page.get_by_role("heading", level=1, name="Application").first).to_be_visible(timeout=15_000)
    expect(page.get_by_role("heading", level=2, name=targets.job_title).first).to_be_visible(timeout=15_000)
    expect(page.locator("main header").get_by_text(targets.business_id).first).to_be_visible(timeout=15_000)
    expect(page.get_by_role("button", name="Withdraw application")).to_be_visible(timeout=10_000)
    expect(page.get_by_label("Application timeline")).to_be_visible(timeout=10_000)
    _assert_no_horizontal_overflow(page, "FE103-overflow-candidate")
    _assert_diagnostics(session, "FE103-diagnostics-candidate")
    _shot(page, evidence, "candidate-application-mobile.png")
