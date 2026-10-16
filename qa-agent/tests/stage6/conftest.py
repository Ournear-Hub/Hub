"""Stage 6 cross-role workflow — structured results."""

from __future__ import annotations

import json
from pathlib import Path
from typing import Any

import pytest

from ournear_qa.evidence import ARTIFACTS_DIR

STAGE6_RESULTS: list[dict[str, Any]] = []
STAGE6_EVIDENCE_ROOT = ARTIFACTS_DIR / "stage6" / "evidence"


def stage6_evidence_dir(run_id: str, case_id: str) -> Path:
    path = STAGE6_EVIDENCE_ROOT / run_id / case_id
    path.mkdir(parents=True, exist_ok=True)
    return path


@pytest.fixture
def stage6_run_id(request) -> str:
    return request.config._ournear_qa_run_id  # type: ignore[attr-defined]


@pytest.fixture
def record_stage6(stage6_run_id: str):
    def _record(
        case_id: str,
        *,
        feature_ids: list[str],
        route: str,
        expected: str,
        actual: str,
        status: str,
        evidence_rel: str | None = None,
        defect_id: str | None = None,
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
            "defect_id": defect_id,
        }
        if notes:
            row["notes"] = notes
        STAGE6_RESULTS.append(row)

    return _record


def pytest_sessionfinish(session, exitstatus):
    run_id = session.config._ournear_qa_run_id  # type: ignore[attr-defined]
    suite_opt = str(session.config.getoption("--ournear-suite") or "adhoc")
    if suite_opt != "stage6" and not STAGE6_RESULTS:
        return
    out_dir = ARTIFACTS_DIR / "stage6" / run_id
    out_dir.mkdir(parents=True, exist_ok=True)
    payload = {
        "run_id": run_id,
        "suite": "stage6",
        "exitstatus": exitstatus,
        "cases": STAGE6_RESULTS,
        "counts": {
            "PASS": sum(1 for r in STAGE6_RESULTS if r["status"] == "PASS"),
            "FAIL": sum(1 for r in STAGE6_RESULTS if r["status"] == "FAIL"),
            "BLOCKED": sum(1 for r in STAGE6_RESULTS if r["status"] == "BLOCKED"),
            "NOT_TESTED": sum(1 for r in STAGE6_RESULTS if r["status"] == "NOT_TESTED"),
            "MISSING": sum(1 for r in STAGE6_RESULTS if r["status"] == "MISSING"),
        },
    }
    (out_dir / "stage6_results.json").write_text(json.dumps(payload, indent=2), encoding="utf-8")
