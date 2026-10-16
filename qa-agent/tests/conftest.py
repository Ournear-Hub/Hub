from __future__ import annotations

from datetime import datetime, timezone

import pytest

from ournear_qa.evidence import new_run_id, run_artifact_dir
from ournear_qa.failure_bundle import write_failure_bundle
from ournear_qa.settings import load_settings
from ournear_qa.trace_lifecycle import start_context_trace, stop_context_trace


def _format_report_timestamp(start) -> str:
    if not start:
        return ""
    if isinstance(start, (int, float)):
        return datetime.fromtimestamp(start, tz=timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")
    return start.strftime("%Y-%m-%dT%H:%M:%SZ")


@pytest.fixture(scope="session")
def qa_run_id() -> str:
    return new_run_id()


@pytest.fixture(scope="session")
def qa_settings():
    return load_settings()


@pytest.fixture(scope="session")
def base_url(qa_settings):
    return str(qa_settings.ournear_base_url).rstrip("/")


@pytest.fixture
def browser_session(page):
    from ournear_qa.browser import BrowserSession

    return BrowserSession.attach(page)


@pytest.fixture(autouse=True)
def _ournear_playwright_trace(request, context):
    if "page" not in request.fixturenames:
        yield
        return
    start_context_trace(context)
    yield
    rep = getattr(request.node, "rep_call", None)
    if rep is not None and rep.failed:
        return
    stop_context_trace(context, retain_path=None)


@pytest.hookimpl(hookwrapper=True, tryfirst=True)
def pytest_runtest_makereport(item, call):
    outcome = yield
    report = outcome.get_result()
    setattr(item, f"rep_{call.when}", report)

    if report.when == "call":
        bucket = item.config._ournear_qa_results  # type: ignore[attr-defined]
        if report.passed:
            bucket["passed"] += 1
        elif report.failed:
            bucket["failed"] += 1
        elif report.skipped:
            bucket["skipped"] += 1
        rows = item.config._ournear_qa_test_rows  # type: ignore[attr-defined]
        rows.append(
            {
                "nodeid": item.nodeid,
                "passed": report.passed,
                "failed": report.failed,
                "skipped": report.skipped,
                "longrepr": str(report.longrepr) if report.skipped else "",
                "duration_ms": getattr(report, "duration", 0) * 1000 if report.duration else None,
            }
        )

    if report.when != "call" or not report.failed:
        return

    page = item.funcargs.get("page")
    run_id = item.config._ournear_qa_run_id  # type: ignore[attr-defined]
    base = item.funcargs.get("base_url", "")
    browser_name = str(item.funcargs.get("browser_name", "unknown"))

    trace_result = None
    if page is not None:
        from ournear_qa.evidence import failure_case_dir

        trace_path = failure_case_dir(run_id, item.nodeid) / "trace.zip"
        trace_result = stop_context_trace(page.context, retain_path=trace_path)

    from ournear_qa.journey.errors import JourneyStepError

    expected = ""
    actual = str(report.longrepr)
    journey_id = None
    step_id = None
    if call.excinfo and call.excinfo.errisinstance(JourneyStepError):
        journey_err = call.excinfo.value
        expected = journey_err.expected
        actual = journey_err.actual
        journey_id = getattr(journey_err, "journey_id", None)
        step_id = journey_err.step_id

    write_failure_bundle(
        run_id=run_id,
        test_nodeid=item.nodeid,
        timestamp=_format_report_timestamp(report.start),
        base_url=base,
        browser_name=browser_name,
        page=page,
        browser_session=item.funcargs.get("browser_session"),
        report_longrepr=str(report.longrepr),
        journey_id=journey_id,
        step_id=step_id,
        expected=expected,
        actual=actual,
        trace_result=trace_result,
    )


def pytest_addoption(parser):
    parser.addoption("--ournear-suite", action="store", default="adhoc", help="QA suite label")


def pytest_configure(config):
    config._ournear_qa_run_id = new_run_id()  # type: ignore[attr-defined]
    config._ournear_qa_results = {"passed": 0, "failed": 0, "skipped": 0, "errors": 0}  # type: ignore[attr-defined]
    config._ournear_qa_test_rows = []  # type: ignore[attr-defined]
    from ournear_qa.run_record import utc_now_iso

    config._ournear_qa_started_at = utc_now_iso()  # type: ignore[attr-defined]


def pytest_sessionfinish(session, exitstatus):
    from ournear_qa.coverage_map import features_for_nodeid
    from ournear_qa.registry import load_registry
    from ournear_qa.report import RunSummary, registry_feature_count
    from ournear_qa.run_record import (
        NormalizedRunRecord,
        RunCaseOutcome,
        CaseResultRecord,
        classify_skip_as_blocked,
        failure_artifact_rel,
        run_record_path,
        suite_to_regression_scope,
        utc_now_iso,
    )

    config = session.config
    bucket = config._ournear_qa_results  # type: ignore[attr-defined]
    run_id = config._ournear_qa_run_id  # type: ignore[attr-defined]
    started = getattr(config, "_ournear_qa_started_at", utc_now_iso())
    finished = utc_now_iso()
    suite = str(config.getoption("--ournear-suite") or "adhoc")
    registry = load_registry()
    settings = None
    try:
        settings = load_settings()
    except Exception:  # noqa: BLE001 — best-effort env capture
        settings = None

    tests: list[CaseResultRecord] = []
    for row in getattr(config, "_ournear_qa_test_rows", []):
        nodeid = row["nodeid"]
        if row.get("failed"):
            outcome = RunCaseOutcome.FAILED
        elif row.get("skipped"):
            reason = row.get("longrepr", "")
            outcome = (
                RunCaseOutcome.BLOCKED if classify_skip_as_blocked(reason) else RunCaseOutcome.SKIPPED
            )
        elif row.get("passed"):
            outcome = RunCaseOutcome.PASSED
        else:
            outcome = RunCaseOutcome.UNKNOWN
        jid, fids = features_for_nodeid(nodeid, registry)
        failure_rel = None
        if outcome == RunCaseOutcome.FAILED:
            failure_rel = failure_artifact_rel(run_id, nodeid)
        tests.append(
            CaseResultRecord(
                nodeid=nodeid,
                outcome=outcome,
                duration_ms=row.get("duration_ms"),
                skip_reason=row.get("longrepr") if outcome in (RunCaseOutcome.SKIPPED, RunCaseOutcome.BLOCKED) else None,
                journey_id=jid,
                feature_ids=fids,
                failure_artifact_rel=failure_rel,
            )
        )

    record = NormalizedRunRecord(
        run_id=run_id,
        started_at=started,
        finished_at=finished,
        suite=suite,
        regression_scope=suite_to_regression_scope(suite),
        environment={
            "allow_run": settings.ournear_allow_run if settings else "",
            "base_url": str(settings.ournear_base_url) if settings else "",
        },
        tests=tests,
        pytest_exitstatus=int(exitstatus),
        complete=True,
        notes=[f"pytest_exitstatus={exitstatus}"],
    )
    record.recompute_counts()
    record.write(run_record_path(run_id))

    summary = RunSummary(
        run_id=run_id,
        passed=bucket["passed"],
        failed=bucket["failed"],
        skipped=bucket["skipped"],
        errors=0,
        feature_registry_count=registry_feature_count(),
    )
    summary.notes.append(f"pytest_exitstatus={exitstatus}")
    summary.notes.append(f"suite={suite}")
    summary.notes.append(f"run_record={run_id}/run-record.json")
    out = run_artifact_dir("session") / "last-pytest-summary.json"
    payload = summary.to_dict()
    payload["blocked"] = record.counts.get("blocked", 0)
    payload["suite"] = suite
    out.parent.mkdir(parents=True, exist_ok=True)
    out.write_text(__import__("json").dumps(payload, indent=2), encoding="utf-8")
