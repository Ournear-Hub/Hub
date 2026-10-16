from __future__ import annotations

from ournear_qa.git_changes import ChangeKind, FileChange, GitChangeSnapshot
from ournear_qa.regression_plan import (
    build_regression_plan,
    load_regression_rules,
    plan_exit_code,
)
from ournear_qa.registry import load_and_validate_registry


def _snapshot(paths: list[str], **kwargs) -> GitChangeSnapshot:
    changes = [FileChange(path=p, kind=ChangeKind.MODIFIED) for p in paths]
    return GitChangeSnapshot(
        repo_root="/repo",
        head="deadbeef",
        branch="main",
        detached=False,
        compare_method="working_tree",
        compare_ref=None,
        changes=changes,
        **kwargs,
    )


def test_empty_diff_plan() -> None:
    registry, _ = load_and_validate_registry()
    plan = build_regression_plan(_snapshot([]), registry)
    assert plan.changed_files == []
    assert plan_exit_code(plan) == 0


def test_broad_impact_route_triggers_full_regression() -> None:
    registry, _ = load_and_validate_registry()
    plan = build_regression_plan(_snapshot(["routes/web.php"]), registry)
    assert plan.recommend_full_regression is True
    assert plan_exit_code(plan) == 4


def test_feature_rule_maps_jobs_ui() -> None:
    registry, _ = load_and_validate_registry()
    plan = build_regression_plan(
        _snapshot(["resources/js/Pages/Jobs/Index.tsx"]),
        registry,
    )
    assert "PUB-02" in plan.features_direct or "PUB-03" in plan.features_direct
    assert plan.phpunit_filters or plan.pytest_targets


def test_dependency_expansion_can_07_when_pub_03_path() -> None:
    registry, _ = load_and_validate_registry()
    plan = build_regression_plan(
        _snapshot(["tests/Feature/ApplicationExperienceTest.php"]),
        registry,
    )
    assert "PUB-03" in plan.features_direct or "CAN-07" in plan.features_direct


def test_unmapped_path_recommends_broader() -> None:
    registry, _ = load_and_validate_registry()
    plan = build_regression_plan(_snapshot(["mystery/unknown_module.xyz"]), registry)
    assert "mystery/unknown_module.xyz" in plan.unmapped_paths
    assert plan.recommend_broader_regression is True
    assert plan_exit_code(plan) == 4


def test_qa_registry_change_selects_registry_tests() -> None:
    registry, _ = load_and_validate_registry()
    plan = build_regression_plan(
        _snapshot(["qa-agent/registry/features.yaml"]),
        registry,
    )
    assert "tests/test_registry_validation.py" in plan.pytest_targets


def test_cross_role_path_rate_limit_flag() -> None:
    registry, _ = load_and_validate_registry()
    plan = build_regression_plan(
        _snapshot(["qa-agent/ournear_qa/journey/cross_role.py"]),
        registry,
    )
    assert "tests/journey/test_cross_role_e2e.py" in plan.pytest_targets
    assert plan.rate_limit_sensitive_pytest


def test_deduplication_stable_ordering() -> None:
    registry, _ = load_and_validate_registry()
    plan = build_regression_plan(
        _snapshot(
            [
                "resources/js/Pages/Jobs/A.tsx",
                "resources/js/Pages/Jobs/B.tsx",
            ]
        ),
        registry,
    )
    assert plan.pytest_targets == sorted(plan.pytest_targets)
    assert plan.phpunit_filters == sorted(plan.phpunit_filters)


def test_selections_explain_changed_path() -> None:
    registry, _ = load_and_validate_registry()
    plan = build_regression_plan(_snapshot(["routes/api.php"]), registry)
    assert any(s.changed_path == "routes/api.php" for s in plan.selections)


def test_rules_file_loads() -> None:
    rules = load_regression_rules()
    assert rules.get("version") == 1
    assert rules.get("broad_impact_globs")
