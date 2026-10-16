import pytest

from ournear_qa.browser.forms import FormHelper
from ournear_qa.browser.navigation import NavigationHelper


@pytest.mark.smoke
def test_candidate_login_reaches_account(browser_session, base_url, qa_settings):
    if not qa_settings.qa_candidate_email or not qa_settings.qa_candidate_password:
        pytest.skip("Set OURNEAR_QA_CANDIDATE_EMAIL and OURNEAR_QA_CANDIDATE_PASSWORD in config/local.env")

    page = browser_session.page
    nav = NavigationHelper(page, base_url)
    forms = FormHelper(page)
    nav.goto_path("/login")
    forms.fill_account_login(qa_settings.qa_candidate_email)
    page.locator("#password").fill(qa_settings.qa_candidate_password)
    forms.click_login_submit()
    nav.wait_for_path("**/account**")
    nav.assert_url_contains("/account")
    nav.assert_heading("Dashboard")


