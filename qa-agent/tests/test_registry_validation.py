from __future__ import annotations

import copy

import pytest
import yaml

from ournear_qa.registry import REGISTRY_PATH, load_registry
from ournear_qa.registry_validate import RegistryValidationError, validate_registry

EXPECTED_FEATURE_COUNT = 53
STABLE_FEATURE_IDS = {"PUB-03", "CAN-04", "CAN-07", "X-03", "EMP-09"}


def test_production_registry_validates() -> None:
    data = load_registry()
    result = validate_registry(data)
    assert result.feature_count == EXPECTED_FEATURE_COUNT
    assert result.journey_count >= 4


def test_stable_feature_ids_present() -> None:
    data = load_registry()
    ids = {row["id"] for row in data["features"]}
    assert STABLE_FEATURE_IDS <= ids


def test_legacy_feature_rows_without_new_fields_still_valid() -> None:
    data = load_registry()
    minimal = copy.deepcopy(data)
    for row in minimal["features"]:
        if row["id"] == "PUB-08":
            row.pop("phpunit", None)
            row.pop("depends_on", None)
    validate_registry(minimal)


def test_invalid_journey_unknown_feature() -> None:
    data = load_registry()
    bad = copy.deepcopy(data)
    bad["journeys"].append(
        {
            "id": "JRN-BAD-FEAT",
            "status": "planned",
            "feature_ids": ["NO-SUCH-FEATURE"],
            "role": "guest",
            "purpose": "x",
            "prerequisites": [],
            "steps": ["step"],
            "expected_outcomes": [],
            "assertions": [],
            "test_data": [],
            "risk": "P3",
        }
    )
    with pytest.raises(RegistryValidationError, match="unknown feature_ids"):
        validate_registry(bad)


def test_invalid_phpunit_format() -> None:
    data = load_registry()
    bad = copy.deepcopy(data)
    for row in bad["features"]:
        if row["id"] == "PUB-08":
            row["phpunit"] = ["not-a-valid-test"]
            break
    with pytest.raises(RegistryValidationError, match="invalid phpunit"):
        validate_registry(bad)


def test_missing_phpunit_file() -> None:
    data = load_registry()
    bad = copy.deepcopy(data)
    for row in bad["features"]:
        if row["id"] == "PUB-08":
            row["phpunit"] = ["TotallyMissingTest"]
            break
    with pytest.raises(RegistryValidationError, match="not found"):
        validate_registry(bad)


def test_feature_self_dependency() -> None:
    data = load_registry()
    bad = copy.deepcopy(data)
    for row in bad["features"]:
        if row["id"] == "PUB-08":
            row["depends_on"] = ["PUB-08"]
            break
    with pytest.raises(RegistryValidationError, match="must not include itself"):
        validate_registry(bad)


def test_feature_unknown_dependency() -> None:
    data = load_registry()
    bad = copy.deepcopy(data)
    for row in bad["features"]:
        if row["id"] == "PUB-08":
            row["depends_on"] = ["MISSING-FEAT"]
            break
    with pytest.raises(RegistryValidationError, match="unknown depends_on"):
        validate_registry(bad)


def test_feature_dependency_cycle() -> None:
    data = load_registry()
    bad = copy.deepcopy(data)
    for row in bad["features"]:
        if row["id"] == "PUB-08":
            row["depends_on"] = ["PUB-09"]
        if row["id"] == "PUB-09":
            row["depends_on"] = ["PUB-08"]
    with pytest.raises(RegistryValidationError, match="cycle"):
        validate_registry(bad)


def test_journey_self_dependency() -> None:
    data = load_registry()
    bad = copy.deepcopy(data)
    bad["journeys"][0]["depends_on"] = [bad["journeys"][0]["id"]]
    with pytest.raises(RegistryValidationError, match="must not include itself"):
        validate_registry(bad)


def test_journey_unknown_dependency() -> None:
    data = load_registry()
    bad = copy.deepcopy(data)
    bad["journeys"][0]["depends_on"] = ["JRN-NOPE"]
    with pytest.raises(RegistryValidationError, match="unknown depends_on journey"):
        validate_registry(bad)


def test_journey_cycle() -> None:
    data = load_registry()
    bad = copy.deepcopy(data)
    j0, j1 = bad["journeys"][0]["id"], bad["journeys"][1]["id"]
    bad["journeys"][0]["depends_on"] = [j1]
    bad["journeys"][1]["depends_on"] = [j0]
    with pytest.raises(RegistryValidationError, match="cycle"):
        validate_registry(bad)


def test_invalid_journey_missing_steps() -> None:
    data = load_registry()
    bad = copy.deepcopy(data)
    bad["journeys"].append(
        {
            "id": "JRN-NO-STEPS",
            "status": "planned",
            "feature_ids": ["PUB-01"],
            "role": "guest",
            "purpose": "x",
            "prerequisites": [],
            "steps": [],
            "expected_outcomes": [],
            "assertions": [],
            "test_data": [],
            "risk": "P3",
        }
    )
    with pytest.raises(RegistryValidationError, match="steps must be"):
        validate_registry(bad)


def test_yaml_fixture_roundtrip() -> None:
    raw = REGISTRY_PATH.read_text(encoding="utf-8")
    parsed = yaml.safe_load(raw)
    validate_registry(parsed)
