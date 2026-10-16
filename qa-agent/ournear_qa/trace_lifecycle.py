from __future__ import annotations

from dataclasses import dataclass
from pathlib import Path
from typing import Any

from ournear_qa.evidence import _TRACE_STOPPED_ATTR

_TRACE_STARTED_ATTR = "_ournear_qa_trace_started"


@dataclass(frozen=True)
class TraceStopResult:
    retained: bool
    path: str | None = None
    error: str | None = None
    skipped_already_stopped: bool = False


def _is_true(context: Any, attr: str) -> bool:
    return getattr(context, attr, False) is True


def start_context_trace(context: Any) -> None:
    if _is_true(context, _TRACE_STARTED_ATTR):
        return
    context.tracing.start(screenshots=True, snapshots=True, sources=True)
    setattr(context, _TRACE_STARTED_ATTR, True)


def stop_context_trace(context: Any, *, retain_path: Path | None) -> TraceStopResult:
    if _is_true(context, _TRACE_STOPPED_ATTR):
        return TraceStopResult(retained=False, skipped_already_stopped=True)

    try:
        if retain_path is not None:
            retain_path.parent.mkdir(parents=True, exist_ok=True)
            context.tracing.stop(path=str(retain_path))
            retained = retain_path.is_file()
            setattr(context, _TRACE_STOPPED_ATTR, True)
            return TraceStopResult(
                retained=retained,
                path=str(retain_path) if retained else None,
                error=None if retained else "trace file missing after stop",
            )
        context.tracing.stop()
        setattr(context, _TRACE_STOPPED_ATTR, True)
        return TraceStopResult(retained=False)
    except Exception as exc:  # noqa: BLE001 — evidence best-effort
        setattr(context, _TRACE_STOPPED_ATTR, True)
        return TraceStopResult(retained=False, error=str(exc))
