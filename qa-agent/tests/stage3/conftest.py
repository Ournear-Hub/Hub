"""Stage 3 application workflow — structured results."""

from __future__ import annotations

import json
from pathlib import Path
from typing import Any

import pytest

from ournear_qa.evidence import ARTIFACTS_DIR

STAGE3_RESULTS: list[dict[str, Any]] = []
STAGE3_GAP_RESULTS: list[dict[str, Any]] = []
STAGE3_EVIDENCE_ROOT = ARTIFACTS_DIR / "stage3" / "evidence"


def stage3_evidence_dir(run_id: str, case_id: str) -> Path:
    path = STAGE3_EVIDENCE_ROOT / run_id / case_id
    path.mkdir(parents=True, exist_ok=True)
    return path


@pytest.fixture
def stage3_run_id(request) -> str:
    return request.config._ournear_qa_run_id  # type: ignore[attr-defined]


@pytest.fixture
def record_stage3(stage3_run_id: str):
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
        STAGE3_RESULTS.append(
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


@pytest.fixture
def record_stage3_gap(stage3_run_id: str):
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
        STAGE3_GAP_RESULTS.append(
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


def _counts_for(cases: list[dict[str, Any]]) -> dict[str, int]:
    return {
        "PASS": sum(1 for r in cases if r["status"] == "PASS"),
        "FAIL": sum(1 for r in cases if r["status"] == "FAIL"),
        "BLOCKED": sum(1 for r in cases if r["status"] == "BLOCKED"),
        "NOT_TESTED": sum(1 for r in cases if r["status"] == "NOT_TESTED"),
        "MISSING": sum(1 for r in cases if r["status"] == "MISSING"),
    }


def pytest_sessionfinish(session, exitstatus):
    run_id = session.config._ournear_qa_run_id  # type: ignore[attr-defined]
    suite_opt = str(session.config.getoption("--ournear-suite") or "adhoc")
    out_dir = ARTIFACTS_DIR / "stage3" / run_id
    out_dir.mkdir(parents=True, exist_ok=True)

    if suite_opt == "stage3_gap_closure" or STAGE3_GAP_RESULTS:
        gap_payload = {
            "run_id": run_id,
            "suite": "stage3_gap_closure",
            "canonical_stage3_run": "20261010T073839Z-d2b6b491",
            "exitstatus": exitstatus,
            "cases": STAGE3_GAP_RESULTS,
            "counts": _counts_for(STAGE3_GAP_RESULTS),
        }
        (out_dir / "stage3_gap_results.json").write_text(
            json.dumps(gap_payload, indent=2), encoding="utf-8"
        )

    if suite_opt != "stage3_gap_closure" and STAGE3_RESULTS:
        payload = {
            "run_id": run_id,
            "suite": "stage3",
            "exitstatus": exitstatus,
            "cases": STAGE3_RESULTS,
            "counts": _counts_for(STAGE3_RESULTS),
        }
        (out_dir / "stage3_results.json").write_text(json.dumps(payload, indent=2), encoding="utf-8")
