from __future__ import annotations

import json
import re
from pathlib import Path

import pytest
from playwright.sync_api import Page, expect

from ournear_qa.browser.navigation import NavigationHelper
from ournear_qa.evidence import ARTIFACTS_DIR

RUN_ID = "20261011T095800Z-f1-responsive-visual"

PUBLIC_ROUTES: list[tuple[str, str]] = [
    ("/", "home"),
    ("/hire", "hire"),
    ("/jobs", "jobs"),
    ("/talent-pool", "talent-pool"),
    ("/staffing", "staffing"),
]

ROUTE_H1 = {
    "/": re.compile(r"RIGHT PEOPLE", re.I),
    "/hire": re.compile(r"Hire", re.I),
    "/jobs": re.compile(r"Find your next role", re.I),
    "/talent-pool": re.compile(r"Talent|Discover|profile|Find people", re.I),
    "/staffing": re.compile(r"Staffing|Workforce|manpower", re.I),
}

VIEWPORTS: list[tuple[str, int, int]] = [
    ("desktop", 1400, 900),
    ("tablet", 768, 1024),
    ("mobile", 390, 844),
]

ARTIFACT_ROOT = ARTIFACTS_DIR / "agent2" / RUN_ID
SCREENSHOT_DIR = ARTIFACT_ROOT / "screenshots"


def _route_slug(path: str) -> str:
    return path.strip("/").replace("/", "-") or "home"


def _overflow_delta(page: Page) -> float:
    data = page.evaluate(
        """() => {
            const doc = document.documentElement;
            const body = document.body;
            const width = Math.max(doc.scrollWidth, body ? body.scrollWidth : 0);
            return width - doc.clientWidth;
        }"""
    )
    return float(data)


def _shot(page: Page, path: str, viewport: str) -> str:
    SCREENSHOT_DIR.mkdir(parents=True, exist_ok=True)
    slug = _route_slug(path)
    target = SCREENSHOT_DIR / f"{slug}_{viewport}.png"
    page.screenshot(path=str(target), full_page=True)
    return target.relative_to(ARTIFACTS_DIR).as_posix()


@pytest.fixture
def console_errors(page: Page):
    errors: list[str] = []

    def on_console(msg):
        if msg.type == "error":
            errors.append(msg.text)

    page.on("console", on_console)
    return errors


@pytest.fixture(scope="session")
def f1_results_path() -> Path:
    ARTIFACT_ROOT.mkdir(parents=True, exist_ok=True)
    return ARTIFACT_ROOT / "f1_visual_results.json"


def _assert_console_clean(errors: list[str]) -> None:
    ignored = ("favicon", "404", "Failed to load resource")
    critical = [e for e in errors if not any(part in e for part in ignored)]
    assert not critical, critical


@pytest.mark.f1_visual
@pytest.mark.parametrize("path,slug", PUBLIC_ROUTES)
@pytest.mark.parametrize("viewport_label,width,height", VIEWPORTS)
def test_f1_responsive_layout_and_screenshot(
    page: Page,
    base_url: str,
    console_errors: list[str],
    path: str,
    slug: str,
    viewport_label: str,
    width: int,
    height: int,
    f1_results_path: Path,
) -> None:
    page.set_viewport_size({"width": width, "height": height})
    NavigationHelper(page, base_url).goto_path(path)
    page.wait_for_load_state("networkidle")

    expect(page.locator("header").first).to_be_visible()
    main = page.locator("main")
    expect(main.get_by_role("heading", level=1, name=ROUTE_H1[path]).first).to_be_visible(timeout=30_000)
    expect(page.get_by_role("contentinfo").first).to_be_visible()

    delta = _overflow_delta(page)
    assert delta <= 8, f"horizontal overflow {delta}px on {path} @ {viewport_label}"

    header = page.locator("header").first
    position = page.evaluate(
        """() => window.getComputedStyle(document.querySelector('header')).position"""
    )
    assert position == "sticky"
    page.evaluate("window.scrollTo(0, 400)")
    page.wait_for_timeout(200)
    box = header.bounding_box()
    assert box is not None and box["y"] <= 8, f"sticky header lost after scroll: {box}"

    evidence = _shot(page, path, viewport_label)

    results: list[dict] = []
    if f1_results_path.is_file():
        results = json.loads(f1_results_path.read_text(encoding="utf-8"))
    results.append(
        {
            "check": "layout",
            "route": path,
            "viewport": viewport_label,
            "dimensions": f"{width}x{height}",
            "overflow_delta_px": delta,
            "screenshot": evidence,
            "status": "PASS",
        }
    )
    f1_results_path.write_text(json.dumps(results, indent=2), encoding="utf-8")

    _assert_console_clean(console_errors)


@pytest.mark.f1_visual
def test_f1_mobile_drawer_navigation_and_focus(page: Page, base_url: str, console_errors: list[str]) -> None:
    page.set_viewport_size({"width": 390, "height": 844})
    NavigationHelper(page, base_url).goto_path("/")
    page.wait_for_load_state("networkidle")

    banner = page.get_by_role("banner")
    open_btn = banner.get_by_role("button", name="Open navigation menu")
    expect(open_btn).to_be_visible()
    open_btn.click()
    panel = page.locator("#public-mobile-nav")
    expect(panel).to_be_visible()
    expect(banner.get_by_role("button", name="Close navigation menu")).to_be_visible()

    focused_in_panel = page.evaluate(
        """() => {
            const panel = document.getElementById('public-mobile-nav');
            return panel ? panel.contains(document.activeElement) : false;
        }"""
    )
    assert focused_in_panel

    delta_open = _overflow_delta(page)
    assert delta_open <= 8

    with page.expect_navigation(url=re.compile(r"/jobs")):
        panel.get_by_role("link", name="Jobs").click()
    page.wait_for_load_state("networkidle")
    expect(page.get_by_role("heading", level=1, name="Find your next role")).to_be_visible()

    page.goto(f"{base_url}/hire", wait_until="networkidle")
    page.get_by_role("button", name="Open navigation menu").click()
    expect(panel).to_be_visible()
    page.keyboard.press("Escape")
    expect(panel).not_to_be_visible()

    _assert_console_clean(console_errors)


@pytest.mark.f1_visual
def test_f1_desktop_primary_navigation_visible(page: Page, base_url: str) -> None:
    page.set_viewport_size({"width": 1400, "height": 900})
    NavigationHelper(page, base_url).goto_path("/")
    page.wait_for_load_state("networkidle")
    primary = page.get_by_role("navigation", name="Primary")
    expect(primary).to_be_visible()
    for label in ("Jobs", "Hire", "Talent Pool", "Staffing"):
        expect(primary.get_by_role("link", name=label)).to_be_visible()
