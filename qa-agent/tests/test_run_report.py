from __future__ import annotations

import json
from pathlib import Path

from ournear_qa.dashboard_html import render_dashboard_html
from ournear_qa.junit_export import write_junit_xml
from ournear_qa.release_readiness import evaluate_release_readiness
from ournear_qa.report import RunSummary
from ournear_qa.run_record import (
    NormalizedRunRecord,
    RunCaseOutcome,
    CaseResultRecord,
    classify_skip_as_blocked,
)
from ournear_qa.run_report import build_report_payload, generate_report_artifacts
from ournear_qa.coverage_map import build_execution_coverage
from ournear_qa.registry import load_registry


def _sample_record() -> NormalizedRunRecord:
    record = NormalizedRunRecord(
        run_id="20260101T000000Z-deadbeef",
        started_at="2026-01-01T00:00:00Z",
        finished_at="2026-01-01T00:00:05Z",
        suite="smoke",
        regression_scope="targeted",
        tests=[
            CaseResultRecord(
                nodeid="tests/smoke/test_public_home.py::test_home_loads",
                outcome=RunCaseOutcome.PASSED,
                journey_id="JRN-SMOKE-HOME-01",
                feature_ids=["PUB-01"],
            ),
            CaseResultRecord(
                nodeid="tests/journey/test_cross_role_e2e.py::test_x",
                outcome=RunCaseOutcome.BLOCKED,
                skip_reason="blocked by account-login rate limit",
                journey_id="JRN-CROSS-ROLE-01",
                feature_ids=["PUB-03"],
            ),
        ],
        pytest_exitstatus=0,
        complete=True,
    )
    record.recompute_counts()
    return record


def test_classify_blocked_skip() -> None:
    assert classify_skip_as_blocked("HTTP 429 account-login rate limit")
    assert not classify_skip_as_blocked("optional dependency missing")


def test_backward_compatible_run_summary_from_record() -> None:
    record = _sample_record()
    payload = record.to_dict()
    payload["feature_registry_count"] = 53
    summary = RunSummary.from_run_record_dict({**payload, "counts": record.counts})
    assert summary.passed == 1
    assert summary.skipped == 1


def test_coverage_uses_execution_not_planned() -> None:
    registry = load_registry()
    record = _sample_record()
    coverage = build_execution_coverage(record, registry)
    assert "PUB-01" in coverage["features_passed"]
    assert "PUB-03" in coverage["features_blocked_or_skipped"]
    assert "PUB-02" in coverage["features_not_executed"]


def test_release_readiness_targeted_not_green() -> None:
    record = _sample_record()
    coverage = build_execution_coverage(record, load_registry())
    readiness = evaluate_release_readiness(record, coverage)
    assert readiness.verdict in ("insufficient_evidence", "not_release_ready")
    assert readiness.targeted_only is True


def test_dashboard_escapes_html_injection() -> None:
    record = _sample_record()
    record.tests[0].nodeid = 'tests/x.py::test_<script>alert("x")</script>'
    html_out = render_dashboard_html(
        record,
        {"journeys": {}, "features_not_executed": []},
        evaluate_release_readiness(record, build_execution_coverage(record, load_registry())),
    )
    assert "<script>" not in html_out
    assert "&lt;script&gt;" in html_out


def test_generate_report_artifacts_no_execution(tmp_path: Path, monkeypatch) -> None:
    from ournear_qa import run_report as run_report_mod

    monkeypatch.setattr(run_report_mod, "ARTIFACTS_DIR", tmp_path)
    record = _sample_record()
    outputs = generate_report_artifacts(record, reports_dir=tmp_path / "reports" / record.run_id)
    assert outputs["json"].is_file()
    payload = json.loads(outputs["json"].read_text(encoding="utf-8"))
    assert payload["run"]["run_id"] == record.run_id
    assert "release_readiness" in payload


def test_junit_xml_blocked_as_skipped(tmp_path: Path) -> None:
    record = _sample_record()
    path = tmp_path / "junit.xml"
    write_junit_xml(record, path)
    text = path.read_text(encoding="utf-8")
    assert "testsuite" in text
    assert "blocked" in text.lower() or "skipped" in text.lower()


def test_build_report_payload_shape() -> None:
    payload = build_report_payload(_sample_record())
    assert "ci_exit_code" in payload
    assert "coverage" in payload
