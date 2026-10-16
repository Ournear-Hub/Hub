import pytest


@pytest.mark.smoke
def test_home_loads(browser_session, base_url):
    page = browser_session.page
    response = page.goto(f"{base_url}/")
    assert response is not None
    assert response.ok, f"Home HTTP {response.status}"
    assert page.title(), "Expected non-empty document title"
    errors = browser_session.console_errors
    assert not errors, f"Console errors: {[e.text for e in errors]}"
