from __future__ import annotations

from dataclasses import dataclass
from pathlib import Path

from playwright.sync_api import Page, expect

from ournear_qa.browser.session import BrowserSession
from ournear_qa.evidence import ARTIFACTS_DIR
from ournear_qa.journey.account_auth import login_account_user
from ournear_qa.journey.errors import JourneyStepError

JRN_ID = "JRN-LR-REG-ACCOUNT-HOME-01"

VIEWPORTS: list[tuple[str, int, int]] = [
    ("desktop", 1440, 900),
    ("mobile", 390, 844),
]


@dataclass
class LrRegisterAccountHomeReport:
    routes_checked: list[str]
    viewports_checked: list[str]
    evidence_paths: list[str]


def _evidence_dir(run_id: str) -> Path:
    path = ARTIFACTS_DIR / "lr-register-account" / run_id
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


def _assert_register_page(page: Page, step_id: str) -> None:
    expect(page.get_by_role("heading", level=1, name="Create your account")).to_be_visible(timeout=15_000)
    expect(page.get_by_role("button", name="Create account")).to_be_visible(timeout=10_000)
    main = page.locator("main")
    expect(main.get_by_role("link", name="Log in")).to_be_visible(timeout=10_000)
    expect(main.get_by_role("link", name="Terms & Conditions").first).to_be_visible(timeout=10_000)
    expect(main.get_by_role("textbox", name="Full name", exact=True)).to_be_visible(timeout=10_000)
    expect(main.get_by_role("textbox", name="Email")).to_be_visible(timeout=10_000)
    expect(main.get_by_role("textbox", name="Password", exact=True)).to_be_visible(timeout=10_000)
    expect(main.get_by_role("textbox", name="Confirm password")).to_be_visible(timeout=10_000)
    expect(page.get_by_text("username is assigned automatically", exact=False)).to_be_visible(timeout=10_000)
    expect(main.get_by_role("button", name="Create account")).to_be_disabled(timeout=10_000)


def _assert_account_home(page: Page, step_id: str) -> None:
    expect(page.get_by_role("heading", name="Dashboard", level=1)).to_be_visible(timeout=15_000)
    expect(page.get_by_text("Welcome back,", exact=False).first).to_be_visible(timeout=15_000)
    nav = page.get_by_role("navigation").first
    expect(nav).to_be_visible(timeout=10_000)


def run_register_spot_check(page: Page, base_url: str, *, run_id: str) -> LrRegisterAccountHomeReport:
    session = BrowserSession.attach(page)
    evidence = _evidence_dir(run_id)
    routes: list[str] = []
    viewports: list[str] = []
    shots: list[str] = []

    for label, width, height in VIEWPORTS:
        page.set_viewport_size({"width": width, "height": height})
        step = f"register-{label}"
        response = page.goto(f"{base_url}/register", wait_until="domcontentloaded")
        if response is None or not response.ok:
            status = response.status if response else "no response"
            raise JourneyStepError(step, "register GET success", f"HTTP {status}", journey_id=JRN_ID)
        page.wait_for_load_state("networkidle")
        routes.append("/register")
        viewports.append(label)
        _assert_register_page(page, step)
        _assert_diagnostics(session, step)
        _assert_no_horizontal_overflow(page, step)
        shots.append(_shot(page, evidence, f"register-{label}.png"))

    return LrRegisterAccountHomeReport(routes_checked=routes, viewports_checked=viewports, evidence_paths=shots)


def run_account_home_spot_check(
    page: Page,
    base_url: str,
    email: str,
    password: str,
    *,
    run_id: str,
) -> LrRegisterAccountHomeReport:
    session = BrowserSession.attach(page)
    evidence = _evidence_dir(run_id)
    routes: list[str] = []
    viewports: list[str] = []
    shots: list[str] = []

    login_account_user(
        page,
        base_url,
        email,
        password,
        journey_id=JRN_ID,
        step_id="login-before-account-home",
    )
    routes.append("/login")
    routes.append("/account")

    for label, width, height in VIEWPORTS:
        page.set_viewport_size({"width": width, "height": height})
        step = f"account-home-{label}"
        response = page.goto(f"{base_url}/account", wait_until="domcontentloaded")
        if response is None or not response.ok:
            status = response.status if response else "no response"
            raise JourneyStepError(step, "account home GET success", f"HTTP {status}", journey_id=JRN_ID)
        page.wait_for_load_state("networkidle")
        viewports.append(label)
        _assert_account_home(page, step)
        _assert_diagnostics(session, step)
        _assert_no_horizontal_overflow(page, step)
        shots.append(_shot(page, evidence, f"account-home-{label}.png"))

    return LrRegisterAccountHomeReport(routes_checked=routes, viewports_checked=viewports, evidence_paths=shots)
