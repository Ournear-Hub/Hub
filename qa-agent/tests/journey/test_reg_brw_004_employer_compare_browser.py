"""REG-BRW-004 — Employer job applicant comparison candidate names (browser)."""

from __future__ import annotations

import socket
import urllib.request
from pathlib import Path

import pytest

from ournear_qa.evidence import ARTIFACTS_DIR
from ournear_qa.journey.employer_applicant_compare import JRN_ID, run_employer_applicant_compare
from ournear_qa.journey.errors import JourneyStepError

RUN_ID = "20261014T-reg-brw-004-employer-compare"
EVIDENCE = ARTIFACTS_DIR / "reg-brw-004" / RUN_ID

pytestmark = [pytest.mark.journey, pytest.mark.reg_brw_004]


def _mysql_up() -> bool:
    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as sock:
        sock.settimeout(2)
        try:
            return sock.connect_ex(("127.0.0.1", 3306)) == 0
        except OSError:
            return False


def _server_up(base_url: str) -> bool:
    try:
        urllib.request.urlopen(base_url.rstrip("/") + "/login", timeout=15)
        return True
    except Exception:
        return False


@pytest.fixture
def desktop_page(page):
    page.set_viewport_size({"width": 1366, "height": 768})
    return page


def test_reg_brw_004_employer_applicant_compare_names(desktop_page, base_url, qa_settings) -> None:
    if qa_settings.ournear_allow_run != "local":
        pytest.skip("Set OURNEAR_ALLOW_RUN=local")
    if not _mysql_up():
        pytest.skip("BLOCKED: MySQL not on 3306")
    if not _server_up(base_url):
        pytest.skip("BLOCKED: start scripts/start-local-web-server.ps1")

    employer_email = (qa_settings.qa_employer_a_email or "").strip()
    employer_password = (qa_settings.qa_employer_a_password or "").strip()
    if not employer_email or not employer_password:
        pytest.skip("QA employer A credentials required")

    EVIDENCE.mkdir(parents=True, exist_ok=True)

    try:
        report = run_employer_applicant_compare(
            desktop_page,
            base_url,
            employer_email,
            employer_password,
            evidence_dir=EVIDENCE,
            viewport_label="desktop-1366x768",
        )
    except JourneyStepError as err:
        pytest.fail(f"{JRN_ID}: {err.step_id} — {err.actual}")

    assert len(report.selected_names) >= 2
    assert report.job_slug
    assert "E4-06-compare-names-visible" in report.steps

    names_path = Path(EVIDENCE / "selected-names.txt")
    names_path.write_text("\n".join(report.selected_names), encoding="utf-8")
