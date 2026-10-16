from __future__ import annotations

import json
import re
from dataclasses import asdict, dataclass, field
from datetime import datetime, timezone
from pathlib import Path
from typing import Any

from ournear_qa.evidence import ARTIFACTS_DIR
from ournear_qa.manual_catalog import VALID_MANUAL_STATUSES, build_manual_catalog
from ournear_qa.registry import load_registry

MANUAL_RESULTS_VERSION = 1
_PASSWORD_PATTERN = re.compile(
    r"(password\s*[:=]\s*\S+|OURNEAR_QA_\w+PASSWORD\s*=\s*\S+)",
    re.IGNORECASE,
)


class ManualResultsValidationError(ValueError):
    pass


@dataclass
class ManualResultRow:
    test_id: str
    status: str
    executed_at: str | None = None
    notes: str | None = None
    evidence_refs: list[str] = field(default_factory=list)

    def to_dict(self) -> dict[str, Any]:
        return asdict(self)


@dataclass
class ManualResultsDocument:
    version: int = MANUAL_RESULTS_VERSION
    manual_run_id: str = ""
    automated_run_id: str | None = None
    executed_at: str = ""
    environment: dict[str, str] = field(default_factory=dict)
    tester: str | None = None
    results: list[ManualResultRow] = field(default_factory=list)
    notes: list[str] = field(default_factory=list)

    def to_dict(self) -> dict[str, Any]:
        return {
            "version": self.version,
            "manual_run_id": self.manual_run_id,
            "automated_run_id": self.automated_run_id,
            "executed_at": self.executed_at,
            "environment": self.environment,
            "tester": self.tester,
            "results": [row.to_dict() for row in self.results],
            "notes": self.notes,
            "evidence_kind": "human_manual",
        }

    def summary_counts(self) -> dict[str, int]:
        counts = {key: 0 for key in VALID_MANUAL_STATUSES}
        for row in self.results:
            status = row.status.upper()
            if status in counts:
                counts[status] += 1
        return counts


def sanitize_manual_text(value: str | None) -> str:
    if not value:
        return ""
    cleaned = _PASSWORD_PATTERN.sub("[REDACTED]", value)
    for token in (".env", "config/local.env"):
        if token in cleaned.lower():
            cleaned = cleaned.replace(token, "[env-file]")
    return cleaned


def _parse_row(raw: dict[str, Any]) -> ManualResultRow:
    test_id = str(raw.get("test_id", "")).strip()
    status = str(raw.get("status", "")).strip().upper()
    if not test_id:
        raise ManualResultsValidationError("Each result requires test_id")
    if status not in VALID_MANUAL_STATUSES:
        raise ManualResultsValidationError(
            f"Invalid status {status!r} for {test_id}; use PASS, FAIL, BLOCKED, NOT_RUN"
        )
    evidence = raw.get("evidence_refs") or raw.get("evidence_ref")
    if isinstance(evidence, str):
        evidence_list = [evidence] if evidence.strip() else []
    else:
        evidence_list = [str(x) for x in evidence or [] if str(x).strip()]
    for ref in evidence_list:
        lower = ref.lower()
        if ".env" in lower or "password" in lower:
            raise ManualResultsValidationError(f"Evidence ref must not reference secrets: {ref!r}")
    return ManualResultRow(
        test_id=test_id,
        status=status,
        executed_at=raw.get("executed_at"),
        notes=sanitize_manual_text(str(raw.get("notes") or "")) or None,
        evidence_refs=evidence_list,
    )


def validate_manual_results_payload(payload: dict[str, Any]) -> ManualResultsDocument:
    catalog = build_manual_catalog(load_registry())
    known_ids = set(catalog.keys())
    manual_run_id = str(payload.get("manual_run_id") or payload.get("run_id") or "").strip()
    if not manual_run_id:
        raise ManualResultsValidationError("manual_run_id is required")

    rows_raw = payload.get("results")
    if not isinstance(rows_raw, list) or not rows_raw:
        raise ManualResultsValidationError("results must be a non-empty list")

    rows: list[ManualResultRow] = []
    seen: set[str] = set()
    for raw in rows_raw:
        if not isinstance(raw, dict):
            raise ManualResultsValidationError("Each result must be an object")
        row = _parse_row(raw)
        if row.test_id not in known_ids:
            raise ManualResultsValidationError(f"Unknown test_id {row.test_id!r}")
        if row.test_id in seen:
            raise ManualResultsValidationError(f"Duplicate test_id {row.test_id!r}")
        seen.add(row.test_id)
        rows.append(row)

    env = payload.get("environment") or {}
    if not isinstance(env, dict):
        raise ManualResultsValidationError("environment must be an object")
    safe_env = {str(k): sanitize_manual_text(str(v)) for k, v in env.items()}

    doc = ManualResultsDocument(
        version=int(payload.get("version", MANUAL_RESULTS_VERSION)),
        manual_run_id=manual_run_id,
        automated_run_id=(str(payload["automated_run_id"]).strip() if payload.get("automated_run_id") else None),
        executed_at=str(payload.get("executed_at") or utc_now_iso()),
        environment=safe_env,
        tester=sanitize_manual_text(str(payload.get("tester") or "")) or None,
        results=rows,
        notes=[sanitize_manual_text(str(n)) for n in payload.get("notes") or [] if str(n).strip()],
    )
    return doc


def utc_now_iso() -> str:
    return datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")


def load_manual_results_json(path: Path) -> ManualResultsDocument:
    payload = json.loads(path.read_text(encoding="utf-8"))
    if not isinstance(payload, dict):
        raise ManualResultsValidationError("Manual results file must be a JSON object")
    return validate_manual_results_payload(payload)


def manual_results_path_for_run(run_id: str) -> Path:
    return ARTIFACTS_DIR / run_id / "manual-results.json"


def manual_results_standalone_dir(manual_run_id: str) -> Path:
    return ARTIFACTS_DIR / "manual" / manual_run_id


def save_manual_results(doc: ManualResultsDocument, *, attach_run_id: str | None = None) -> Path:
    if attach_run_id:
        path = manual_results_path_for_run(attach_run_id)
        doc.automated_run_id = attach_run_id
    else:
        base = manual_results_standalone_dir(doc.manual_run_id)
        base.mkdir(parents=True, exist_ok=True)
        path = base / "manual-results.json"
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(doc.to_dict(), indent=2), encoding="utf-8")
    return path


def load_manual_results_for_run(run_id: str) -> ManualResultsDocument | None:
    path = manual_results_path_for_run(run_id)
    if not path.is_file():
        return None
    return load_manual_results_json(path)


def merge_manual_into_report_payload(payload: dict[str, Any], manual: ManualResultsDocument | None) -> dict[str, Any]:
    if manual is None:
        return payload
    payload = dict(payload)
    payload["manual_results"] = manual.to_dict()
    payload["manual_results_summary"] = {
        "counts": manual.summary_counts(),
        "disclaimer": "Human manual results are separate from Playwright/pytest outcomes.",
    }
    return payload
