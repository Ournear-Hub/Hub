from __future__ import annotations

from dataclasses import asdict, dataclass, field
from typing import Any, Literal

from playwright.sync_api import Page

from ournear_qa.browser.redact import sanitize_url

_SESSION_ATTR = "_ournear_qa_browser_session_attached"
_MAX_RECORDS = 50

RequestFailureClass = Literal["cancelled", "failed"]


@dataclass(frozen=True)
class ConsoleErrorRecord:
    text: str
    type: str = "error"


@dataclass(frozen=True)
class PageErrorRecord:
    message: str


@dataclass(frozen=True)
class RequestFailureRecord:
    method: str
    url: str
    failure: str
    classification: RequestFailureClass


@dataclass(frozen=True)
class HttpErrorRecord:
    method: str
    url: str
    status: int


@dataclass
class BrowserDiagnostics:
    console_errors: list[ConsoleErrorRecord] = field(default_factory=list)
    page_errors: list[PageErrorRecord] = field(default_factory=list)
    request_failures: list[RequestFailureRecord] = field(default_factory=list)
    http_errors: list[HttpErrorRecord] = field(default_factory=list)

    def to_dict(self) -> dict[str, Any]:
        return {
            "console_errors": [asdict(r) for r in self.console_errors],
            "page_errors": [asdict(r) for r in self.page_errors],
            "request_failures": [asdict(r) for r in self.request_failures],
            "http_errors": [asdict(r) for r in self.http_errors],
            "summary": {
                "console_error_count": len(self.console_errors),
                "page_error_count": len(self.page_errors),
                "request_failure_count": len(self.request_failures),
                "http_error_count": len(self.http_errors),
                "actionable_request_failures": len(
                    [r for r in self.request_failures if r.classification == "failed"]
                ),
            },
        }


def classify_request_failure(failure_text: str) -> RequestFailureClass:
    """Navigation aborts and user-initiated cancellations are not treated as defects."""
    lower = failure_text.lower()
    cancelled_markers = (
        "err_aborted",
        "aborted",
        "cancelled",
        "canceled",
        "ns_binding_aborted",
        "net::err_blocked_by_client",
    )
    if any(marker in lower for marker in cancelled_markers):
        return "cancelled"
    return "failed"


class BrowserSession:
    def __init__(self, page: Page) -> None:
        self._page = page
        self._diagnostics = BrowserDiagnostics()
        self._bound = False

    @classmethod
    def attach(cls, page: Page) -> BrowserSession:
        existing = getattr(page, _SESSION_ATTR, None)
        if existing is not None:
            return existing
        session = cls(page)
        session._register_listeners()
        setattr(page, _SESSION_ATTR, session)
        return session

    @property
    def page(self) -> Page:
        return self._page

    @property
    def console_errors(self) -> list[ConsoleErrorRecord]:
        return list(self._diagnostics.console_errors)

    @property
    def page_errors(self) -> list[PageErrorRecord]:
        return list(self._diagnostics.page_errors)

    def actionable_request_failures(self) -> list[RequestFailureRecord]:
        return [r for r in self._diagnostics.request_failures if r.classification == "failed"]

    def http_error_responses(self) -> list[HttpErrorRecord]:
        return list(self._diagnostics.http_errors)

    def snapshot(self) -> dict[str, Any]:
        return self._diagnostics.to_dict()

    def _register_listeners(self) -> None:
        if self._bound:
            return
        self._bound = True

        def on_console(msg) -> None:
            if msg.type != "error":
                return
            if len(self._diagnostics.console_errors) >= _MAX_RECORDS:
                return
            self._diagnostics.console_errors.append(
                ConsoleErrorRecord(text=msg.text or "", type=msg.type)
            )

        def on_page_error(exc: BaseException) -> None:
            if len(self._diagnostics.page_errors) >= _MAX_RECORDS:
                return
            self._diagnostics.page_errors.append(PageErrorRecord(message=str(exc)))

        def on_request_failed(request) -> None:
            if len(self._diagnostics.request_failures) >= _MAX_RECORDS:
                return
            failure_text = request.failure or "unknown"
            self._diagnostics.request_failures.append(
                RequestFailureRecord(
                    method=request.method,
                    url=sanitize_url(request.url),
                    failure=failure_text,
                    classification=classify_request_failure(failure_text),
                )
            )

        def on_response(response) -> None:
            status = response.status
            if status < 400:
                return
            if len(self._diagnostics.http_errors) >= _MAX_RECORDS:
                return
            self._diagnostics.http_errors.append(
                HttpErrorRecord(
                    method=response.request.method,
                    url=sanitize_url(response.url),
                    status=status,
                )
            )

        self._page.on("console", on_console)
        self._page.on("pageerror", on_page_error)
        self._page.on("requestfailed", on_request_failed)
        self._page.on("response", on_response)
