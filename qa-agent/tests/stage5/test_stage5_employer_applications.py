"""Stage 5 — employer application lists, detail, resume, filters, isolation."""

from __future__ import annotations

import re
import time

import pytest
from playwright.sync_api import Page, expect

from ournear_qa.browser.navigation import NavigationHelper
from ournear_qa.evidence import ARTIFACTS_DIR
from ournear_qa.journey.employer_resume import (
    discover_employer_journey_target,
    run_employer_a_resume_steps,
    run_employer_b_isolation_steps,
)
from ournear_qa.journey.errors import JourneyStepError
from ournear_qa.journey.employer_resume import assert_http_forbidden
from ournear_qa.journey.employer_resume import login_employer
from ournear_qa.journey.employer_resume import _application_show_path, _resume_path

pytestmark = [pytest.mark.stage5]

STAGE5_EVIDENCE_ROOT = ARTIFACTS_DIR / "stage5" / "evidence"


@pytest.fixture
def desktop_page(page: Page):
    page.set_viewport_size({"width": 1400, "height": 900})
    return page


@pytest.fixture(scope="module")
def employer_target():
    target = discover_employer_journey_target()
    if target is None:
        pytest.skip("Run php artisan qa:hiring-fixtures and ensure candidate application on Employer A job")
    if not target.resume_path_set:
        pytest.skip("Fixture application missing resume snapshot")
    return target


def stage5_evidence_dir(run_id: str, case_id: str):
    path = STAGE5_EVIDENCE_ROOT / run_id / case_id
    path.mkdir(parents=True, exist_ok=True)
    return path


def _shot(page: Page, directory, name: str) -> str:
    target = directory / name
    page.screenshot(path=str(target), full_page=True)
    return target.relative_to(ARTIFACTS_DIR).as_posix()


def _employer_creds(qa_settings) -> tuple[str, str]:
    if not qa_settings.qa_employer_a_email or not qa_settings.qa_employer_a_password:
        pytest.skip("Set OURNEAR_QA_EMPLOYER_A_EMAIL and OURNEAR_QA_EMPLOYER_A_PASSWORD")
    return qa_settings.qa_employer_a_email, qa_settings.qa_employer_a_password


def _parse_inspect_totals() -> int | None:
    target = discover_employer_journey_target()
    if target is None:
        return None
    import shutil
    import subprocess
    from pathlib import Path

    script = Path(__file__).resolve().parents[3] / "scripts" / "inspect-qa-employer-journey-target.php"
    php = shutil.which("php")
    if not script.is_file() or php is None:
        return None
    completed = subprocess.run([php, str(script)], capture_output=True, text=True, check=False)
    for line in completed.stdout.splitlines():
        if line.startswith("employer_a_application_total="):
            return int(line.split("=", 1)[1].strip())
    return None


def _skip_login_throttle(err: JourneyStepError) -> None:
    if "429" in err.actual and "account-login" in err.actual:
        pytest.skip("account-login rate limit")


def test_stg5_resume_and_list_journey(
    desktop_page, base_url, qa_settings, employer_target, stage5_run_id, record_stage5
) -> None:
    """Maps JRN-EMPLOYER-RESUME-01 steps to STG5 list/detail/resume cases."""
    email, password = _employer_creds(qa_settings)
    evidence = stage5_evidence_dir(stage5_run_id, "STG5-JRN")
    try:
        ctx = run_employer_a_resume_steps(desktop_page, base_url, email, password, employer_target)
    except JourneyStepError as err:
        _skip_login_throttle(err)
        raise

    shot = _shot(desktop_page, evidence, "journey-complete.png")
    t = employer_target

    if "D2-09-hiring-applications-list" in ctx.steps_completed:
        record_stage5(
            "STG5-LIST-001",
            feature_ids=["EMP-04"],
            route="/account/hiring/applications",
            expected=f"Fixture application {t.business_id} on hiring list",
            actual="Visible on /account/hiring/applications",
            status="PASS",
            evidence_rel=shot,
        )
    if "D2-02-applicants-list" in ctx.steps_completed:
        record_stage5(
            "STG5-LIST-002",
            feature_ids=["EMP-03"],
            route=f"/account/jobs/{t.job_slug}/applicants",
            expected="Job applicants list loads",
            actual=f"Heading {t.job_title!r}",
            status="PASS",
            evidence_rel=shot,
        )
    if "D2-04-application-detail" in ctx.steps_completed:
        record_stage5(
            "STG5-DET-001",
            feature_ids=["EMP-03"],
            route=f"/account/jobs/{t.job_slug}/applications/{t.application_id}",
            expected="Application ID and detail visible",
            actual=f"business_id={t.business_id}",
            status="PASS",
            evidence_rel=shot,
        )
    if "D2-07-download-resume" in ctx.steps_completed:
        record_stage5(
            "STG5-RES-001",
            feature_ids=["EMP-03", "JRN-EMPLOYER-RESUME-01"],
            route=f"/account/employer-applications/{t.application_id}/resume",
            expected="Readable PDF via View and Download",
            actual="D2-06 inline + D2-07 download passed",
            status="PASS",
            evidence_rel=shot,
        )
    if "D2-08-persist-after-reload" in ctx.steps_completed:
        record_stage5(
            "STG5-RES-002",
            feature_ids=["EMP-03"],
            route=f"/account/jobs/{t.job_slug}/applications/{t.application_id}",
            expected="Detail persists after reload",
            actual="business_id and resume links visible after reload",
            status="PASS",
            evidence_rel=shot,
        )


def test_stg5_filt_001_search_filter(
    desktop_page, base_url, qa_settings, employer_target, stage5_run_id, record_stage5
) -> None:
    case = "STG5-FILT-001"
    evidence = stage5_evidence_dir(stage5_run_id, case)
    email, password = _employer_creds(qa_settings)
    login_employer(desktop_page, base_url, email, password)
    NavigationHelper(desktop_page, base_url).goto_path("/account/hiring/applications")
    expect(desktop_page.get_by_text(employer_target.business_id).first).to_be_visible(timeout=20_000)
    candidate_name = desktop_page.locator("article, li, div").filter(
        has_text=employer_target.business_id
    ).first.inner_text().split("\n")[0][:40]

    desktop_page.get_by_placeholder("Candidate name").fill(candidate_name.split()[0] if candidate_name else "QA")
    desktop_page.get_by_role("button", name="Filter").click()
    desktop_page.wait_for_load_state("networkidle")
    expect(desktop_page.get_by_text(employer_target.business_id).first).to_be_visible(timeout=15_000)
    evidence_rel = _shot(desktop_page, evidence, "search-hit.png")
    record_stage5(
        case,
        feature_ids=["EMP-04"],
        route="/account/hiring/applications",
        expected="Search returns fixture application",
        actual=f"business_id visible after search term",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg5_filt_002_clear_filters(
    desktop_page, base_url, qa_settings, employer_target, stage5_run_id, record_stage5
) -> None:
    case = "STG5-FILT-002"
    evidence = stage5_evidence_dir(stage5_run_id, case)
    email, password = _employer_creds(qa_settings)
    login_employer(desktop_page, base_url, email, password)
    NavigationHelper(desktop_page, base_url).goto_path("/account/hiring/applications?q=zzzz-no-match-zzzz")
    desktop_page.wait_for_load_state("networkidle")
    NavigationHelper(desktop_page, base_url).goto_path("/account/hiring/applications")
    expect(desktop_page.get_by_text(employer_target.business_id).first).to_be_visible(timeout=20_000)
    evidence_rel = _shot(desktop_page, evidence, "cleared-filters.png")
    record_stage5(
        case,
        feature_ids=["EMP-04"],
        route="/account/hiring/applications",
        expected="Clearing query restores fixture row",
        actual="business_id visible after navigating without filters",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg5_cnt_001_list_total(
    desktop_page, base_url, qa_settings, employer_target, stage5_run_id, record_stage5
) -> None:
    case = "STG5-CNT-001"
    evidence = stage5_evidence_dir(stage5_run_id, case)
    expected_total = _parse_inspect_totals()
    email, password = _employer_creds(qa_settings)
    login_employer(desktop_page, base_url, email, password)
    NavigationHelper(desktop_page, base_url).goto_path("/account/hiring/applications")
    count_line = desktop_page.get_by_text(re.compile(r"\d+ applications?")).first.inner_text()
    match = re.search(r"(\d+)", count_line)
    ui_total = int(match.group(1)) if match else -1
    evidence_rel = _shot(desktop_page, evidence, "count-line.png")
    if expected_total is None:
        record_stage5(
            case,
            feature_ids=["EMP-04"],
            route="/account/hiring/applications",
            expected="UI total matches employer-scoped count",
            actual=f"UI shows {ui_total}; inspect script unavailable",
            status="BLOCKED",
            evidence_rel=evidence_rel,
        )
        pytest.skip("inspect script total unavailable")
    status = "PASS" if ui_total == expected_total else "FAIL"
    record_stage5(
        case,
        feature_ids=["EMP-04"],
        route="/account/hiring/applications",
        expected=f"List total = {expected_total}",
        actual=f"UI total={ui_total}, script total={expected_total}",
        status=status,
        evidence_rel=evidence_rel,
    )
    if status == "FAIL":
        pytest.fail(f"count mismatch UI={ui_total} expected={expected_total}")


def test_stg5_act_001_read_only_hiring_actions(
    desktop_page, base_url, qa_settings, employer_target, stage5_run_id, record_stage5
) -> None:
    case = "STG5-ACT-001"
    evidence = stage5_evidence_dir(stage5_run_id, case)
    email, password = _employer_creds(qa_settings)
    login_employer(desktop_page, base_url, email, password)
    NavigationHelper(desktop_page, base_url).goto_path(
        f"/account/jobs/{employer_target.job_slug}/applications/{employer_target.application_id}"
    )
    expect(desktop_page.get_by_role("heading", name="Application actions")).to_be_visible(timeout=15_000)
    has_shortlist_btn = desktop_page.get_by_role("button", name=re.compile(r"Shortlist|Reject|Interview", re.I)).count() > 0
    evidence_rel = _shot(desktop_page, evidence, "application-actions.png")
    if has_shortlist_btn:
        record_stage5(
            case,
            feature_ids=["EMP-03"],
            route=f"/account/jobs/{employer_target.job_slug}/applications/{employer_target.application_id}",
            expected="Document employer self-service actions if present",
            actual="Unexpected action buttons found — needs product review",
            status="FAIL",
            evidence_rel=evidence_rel,
        )
        pytest.fail("Employer action buttons unexpectedly present")
    record_stage5(
        case,
        feature_ids=["EMP-03"],
        route=f"/account/jobs/{employer_target.job_slug}/applications/{employer_target.application_id}",
        expected="Employer self-service shortlist/reject/interview controls",
        actual="MISSING — read-only copy: recruitment operations handle status changes",
        status="MISSING",
        evidence_rel=evidence_rel,
    )


def test_stg5_rel_001_relogin_persistence(
    desktop_page, base_url, qa_settings, employer_target, stage5_run_id, record_stage5
) -> None:
    case = "STG5-REL-001"
    evidence = stage5_evidence_dir(stage5_run_id, case)
    email, password = _employer_creds(qa_settings)
    desktop_page.context.clear_cookies()
    login_employer(desktop_page, base_url, email, password)
    path = f"/account/jobs/{employer_target.job_slug}/applications/{employer_target.application_id}"
    NavigationHelper(desktop_page, base_url).goto_path(path)
    expect(desktop_page.get_by_text(employer_target.business_id).first).to_be_visible(timeout=15_000)
    desktop_page.context.clear_cookies()
    login_employer(desktop_page, base_url, email, password)
    NavigationHelper(desktop_page, base_url).goto_path(path)
    expect(desktop_page.get_by_text(employer_target.business_id).first).to_be_visible(timeout=15_000)
    evidence_rel = _shot(desktop_page, evidence, "after-relogin.png")
    record_stage5(
        case,
        feature_ids=["EMP-03"],
        route=path,
        expected="Same application after logout/login",
        actual=f"business_id={employer_target.business_id} visible",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg5_iso_001_employer_b(
    desktop_page, base_url, qa_settings, employer_target, stage5_run_id, record_stage5
) -> None:
    case = "STG5-ISO-001"
    evidence = stage5_evidence_dir(stage5_run_id, case)
    email = qa_settings.qa_employer_b_email or "qa-employer-b@example.test"
    password = qa_settings.qa_employer_b_password or qa_settings.qa_employer_a_password
    if not password:
        pytest.skip("Employer B password required")
    try:
        run_employer_b_isolation_steps(desktop_page, base_url, email, password, employer_target)
    except JourneyStepError as err:
        _skip_login_throttle(err)
        raise
    evidence_rel = _shot(desktop_page, evidence, "employer-b-forbidden.png")
    record_stage5(
        case,
        feature_ids=["EMP-09"],
        route="/account/hiring/applications",
        expected="Employer B denied list leak, detail URL, resume URL",
        actual="Isolation steps completed",
        status="PASS",
        evidence_rel=evidence_rel,
    )


def test_stg5_iso_002_candidate_forbidden(
    desktop_page, base_url, qa_settings, employer_target, stage5_run_id, record_stage5
) -> None:
    case = "STG5-ISO-002"
    evidence = stage5_evidence_dir(stage5_run_id, case)
    if not qa_settings.qa_candidate_email or not qa_settings.qa_candidate_password:
        pytest.skip("Candidate credentials required")
    from ournear_qa.journey.account_auth import login_account_user

    desktop_page.context.clear_cookies()
    try:
        login_account_user(
            desktop_page,
            base_url,
            qa_settings.qa_candidate_email,
            qa_settings.qa_candidate_password,
            journey_id="STG5",
            step_id="candidate-login",
        )
    except JourneyStepError as err:
        _skip_login_throttle(err)
        raise
    assert_http_forbidden(
        desktop_page,
        base_url,
        _application_show_path(employer_target.job_slug, employer_target.application_id),
        forbidden_token=employer_target.business_id,
    )
    evidence_rel = _shot(desktop_page, evidence, "candidate-403.png")
    record_stage5(
        case,
        feature_ids=["X-03"],
        route=_application_show_path(employer_target.job_slug, employer_target.application_id),
        expected="Candidate cannot open employer application workspace",
        actual="403/forbidden on direct URL",
        status="PASS",
        evidence_rel=evidence_rel,
    )
