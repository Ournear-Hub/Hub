from __future__ import annotations

import json
import re
from dataclasses import asdict, dataclass, field
from datetime import datetime, timezone
from pathlib import Path
from typing import Any
from uuid import uuid4

from ournear_qa.browser.redact import redact_sensitive_strings, sanitize_url

ROOT = Path(__file__).resolve().parents[1]
ARTIFACTS_DIR = ROOT / "artifacts"

_SECRET_KEYS = re.compile(
    r"(password|secret|token|authorization|cookie|api[_-]?key)",
    re.IGNORECASE,
)

_TRACE_STOPPED_ATTR = "_ournear_qa_trace_stopped"


def sanitize_value(value: Any) -> Any:
    if isinstance(value, dict):
        return sanitize_mapping(value)
    if isinstance(value, list):
        return [sanitize_value(item) for item in value]
    if isinstance(value, str):
        redacted = redact_sensitive_strings(value)
        if redacted.startswith("http://") or redacted.startswith("https://"):
            return sanitize_url(redacted)
        return redacted
    return value


def sanitize_mapping(data: dict[str, Any]) -> dict[str, Any]:
    out: dict[str, Any] = {}
    for key, value in data.items():
        if _SECRET_KEYS.search(str(key)):
            out[key] = "[REDACTED]"
        else:
            out[key] = sanitize_value(value)
    return out


def failure_case_dir(run_id: str, test_nodeid: str) -> Path:
    safe = test_nodeid.replace("::", "_").replace("/", "_")
    return run_artifact_dir(run_id) / safe


@dataclass
class FailureEvidence:
    run_id: str
    test_nodeid: str
    timestamp: str
    base_url: str
    expected: str = ""
    actual: str = ""
    screenshot_path: str | None = None
    extra: dict[str, Any] = field(default_factory=dict)

    def write(self, directory: Path) -> Path:
        directory.mkdir(parents=True, exist_ok=True)
        path = directory / "failure.json"
        payload = sanitize_mapping(asdict(self))
        path.write_text(json.dumps(payload, indent=2), encoding="utf-8")
        return path


def new_run_id() -> str:
    stamp = datetime.now(timezone.utc).strftime("%Y%m%dT%H%M%SZ")
    return f"{stamp}-{uuid4().hex[:8]}"


def run_artifact_dir(run_id: str) -> Path:
    return ARTIFACTS_DIR / run_id
