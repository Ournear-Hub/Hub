from __future__ import annotations

from pathlib import Path

from playwright.sync_api import Page, expect

from ournear_qa.browser.locators import (
    DEFAULT_ACTION_TIMEOUT_MS,
    LocatorError,
    by_label,
    by_role_button,
)


class FormHelper:
    """Explicit, accessible form interactions — no implicit submit."""

    def __init__(self, page: Page) -> None:
        self._page = page

    @property
    def page(self) -> Page:
        return self._page

    def fill_field(self, label: str, value: str, *, exact: bool = False) -> None:
        field = by_label(self._page, label, exact=exact)
        field.fill(value, timeout=DEFAULT_ACTION_TIMEOUT_MS)

    def fill_account_login(self, value: str) -> None:
        """Account `/login` username field — try current and legacy accessible labels."""
        last: LocatorError | None = None
        for label in (
            "Username or email address",
            "Email or username",
            "Username or Email",
        ):
            try:
                self.fill_field(label, value)
                return
            except LocatorError as exc:
                last = exc
        if last is not None:
            raise last
        raise LocatorError("No account login field label matched")

    def fill_account_password(self, value: str) -> None:
        """Account login password — prefer stable id over label (register tab may duplicate labels)."""
        field = self._page.locator("#password, input[name='password'][autocomplete='current-password']").first
        field.wait_for(state="attached", timeout=DEFAULT_ACTION_TIMEOUT_MS)
        field.fill(value, timeout=DEFAULT_ACTION_TIMEOUT_MS)

    def click_login_submit(self) -> None:
        """Primary submit on Account Auth login tab (label is \"Login\", not \"Log in\")."""
        last: LocatorError | None = None
        for name in ("Login", "Log in"):
            try:
                self.click_submit(name)
                return
            except LocatorError as exc:
                last = exc
        if last is not None:
            raise last
        raise LocatorError("No login submit button matched")

    def fill_textarea(self, label: str, value: str, *, exact: bool = False) -> None:
        area = by_label(self._page, label, exact=exact)
        area.fill(value, timeout=DEFAULT_ACTION_TIMEOUT_MS)

    def select_option(self, label: str, value: str, *, exact: bool = False) -> None:
        select = by_label(self._page, label, exact=exact)
        select.select_option(value, timeout=DEFAULT_ACTION_TIMEOUT_MS)

    def set_checkbox(self, label: str, checked: bool, *, exact: bool = False) -> None:
        box = by_label(self._page, label, exact=exact)
        if checked:
            box.check(timeout=DEFAULT_ACTION_TIMEOUT_MS)
        else:
            box.uncheck(timeout=DEFAULT_ACTION_TIMEOUT_MS)

    def choose_radio(self, label: str, *, exact: bool = False) -> None:
        radio = by_label(self._page, label, exact=exact)
        radio.check(timeout=DEFAULT_ACTION_TIMEOUT_MS)

    def upload_file(self, label: str, file_path: Path, *, exact: bool = False) -> None:
        if not file_path.is_file():
            raise FileNotFoundError(str(file_path))
        input_loc = by_label(self._page, label, exact=exact)
        input_loc.set_input_files(str(file_path), timeout=DEFAULT_ACTION_TIMEOUT_MS)

    def click_submit(self, name: str, *, exact: bool = False) -> None:
        by_role_button(self._page, name, exact=exact).click(timeout=DEFAULT_ACTION_TIMEOUT_MS)

    def expect_field_value(self, label: str, value: str, *, exact: bool = False) -> None:
        expect(by_label(self._page, label, exact=exact)).to_have_value(
            value, timeout=DEFAULT_ACTION_TIMEOUT_MS
        )

    def expect_validation_message(self, text: str) -> None:
        expect(self._page.get_by_text(text)).to_be_visible(timeout=DEFAULT_ACTION_TIMEOUT_MS)

    def expect_field_invalid(self, label: str, *, exact: bool = False) -> None:
        field = by_label(self._page, label, exact=exact)
        expect(field).to_have_attribute("aria-invalid", "true", timeout=DEFAULT_ACTION_TIMEOUT_MS)

    def expect_success_message(self, text: str) -> None:
        expect(self._page.get_by_role("status").filter(has_text=text)).to_be_visible(
            timeout=DEFAULT_ACTION_TIMEOUT_MS
        )


def require_field(page: Page, label: str, *, exact: bool = False):
    """Resolve a labeled control or raise LocatorError."""
    return by_label(page, label, exact=exact)
