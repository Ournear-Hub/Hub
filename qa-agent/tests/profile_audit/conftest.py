"""Profile field audit — structured results (single-session browser matrix)."""

from __future__ import annotations

import json
from pathlib import Path
from typing import Any

import pytest

from ournear_qa.evidence import ARTIFACTS_DIR

PROFILE_AUDIT_RESULTS: list[dict[str, Any]] = []
EVIDENCE_ROOT = ARTIFACTS_DIR / "profile_audit" / "evidence"


def evidence_dir(run_id: str, case_id: str) -> Path:
    path = EVIDENCE_ROOT / run_id / case_id
    path.mkdir(parents=True, exist_ok=True)
    return path


@pytest.fixture
def profile_audit_run_id(request) -> str:
    return request.config._ournear_qa_run_id  # type: ignore[attr-defined]


@pytest.fixture
def record_profile_audit(profile_audit_run_id: str):
    def _record(
        case_id: str,
        *,
        field_id: str,
        route: str,
        expected: str,
        actual: str,
        status: str,
        evidence_rel: str | None = None,
    ) -> None:
        PROFILE_AUDIT_RESULTS.append(
            {
                "case_id": case_id,
                "field_id": field_id,
                "route": route,
                "expected": expected,
                "actual": actual,
                "status": status,
                "evidence_rel": evidence_rel,
            }
        )

    return _record


def pytest_sessionfinish(session, exitstatus):
    run_id = session.config._ournear_qa_run_id  # type: ignore[attr-defined]
    suite_opt = str(session.config.getoption("--ournear-suite") or "adhoc")
    out_dir = ARTIFACTS_DIR / "profile_audit" / run_id
    out_dir.mkdir(parents=True, exist_ok=True)
    payload = {
        "run_id": run_id,
        "suite": suite_opt,
        "exitstatus": exitstatus,
        "cases": PROFILE_AUDIT_RESULTS,
        "counts": {
            "PASS": sum(1 for r in PROFILE_AUDIT_RESULTS if r["status"] == "PASS"),
            "FAIL": sum(1 for r in PROFILE_AUDIT_RESULTS if r["status"] == "FAIL"),
            "BLOCKED": sum(1 for r in PROFILE_AUDIT_RESULTS if r["status"] == "BLOCKED"),
            "NOT_TESTED": sum(1 for r in PROFILE_AUDIT_RESULTS if r["status"] == "NOT_TESTED"),
        },
    }
    (out_dir / "profile_audit_results.json").write_text(json.dumps(payload, indent=2), encoding="utf-8")
