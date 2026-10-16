from __future__ import annotations

import json
from pathlib import Path

import pytest

from ournear_qa.manual_catalog import build_manual_catalog
from ournear_qa.manual_export import export_manual_json, write_manual_export
from ournear_qa.manual_results import (
    ManualResultsValidationError,
    load_manual_results_for_run,
    save_manual_results,
    validate_manual_results_payload,
)
from ournear_qa.registry import load_registry
from ournear_qa.release_readiness import evaluate_release_readiness
from ournear_qa.run_record import NormalizedRunRecord, RunCaseOutcome, CaseResultRecord
from ournear_qa.run_report import build_report_payload
from ournear_qa.coverage_map import build_execution_coverage


def test_manual_catalog_includes_journeys_and_companion_cases() -> None:
    catalog = build_manual_catalog(load_registry())
    assert "JRN-SMOKE-LOGIN-01" in catalog
    assert "MAN-CAN-01-DASHBOARD-GUIDANCE" in catalog
    assert catalog["MAN-CAN-01-DASHBOARD-GUIDANCE"].feature_ids == ["CAN-01", "CAN-02"]


def test_export_manual_json_by_feature(tmp_path: Path) -> None:
    out = tmp_path / "can01.json"
    meta = write_manual_export(out, "json", feature_id="CAN-01")
    assert meta["case_count"] >= 2
    payload = json.loads(out.read_text(encoding="utf-8"))
    ids = {row["test_id"] for row in payload["cases"]}
    assert "MAN-CAN-01-DASHBOARD-GUIDANCE" in ids


def test_validate_rejects_unknown_test_id() -> None:
    with pytest.raises(ManualResultsValidationError, match="Unknown test_id"):
        validate_manual_results_payload(
            {
                "manual_run_id": "demo-1",
                "results": [{"test_id": "NOT-A-REAL-TEST", "status": "PASS"}],
            }
        )


def test_validate_rejects_bad_status() -> None:
    with pytest.raises(ManualResultsValidationError, match="Invalid status"):
        validate_manual_results_payload(
            {
                "manual_run_id": "demo-2",
                "results": [{"test_id": "JRN-SMOKE-LOGIN-01", "status": "MAYBE"}],
            }
        )


def test_validate_rejects_password_in_notes() -> None:
    doc = validate_manual_results_payload(
        {
            "manual_run_id": "demo-3",
            "tester": "qa",
            "results": [
                {
                    "test_id": "JRN-SMOKE-LOGIN-01",
                    "status": "PASS",
                    "notes": "password=Secret1!",
                }
            ],
        }
    )
    assert doc.results[0].notes is not None
    assert "[REDACTED]" in doc.results[0].notes


def test_manual_results_attach_to_run_and_report(tmp_path: Path, monkeypatch: pytest.MonkeyPatch) -> None:
    from ournear_qa import evidence as evidence_mod

    monkeypatch.setattr(evidence_mod, "ARTIFACTS_DIR", tmp_path)

    run_id = "20260101T120000Z-demo"
    record = NormalizedRunRecord(
        run_id=run_id,
        suite="smoke",
        regression_scope="targeted",
        tests=[
            CaseResultRecord(
                nodeid="tests/smoke/test_account_login.py::test_candidate_login_reaches_account",
                outcome=RunCaseOutcome.PASSED,
                journey_id="JRN-SMOKE-LOGIN-01",
                feature_ids=["ACC-AUTH-01", "CAN-01"],
            )
        ],
        complete=True,
    )
    record.recompute_counts()
    (tmp_path / run_id).mkdir(parents=True)
    (tmp_path / run_id / "run-record.json").write_text(
        json.dumps(record.to_dict()),
        encoding="utf-8",
    )

    doc = validate_manual_results_payload(
        {
            "manual_run_id": "manual-demo-1",
            "automated_run_id": run_id,
            "environment": {"base_url": "http://127.0.0.1:8000"},
            "results": [
                {"test_id": "JRN-SMOKE-LOGIN-01", "status": "PASS", "evidence_refs": ["screenshots/login.png"]},
                {"test_id": "MAN-CAN-01-DASHBOARD-GUIDANCE", "status": "NOT_RUN"},
            ],
        }
    )
    save_manual_results(doc, attach_run_id=run_id)
    loaded = load_manual_results_for_run(run_id)
    assert loaded is not None
    assert loaded.summary_counts()["PASS"] == 1

    payload = build_report_payload(record)
    assert "manual_results" in payload
    assert payload["manual_results"]["evidence_kind"] == "human_manual"
    readiness = evaluate_release_readiness(
        record,
        build_execution_coverage(record, load_registry()),
        loaded,
    )
    assert readiness.verdict == "insufficient_evidence"
    assert any("does not replace Playwright" in r for r in readiness.reasons)


def test_export_markdown_contains_result_table() -> None:
    catalog = build_manual_catalog(load_registry())
    case = catalog["JRN-SMOKE-LOGIN-01"]
    from ournear_qa.manual_export import export_manual_markdown

    md = export_manual_markdown([case])
    assert "| Result | Evidence ref | Notes |" in md
    assert "JRN-SMOKE-LOGIN-01" in md


def test_ci_exit_unchanged_when_manual_pass_only() -> None:
    from ournear_qa.release_readiness import ci_exit_code_for_run

    record = NormalizedRunRecord(run_id="x", suite="smoke", complete=True)
    record.recompute_counts()
    manual = validate_manual_results_payload(
        {
            "manual_run_id": "m1",
            "results": [{"test_id": "JRN-SMOKE-LOGIN-01", "status": "PASS"}],
        }
    )
    readiness = evaluate_release_readiness(record, {"journeys": {}}, manual)
    assert ci_exit_code_for_run(record, readiness) == 4
