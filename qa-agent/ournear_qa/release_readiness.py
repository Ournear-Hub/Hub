from __future__ import annotations

from dataclasses import dataclass, field
from typing import Any

from ournear_qa.manual_results import ManualResultsDocument
from ournear_qa.run_record import NormalizedRunRecord

CRITICAL_JOURNEYS = (
    "JRN-SMOKE-LOGIN-01",
    "JRN-CANDIDATE-APPLY-01",
    "JRN-EMPLOYER-JOB-PUBLISH-01",
    "JRN-EMPLOYER-RESUME-01",
    "JRN-ADMIN-APPLICATION-01",
    "JRN-CROSS-ROLE-01",
)


@dataclass
class ReleaseReadiness:
    verdict: str
    reasons: list[str] = field(default_factory=list)
    targeted_only: bool = True

    def to_dict(self) -> dict[str, Any]:
        return {
            "verdict": self.verdict,
            "reasons": self.reasons,
            "targeted_only": self.targeted_only,
        }


def evaluate_release_readiness(
    record: NormalizedRunRecord,
    coverage: dict[str, Any],
    manual: ManualResultsDocument | None = None,
) -> ReleaseReadiness:
    reasons: list[str] = []
    targeted_only = record.regression_scope != "full"

    if record.counts.get("failed", 0) > 0:
        reasons.append("One or more tests failed in this run.")
    if not record.complete:
        reasons.append("Run did not complete (interrupted or partial evidence).")
    if record.counts.get("blocked", 0) > 0:
        reasons.append("Blocked tests (rate limits or prerequisites) — not counted as pass.")

    journeys = coverage.get("journeys") or {}
    for jid in CRITICAL_JOURNEYS:
        state = journeys.get(jid)
        if state == "failed":
            reasons.append(f"Critical journey {jid} failed.")
        elif state == "blocked":
            reasons.append(f"Critical journey {jid} was blocked.")

    if targeted_only:
        reasons.append(
            "Executed scope was targeted (suite-limited), not full application regression."
        )

    if coverage.get("features_failed"):
        reasons.append("Features with failing test evidence present.")

    not_executed = coverage.get("features_not_executed") or []
    if len(not_executed) > 40:
        reasons.append(
            f"{len(not_executed)} mapped features had no execution evidence in this run."
        )

    if manual is not None:
        counts = manual.summary_counts()
        if counts.get("FAIL", 0) > 0:
            reasons.append(
                f"Human manual checklist reported {counts['FAIL']} failure(s) — not release-ready."
            )
        if counts.get("BLOCKED", 0) > 0:
            reasons.append("Human manual tests blocked — environment or prerequisite gap.")
        if counts.get("PASS", 0) > 0:
            reasons.append(
                "Human manual PASS recorded — does not replace Playwright/PHPUnit browser proof."
            )
        if counts.get("NOT_RUN", 0) > 0:
            reasons.append(
                f"{counts['NOT_RUN']} manual case(s) marked NOT_RUN in submitted checklist."
            )

    if reasons:
        if any("failed" in r.lower() for r in reasons) or record.counts.get("failed", 0):
            verdict = "not_release_ready"
        else:
            verdict = "insufficient_evidence"
    else:
        verdict = "release_ready"

    return ReleaseReadiness(verdict=verdict, reasons=reasons, targeted_only=targeted_only)


def ci_exit_code_for_run(record: NormalizedRunRecord, readiness: ReleaseReadiness) -> int:
    if not record.complete:
        return 2
    if record.counts.get("failed", 0) > 0:
        return 1
    if record.counts.get("blocked", 0) > 0 and readiness.verdict != "release_ready":
        return 2
    if readiness.verdict in ("not_release_ready", "insufficient_evidence"):
        return 4
    return 0
