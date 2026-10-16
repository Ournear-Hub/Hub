from __future__ import annotations

import json
from pathlib import Path
from typing import Any

from ournear_qa.coverage_map import build_execution_coverage
from ournear_qa.dashboard_html import render_dashboard_html, write_dashboard
from ournear_qa.evidence import ARTIFACTS_DIR
from ournear_qa.junit_export import write_junit_xml
from ournear_qa.registry import load_registry
from ournear_qa.manual_results import load_manual_results_for_run, merge_manual_into_report_payload
from ournear_qa.release_readiness import ReleaseReadiness, ci_exit_code_for_run, evaluate_release_readiness
from ournear_qa.run_record import NormalizedRunRecord, latest_run_record_path, load_run_record


def build_report_payload(record: NormalizedRunRecord) -> dict[str, Any]:
    registry = load_registry()
    coverage = build_execution_coverage(record, registry)
    manual = load_manual_results_for_run(record.run_id)
    readiness = evaluate_release_readiness(record, coverage, manual)
    payload = {
        "run": record.to_dict(),
        "coverage": coverage,
        "release_readiness": readiness.to_dict(),
        "ci_exit_code": ci_exit_code_for_run(record, readiness),
    }
    return merge_manual_into_report_payload(payload, manual)


def generate_report_artifacts(
    record: NormalizedRunRecord,
    *,
    reports_dir: Path | None = None,
    write_html: bool = True,
    write_json: bool = True,
    write_junit: bool = False,
) -> dict[str, Path]:
    registry = load_registry()
    coverage = build_execution_coverage(record, registry)
    manual = load_manual_results_for_run(record.run_id)
    readiness = evaluate_release_readiness(record, coverage, manual)
    payload = merge_manual_into_report_payload(
        {
            "run": record.to_dict(),
            "coverage": coverage,
            "release_readiness": readiness.to_dict(),
            "ci_exit_code": ci_exit_code_for_run(record, readiness),
        },
        manual,
    )
    base = reports_dir or (ARTIFACTS_DIR / "reports" / record.run_id)
    base.mkdir(parents=True, exist_ok=True)
    outputs: dict[str, Path] = {}
    if write_json:
        json_path = base / "report.json"
        json_path.write_text(json.dumps(payload, indent=2), encoding="utf-8")
        outputs["json"] = json_path
    if write_html:
        html_path = base / "dashboard.html"
        write_dashboard(
            html_path,
            render_dashboard_html(record, coverage, readiness, manual=manual),
        )
        outputs["html"] = html_path
    if write_junit:
        junit_path = base / "junit.xml"
        write_junit_xml(record, junit_path)
        outputs["junit"] = junit_path
    return outputs


def resolve_run_record(run_id: str | None, artifacts_root: Path = ARTIFACTS_DIR) -> NormalizedRunRecord:
    if run_id:
        path = artifacts_root / run_id / "run-record.json"
        if not path.is_file():
            raise FileNotFoundError(f"No run-record for run_id {run_id!r}")
        return load_run_record(path)
    latest = latest_run_record_path(artifacts_root)
    if latest is None:
        raise FileNotFoundError("No run records found under artifacts/")
    return load_run_record(latest)
