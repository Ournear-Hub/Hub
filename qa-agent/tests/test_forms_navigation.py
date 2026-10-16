from __future__ import annotations

from pathlib import Path

import pytest

from ournear_qa.browser.files import synthetic_pdf_file, synthetic_text_file
from ournear_qa.browser.forms import FormHelper
from ournear_qa.browser.locators import LocatorError
from ournear_qa.browser.navigation import NavigationError, NavigationHelper
from ournear_qa.browser import BrowserSession

FIXTURES = Path(__file__).resolve().parent / "fixtures"


def _route_fixture(page, url: str, filename: str) -> None:
    body = (FIXTURES / filename).read_text(encoding="utf-8")
    page.route(url, lambda route: route.fulfill(content_type="text/html", body=body))


@pytest.fixture
def form_page(page):
    _route_fixture(page, "https://qa.test/form", "form_page.html")
    page.goto("https://qa.test/form")
    return page


def test_form_fill_text_select_checkbox_radio(form_page) -> None:
    forms = FormHelper(form_page)
    forms.fill_field("Full name", "Ada Lovelace")
    forms.fill_textarea("Bio", "Analyst")
    forms.select_option("Country", "ca")
    forms.set_checkbox("Accept terms", True)
    forms.choose_radio("Pro")
    forms.expect_field_value("Full name", "Ada Lovelace")


def test_form_upload_synthetic_file(form_page) -> None:
    forms = FormHelper(form_page)
    upload = synthetic_text_file()
    forms.upload_file("Upload document", upload)
    count = form_page.locator("#upload").evaluate("el => el.files.length")
    assert count == 1


def test_form_validation_and_explicit_submit(form_page) -> None:
    forms = FormHelper(form_page)
    forms.fill_field("Full name", "Test User")
    forms.fill_field("Email", "not-an-email")
    forms.click_submit("Send form")
    forms.expect_field_invalid("Email")
    forms.fill_field("Email", "user@example.test")
    forms.click_submit("Send form")
    forms.expect_success_message("Saved successfully")


def test_form_ambiguous_label_raises(form_page) -> None:
    _route_fixture(form_page, "https://qa.test/ambiguous", "ambiguous_page.html")
    form_page.goto("https://qa.test/ambiguous")
    forms = FormHelper(form_page)
    with pytest.raises(LocatorError, match="Ambiguous"):
        forms.fill_field("Name", "x")


def test_form_missing_label_raises(form_page) -> None:
    forms = FormHelper(form_page)
    with pytest.raises(LocatorError, match="No match"):
        forms.fill_field("Does not exist", "x")


def test_navigation_local_path_and_heading(browser_session, base_url) -> None:
    nav = NavigationHelper(browser_session.page, base_url)
    nav.goto_path("/")
    nav.assert_url_contains("/")


def test_navigation_rejects_external_path(browser_session, base_url) -> None:
    nav = NavigationHelper(browser_session.page, base_url)
    with pytest.raises(NavigationError, match="External"):
        nav.goto_path("http://evil.test/")

    with pytest.raises(NavigationError, match="must start with"):
        nav.goto_path("evil")


def test_navigation_rejects_non_local_base(page) -> None:
    with pytest.raises(NavigationError, match="approved local"):
        NavigationHelper(page, "https://production.example/")


def test_navigation_timeout_message(form_page, base_url) -> None:
    nav = NavigationHelper(form_page, base_url)
    with pytest.raises(NavigationError, match="Timed out waiting for URL"):
        nav.wait_for_path("**/never-matches/**", timeout_ms=500)


def test_synthetic_pdf_written() -> None:
    path = synthetic_pdf_file()
    assert path.stat().st_size > 20
    assert path.read_bytes().startswith(b"%PDF")


def test_browser_session_still_collects_console(form_page) -> None:
    session = BrowserSession.attach(form_page)
    with form_page.expect_console_message(lambda msg: msg.type == "error"):
        form_page.evaluate("() => console.error('c2-regression')")
    assert any("c2-regression" in row.text for row in session.console_errors)
