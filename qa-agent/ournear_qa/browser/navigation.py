from __future__ import annotations

from urllib.parse import urlparse

from playwright.sync_api import Page, expect

from ournear_qa.settings import _LOCAL_HOSTS

DEFAULT_NAV_TIMEOUT_MS = 30_000


class NavigationError(Exception):
    """Raised when navigation or page-state assertions fail."""


class NavigationHelper:
    def __init__(self, page: Page, base_url: str) -> None:
        self._page = page
        self._base_url = base_url.rstrip("/")
        host = (urlparse(self._base_url).hostname or "").lower()
        if host not in _LOCAL_HOSTS:
            raise NavigationError(
                f"NavigationHelper base_url host {host!r} is not an approved local host."
            )

    @property
    def page(self) -> Page:
        return self._page

    def goto_path(self, path: str, *, timeout_ms: int = DEFAULT_NAV_TIMEOUT_MS):
        if "://" in path:
            raise NavigationError("External paths are not allowed; use goto_path with a relative path.")
        if not path.startswith("/"):
            raise NavigationError(f"path must start with '/' (got {path!r})")

        url = f"{self._base_url}{path}"
        host = (urlparse(url).hostname or "").lower()
        if host not in _LOCAL_HOSTS:
            raise NavigationError(f"Refusing to navigate to non-local host {host!r}")

        response = self._page.goto(url, wait_until="domcontentloaded", timeout=timeout_ms)
        if response is None:
            raise NavigationError(f"No response for GET {path}")
        if not response.ok:
            raise NavigationError(f"GET {path} returned HTTP {response.status}")
        return response

    def wait_for_path(self, pattern: str, *, timeout_ms: int = DEFAULT_NAV_TIMEOUT_MS) -> None:
        try:
            self._page.wait_for_url(pattern, timeout=timeout_ms)
        except Exception as exc:  # noqa: BLE001
            raise NavigationError(
                f"Timed out waiting for URL matching {pattern!r} (current: {self._page.url})"
            ) from exc

    def assert_url_contains(self, fragment: str) -> None:
        if fragment not in self._page.url:
            raise NavigationError(
                f"Expected URL to contain {fragment!r}, got {self._page.url!r}"
            )

    def assert_heading(self, name: str, *, level: int = 1) -> None:
        try:
            expect(
                self._page.get_by_role("heading", name=name, level=level)
            ).to_be_visible(timeout=DEFAULT_NAV_TIMEOUT_MS)
        except Exception as exc:  # noqa: BLE001
            raise NavigationError(f"Expected heading {name!r} (level {level}) to be visible") from exc

    def assert_visible_text(self, text: str) -> None:
        try:
            expect(self._page.get_by_text(text)).to_be_visible(timeout=DEFAULT_NAV_TIMEOUT_MS)
        except Exception as exc:  # noqa: BLE001
            raise NavigationError(f"Expected visible text {text!r}") from exc
