from __future__ import annotations

import json
from pathlib import Path
from unittest.mock import MagicMock

import pytest

from ournear_qa.browser.redact import redact_sensitive_strings
from ournear_qa.evidence import failure_case_dir, sanitize_mapping, sanitize_value
from ournear_qa.failure_bundle import ArtifactRef, FailureManifest, write_failure_bundle
from ournear_qa.trace_lifecycle import TraceStopResult, start_context_trace, stop_context_trace


def test_redact_sensitive_strings_synthetic_values() -> None:
    raw = "login failed password=SuperSecret1! token=abc123 Bearer deadbeef Authorization: Basic x"
    redacted = redact_sensitive_strings(raw)
    assert "SuperSecret1" not in redacted
    assert "abc123" not in redacted
    assert "deadbeef" not in redacted
    assert "[REDACTED]" in redacted


def test_sanitize_value_recurses_diagnostics() -> None:
    payload = {
        "console_errors": [{"text": "password=HiddenValue", "type": "error"}],
        "nested_token": "should redact key",
    }
    out = sanitize_mapping(payload)
    assert out["nested_token"] == "[REDACTED]"
    assert "HiddenValue" not in out["console_errors"][0]["text"]


def test_manifest_artifact_refs_honest_when_missing() -> None:
    manifest = FailureManifest(
        run_id="run",
        test_nodeid="tests::x",
        timestamp="t",
        outcome="failed",
        browser="chromium",
        base_url="http://127.0.0.1:8000",
        artifacts=[
            ArtifactRef(name="trace", relative_path=None, present=False, note="not captured"),
        ],
    )
    tmp = Path(__file__).parent / "_manifest_tmp"
    tmp.mkdir(exist_ok=True)
    path = manifest.write(tmp)
    data = json.loads(path.read_text(encoding="utf-8"))
    assert data["artifacts"][0]["present"] is False
    assert data["artifacts"][0]["note"] == "not captured"
    path.unlink()
    tmp.rmdir()


def test_write_failure_bundle_creates_linked_artifacts(page, qa_run_id, monkeypatch) -> None:
    nodeid = "tests/test_failure_bundle.py::test_write_failure_bundle_creates_linked_artifacts"
    case_dir = failure_case_dir(qa_run_id, nodeid)

    page.set_content("<title>Bundle Test</title><h1>fail soon</h1>")
    trace_path = case_dir / "trace.zip"
    trace_result = stop_context_trace(page.context, retain_path=trace_path)

    write_failure_bundle(
        run_id=qa_run_id,
        test_nodeid=nodeid,
        timestamp="2026-10-09T00:00:00Z",
        base_url="http://127.0.0.1:8000",
        browser_name="chromium",
        page=page,
        browser_session=None,
        report_longrepr="assert False",
        journey_id="JRN-TEST",
        step_id="STEP-1",
        expected="pass",
        actual="fail",
        trace_result=trace_result,
    )

    assert (case_dir / "failure.json").is_file()
    assert (case_dir / "manifest.json").is_file()
    manifest = json.loads((case_dir / "manifest.json").read_text(encoding="utf-8"))
    failure = json.loads((case_dir / "failure.json").read_text(encoding="utf-8"))
    assert failure["extra"]["journey_id"] == "JRN-TEST"
    assert failure["extra"]["step_id"] == "STEP-1"
    assert "SuperSecret" not in json.dumps(failure)
    by_name = {a["name"]: a for a in manifest["artifacts"]}
    assert by_name["failure_json"]["present"] is True
    assert by_name["screenshot"]["present"] is True
    if trace_result.retained:
        assert by_name["trace"]["present"] is True
        assert (case_dir / "trace.zip").is_file()


def test_stop_context_trace_idempotent() -> None:
    fake_tracing = MagicMock()

    class FakeContext:
        tracing = fake_tracing

    context = FakeContext()
    first = stop_context_trace(context, retain_path=None)
    second = stop_context_trace(context, retain_path=Path("ignored.zip"))
    assert fake_tracing.stop.call_count == 1
    assert first.skipped_already_stopped is False
    assert second.skipped_already_stopped is True


def test_passing_browser_test_leaves_no_failure_artifacts(page, qa_run_id, request) -> None:
    page.set_content("<p>ok</p>")
    nodeid = request.node.nodeid
    case_dir = failure_case_dir(qa_run_id, nodeid)
    assert not case_dir.exists() or not (case_dir / "trace.zip").exists()


def test_write_failure_bundle_redacts_console_diagnostics(page, qa_run_id) -> None:
    from ournear_qa.browser import BrowserSession

    session = BrowserSession.attach(page)
    page.set_content("<script>console.error('password=LeakedValue');</script>")
    nodeid = "tests/test_failure_bundle.py::test_write_failure_bundle_redacts_console_diagnostics"
    write_failure_bundle(
        run_id=qa_run_id,
        test_nodeid=nodeid,
        timestamp="t",
        base_url="http://127.0.0.1:8000",
        browser_name="chromium",
        page=page,
        browser_session=session,
        report_longrepr="x",
        journey_id=None,
        step_id=None,
        expected="",
        actual="",
        trace_result=None,
    )
    case_dir = failure_case_dir(qa_run_id, nodeid)
    failure = json.loads((case_dir / "failure.json").read_text(encoding="utf-8"))
    blob = json.dumps(failure)
    assert "LeakedValue" not in blob
