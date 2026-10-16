from __future__ import annotations

import json
from dataclasses import dataclass, field
from pathlib import Path
from typing import Any

from playwright.sync_api import Page

from ournear_qa.browser.redact import redact_sensitive_strings, sanitize_url
from ournear_qa.evidence import FailureEvidence, failure_case_dir, sanitize_mapping
from ournear_qa.trace_lifecycle import TraceStopResult


@dataclass
class ArtifactRef:
    name: str
    relative_path: str | None
    present: bool
    note: str | None = None

    def to_dict(self) -> dict[str, Any]:
        return {
            "name": self.name,
            "relative_path": self.relative_path,
            "present": self.present,
            "note": self.note,
        }


@dataclass
class FailureManifest:
    run_id: str
    test_nodeid: str
    timestamp: str
    outcome: str
    browser: str
    base_url: str
    page_url: str | None = None
    page_title: str | None = None
    expected: str = ""
    actual: str = ""
    journey_id: str | None = None
    step_id: str | None = None
    artifacts: list[ArtifactRef] = field(default_factory=list)

    def write(self, directory: Path) -> Path:
        directory.mkdir(parents=True, exist_ok=True)
        path = directory / "manifest.json"
        payload = sanitize_mapping(
            {
                "run_id": self.run_id,
                "test_nodeid": self.test_nodeid,
                "timestamp": self.timestamp,
                "outcome": self.outcome,
                "browser": self.browser,
                "base_url": self.base_url,
                "page_url": self.page_url,
                "page_title": self.page_title,
                "expected": self.expected,
                "actual": self.actual,
                "journey_id": self.journey_id,
                "step_id": self.step_id,
                "artifacts": [a.to_dict() for a in self.artifacts],
            }
        )
        path.write_text(json.dumps(payload, indent=2), encoding="utf-8")
        return path


def _page_context(page: Page) -> tuple[str | None, str | None, str | None]:
    url_note = None
    title_note = None
    try:
        url = sanitize_url(page.url)
    except Exception as exc:  # noqa: BLE001
        url = None
        url_note = str(exc)
    try:
        title = redact_sensitive_strings(page.title())
    except Exception as exc:  # noqa: BLE001
        title = None
        title_note = str(exc)
    note = "; ".join(filter(None, [url_note, title_note]))
    return url, title, note or None


def write_failure_bundle(
    *,
    run_id: str,
    test_nodeid: str,
    timestamp: str,
    base_url: str,
    browser_name: str,
    page: Page | None,
    browser_session: Any | None,
    report_longrepr: str,
    journey_id: str | None,
    step_id: str | None,
    expected: str,
    actual: str,
    trace_result: TraceStopResult | None,
) -> Path:
    case_dir = failure_case_dir(run_id, test_nodeid)
    case_dir.mkdir(parents=True, exist_ok=True)

    screenshot_rel = "screenshot.png"
    screenshot_path = case_dir / screenshot_rel
    shot_abs: str | None = None
    screenshot_error: str | None = None
    if page is not None:
        try:
            page.screenshot(path=str(screenshot_path), full_page=True)
            shot_abs = str(screenshot_path) if screenshot_path.is_file() else None
        except Exception as exc:  # noqa: BLE001
            screenshot_error = str(exc)

    page_url, page_title, page_note = (None, None, None)
    if page is not None:
        page_url, page_title, page_note = _page_context(page)

    extra: dict[str, Any] = {}
    if screenshot_error:
        extra["screenshot_error"] = screenshot_error
    if page_note:
        extra["page_context_error"] = page_note
    if page_url is not None:
        extra["page_url"] = page_url
    if page_title is not None:
        extra["page_title"] = page_title
    extra["browser"] = browser_name
    if journey_id:
        extra["journey_id"] = journey_id
    if step_id:
        extra["step_id"] = step_id
    if browser_session is not None:
        extra["browser_diagnostics"] = browser_session.snapshot()
    if trace_result is not None:
        extra["trace"] = {
            "retained": trace_result.retained,
            "path": trace_result.path,
            "error": trace_result.error,
            "skipped_already_stopped": trace_result.skipped_already_stopped,
        }

    evidence = FailureEvidence(
        run_id=run_id,
        test_nodeid=test_nodeid,
        timestamp=timestamp,
        base_url=base_url,
        expected=expected,
        actual=actual,
        screenshot_path=shot_abs,
        extra=extra,
    )
    evidence.write(case_dir)

    artifacts: list[ArtifactRef] = [
        ArtifactRef(
            name="failure_json",
            relative_path="failure.json",
            present=(case_dir / "failure.json").is_file(),
        ),
        ArtifactRef(
            name="screenshot",
            relative_path=screenshot_rel if shot_abs else None,
            present=shot_abs is not None,
            note=screenshot_error,
        ),
    ]

    trace_note = None
    trace_present = False
    trace_rel: str | None = None
    if trace_result is None:
        trace_note = "tracing not active for this test"
    elif trace_result.skipped_already_stopped:
        trace_note = "trace stop skipped (already stopped)"
    elif trace_result.error:
        trace_note = trace_result.error
    elif trace_result.retained and trace_result.path:
        trace_rel = "trace.zip"
        trace_present = (case_dir / trace_rel).is_file()
    else:
        trace_note = "trace discarded or not retained"

    artifacts.append(
        ArtifactRef(
            name="trace",
            relative_path=trace_rel,
            present=trace_present,
            note=trace_note,
        )
    )

    manifest = FailureManifest(
        run_id=run_id,
        test_nodeid=test_nodeid,
        timestamp=timestamp,
        outcome="failed",
        browser=browser_name,
        base_url=base_url,
        page_url=page_url,
        page_title=page_title,
        expected=expected,
        actual=actual,
        journey_id=journey_id,
        step_id=step_id,
        artifacts=artifacts,
    )
    manifest.write(case_dir)
    return case_dir
