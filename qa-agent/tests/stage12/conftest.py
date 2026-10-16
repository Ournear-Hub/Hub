"""Stage 12 release gate — structured results."""

from __future__ import annotations

import json
from typing import Any

import pytest

from ournear_qa.evidence import ARTIFACTS_DIR

STAGE12_RESULTS: list[dict[str, Any]] = []


@pytest.fixture
def stage12_run_id(request) -> str:
    return request.config._ournear_qa_run_id  # type: ignore[attr-defined]


@pytest.fixture
def record_stage12(stage12_run_id: str):
    def _record(case_id: str, *, status: str, actual: str, expected: str = "") -> None:
        STAGE12_RESULTS.append(
            {"case_id": case_id, "status": status, "actual": actual, "expected": expected}
        )

    return _record


def pytest_sessionfinish(session, exitstatus):
    run_id = session.config._ournear_qa_run_id  # type: ignore[attr-defined]
    suite_opt = str(session.config.getoption("--ournear-suite") or "adhoc")
    if suite_opt != "stage12" and not STAGE12_RESULTS:
        return
    out_dir = ARTIFACTS_DIR / "stage12" / run_id
    out_dir.mkdir(parents=True, exist_ok=True)
    payload = {
        "run_id": run_id,
        "suite": "stage12",
        "exitstatus": exitstatus,
        "cases": STAGE12_RESULTS,
    }
    (out_dir / "stage12_results.json").write_text(json.dumps(payload, indent=2), encoding="utf-8")
