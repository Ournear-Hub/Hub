from __future__ import annotations

import json
from dataclasses import asdict, dataclass, field
from datetime import datetime, timezone
from enum import Enum
from pathlib import Path
from typing import Any

from ournear_qa.evidence import ARTIFACTS_DIR, failure_case_dir, run_artifact_dir

RUN_RECORD_VERSION = 1


class RunCaseOutcome(str, Enum):
    PASSED = "passed"
    FAILED = "failed"
    SKIPPED = "skipped"
    BLOCKED = "blocked"
    NOT_RUN = "not_run"
    UNKNOWN = "unknown"


_BLOCKED_SKIP_MARKERS = (
    "rate limit",
    "429",
    "blocked",
    "throttled",
    "preflight",
    "job-applications",
    "account-login",
)


def classify_skip_as_blocked(longrepr: str) -> bool:
    lower = (longrepr or "").lower()
    return any(marker in lower for marker in _BLOCKED_SKIP_MARKERS)


@dataclass
class CaseResultRecord:
    nodeid: str
    outcome: RunCaseOutcome
    duration_ms: float | None = None
    skip_reason: str | None = None
    journey_id: str | None = None
    feature_ids: list[str] = field(default_factory=list)
    failure_artifact_rel: str | None = None

    def to_dict(self) -> dict[str, Any]:
        data = asdict(self)
        data["outcome"] = self.outcome.value
        return data


@dataclass
class NormalizedRunRecord:
    version: int = RUN_RECORD_VERSION
    run_id: str = ""
    started_at: str = ""
    finished_at: str = ""
    duration_seconds: float | None = None
    suite: str = "adhoc"
    regression_scope: str = "targeted"
    environment: dict[str, str] = field(default_factory=dict)
    counts: dict[str, int] = field(default_factory=dict)
    tests: list[CaseResultRecord] = field(default_factory=list)
    pytest_exitstatus: int | None = None
    complete: bool = True
    notes: list[str] = field(default_factory=list)

    def to_dict(self) -> dict[str, Any]:
        return {
            "version": self.version,
            "run_id": self.run_id,
            "started_at": self.started_at,
            "finished_at": self.finished_at,
            "duration_seconds": self.duration_seconds,
            "suite": self.suite,
            "regression_scope": self.regression_scope,
            "environment": self.environment,
            "counts": self.counts,
            "tests": [t.to_dict() for t in self.tests],
            "pytest_exitstatus": self.pytest_exitstatus,
            "complete": self.complete,
            "notes": self.notes,
        }

    def recompute_counts(self) -> None:
        counts = {
            "passed": 0,
            "failed": 0,
            "skipped": 0,
            "blocked": 0,
            "unknown": 0,
        }
        for test in self.tests:
            key = test.outcome.value
            if key in counts:
                counts[key] += 1
            else:
                counts["unknown"] += 1
        self.counts = counts

    def write(self, path: Path) -> None:
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_text(json.dumps(self.to_dict(), indent=2), encoding="utf-8")


def run_record_path(run_id: str) -> Path:
    return run_artifact_dir(run_id) / "run-record.json"


def latest_run_record_path(artifacts_root: Path = ARTIFACTS_DIR) -> Path | None:
    session_pointer = artifacts_root / "session" / "last-pytest-summary.json"
    if session_pointer.is_file():
        payload = json.loads(session_pointer.read_text(encoding="utf-8"))
        run_id = payload.get("run_id")
        if run_id:
            candidate = run_record_path(str(run_id))
            if candidate.is_file():
                return candidate
    runs_dir = artifacts_root
    candidates = sorted(
        (p for p in runs_dir.iterdir() if p.is_dir() and (p / "run-record.json").is_file()),
        key=lambda p: p.stat().st_mtime,
        reverse=True,
    )
    if candidates:
        return candidates[0] / "run-record.json"
    return None


def load_run_record(path: Path) -> NormalizedRunRecord:
    payload = json.loads(path.read_text(encoding="utf-8"))
    tests = [
        CaseResultRecord(
            nodeid=str(row["nodeid"]),
            outcome=RunCaseOutcome(str(row.get("outcome", "unknown"))),
            duration_ms=row.get("duration_ms"),
            skip_reason=row.get("skip_reason"),
            journey_id=row.get("journey_id"),
            feature_ids=list(row.get("feature_ids") or []),
            failure_artifact_rel=row.get("failure_artifact_rel"),
        )
        for row in payload.get("tests") or []
    ]
    return NormalizedRunRecord(
        version=int(payload.get("version", 1)),
        run_id=str(payload.get("run_id", "")),
        started_at=str(payload.get("started_at", "")),
        finished_at=str(payload.get("finished_at", "")),
        duration_seconds=payload.get("duration_seconds"),
        suite=str(payload.get("suite", "adhoc")),
        regression_scope=str(payload.get("regression_scope", "targeted")),
        environment=dict(payload.get("environment") or {}),
        counts=dict(payload.get("counts") or {}),
        tests=tests,
        pytest_exitstatus=payload.get("pytest_exitstatus"),
        complete=bool(payload.get("complete", True)),
        notes=list(payload.get("notes") or []),
    )


def suite_to_regression_scope(suite: str) -> str:
    if suite in ("smoke", "registry", "journey", "unit"):
        return "targeted"
    return "targeted"


def utc_now_iso() -> str:
    return datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")


def failure_artifact_rel(run_id: str, nodeid: str) -> str:
    rel = failure_case_dir(run_id, nodeid).relative_to(ARTIFACTS_DIR)
    return rel.as_posix()
