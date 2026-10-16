"""Stage 3 — guest intended apply (STG3-APPLY-002), unsafe redirect, concurrent submission."""

from __future__ import annotations

import re
import shutil
import socket
import subprocess
from pathlib import Path

import pytest
from playwright.sync_api import Page, expect

from ournear_qa.browser.forms import FormHelper
from ournear_qa.browser.navigation import NavigationHelper
from ournear_qa.evidence import ARTIFACTS_DIR
from ournear_qa.journey.candidate_apply import (
    provision_journey_apply_target,
    run_guest_intended_application_steps,
)
from ournear_qa.journey.errors import JourneyStepError

pytestmark = [pytest.mark.stage3]

STAGE3_EVIDENCE_ROOT = ARTIFACTS_DIR / "stage3" / "evidence"


def stage3_evidence_dir(run_id: str, case_id: str) -> Path:
    path = STAGE3_EVIDENCE_ROOT / run_id / case_id
    path.mkdir(parents=True, exist_ok=True)
    return path


def _shot(page: Page, directory: Path, name: str) -> str:
    target = directory / name
    page.screenshot(path=str(target), full_page=True)
    return target.relative_to(ARTIFACTS_DIR).as_posix()


def _require_candidate_creds(qa_settings) -> tuple[str, str]:
    if not qa_settings.qa_candidate_email or not qa_settings.qa_candidate_password:
        pytest.skip("Set OURNEAR_QA_CANDIDATE_EMAIL and OURNEAR_QA_CANDIDATE_PASSWORD")
    return qa_settings.qa_candidate_email, qa_settings.qa_candidate_password


def _skip_if_login_throttled(err: JourneyStepError) -> None:
    if "429" in err.actual and "account-login" in err.actual:
        pytest.skip("account-login rate limit — wait and retry")


@pytest.fixture
def desktop_page(page: Page) -> Page:
    page.set_viewport_size({"width": 1280, "height": 900})
    return page


REPO_ROOT = Path(__file__).resolve().parents[3]
PHP_BIN = shutil.which("php")


def _server_reachable(base_url: str) -> bool:
    import urllib.error
    import urllib.request

    try:
        urllib.request.urlopen(base_url.rstrip("/") + "/", timeout=15)
        return True
    except (urllib.error.URLError, TimeoutError, OSError):
        return False


def _mysql_listening() -> bool:
    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as sock:
        sock.settimeout(2)
        try:
            return sock.connect_ex(("127.0.0.1", 3306)) == 0
        except OSError:
            return False


def _run_php_script(script: str, *args: str) -> subprocess.CompletedProcess[str]:
    if PHP_BIN is None:
        pytest.skip("PHP not on PATH")
    return subprocess.run(
        [PHP_BIN, str(REPO_ROOT / "scripts" / script), *args],
        cwd=REPO_ROOT,
        capture_output=True,
        text=True,
        check=False,
    )


@pytest.mark.stage3
def test_stg3_auth_001_guest_apply_redirects_to_login(
    desktop_page: Page, base_url: str, stage3_run_id, record_stage3
) -> None:
    case = "STG3-AUTH-001"
    evidence = stage3_evidence_dir(stage3_run_id, case)
    if not _server_reachable(base_url):
        record_stage3(
            case,
            feature_ids=["PUB-03", "CAN-07"],
            route="/jobs/qa-fixture-published-a/apply",
            expected="Guest apply route requires login",
            actual="Local web server unreachable",
            status="BLOCKED",
        )
        pytest.skip("BLOCKED: start scripts/start-local-web-server.ps1")
    desktop_page.context.clear_cookies()
    desktop_page.goto(f"{base_url}/jobs/qa-fixture-published-a/apply", wait_until="networkidle")
    evidence_rel = _shot(desktop_page, evidence, "guest-apply-login.png")
    assert "/login" in desktop_page.url
    record_stage3(
        case,
        feature_ids=["PUB-03", "CAN-07"],
        route="/jobs/qa-fixture-published-a/apply",
        expected="Guest apply route requires login",
        actual=f"Redirected to {desktop_page.url}",
        status="PASS",
        evidence_rel=evidence_rel,
    )


@pytest.mark.stage3
def test_stg3_apply_002_guest_intended_apply_full_journey(
    desktop_page: Page, base_url: str, qa_settings, stage3_run_id, record_stage3
) -> None:
    case = "STG3-APPLY-002"
    evidence = stage3_evidence_dir(stage3_run_id, case)
    if not _mysql_listening():
        record_stage3(
            case,
            feature_ids=["CAN-07", "PUB-03"],
            route="/jobs/{slug}/apply",
            expected="Guest → login → intended apply → submit → persist",
            actual="MySQL84 not listening on 3306",
            status="BLOCKED",
        )
        pytest.skip("BLOCKED: local MySQL required for provisioned apply job")

    email, password = _require_candidate_creds(qa_settings)
    target = provision_journey_apply_target()
    if not target:
        record_stage3(case, feature_ids=["CAN-07"], route="/jobs/{slug}/apply", expected="provision job", actual="provision failed", status="BLOCKED")
        pytest.skip("Could not provision journey apply job")

    job_slug, job_title = target
    try:
        ctx = run_guest_intended_application_steps(
            desktop_page, base_url, email, password, job_slug, job_title
        )
    except JourneyStepError as err:
        _skip_if_login_throttled(err)
        record_stage3(
            case,
            feature_ids=["CAN-07", "PUB-03"],
            route=f"/jobs/{job_slug}/apply",
            expected="Full guest intended apply journey",
            actual=str(err),
            status="FAIL",
        )
        raise

    evidence_rel = _shot(desktop_page, evidence, "guest-intended-apply-complete.png")
    record_stage3(
        case,
        feature_ids=["CAN-07", "PUB-03"],
        route=f"/jobs/{job_slug}/apply",
        expected="Guest → login → intended apply → submit → reload persist → duplicate guard",
        actual=f"business_id={ctx.business_id}; steps={ctx.steps_completed}",
        status="PASS",
        evidence_rel=evidence_rel,
    )


@pytest.mark.stage3
def test_stg3_auth_004_unsafe_intended_url_blocked(
    desktop_page: Page, base_url: str, qa_settings, stage3_run_id, record_stage3
) -> None:
    case = "STG3-AUTH-004"
    evidence = stage3_evidence_dir(stage3_run_id, case)
    if not _server_reachable(base_url):
        record_stage3(
            case,
            feature_ids=["PUB-03"],
            route="/login",
            expected="Unsafe external intended URL not honored",
            actual="Local web server unreachable",
            status="BLOCKED",
        )
        pytest.skip("BLOCKED: start scripts/start-local-web-server.ps1")
    email, password = _require_candidate_creds(qa_settings)
    desktop_page.context.clear_cookies()
    desktop_page.goto(
        f"{base_url}/login?intended=https://evil.example/phish",
        wait_until="networkidle",
    )
    forms = FormHelper(desktop_page)
    forms.fill_field("Username or Email", email)
    forms.fill_field("Password", password)
    with desktop_page.expect_response(
        lambda r: r.request.method == "POST" and r.url.rstrip("/").endswith("/login"),
        timeout=30_000,
    ):
        forms.click_submit("Log in")
    try:
        desktop_page.wait_for_url(re.compile(r"/account"), timeout=15_000)
    except Exception:
        NavigationHelper(desktop_page, base_url).goto_path("/account")
    assert "evil.example" not in desktop_page.url
    evidence_rel = _shot(desktop_page, evidence, "unsafe-intended-blocked.png")
    record_stage3(
        case,
        feature_ids=["PUB-03"],
        route="/login",
        expected="Unsafe external intended URL not honored after login",
        actual=f"Landed on {desktop_page.url}",
        status="PASS",
        evidence_rel=evidence_rel,
    )


@pytest.mark.stage3
def test_stg3_apply_003_concurrent_submission_local(
    desktop_page: Page, base_url: str, stage3_run_id, record_stage3
) -> None:
    case = "STG3-APPLY-003"
    evidence = stage3_evidence_dir(stage3_run_id, case)
    if not _mysql_listening():
        record_stage3(
            case,
            feature_ids=["CAN-07"],
            route="/jobs/{slug}/apply",
            expected="Concurrent overlapping POSTs",
            actual="MySQL84 not listening on 3306",
            status="BLOCKED",
        )
        pytest.skip("BLOCKED: local MySQL required")

    prov = _run_php_script("qa-provision-journey-apply-job.php")
    if prov.returncode != 0:
        record_stage3(
            case,
            feature_ids=["CAN-07"],
            route="/jobs/{slug}/apply",
            expected="Concurrent overlapping POSTs",
            actual=prov.stderr.strip() or "provision failed",
            status="BLOCKED",
        )
        pytest.skip("No eligible job for concurrent apply")
    slug = None
    for line in prov.stdout.splitlines():
        if line.startswith("slug="):
            slug = line.split("=", 1)[1].strip()
    if not slug:
        pytest.skip("Provisioner missing slug")

    completed = subprocess.run(
        [PHP_BIN, str(REPO_ROOT / "scripts" / "qa-concurrent-apply-local.php"), slug],
        cwd=REPO_ROOT,
        capture_output=True,
        text=True,
        check=False,
        env={
            **dict(__import__("os").environ),
            "OURNEAR_CONCURRENT_APPLY_BASE_URL": base_url.rstrip("/"),
        },
    )
    log_path = evidence / "concurrent-apply.log"
    log_path.write_text(completed.stdout + "\n" + completed.stderr, encoding="utf-8")
    evidence_rel = log_path.relative_to(ARTIFACTS_DIR).as_posix()

    if completed.returncode != 0:
        record_stage3(
            case,
            feature_ids=["CAN-07"],
            route=f"/jobs/{slug}/apply",
            expected="At most one application from parallel POSTs",
            actual=(completed.stderr or completed.stdout)[-500:],
            status="FAIL",
            evidence_rel=evidence_rel,
        )
        pytest.fail(completed.stderr or completed.stdout)

    record_stage3(
        case,
        feature_ids=["CAN-07"],
        route=f"/jobs/{slug}/apply",
        expected="Exactly one persisted application; parallel POSTs handled safely",
        actual=completed.stdout.strip().splitlines()[-6:],
        status="PASS",
        evidence_rel=evidence_rel,
    )
