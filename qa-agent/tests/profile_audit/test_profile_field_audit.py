"""Exhaustive profile field save/reload checks — one login per role (rate-limit safe)."""

from __future__ import annotations

import re
import time

import pytest
from playwright.sync_api import Page, expect

from ournear_qa.browser.forms import FormHelper
from ournear_qa.evidence import ARTIFACTS_DIR

EVIDENCE_ROOT = ARTIFACTS_DIR / "profile_audit" / "evidence"


def evidence_dir(run_id: str, case_id: str):
    path = EVIDENCE_ROOT / run_id / case_id
    path.mkdir(parents=True, exist_ok=True)
    return path

pytestmark = [pytest.mark.profile_audit]


def _shot(page: Page, directory, name: str) -> str:
    target = directory / name
    page.screenshot(path=str(target), full_page=True)
    return target.relative_to(ARTIFACTS_DIR).as_posix()


def _login_account(page: Page, base_url: str, login_id: str, password: str) -> None:
    page.goto(f"{base_url}/login", wait_until="networkidle")
    if "Too Many Requests" in page.title():
        time.sleep(20)
        page.goto(f"{base_url}/login", wait_until="networkidle")
    forms = FormHelper(page)
    forms.fill_account_login(login_id)
    forms.fill_field("Password", password)
    for attempt in range(3):
        with page.expect_response(
            lambda r: r.request.method == "POST" and r.url.rstrip("/").endswith("/login"),
            timeout=30_000,
        ) as response_info:
            forms.click_login_submit()
        if response_info.value.status == 429:
            time.sleep(int(response_info.value.headers.get("retry-after", "15") or 15) + 2)
            continue
        break
    page.wait_for_url(re.compile(r"/account"), timeout=30_000)


def _save_main_profile_form(page: Page) -> None:
    page.get_by_role("button", name="Save", exact=True).click()
    page.wait_for_load_state("networkidle")


def _save_professional_form(page: Page) -> None:
    page.locator("form").filter(has=page.locator("#current_designation, #availability, #employment_preference")).get_by_role(
        "button", name="Save", exact=True
    ).first.click()
    page.wait_for_load_state("networkidle")


@pytest.fixture
def desktop_page(page: Page):
    page.set_viewport_size({"width": 1400, "height": 900})
    return page


def test_pfa_candidate_fields_single_session(
    desktop_page,
    base_url,
    qa_settings,
    profile_audit_run_id,
    record_profile_audit,
) -> None:
    if not qa_settings.qa_candidate_email or not qa_settings.qa_candidate_password:
        pytest.skip("Candidate QA creds missing")
    tag = profile_audit_run_id[:8]
    email = qa_settings.qa_candidate_email
    password = qa_settings.qa_candidate_password
    _login_account(desktop_page, base_url, email, password)

    checks: list[tuple[str, str, str, callable]] = []

    def check_basic_location():
        desktop_page.goto(f"{base_url}/account/profile#basic", wait_until="networkidle")
        marker = f"PFA loc {tag}"
        desktop_page.locator("#location").fill(marker)
        _save_main_profile_form(desktop_page)
        desktop_page.reload(wait_until="networkidle")
        desktop_page.goto(f"{base_url}/account/profile#basic", wait_until="networkidle")
        expect(desktop_page.locator("#location")).to_have_value(marker)

    checks.append(("PFA-C-001", "location", "/account/profile#basic", check_basic_location))

    def check_about_headline():
        desktop_page.goto(f"{base_url}/account/profile#about", wait_until="networkidle")
        marker = f"PFA headline {tag}"
        desktop_page.locator("#professional_headline").fill(marker)
        _save_main_profile_form(desktop_page)
        desktop_page.reload(wait_until="networkidle")
        desktop_page.goto(f"{base_url}/account/profile#about", wait_until="networkidle")
        expect(desktop_page.locator("#professional_headline")).to_have_value(marker)

    checks.append(("PFA-C-002", "professional_headline", "/account/profile#about", check_about_headline))

    def check_professional_company():
        desktop_page.goto(f"{base_url}/account/profile#professional", wait_until="networkidle")
        marker = f"PFA Co {tag}"
        desktop_page.locator("#current_company").fill(marker)
        _save_professional_form(desktop_page)
        desktop_page.reload(wait_until="networkidle")
        desktop_page.goto(f"{base_url}/account/profile#professional", wait_until="networkidle")
        expect(desktop_page.locator("#current_company")).to_have_value(marker)

    checks.append(("PFA-C-003", "current_company", "/account/profile#professional", check_professional_company))

    def check_preferences_availability():
        desktop_page.goto(f"{base_url}/account/profile#preferences", wait_until="networkidle")
        marker = f"Immediate {tag}"
        desktop_page.locator("#availability").fill(marker)
        _save_professional_form(desktop_page)
        desktop_page.reload(wait_until="networkidle")
        desktop_page.goto(f"{base_url}/account/profile#preferences", wait_until="networkidle")
        expect(desktop_page.locator("#availability")).to_have_value(marker)

    checks.append(("PFA-C-004", "availability", "/account/profile#preferences", check_preferences_availability))

    def check_employment_history():
        desktop_page.goto(f"{base_url}/account/profile#experience", wait_until="networkidle")
        company = f"PFA EmpCo {tag}"
        desktop_page.locator("#history_company").fill(company)
        desktop_page.locator("#history_designation").fill("Analyst")
        desktop_page.locator("#history_started_on").fill("2020-01-01")
        desktop_page.get_by_label("This is my current employment").check()
        desktop_page.get_by_role("button", name="Add record").click()
        desktop_page.wait_for_load_state("networkidle")
        desktop_page.reload(wait_until="networkidle")
        desktop_page.goto(f"{base_url}/account/profile#experience", wait_until="networkidle")
        expect(desktop_page.get_by_text(company).first).to_be_visible()

    checks.append(("PFA-C-005", "employment_history", "/account/profile#experience", check_employment_history))

    def check_education():
        desktop_page.goto(f"{base_url}/account/profile#education", wait_until="networkidle")
        inst = f"PFA Uni {tag}"
        desktop_page.locator("#education_institution").fill(inst)
        desktop_page.get_by_role("button", name="Add education").click()
        desktop_page.wait_for_load_state("networkidle")
        desktop_page.reload(wait_until="networkidle")
        desktop_page.goto(f"{base_url}/account/profile#education", wait_until="networkidle")
        expect(desktop_page.get_by_text(inst).first).to_be_visible()

    checks.append(("PFA-C-006", "education_institution", "/account/profile#education", check_education))

    def check_project():
        desktop_page.goto(f"{base_url}/account/profile#projects", wait_until="networkidle")
        name = f"PFA Proj {tag}"
        desktop_page.locator("#project_name").fill(name)
        desktop_page.get_by_role("button", name="Add project").click()
        desktop_page.wait_for_load_state("networkidle")
        desktop_page.reload(wait_until="networkidle")
        desktop_page.goto(f"{base_url}/account/profile#projects", wait_until="networkidle")
        expect(desktop_page.get_by_text(name).first).to_be_visible()

    checks.append(("PFA-C-007", "project_name", "/account/profile#projects", check_project))

    def check_certification():
        desktop_page.goto(f"{base_url}/account/profile#certifications", wait_until="networkidle")
        name = f"PFA Cert {tag}"
        desktop_page.locator("#certification_name").fill(name)
        desktop_page.get_by_role("button", name="Add certification").click()
        desktop_page.wait_for_load_state("networkidle")
        desktop_page.reload(wait_until="networkidle")
        desktop_page.goto(f"{base_url}/account/profile#certifications", wait_until="networkidle")
        expect(desktop_page.get_by_text(name).first).to_be_visible()

    checks.append(("PFA-C-008", "certification_name", "/account/profile#certifications", check_certification))

    def check_language():
        desktop_page.goto(f"{base_url}/account/profile#languages", wait_until="networkidle")
        name = f"PFA Lang {tag}"
        desktop_page.locator("#language_name").fill(name)
        desktop_page.get_by_role("button", name="Add language").click()
        desktop_page.wait_for_load_state("networkidle")
        desktop_page.reload(wait_until="networkidle")
        desktop_page.goto(f"{base_url}/account/profile#languages", wait_until="networkidle")
        expect(desktop_page.get_by_text(name).first).to_be_visible()

    checks.append(("PFA-C-009", "language_name", "/account/profile#languages", check_language))

    def check_employment_preference():
        desktop_page.goto(f"{base_url}/account/profile#preferences", wait_until="networkidle")
        marker = f"PFA pref {tag}"
        desktop_page.locator("#employment_preference").fill(marker)
        _save_professional_form(desktop_page)
        desktop_page.reload(wait_until="networkidle")
        desktop_page.goto(f"{base_url}/account/profile#preferences", wait_until="networkidle")
        expect(desktop_page.locator("#employment_preference")).to_have_value(marker)

    checks.append(("PFA-C-010", "employment_preference", "/account/profile#preferences", check_employment_preference))

    def check_professional_salary():
        desktop_page.goto(f"{base_url}/account/profile#professional", wait_until="networkidle")
        desktop_page.locator("#current_salary").fill("50000")
        _save_professional_form(desktop_page)
        desktop_page.reload(wait_until="networkidle")
        desktop_page.goto(f"{base_url}/account/profile#professional", wait_until="networkidle")
        expect(desktop_page.locator("#current_salary")).to_have_value(re.compile(r"50000"))

    checks.append(("PFA-C-011", "current_salary", "/account/profile#professional", check_professional_salary))

    failures: list[str] = []
    for case_id, field_id, route, fn in checks:
        ev = evidence_dir(profile_audit_run_id, case_id)
        try:
            fn()
            evidence_rel = _shot(desktop_page, ev, "pass.png")
            record_profile_audit(
                case_id,
                field_id=field_id,
                route=route,
                expected="Save persists after reload",
                actual="Value or record visible after reload",
                status="PASS",
                evidence_rel=evidence_rel,
            )
        except Exception as exc:  # noqa: BLE001 — record FAIL for audit report
            evidence_rel = _shot(desktop_page, ev, "fail.png")
            record_profile_audit(
                case_id,
                field_id=field_id,
                route=route,
                expected="Save persists after reload",
                actual=str(exc)[:500],
                status="FAIL",
                evidence_rel=evidence_rel,
            )
            failures.append(f"{case_id}: {exc}")

    if failures:
        raise AssertionError("; ".join(failures[:3]))


def test_pfa_employer_fields_single_session(
    desktop_page,
    base_url,
    qa_settings,
    profile_audit_run_id,
    record_profile_audit,
) -> None:
    if not qa_settings.qa_employer_a_email or not qa_settings.qa_employer_a_password:
        pytest.skip("Employer QA creds missing")
    tag = profile_audit_run_id[:8]
    email = qa_settings.qa_employer_a_email
    password = qa_settings.qa_employer_a_password
    _login_account(desktop_page, base_url, email, password)
    desktop_page.goto(f"{base_url}/account/profile", wait_until="networkidle")
    desktop_page.get_by_role("button", name="Edit company profile").click()
    desktop_page.locator("#company_name").wait_for(state="visible")

    fields = [
        ("PFA-E-001", "company_name", "#company_name", f"PFA Co {tag}"),
        ("PFA-E-002", "contact_person", "#contact_person", f"PFA Contact {tag}"),
        ("PFA-E-003", "about_company", "#about_company", f"PFA about {tag}"),
        ("PFA-E-004", "company_website", "#company_website", "https://example.test/pfa"),
        ("PFA-E-005", "industry", "#industry", f"PFA Industry {tag}"),
        ("PFA-E-006", "company_size", "#company_size", "51-200"),
        ("PFA-E-007", "benefits_text", "#benefits_text", f"Health\nPFA {tag}"),
        ("PFA-E-008", "socials_linkedin", "#socials_linkedin", "https://linkedin.com/company/pfa"),
    ]

    for case_id, field_id, selector, marker in fields:
        ev = evidence_dir(profile_audit_run_id, case_id)
        try:
            desktop_page.locator(selector).fill(marker)
            _save_main_profile_form(desktop_page)
            desktop_page.reload(wait_until="networkidle")
            desktop_page.get_by_role("button", name="Edit company profile").click()
            expect(desktop_page.locator(selector)).to_have_value(marker)
            evidence_rel = _shot(desktop_page, ev, "pass.png")
            record_profile_audit(
                case_id,
                field_id=field_id,
                route="/account/profile",
                expected="Employer field persists after reload",
                actual="Field value matches marker",
                status="PASS",
                evidence_rel=evidence_rel,
            )
        except Exception as exc:  # noqa: BLE001
            evidence_rel = _shot(desktop_page, ev, "fail.png")
            record_profile_audit(
                case_id,
                field_id=field_id,
                route="/account/profile",
                expected="Employer field persists after reload",
                actual=str(exc)[:500],
                status="FAIL",
                evidence_rel=evidence_rel,
            )
            raise
