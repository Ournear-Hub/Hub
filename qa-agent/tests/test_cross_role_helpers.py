from __future__ import annotations

from unittest.mock import patch

import pytest

from ournear_qa.journey.cross_role import resolve_application_by_business_id
from ournear_qa.journey.errors import JourneyStepError


def test_resolve_application_by_business_id_missing_record() -> None:
    assert resolve_application_by_business_id("ONT-A-000000") is None


def test_cross_role_provision_none_raises_step_error() -> None:
    from ournear_qa.journey import cross_role

    with patch.object(cross_role, "provision_journey_apply_target", return_value=None):
        with pytest.raises(JourneyStepError) as exc:
            cross_role.run_cross_role_e2e(
                page=None,  # type: ignore[arg-type]
                base_url="http://127.0.0.1:8000",
                candidate_email="a@example.test",
                candidate_password="x",
                employer_a_email="b@example.test",
                employer_a_password="x",
                employer_b_email="c@example.test",
                employer_b_password="x",
                admin_email="d@example.test",
                admin_password="x",
            )
    assert exc.value.step_id == "D4-00-provision"


def test_cross_role_registry_journey_verified() -> None:
    from pathlib import Path

    import yaml

    registry = Path(__file__).resolve().parents[1] / "registry" / "features.yaml"
    data = yaml.safe_load(registry.read_text(encoding="utf-8"))
    journey = next(j for j in data["journeys"] if j["id"] == "JRN-CROSS-ROLE-01")
    assert journey["status"] == "implemented"
    assert "JRN-CANDIDATE-APPLY-01" in journey["depends_on"]
