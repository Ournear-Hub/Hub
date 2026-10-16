"""Stage 12 — release gate integrity checks (no verdict override)."""

from __future__ import annotations

import pytest

pytestmark = [pytest.mark.stage12]


def test_stg12_gate_critical_journeys_registered(record_stage12) -> None:
    from tests.test_web_release_gate import (
        P0_IMPLEMENTED_JOURNEYS,
        test_critical_journeys_include_p0_recruitment_lifecycle,
        test_registry_p0_journeys_marked_implemented,
    )

    test_critical_journeys_include_p0_recruitment_lifecycle()
    test_registry_p0_journeys_marked_implemented()
    record_stage12(
        "STG12-GATE-002",
        status="PASS",
        expected="P0 journeys in CRITICAL_JOURNEYS and registry implemented",
        actual="test_web_release_gate checks passed",
    )


def test_stg12_gate_readiness_insufficient_for_targeted_run(record_stage12) -> None:
    from ournear_qa.release_readiness import evaluate_release_readiness
    from ournear_qa.run_record import NormalizedRunRecord

    record = NormalizedRunRecord(
        run_id="stage12-doc",
        suite="stage12",
        complete=True,
        regression_scope="targeted",
        counts={"passed": 1, "failed": 0, "blocked": 0, "skipped": 0},
    )
    readiness = evaluate_release_readiness(record, coverage={"journeys": {}}, manual=None)
    assert readiness.verdict == "insufficient_evidence"
    assert readiness.targeted_only is True
    record_stage12(
        "STG12-GATE-001",
        status="PASS",
        expected="Targeted runs remain insufficient_evidence",
        actual=f"verdict={readiness.verdict}",
    )
