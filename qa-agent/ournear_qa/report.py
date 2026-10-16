from __future__ import annotations

import json
from dataclasses import dataclass, field
from pathlib import Path
from typing import Any

from ournear_qa.registry import load_registry


@dataclass
class RunSummary:
    run_id: str
    passed: int = 0
    failed: int = 0
    skipped: int = 0
    errors: int = 0
    feature_registry_count: int = 0
    notes: list[str] = field(default_factory=list)

    def to_dict(self) -> dict[str, Any]:
        return {
            "run_id": self.run_id,
            "results": {
                "passed": self.passed,
                "failed": self.failed,
                "skipped": self.skipped,
                "errors": self.errors,
            },
            "feature_registry_count": self.feature_registry_count,
            "notes": self.notes,
        }

    @classmethod
    def from_run_record_dict(cls, payload: dict[str, Any]) -> RunSummary:
        counts = payload.get("counts") or {}
        return cls(
            run_id=str(payload.get("run_id", "")),
            passed=int(counts.get("passed", 0)),
            failed=int(counts.get("failed", 0)),
            skipped=int(counts.get("skipped", 0)) + int(counts.get("blocked", 0)),
            errors=0,
            feature_registry_count=int(payload.get("feature_registry_count", 0)),
            notes=list(payload.get("notes") or []),
        )

    def write_json(self, path: Path) -> None:
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_text(json.dumps(self.to_dict(), indent=2), encoding="utf-8")


def registry_feature_count() -> int:
    data = load_registry()
    features = data.get("features") or []
    return len(features)
