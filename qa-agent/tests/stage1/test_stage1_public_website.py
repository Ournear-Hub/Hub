"""Stage 1 — browser verification of public website and job discovery."""

from __future__ import annotations

import re

import pytest
from playwright.sync_api import Page, expect

from ournear_qa.browser.navigation import NavigationHelper
from ournear_qa.evidence import ARTIFACTS_DIR

pytestmark = [pytest.mark.stage1]

STAGE1_EVIDENCE_ROOT = ARTIFACTS_DIR / "stage1" / "evidence"


def stage1_evidence_dir(run_id: str, case_id: str):
    path = STAGE1_EVIDENCE_ROOT / run_id / case_id
    path.mkdir(parents=True, exist_ok=True)
    return path


def _shot(page: Page, directory, name: str) -> str:
    target = directory / name
    page.screenshot(path=str(target), full_page=True)
    return target.relative_to(ARTIFACTS_DIR).as_posix()


def _jobs_search_form(page: Page):
    return page.locator('form:has(#job-keywords)')


def _jobs_search_button(page: Page):
    return _jobs_search_form(page).get_by_role("button", name="Search")


def _job_results(page: Page):
    return page.locator("aside [aria-label='Job results']").first


def _console_errors(page: Page) -> list[str]:
    errors: list[str] = []

    def on_console(msg):
        if msg.type == "error":
            errors.append(msg.text)

    page.on("console", on_console)
    return errors


@pytest.fixture
def desktop_page(page: Page):
    page.set_viewport_size({"width": 1400, "height": 900})
    return page


@pytest.fixture
def mobile_page(page: Page):
    page.set_viewport_size({"width": 390, "height": 844})
    return page


def test_stg1_pub_001_002_home_and_nav(desktop_page, base_url, stage1_run_id, record_stage1) -> None:
    case = "STG1-PUB-001"
    evidence = stage1_evidence_dir(stage1_run_id, case)
    nav = NavigationHelper(desktop_page, base_url)
    nav.goto_path("/")
    desktop_page.wait_for_load_state("networkidle")
    evidence_rel = _shot(desktop_page, evidence, "home.png")
    expect(desktop_page.get_by_role("navigation", name="Primary")).to_be_visible()
    record_stage1(
        case,
        feature_ids=["PUB-01"],
        route="/",
        expected="Home renders with primary navigation",
        actual="Home loaded; Primary nav visible",
        status="PASS",
        evidence_rel=evidence_rel,
    )

    primary = desktop_page.get_by_role("navigation", name="Primary")
    for label, href in [
        ("Jobs", "/jobs"),
        ("Hiring", "/hire"),
        ("Talent Pool", "/talent-pool"),
        ("Staffing", "/staffing"),
    ]:
        with desktop_page.expect_navigation(url=re.compile(re.escape(href))):
            primary.get_by_role("link", name=label, exact=True).click()
        desktop_page.wait_for_load_state("networkidle")
        nav.goto_path("/")

    footer = desktop_page.get_by_role("contentinfo")
    with desktop_page.expect_navigation(url=re.compile(r"/about")):
        footer.get_by_role("link", name="About", exact=True).click()
    desktop_page.wait_for_load_state("networkidle")
    nav.goto_path("/")

    record_stage1(
        "STG1-PUB-002",
        feature_ids=["PUB-01"],
        route="/",
        expected="Primary nav links open target public routes",
        actual="Jobs, Hiring, Talent Pool, Staffing + footer About navigated successfully",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg1_pub_003_jobs_listing(desktop_page, base_url, stage1_run_id, record_stage1) -> None:
    case = "STG1-PUB-003"
    evidence = stage1_evidence_dir(stage1_run_id, case)
    NavigationHelper(desktop_page, base_url).goto_path("/jobs")
    desktop_page.wait_for_load_state("networkidle")
    evidence_rel = _shot(desktop_page, evidence, "jobs-index.png")
    expect(desktop_page.get_by_role("heading", name="Jobs")).to_be_visible()
    expect(desktop_page.locator('input[name="keyword"]')).to_be_visible()
    expect(desktop_page.locator("aside").get_by_text(re.compile(r"\d+ open jobs", re.I)).first).to_be_visible()
    record_stage1(
        case,
        feature_ids=["PUB-02"],
        route="/jobs",
        expected="Jobs index with filters and open-job count",
        actual="Jobs heading, keyword field, and count visible",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg1_pub_004_keyword_search(desktop_page, base_url, stage1_run_id, record_stage1) -> None:
    case = "STG1-PUB-004"
    evidence = stage1_evidence_dir(stage1_run_id, case)
    NavigationHelper(desktop_page, base_url).goto_path("/jobs")
    desktop_page.wait_for_load_state("networkidle")
    desktop_page.locator('input[name="keyword"]').fill("QA Fixture Published Role")
    _jobs_search_button(desktop_page).click()
    desktop_page.wait_for_load_state("networkidle")
    evidence_rel = _shot(desktop_page, evidence, "keyword-search.png")
    expect(desktop_page.locator('a[href*="qa-fixture-published-a"]').first).to_be_attached()
    record_stage1(
        case,
        feature_ids=["PUB-02"],
        route="/jobs",
        expected="Keyword search surfaces fixture published job",
        actual="Fixture published job link present in results",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg1_pub_005_company_filter_desktop(desktop_page, base_url, stage1_run_id, record_stage1) -> None:
    case = "STG1-PUB-005"
    evidence = stage1_evidence_dir(stage1_run_id, case)
    NavigationHelper(desktop_page, base_url).goto_path("/jobs")
    company = desktop_page.locator("#job-company")
    expect(company).to_be_visible()
    company.select_option("QA Hiring Co A")
    _jobs_search_button(desktop_page).click()
    desktop_page.wait_for_load_state("networkidle")
    evidence_rel = _shot(desktop_page, evidence, "company-filter-desktop.png")
    expect(company).to_have_value("QA Hiring Co A")
    cards = _job_results(desktop_page).locator("article")
    expect(cards.first).to_be_attached()
    record_stage1(
        case,
        feature_ids=["PUB-02"],
        route="/jobs",
        expected="Visible company select filters results",
        actual="QA Hiring Co A selected; results listed",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg1_pub_006_company_filter_mobile(mobile_page, base_url, stage1_run_id, record_stage1) -> None:
    case = "STG1-PUB-006"
    evidence = stage1_evidence_dir(stage1_run_id, case)
    NavigationHelper(mobile_page, base_url).goto_path("/jobs")
    mobile_page.wait_for_load_state("networkidle")
    company = mobile_page.locator("#job-company")
    expect(company).to_be_visible()
    company.select_option("QA Hiring Co A")
    _jobs_search_button(mobile_page).click()
    mobile_page.wait_for_load_state("networkidle")
    evidence_rel = _shot(mobile_page, evidence, "company-filter-mobile.png")
    expect(company).to_have_value("QA Hiring Co A")
    record_stage1(
        case,
        feature_ids=["PUB-02"],
        route="/jobs",
        expected="Company filter operable on mobile without hidden control",
        actual="#job-company visible and filter applied",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg1_pub_007_other_filters(desktop_page, base_url, stage1_run_id, record_stage1) -> None:
    case = "STG1-PUB-007"
    evidence = stage1_evidence_dir(stage1_run_id, case)
    NavigationHelper(desktop_page, base_url).goto_path("/jobs")
    type_select = desktop_page.locator("#job-type")
    if type_select.locator("option").count() > 1:
        type_select.select_option(index=1)
    loc_select = desktop_page.locator("#job-location")
    if loc_select.locator("option").count() > 1:
        loc_select.select_option(index=1)
    _jobs_search_button(desktop_page).click()
    desktop_page.wait_for_load_state("networkidle")
    evidence_rel = _shot(desktop_page, evidence, "other-filters.png")
    expect(desktop_page.get_by_role("heading", name="Jobs")).to_be_visible()
    record_stage1(
        case,
        feature_ids=["PUB-02"],
        route="/jobs",
        expected="Job type and location filters submit without error",
        actual="Search completed; jobs page stable",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg1_pub_008_clear_filters(desktop_page, base_url, stage1_run_id, record_stage1) -> None:
    case = "STG1-PUB-008"
    evidence = stage1_evidence_dir(stage1_run_id, case)
    NavigationHelper(desktop_page, base_url).goto_path("/jobs?company=QA%20Hiring%20Co%20A")
    desktop_page.wait_for_load_state("networkidle")
    _jobs_search_form(desktop_page).get_by_role("button", name="Clear").click()
    desktop_page.wait_for_load_state("networkidle")
    evidence_rel = _shot(desktop_page, evidence, "clear-filters.png")
    expect(desktop_page.locator("#job-company")).to_have_value("")
    record_stage1(
        case,
        feature_ids=["PUB-02"],
        route="/jobs",
        expected="Clear filters resets company selection",
        actual="Company select empty after Clear filters",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg1_pub_009_pagination(desktop_page, base_url, stage1_run_id, record_stage1) -> None:
    case = "STG1-PUB-009"
    evidence = stage1_evidence_dir(stage1_run_id, case)
    NavigationHelper(desktop_page, base_url).goto_path("/jobs")
    desktop_page.locator("#job-company").select_option("QA Hiring Co A")
    _jobs_search_button(desktop_page).click()
    desktop_page.wait_for_load_state("networkidle")
    results = _job_results(desktop_page)
    initial_cards = results.locator("article").count()
    aside = desktop_page.locator("aside").first
    showing_line = aside.get_by_text(re.compile(r"showing \d+", re.I))
    if showing_line.count() == 0:
        record_stage1(
            case,
            feature_ids=["PUB-02"],
            route="/jobs",
            expected="Infinite scroll when >9 company jobs",
            actual="No partial list — insufficient QA Hiring Co A jobs (run qa:hiring-fixtures)",
            status="BLOCKED",
            evidence_rel=None,
        )
        pytest.skip("Need >9 QA Hiring Co A jobs — run php artisan qa:hiring-fixtures")

    results.evaluate("el => { el.scrollTop = el.scrollHeight; }")
    desktop_page.wait_for_timeout(1500)
    desktop_page.wait_for_load_state("networkidle")
    final_count = results.locator("article").count()
    if final_count <= initial_cards:
        desktop_page.wait_for_function(
            f"() => document.querySelectorAll('aside [aria-label=\"Job results\"] article').length > {initial_cards}",
            timeout=15000,
        )
        final_count = results.locator("article").count()

    evidence_rel = _shot(desktop_page, evidence, "infinite-scroll-more-jobs.png")
    assert final_count > initial_cards, f"Expected more job cards after scroll ({initial_cards} -> {final_count})"
    expect(desktop_page.locator("#job-company")).to_have_value("QA Hiring Co A")
    record_stage1(
        case,
        feature_ids=["PUB-02"],
        route="/jobs",
        expected="Company filter retained while loading more jobs",
        actual=f"Cards grew {initial_cards} → {final_count}; company select retained",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg1_pub_010_empty_results(desktop_page, base_url, stage1_run_id, record_stage1) -> None:
    case = "STG1-PUB-010"
    evidence = stage1_evidence_dir(stage1_run_id, case)
    NavigationHelper(desktop_page, base_url).goto_path("/jobs")
    desktop_page.locator('input[name="keyword"]').fill("zzzz-no-matching-jobs-zzzz")
    _jobs_search_button(desktop_page).click()
    desktop_page.wait_for_load_state("networkidle")
    evidence_rel = _shot(desktop_page, evidence, "empty-results.png")
    expect(desktop_page.get_by_role("heading", name="No matching roles")).to_be_visible()
    record_stage1(
        case,
        feature_ids=["PUB-02"],
        route="/jobs",
        expected="Empty state when no matches",
        actual="No matching roles heading shown",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg1_pub_011_job_detail(page, base_url, stage1_run_id, record_stage1) -> None:
    case = "STG1-PUB-011"
    evidence = stage1_evidence_dir(stage1_run_id, case)
    page.set_viewport_size({"width": 1400, "height": 900})
    job_slug = "qa-fixture-published-a"
    job_title = "QA Fixture Published Role (Employer A)"
    NavigationHelper(page, base_url).goto_path(f"/jobs?job={job_slug}")
    page.wait_for_load_state("networkidle")
    desktop_evidence = _shot(page, evidence, "job-detail-desktop.png")
    detail = page.get_by_role("region", name="Selected job")
    expect(detail.locator("h1")).to_contain_text(job_title)
    expect(
        _job_results(page).get_by_role("button", name=job_title, exact=True),
    ).to_be_visible()
    expect(page).to_have_url(re.compile(re.escape(f"job={job_slug}")))

    page.set_viewport_size({"width": 390, "height": 844})
    NavigationHelper(page, base_url).goto_path(f"/jobs/{job_slug}")
    page.wait_for_load_state("networkidle")
    _shot(page, evidence, "job-detail-mobile.png")
    expect(page.locator("main h1.text-ink", has_text=job_title)).to_be_visible()

    record_stage1(
        case,
        feature_ids=["PUB-03"],
        route="/jobs",
        expected="Job detail reflects listing selection",
        actual="Desktop pane and mobile full page show fixture job",
        status="PASS",
        evidence_rel=desktop_evidence,
    )


def test_stg1_pub_012_guest_apply_redirect(desktop_page, base_url, stage1_run_id, record_stage1) -> None:
    case = "STG1-PUB-012"
    evidence = stage1_evidence_dir(stage1_run_id, case)
    desktop_page.goto(f"{base_url}/jobs/qa-fixture-published-a/apply", wait_until="networkidle", timeout=60000)
    evidence_rel = _shot(desktop_page, evidence, "guest-apply-redirect.png")
    expect(desktop_page).to_have_url(re.compile(r"/login"))
    record_stage1(
        case,
        feature_ids=["PUB-03"],
        route="/jobs/{slug}/apply",
        expected="Guest apply redirects to login",
        actual=f"URL redirected to login ({desktop_page.url})",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg1_pub_013_draft_not_public(desktop_page, base_url, stage1_run_id, record_stage1) -> None:
    case = "STG1-PUB-013"
    evidence = stage1_evidence_dir(stage1_run_id, case)
    response = desktop_page.goto(f"{base_url}/jobs/qa-fixture-draft-a", wait_until="domcontentloaded")
    evidence_rel = _shot(desktop_page, evidence, "draft-404.png")
    status_code = response.status if response else 0
    assert status_code == 404, f"Expected 404 for draft slug, got {status_code}"
    record_stage1(
        case,
        feature_ids=["PUB-02"],
        route="/jobs/qa-fixture-draft-a",
        expected="Draft job not public",
        actual=f"HTTP {status_code}",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg1_pub_014_published_visible(desktop_page, base_url, stage1_run_id, record_stage1) -> None:
    case = "STG1-PUB-014"
    evidence = stage1_evidence_dir(stage1_run_id, case)
    response = desktop_page.goto(f"{base_url}/jobs/qa-fixture-published-a", wait_until="networkidle")
    evidence_rel = _shot(desktop_page, evidence, "published-job.png")
    assert response and response.ok
    expect(desktop_page.get_by_text(re.compile(r"QA Fixture Published Role", re.I)).first).to_be_visible()
    record_stage1(
        case,
        feature_ids=["PUB-02"],
        route="/jobs/qa-fixture-published-a",
        expected="Published fixture job visible",
        actual="200 OK with fixture title",
        status="PASS",
        evidence_rel=evidence_rel,
    )


@pytest.mark.parametrize(
    "case_id, route, feature_ids, heading",
    [
        ("STG1-PUB-015", "/companies", ["PUB-04"], "Companies"),
        ("STG1-PUB-016", "/hire", ["PUB-05"], "Hire"),
        ("STG1-PUB-017", "/talent-pool", ["PUB-05"], "Talent"),
        ("STG1-PUB-018", "/staffing", ["PUB-05"], "Staffing"),
        ("STG1-PUB-019", "/about", ["PUB-01"], "Our Story"),
        ("STG1-PUB-020", "/services", ["PUB-01"], "Services"),
        ("STG1-PUB-021", "/for-employers", ["PUB-07"], "Employer"),
        ("STG1-PUB-022", "/contact", ["PUB-07"], "Contact"),
        ("STG1-PUB-023", "/privacy-policy", ["PUB-08"], "Privacy"),
        ("STG1-PUB-024", "/terms-and-conditions", ["PUB-08"], "Terms"),
        ("STG1-PUB-025", "/freelancers", ["PUB-09"], "Freelancer"),
    ],
)
def test_stg1_public_static_pages(
    desktop_page,
    base_url,
    stage1_run_id,
    record_stage1,
    case_id,
    route,
    feature_ids,
    heading,
) -> None:
    evidence = stage1_evidence_dir(stage1_run_id, case_id)
    NavigationHelper(desktop_page, base_url).goto_path(route)
    desktop_page.wait_for_load_state("networkidle")
    evidence_rel = _shot(desktop_page, evidence, "page.png")
    expect(desktop_page.get_by_role("heading", name=re.compile(heading, re.I)).first).to_be_visible()
    record_stage1(
        case_id,
        feature_ids=feature_ids,
        route=route,
        expected=f"{route} loads with expected heading",
        actual=f"Page loaded; heading matches /{heading}/i",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg1_pub_026_footer_links(desktop_page, base_url, stage1_run_id, record_stage1) -> None:
    case = "STG1-PUB-026"
    evidence = stage1_evidence_dir(stage1_run_id, case)
    NavigationHelper(desktop_page, base_url).goto_path("/")
    footer = desktop_page.get_by_role("contentinfo")
    for label, part in [
        ("Contact", "/contact"),
        ("Privacy Policy", "/privacy-policy"),
        ("Terms & Conditions", "/terms-and-conditions"),
    ]:
        with desktop_page.expect_navigation(url=re.compile(re.escape(part))):
            footer.get_by_role("link", name=label, exact=True).click()
        desktop_page.wait_for_load_state("networkidle")
        desktop_page.goto(f"{base_url}/")
    evidence_rel = _shot(desktop_page, evidence, "footer.png")
    record_stage1(
        case,
        feature_ids=["PUB-08"],
        route="/",
        expected="Footer support links navigate",
        actual="Contact, Privacy Policy, Terms & Conditions links OK",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg1_pub_027_auth_links(desktop_page, base_url, stage1_run_id, record_stage1) -> None:
    case = "STG1-PUB-027"
    evidence = stage1_evidence_dir(stage1_run_id, case)
    NavigationHelper(desktop_page, base_url).goto_path("/jobs")
    header = desktop_page.locator("header")
    with desktop_page.expect_navigation(url=re.compile(r"/login")):
        header.get_by_role("link", name="Login").click()
    desktop_page.wait_for_load_state("networkidle")
    NavigationHelper(desktop_page, base_url).goto_path("/jobs")
    desktop_page.wait_for_load_state("networkidle")
    with desktop_page.expect_navigation(url=re.compile(r"/register")):
        header.get_by_role("link", name="Signup").click()
    desktop_page.wait_for_load_state("networkidle")
    evidence_rel = _shot(desktop_page, evidence, "auth-links.png")
    record_stage1(
        case,
        feature_ids=["ACC-AUTH-01"],
        route="/login",
        expected="Login and Signup reachable from public header",
        actual="Both routes opened from jobs page header",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg1_pub_028_query_reload(desktop_page, base_url, stage1_run_id, record_stage1) -> None:
    case = "STG1-PUB-028"
    evidence = stage1_evidence_dir(stage1_run_id, case)
    NavigationHelper(desktop_page, base_url).goto_path("/jobs?company=QA%20Hiring%20Co%20A")
    desktop_page.wait_for_load_state("networkidle")
    desktop_page.reload(wait_until="networkidle")
    evidence_rel = _shot(desktop_page, evidence, "reload-company-query.png")
    expect(desktop_page.locator("#job-company")).to_have_value("QA Hiring Co A")
    record_stage1(
        case,
        feature_ids=["PUB-02"],
        route="/jobs",
        expected="Company filter survives reload",
        actual="Select value matches query after reload",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg1_pub_029_030_auth_boundaries(desktop_page, base_url, stage1_run_id, record_stage1) -> None:
    evidence = stage1_evidence_dir(stage1_run_id, "STG1-PUB-029")
    desktop_page.goto(f"{base_url}/account", wait_until="networkidle")
    _shot(desktop_page, evidence, "account-guest.png")
    assert "/login" in desktop_page.url
    record_stage1(
        "STG1-PUB-029",
        feature_ids=["X-01"],
        route="/account",
        expected="Guest redirected from /account",
        actual=f"Redirected to {desktop_page.url}",
        status="PASS",
        evidence_rel="stage1/evidence/" + stage1_run_id + "/STG1-PUB-029/account-guest.png",
    )

    admin_evidence = stage1_evidence_dir(stage1_run_id, "STG1-PUB-030")
    desktop_page.goto(f"{base_url}/admin", wait_until="networkidle")
    admin_rel = _shot(desktop_page, admin_evidence, "admin-guest.png")
    assert "/admin/login" in desktop_page.url or "/login" in desktop_page.url
    record_stage1(
        "STG1-PUB-030",
        feature_ids=["X-01"],
        route="/admin",
        expected="Guest redirected from admin workspace",
        actual=f"Redirected to {desktop_page.url}",
        status="PASS",
        evidence_rel=admin_rel,
    )
