from __future__ import annotations

from urllib.parse import unquote

from ournear_qa.browser import BrowserSession, classify_request_failure
from ournear_qa.browser.redact import sanitize_url


def test_classify_cancelled_request_failure() -> None:
    assert classify_request_failure("net::ERR_ABORTED") == "cancelled"
    assert classify_request_failure("NS_BINDING_ABORTED") == "cancelled"
    assert classify_request_failure("net::ERR_CONNECTION_REFUSED") == "failed"


def test_sanitize_url_redacts_query_secrets() -> None:
    url = "https://example.test/login?password=Secret1&next=/jobs"
    sanitized = sanitize_url(url)
    assert "Secret1" not in sanitized
    assert "[REDACTED]" in unquote(sanitized)
    assert "next=" in sanitized


def test_sanitize_url_redacts_userinfo() -> None:
    url = "https://user:pass@127.0.0.1:8000/account"
    sanitized = sanitize_url(url)
    assert "pass" not in sanitized.split("@")[0]
    assert "[REDACTED]" in sanitized


def test_attach_is_idempotent(page) -> None:
    first = BrowserSession.attach(page)
    second = BrowserSession.attach(page)
    assert first is second
    page.set_content("<script>console.error('once'); console.error('twice');</script>")
    assert len(first.console_errors) == 2
    third = BrowserSession.attach(page)
    assert third is first
    assert len(third.console_errors) == 2


def test_console_errors_captured(page) -> None:
    session = BrowserSession.attach(page)
    with page.expect_console_message(lambda msg: msg.type == "error" and "diag-console" in msg.text):
        page.set_content("<script>console.error('diag-console');</script>")
    texts = [r.text for r in session.console_errors]
    assert "diag-console" in texts


def test_page_errors_captured(page) -> None:
    session = BrowserSession.attach(page)
    with page.expect_event("pageerror") as exc_info:
        page.set_content("<script>throw new Error('diag-page');</script>")
    assert "diag-page" in str(exc_info.value)
    messages = [r.message for r in session.page_errors]
    assert any("diag-page" in m for m in messages)


def test_http_error_response_captured(page) -> None:
    session = BrowserSession.attach(page)
    page.route(
        "https://qa.test/page",
        lambda route: route.fulfill(
            content_type="text/html",
            body="<script>fetch('/missing-resource').catch(function(){});</script>",
        ),
    )
    page.route("**/missing-resource", lambda route: route.fulfill(status=404, body="nope"))
    with page.expect_response(lambda response: response.status == 404):
        page.goto("https://qa.test/page")
    errors = session.http_error_responses()
    assert any(e.status == 404 and "missing-resource" in e.url for e in errors)


def test_request_failure_captured(page) -> None:
    session = BrowserSession.attach(page)
    page.route("**/blocked", lambda route: route.abort("failed"))
    page.route(
        "https://qa.test/page",
        lambda route: route.fulfill(
            content_type="text/html",
            body="<script>fetch('/blocked').catch(function(){});</script>",
        ),
    )
    with page.expect_event("requestfailed"):
        page.goto("https://qa.test/page")
    failures = session.actionable_request_failures()
    assert failures
    assert failures[0].classification == "failed"


def test_snapshot_serializable(page) -> None:
    session = BrowserSession.attach(page)
    with page.expect_console_message(lambda msg: msg.type == "error"):
        page.set_content("<script>console.error('snap');</script>")
    snap = session.snapshot()
    assert snap["summary"]["console_error_count"] >= 1
    assert snap["console_errors"][0]["text"] == "snap"


def test_evidence_sanitizer_redacts_diagnostics_keys() -> None:
    from ournear_qa.evidence import sanitize_mapping

    payload = sanitize_mapping(
        {
            "browser_diagnostics": {
                "http_errors": [{"url": "http://x?token=abc", "method": "GET", "status": 401}]
            },
            "qa_password": "hidden",
        }
    )
    assert payload["qa_password"] == "[REDACTED]"
