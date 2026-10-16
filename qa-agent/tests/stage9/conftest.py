"""Stage 9 employer ↔ candidate — structured results."""

from __future__ import annotations

import json
from typing import Any

import pytest

from ournear_qa.evidence import ARTIFACTS_DIR

STAGE9_RESULTS: list[dict[str, Any]] = []


@pytest.fixture
def stage9_run_id(request) -> str:
    return request.config._ournear_qa_run_id  # type: ignore[attr-defined]


@pytest.fixture
def record_stage9(stage9_run_id: str):
    def _record(
        case_id: str,
        *,
        feature_ids: list[str],
        route: str,
        expected: str,
        actual: str,
        status: str,
        evidence_rel: str | None = None,
        notes: str | None = None,
    ) -> None:
        row: dict[str, Any] = {
            "case_id": case_id,
            "feature_ids": feature_ids,
            "route": route,
            "expected": expected,
            "actual": actual,
            "status": status,
            "evidence_rel": evidence_rel,
        }
        if notes:
            row["notes"] = notes
        STAGE9_RESULTS.append(row)

    return _record


def pytest_sessionfinish(session, exitstatus):
    run_id = session.config._ournear_qa_run_id  # type: ignore[attr-defined]
    suite_opt = str(session.config.getoption("--ournear-suite") or "adhoc")
    if suite_opt != "stage9" and not STAGE9_RESULTS:
        return
    out_dir = ARTIFACTS_DIR / "stage9" / run_id
    out_dir.mkdir(parents=True, exist_ok=True)
    payload = {
        "run_id": run_id,
        "suite": "stage9",
        "exitstatus": exitstatus,
        "cases": STAGE9_RESULTS,
        "counts": {
            "PASS": sum(1 for r in STAGE9_RESULTS if r["status"] == "PASS"),
            "FAIL": sum(1 for r in STAGE9_RESULTS if r["status"] == "FAIL"),
            "BLOCKED": sum(1 for r in STAGE9_RESULTS if r["status"] == "BLOCKED"),
            "NOT_TESTED": sum(1 for r in STAGE9_RESULTS if r["status"] == "NOT_TESTED"),
            "MISSING": sum(1 for r in STAGE9_RESULTS if r["status"] == "MISSING"),
        },
    }
    (out_dir / "stage9_results.json").write_text(json.dumps(payload, indent=2), encoding="utf-8")
