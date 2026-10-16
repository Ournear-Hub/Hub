from __future__ import annotations

from pathlib import Path

import pytest

from ournear_qa.journey.employer_job_publish import (
    EMPLOYER_JOB_LIFECYCLE_UI_ACTIONS,
    JRN_ID,
    discover_job_form_defaults,
    journey_job_title,
)


def test_journey_id_registered_prefix() -> None:
    assert JRN_ID.startswith("JRN-EMPLOYER-")


def test_lifecycle_ui_actions_match_product_surface() -> None:
    assert EMPLOYER_JOB_LIFECYCLE_UI_ACTIONS == frozenset({"publish", "pause", "close"})


def test_journey_job_title_unique_tokens() -> None:
    a = journey_job_title("aaaa")
    b = journey_job_title("bbbb")
    assert a != b
    assert "QA Journey Publish Role" in a


def test_discover_job_form_defaults_from_fixture_script() -> None:
    repo_root = Path(__file__).resolve().parents[2]
    defaults = discover_job_form_defaults(repo_root)
    if defaults is None:
        pytest.skip("php artisan qa:hiring-fixtures and PHP CLI required for master-data discovery")
    assert defaults.industry_id.isdigit()
    assert defaults.job_category_id.isdigit()
    assert defaults.job_title_id.isdigit()


def test_registry_lists_employer_job_publish_journey() -> None:
    import yaml

    registry = Path(__file__).resolve().parents[1] / "registry" / "features.yaml"
    data = yaml.safe_load(registry.read_text(encoding="utf-8"))
    journey_ids = [j["id"] for j in data.get("journeys", [])]
    assert "JRN-EMPLOYER-JOB-PUBLISH-01" in journey_ids
