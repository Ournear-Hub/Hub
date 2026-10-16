from __future__ import annotations

import html
from pathlib import Path
from typing import Any

from ournear_qa.manual_results import ManualResultsDocument
from ournear_qa.release_readiness import ReleaseReadiness
from ournear_qa.run_record import NormalizedRunRecord


def _esc(value: Any) -> str:
    return html.escape("" if value is None else str(value))


def render_dashboard_html(
    record: NormalizedRunRecord,
    coverage: dict[str, Any],
    readiness: ReleaseReadiness,
    *,
    manual: ManualResultsDocument | None = None,
    artifact_root_label: str = "artifacts",
) -> str:
    counts = record.counts or {}
    rows = []
    for test in record.tests:
        artifact = ""
        if test.failure_artifact_rel:
            artifact = f'<a href="{_esc(test.failure_artifact_rel)}/manifest.json">evidence</a>'
        rows.append(
            "<tr>"
            f"<td>{_esc(test.nodeid)}</td>"
            f"<td>{_esc(test.outcome.value)}</td>"
            f"<td>{_esc(test.journey_id or '')}</td>"
            f"<td>{_esc(', '.join(test.feature_ids))}</td>"
            f"<td>{artifact}</td>"
            "</tr>"
        )
    if not rows:
        rows.append('<tr><td colspan="5">No tests recorded for this run.</td></tr>')

    journey_lines = "".join(
        f"<li>{_esc(jid)}: {_esc(state)}</li>" for jid, state in sorted((coverage.get("journeys") or {}).items())
    )
    if not journey_lines:
        journey_lines = "<li>No journey outcomes derived from this run.</li>"

    reason_lines = "".join(f"<li>{_esc(r)}</li>" for r in readiness.reasons)
    if not reason_lines:
        reason_lines = "<li>No blocking reasons recorded.</li>"

    manual_rows = []
    if manual is not None:
        for row in manual.results:
            manual_rows.append(
                "<tr>"
                f"<td>{_esc(row.test_id)}</td>"
                f"<td><strong>{_esc(row.status)}</strong> (human)</td>"
                f"<td>{_esc(', '.join(row.evidence_refs))}</td>"
                f"<td>{_esc(row.notes or '')}</td>"
                "</tr>"
            )
    manual_section = ""
    if manual_rows:
        manual_section = f"""
  <h2>Human manual checklist (separate from automation)</h2>
  <p>Tester: {_esc(manual.tester or '—')} · manual_run_id: {_esc(manual.manual_run_id)}</p>
  <table>
    <thead><tr><th>Test ID</th><th>Status</th><th>Evidence</th><th>Notes</th></tr></thead>
    <tbody>{''.join(manual_rows)}</tbody>
  </table>
"""

    return f"""<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <title>OurNear QA Run {_esc(record.run_id)}</title>
  <style>
    body {{ font-family: system-ui, sans-serif; margin: 1.5rem; line-height: 1.4; }}
    table {{ border-collapse: collapse; width: 100%; }}
    th, td {{ border: 1px solid #ccc; padding: 0.35rem 0.5rem; text-align: left; }}
    th {{ background: #f4f4f4; }}
    .badge {{ display: inline-block; padding: 0.15rem 0.45rem; border-radius: 4px; background: #eee; }}
  </style>
</head>
<body>
  <h1>QA Run Dashboard</h1>
  <p><span class="badge">run_id</span> {_esc(record.run_id)}</p>
  <p><span class="badge">suite</span> {_esc(record.suite)} &nbsp;
     <span class="badge">scope</span> {_esc(record.regression_scope)}</p>
  <p><span class="badge">started</span> {_esc(record.started_at)} &nbsp;
     <span class="badge">finished</span> {_esc(record.finished_at)}</p>
  <h2>Outcome counts</h2>
  <ul>
    <li>Passed: {_esc(counts.get('passed', 0))}</li>
    <li>Failed: {_esc(counts.get('failed', 0))}</li>
    <li>Skipped: {_esc(counts.get('skipped', 0))}</li>
    <li>Blocked: {_esc(counts.get('blocked', 0))}</li>
  </ul>
  <h2>Release readiness</h2>
  <p><strong>{_esc(readiness.verdict)}</strong> (targeted_only={_esc(readiness.targeted_only)})</p>
  <ul>{reason_lines}</ul>
  <h2>Journeys (from executed tests only)</h2>
  <ul>{journey_lines}</ul>
  <h2>Feature coverage gaps (not executed this run)</h2>
  <p>{_esc(len(coverage.get('features_not_executed') or []))} mapped features without pass/fail/blocked evidence.</p>
{manual_section}
  <h2>Automated tests (Playwright / pytest)</h2>
  <table>
    <thead><tr><th>Test</th><th>Outcome</th><th>Journey</th><th>Features</th><th>Artifacts</th></tr></thead>
    <tbody>{''.join(rows)}</tbody>
  </table>
  <p><small>Artifact root: {_esc(artifact_root_label)} — local only, no secrets included.</small></p>
</body>
</html>
"""


def write_dashboard(path: Path, html_content: str) -> Path:
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(html_content, encoding="utf-8")
    return path
