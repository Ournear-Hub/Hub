from __future__ import annotations

import csv
import json
from io import StringIO
from pathlib import Path
from typing import Any

from ournear_qa.manual_catalog import ManualCase, build_manual_catalog, feature_names, filter_catalog
from ournear_qa.registry import load_registry


def export_manual_json(cases: list[ManualCase]) -> str:
    payload = {
        "version": 1,
        "case_count": len(cases),
        "cases": [case.to_export_dict() for case in cases],
    }
    return json.dumps(payload, indent=2)


def export_manual_markdown(cases: list[ManualCase], registry: dict[str, Any] | None = None) -> str:
    registry = registry or load_registry()
    names = feature_names(registry)
    lines = [
        "# OurNear Hire — Manual QA checklist",
        "",
        f"**Cases:** {len(cases)}",
        "",
        "Record results in the `Result` column (`PASS`, `FAIL`, `BLOCKED`, `NOT_RUN`). "
        "Import with `ournear-qa import-manual-results`.",
        "",
    ]
    for case in cases:
        feat_labels = ", ".join(f"{fid} ({names.get(fid, '?')})" for fid in case.feature_ids)
        lines.append(f"## {case.id}")
        lines.append("")
        lines.append(f"- **Features:** {feat_labels}")
        lines.append(f"- **Role:** {case.role}")
        lines.append(f"- **Risk:** {case.risk}")
        lines.append(f"- **Source:** {case.source}")
        lines.append(f"- **Purpose:** {case.purpose}")
        if case.prerequisites:
            lines.append("- **Preconditions:**")
            lines.extend(f"  - {p}" for p in case.prerequisites)
        lines.append("- **Steps:**")
        for index, step in enumerate(case.steps, start=1):
            lines.append(f"  {index}. {step}")
        lines.append("- **Expected results:**")
        lines.extend(f"  - {e}" for e in case.expected_results)
        if case.persistence_checks:
            lines.append("- **Persistence checks:**")
            lines.extend(f"  - {p}" for p in case.persistence_checks)
        if case.authorization_checks:
            lines.append("- **Authorization checks:**")
            lines.extend(f"  - {a}" for a in case.authorization_checks)
        if case.related_automated:
            lines.append("- **Related automated tests:**")
            lines.extend(f"  - {r}" for r in case.related_automated)
        if case.evidence_requirements:
            lines.append("- **Evidence:**")
            lines.extend(f"  - {e}" for e in case.evidence_requirements)
        lines.append("")
        lines.append("| Result | Evidence ref | Notes |")
        lines.append("| --- | --- | --- |")
        lines.append("|  |  |  |")
        lines.append("")
    return "\n".join(lines)


def export_manual_csv(cases: list[ManualCase]) -> str:
    buffer = StringIO()
    writer = csv.writer(buffer)
    writer.writerow(
        [
            "test_id",
            "feature_ids",
            "role",
            "risk",
            "source",
            "purpose",
            "preconditions",
            "steps",
            "expected_results",
            "result",
            "evidence_ref",
            "notes",
        ]
    )
    for case in cases:
        writer.writerow(
            [
                case.id,
                ";".join(case.feature_ids),
                case.role,
                case.risk,
                case.source,
                case.purpose,
                " | ".join(case.prerequisites),
                " | ".join(case.steps),
                " | ".join(case.expected_results),
                "",
                "",
                "",
            ]
        )
    return buffer.getvalue()


def write_manual_export(
    path: Path,
    fmt: str,
    *,
    test_id: str | None = None,
    feature_id: str | None = None,
    journey_id: str | None = None,
    risk: str | None = None,
    source: str | None = None,
) -> dict[str, Any]:
    registry = load_registry()
    catalog = build_manual_catalog(registry)
    cases = filter_catalog(
        catalog,
        test_id=test_id,
        feature_id=feature_id,
        journey_id=journey_id,
        risk=risk,
        source=source,
    )
    if fmt == "json":
        content = export_manual_json(cases)
    elif fmt == "csv":
        content = export_manual_csv(cases)
    else:
        content = export_manual_markdown(cases, registry)
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(content, encoding="utf-8")
    return {"path": str(path), "format": fmt, "case_count": len(cases)}
