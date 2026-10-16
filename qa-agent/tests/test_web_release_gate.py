from __future__ import annotations

from pathlib import Path

import yaml

from ournear_qa.release_readiness import CRITICAL_JOURNEYS

P0_IMPLEMENTED_JOURNEYS = frozenset(
    {
        "JRN-CANDIDATE-APPLY-01",
        "JRN-EMPLOYER-JOB-PUBLISH-01",
        "JRN-EMPLOYER-RESUME-01",
        "JRN-ADMIN-APPLICATION-01",
        "JRN-CROSS-ROLE-01",
    }
)

TRIAL_EXPIRY_PHPUNIT_MARKERS = (
    "EmployerAccountWorkspaceTest::test_employer_jobs_are_owned_validated_trial_then_basic",
    "JobsAtsFoundationStage5Test::test_lifecycle_pause_hides_public_job",
)


def test_critical_journeys_include_p0_recruitment_lifecycle() -> None:
    for jid in P0_IMPLEMENTED_JOURNEYS:
        assert jid in CRITICAL_JOURNEYS


def test_registry_p0_journeys_marked_implemented() -> None:
    registry = Path(__file__).resolve().parents[1] / "registry" / "features.yaml"
    data = yaml.safe_load(registry.read_text(encoding="utf-8"))
    by_id = {j["id"]: j for j in data.get("journeys", [])}
    for jid in P0_IMPLEMENTED_JOURNEYS:
        assert by_id[jid]["status"] == "implemented", jid


def test_trial_expiry_documented_in_phpunit_not_browser() -> None:
    phpunit = Path(__file__).resolve().parents[2] / "tests" / "Feature"
    text = "\n".join(p.read_text(encoding="utf-8", errors="ignore") for p in phpunit.glob("*.php"))
    assert "trial_expires_at" in text
    assert "travel(61)" in text or "addHour()" in text
