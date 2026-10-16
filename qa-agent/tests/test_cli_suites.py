from __future__ import annotations

from ournear_qa.cli import REGISTRY_TEST_PATHS, SUITE_DIRS, SUITE_PYTEST_MARKERS


def test_journey_suite_registered() -> None:
    assert SUITE_DIRS["journey"] == "tests/journey"
    assert SUITE_PYTEST_MARKERS["journey"] == ["-m", "journey"]


def test_registry_suite_paths() -> None:
    assert len(REGISTRY_TEST_PATHS) == 2
