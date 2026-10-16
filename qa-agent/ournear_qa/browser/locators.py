from __future__ import annotations

from playwright.sync_api import Locator, Page

DEFAULT_ACTION_TIMEOUT_MS = 15_000


class LocatorError(Exception):
    """Raised when a control cannot be resolved uniquely."""


def _ensure_unique(locator: Locator, description: str) -> Locator:
    timeout = DEFAULT_ACTION_TIMEOUT_MS
    try:
        locator.first.wait_for(state="attached", timeout=timeout)
    except Exception as exc:  # noqa: BLE001
        raise LocatorError(f"No match for {description}") from exc

    count = locator.count()
    if count == 0:
        raise LocatorError(f"No match for {description}")
    if count > 1:
        raise LocatorError(f"Ambiguous {description}: {count} matches")
    return locator.first


def by_label(page: Page, label: str, *, exact: bool = False) -> Locator:
    return _ensure_unique(page.get_by_label(label, exact=exact), f"label {label!r}")


def by_role_button(page: Page, name: str, *, exact: bool = False) -> Locator:
    return _ensure_unique(
        page.get_by_role("button", name=name, exact=exact),
        f"button {name!r}",
    )
