"""Stage 11 lifecycle — structured results."""

from __future__ import annotations

import json
from typing import Any

import pytest

from ournear_qa.evidence import ARTIFACTS_DIR

STAGE11_RESULTS: list[dict[str, Any]] = []


@pytest.fixture
def stage11_run_id(request) -> str:
    return request.config._ournear_qa_run_id  # type: ignore[attr-defined]


@pytest.fixture
def record_stage11(stage11_run_id: str):
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
        automation: str = "browser",
    ) -> None:
        row: dict[str, Any] = {
            "case_id": case_id,
            "feature_ids": feature_ids,
            "route": route,
            "expected": expected,
            "actual": actual,
            "status": status,
            "automation": automation,
            "evidence_rel": evidence_rel,
        }
        if notes:
            row["notes"] = notes
        STAGE11_RESULTS.append(row)

    return _record


def pytest_sessionfinish(session, exitstatus):
    run_id = session.config._ournear_qa_run_id  # type: ignore[attr-defined]
    suite_opt = str(session.config.getoption("--ournear-suite") or "adhoc")
    if suite_opt != "stage11" and not STAGE11_RESULTS:
        return
    out_dir = ARTIFACTS_DIR / "stage11" / run_id
    out_dir.mkdir(parents=True, exist_ok=True)
    payload = {
        "run_id": run_id,
        "suite": "stage11",
        "exitstatus": exitstatus,
        "cases": STAGE11_RESULTS,
        "counts": {
            "PASS": sum(1 for r in STAGE11_RESULTS if r["status"] == "PASS"),
            "FAIL": sum(1 for r in STAGE11_RESULTS if r["status"] == "FAIL"),
            "BLOCKED": sum(1 for r in STAGE11_RESULTS if r["status"] == "BLOCKED"),
            "NOT_TESTED": sum(1 for r in STAGE11_RESULTS if r["status"] == "NOT_TESTED"),
            "MISSING": sum(1 for r in STAGE11_RESULTS if r["status"] == "MISSING"),
        },
    }
    (out_dir / "stage11_results.json").write_text(json.dumps(payload, indent=2), encoding="utf-8")
