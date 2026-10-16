from __future__ import annotations

from urllib.parse import urlparse

from playwright.sync_api import Page, Response, expect

from ournear_qa.browser.forms import FormHelper
from ournear_qa.browser.navigation import NavigationHelper
from ournear_qa.journey.errors import JourneyStepError

JRN_ACCOUNT_LOGIN = "account-login"


def _is_authenticated_account_url(url: str) -> bool:
    path = urlparse(url).path.rstrip("/") or "/"
    if path in ("/account",):
        return True
    return path.startswith("/account/")


def _is_account_login_post(response: Response) -> bool:
    if response.request.method != "POST":
        return False
    path = urlparse(response.url).path.rstrip("/")
    return path.endswith("/login") and "/admin/" not in response.url


def login_account_user(
    page: Page,
    base_url: str,
    email: str,
    password: str,
    *,
    journey_id: str,
    step_id: str = "login-account",
    nav: NavigationHelper | None = None,
) -> NavigationHelper:
    navigation = nav or NavigationHelper(page, base_url)
    forms = FormHelper(page)
    page.context.clear_cookies()
    navigation.goto_path("/login")
    forms.fill_account_login(email)
    forms.fill_account_password(password)

    with page.expect_response(_is_account_login_post, timeout=30_000) as response_info:
        forms.click_login_submit()
    login_response = response_info.value

    if login_response.status == 429:
        retry_after = login_response.headers.get("retry-after", "")
        detail = "HTTP 429 Too Many Requests (throttle:account-login)"
        if retry_after:
            detail = f"{detail}; Retry-After={retry_after}"
        raise JourneyStepError(
            step_id,
            "account login POST accepted (not rate limited)",
            detail,
            journey_id=journey_id,
        )
    if login_response.status >= 400:
        raise JourneyStepError(
            step_id,
            "account login POST success (<400)",
            f"HTTP {login_response.status}",
            journey_id=journey_id,
        )

    try:
        page.wait_for_url(lambda url: _is_authenticated_account_url(url), timeout=30_000)
    except Exception:
        page.goto(f"{base_url.rstrip('/')}/account", wait_until="networkidle")
    if not _is_authenticated_account_url(page.url):
        raise JourneyStepError(
            step_id,
            "account area after login",
            page.url,
            journey_id=journey_id,
        )

    _assert_account_home_loaded(page)
    return navigation


def _assert_account_home_loaded(page: Page) -> None:
    """Candidate home is Personal Dashboard; employer home is company workspace (no h1 Dashboard)."""
    personal = page.get_by_role("heading", name="Personal Dashboard", level=1)
    legacy = page.get_by_role("heading", name="Dashboard", level=1)
    company = page.get_by_role("region", name="Company workspace")
    if personal.count() > 0:
        expect(personal).to_be_visible(timeout=15_000)
        return
    if legacy.count() > 0:
        expect(legacy).to_be_visible(timeout=15_000)
        return
    if company.count() > 0:
        expect(company).to_be_visible(timeout=15_000)
        return
    raise JourneyStepError(
        "login-account-home",
        "Personal Dashboard, Dashboard, or Company workspace",
        "none visible after login",
        journey_id=JRN_ACCOUNT_LOGIN,
    )
