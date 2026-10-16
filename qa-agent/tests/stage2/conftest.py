"""Stage 2 candidate profile — evidence and structured results."""

from __future__ import annotations

import json
from pathlib import Path
from typing import Any

import pytest

from ournear_qa.evidence import ARTIFACTS_DIR

STAGE2_RESULTS: list[dict[str, Any]] = []
STAGE2_EVIDENCE_ROOT = ARTIFACTS_DIR / "stage2" / "evidence"


def stage2_evidence_dir(run_id: str, case_id: str) -> Path:
    path = STAGE2_EVIDENCE_ROOT / run_id / case_id
    path.mkdir(parents=True, exist_ok=True)
    return path


@pytest.fixture
def stage2_run_id(request) -> str:
    return request.config._ournear_qa_run_id  # type: ignore[attr-defined]


@pytest.fixture
def record_stage2(stage2_run_id: str):
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
    ) -> None:
        STAGE2_RESULTS.append(
            {
                "case_id": case_id,
                "feature_ids": feature_ids,
                "route": route,
                "expected": expected,
                "actual": actual,
                "status": status,
                "evidence_rel": evidence_rel,
                "defect_id": defect_id,
            }
        )

    return _record


def pytest_sessionfinish(session, exitstatus):
    run_id = session.config._ournear_qa_run_id  # type: ignore[attr-defined]
    out_dir = ARTIFACTS_DIR / "stage2" / run_id
    out_dir.mkdir(parents=True, exist_ok=True)
    payload = {
        "run_id": run_id,
        "suite": "stage2",
        "exitstatus": exitstatus,
        "cases": STAGE2_RESULTS,
        "counts": {
            "PASS": sum(1 for r in STAGE2_RESULTS if r["status"] == "PASS"),
            "FAIL": sum(1 for r in STAGE2_RESULTS if r["status"] == "FAIL"),
            "BLOCKED": sum(1 for r in STAGE2_RESULTS if r["status"] == "BLOCKED"),
            "NOT_TESTED": sum(1 for r in STAGE2_RESULTS if r["status"] == "NOT_TESTED"),
            "MISSING": sum(1 for r in STAGE2_RESULTS if r["status"] == "MISSING"),
        },
    }
    (out_dir / "stage2_results.json").write_text(json.dumps(payload, indent=2), encoding="utf-8")
