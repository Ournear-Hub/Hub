from __future__ import annotations

from dataclasses import asdict, dataclass, field
from pathlib import Path, PurePosixPath
from typing import Any

import yaml

from ournear_qa.git_changes import FileChange, GitChangeSnapshot
from ournear_qa.registry_validate import REPO_ROOT, validate_registry

RULES_PATH = Path(__file__).resolve().parents[1] / "registry" / "regression_rules.yaml"
QA_AGENT_ROOT = Path(__file__).resolve().parents[1]


@dataclass(frozen=True)
class SelectionReason:
    changed_path: str
    rule_id: str
    affected_features: list[str]
    dependency_expansion: list[str]
    selected_phpunit: list[str]
    selected_pytest: list[str]
    note: str

    def to_dict(self) -> dict[str, Any]:
        return asdict(self)


@dataclass
class RegressionPlan:
    git: GitChangeSnapshot
    changed_files: list[str]
    features_direct: list[str]
    features_expanded: list[str]
    phpunit_filters: list[str]
    pytest_targets: list[str]
    selections: list[SelectionReason]
    unmapped_paths: list[str]
    coverage_gaps: list[str]
    recommend_full_regression: bool
    recommend_broader_regression: bool
    rate_limit_sensitive_pytest: list[str]
    notes: list[str] = field(default_factory=list)

    @property
    def targeted_sufficient(self) -> bool:
        return (
            not self.recommend_full_regression
            and not self.recommend_broader_regression
            and not self.unmapped_paths
            and not self.coverage_gaps
        )

    def to_dict(self) -> dict[str, Any]:
        return {
            "git": {
                "repo_root": self.git.repo_root,
                "head": self.git.head,
                "branch": self.git.branch,
                "detached": self.git.detached,
                "compare_method": self.git.compare_method,
                "compare_ref": self.git.compare_ref,
                "empty_diff": self.git.empty,
            },
            "changed_files": self.changed_files,
            "features_direct": self.features_direct,
            "features_expanded": self.features_expanded,
            "phpunit_filters": self.phpunit_filters,
            "pytest_targets": self.pytest_targets,
            "selections": [s.to_dict() for s in self.selections],
            "unmapped_paths": self.unmapped_paths,
            "coverage_gaps": self.coverage_gaps,
            "recommend_full_regression": self.recommend_full_regression,
            "recommend_broader_regression": self.recommend_broader_regression,
            "rate_limit_sensitive_pytest": self.rate_limit_sensitive_pytest,
            "targeted_sufficient": self.targeted_sufficient,
            "notes": self.notes,
        }


def load_regression_rules(path: Path = RULES_PATH) -> dict[str, Any]:
    with path.open(encoding="utf-8") as handle:
        return yaml.safe_load(handle) or {}


def _path_matches(path: str, pattern: str) -> bool:
    return PurePosixPath(path).match(pattern)


def _feature_index(registry: dict[str, Any]) -> dict[str, dict[str, Any]]:
    return {str(row["id"]): row for row in registry.get("features") or [] if row.get("id")}


def _dependents_map(registry: dict[str, Any]) -> dict[str, list[str]]:
    dependents: dict[str, list[str]] = {}
    for row in registry.get("features") or []:
        fid = str(row.get("id", ""))
        for dep in row.get("depends_on") or []:
            dependents.setdefault(str(dep), []).append(fid)
    return dependents


def _normalize_pytest_target(raw: str) -> str:
    target = raw.split(" then ")[-1].strip()
    if target.startswith("qa-agent/"):
        target = target.removeprefix("qa-agent/")
    return target.split("::")[0]


def _journey_pytest_for_features(registry: dict[str, Any], feature_ids: set[str]) -> list[str]:
    paths: list[str] = []
    for journey in registry.get("journeys") or []:
        j_features = set(str(x) for x in journey.get("feature_ids") or [])
        if not j_features.intersection(feature_ids):
            continue
        for assertion in journey.get("assertions") or []:
            if not isinstance(assertion, str):
                continue
            if assertion.startswith("pytest:"):
                paths.append(_normalize_pytest_target(assertion.removeprefix("pytest:")))
    return paths


def _phpunit_for_features(feature_index: dict[str, dict[str, Any]], feature_ids: list[str]) -> list[str]:
    filters: list[str] = []
    for fid in feature_ids:
        row = feature_index.get(fid)
        if not row:
            continue
        for cls in row.get("phpunit") or []:
            filters.append(str(cls))
    return filters


def _parse_journey_phpunit(registry: dict[str, Any], feature_ids: set[str]) -> list[str]:
    out: list[str] = []
    for journey in registry.get("journeys") or []:
        j_features = set(str(x) for x in journey.get("feature_ids") or [])
        if not j_features.intersection(feature_ids):
            continue
        for assertion in journey.get("assertions") or []:
            if isinstance(assertion, str) and assertion.startswith("phpunit:"):
                out.append(assertion.removeprefix("phpunit:").strip())
    return out


def build_regression_plan(
    snapshot: GitChangeSnapshot,
    registry: dict[str, Any],
    rules: dict[str, Any] | None = None,
    *,
    repo_root: Path = REPO_ROOT,
) -> RegressionPlan:
    rules = rules or load_regression_rules()
    feature_index = _feature_index(registry)
    dependents = _dependents_map(registry)

    broad_globs = rules.get("broad_impact_globs") or []
    qa_broad = rules.get("qa_broad_impact_globs") or []
    feature_rules = rules.get("feature_path_rules") or []
    qa_rules = rules.get("qa_agent_rules") or []

    recommend_full = False
    recommend_broader = False
    selections: list[SelectionReason] = []
    direct_features: set[str] = set()
    pytest_targets: set[str] = set()
    phpunit_filters: set[str] = set()
    rate_limit_sensitive: set[str] = set()
    unmapped: list[str] = []
    notes: list[str] = []

    if snapshot.error:
        raise ValueError(snapshot.error)

    paths_touched: set[str] = set()
    for change in snapshot.changes:
        for p in change.paths_for_mapping:
            paths_touched.add(p)

    changed_files = sorted(paths_touched)

    if not changed_files:
        notes.append("No changed files detected.")
        return RegressionPlan(
            git=snapshot,
            changed_files=[],
            features_direct=[],
            features_expanded=[],
            phpunit_filters=[],
            pytest_targets=[],
            selections=[],
            unmapped_paths=[],
            coverage_gaps=[],
            recommend_full_regression=False,
            recommend_broader_regression=False,
            rate_limit_sensitive_pytest=[],
            notes=notes,
        )

    for path in changed_files:
        matched = False
        rule_id = ""

        for pattern in broad_globs:
            if _path_matches(path, pattern):
                recommend_full = True
                matched = True
                rule_id = f"broad_impact:{pattern}"
                selections.append(
                    SelectionReason(
                        changed_path=path,
                        rule_id=rule_id,
                        affected_features=[],
                        dependency_expansion=[],
                        selected_phpunit=[],
                        selected_pytest=[],
                        note="Shared Laravel infrastructure — recommend full regression",
                    )
                )
                break

        for pattern in qa_broad:
            if _path_matches(path, pattern):
                recommend_broader = True
                matched = True
                rule_id = f"qa_broad:{pattern}"
                pytest_targets.add("tests/test_registry_validation.py")
                pytest_targets.add("tests/smoke")
                selections.append(
                    SelectionReason(
                        changed_path=path,
                        rule_id=rule_id,
                        affected_features=[],
                        dependency_expansion=[],
                        selected_phpunit=[],
                        selected_pytest=sorted(pytest_targets),
                        note="QA framework change — run registry + smoke at minimum",
                    )
                )

        path_features: set[str] = set()
        path_phpunit: set[str] = set()
        path_pytest: set[str] = set()

        for rule in feature_rules:
            pattern = str(rule.get("pattern", ""))
            if pattern and _path_matches(path, pattern):
                matched = True
                rule_id = f"feature:{pattern}"
                for fid in rule.get("features") or []:
                    path_features.add(str(fid))

        for rule in qa_rules:
            pattern = str(rule.get("pattern", ""))
            if pattern and _path_matches(path, pattern):
                matched = True
                rule_id = rule_id or f"qa:{pattern}"
                for rel in rule.get("pytest_paths") or []:
                    rel_path = _normalize_pytest_target(str(rel).replace("\\", "/"))
                    path_pytest.add(rel_path)
                if rule.get("rate_limit_sensitive"):
                    for rel in rule.get("pytest_paths") or []:
                        rate_limit_sensitive.add(_normalize_pytest_target(str(rel)))

        if path.startswith("qa-agent/registry/features.yaml"):
            recommend_broader = True
            path_pytest.add("tests/test_registry_validation.py")
            path_pytest.add("tests/test_registry.py")
            matched = True
            rule_id = rule_id or "registry:features.yaml"

        if not matched:
            unmapped.append(path)
            recommend_broader = True
            continue

        direct_features.update(path_features)
        expanded: set[str] = set()
        for fid in list(path_features):
            for dep in dependents.get(fid, []):
                expanded.add(dep)

        expanded_features = sorted(expanded - path_features)
        all_features = sorted(path_features | set(expanded_features))

        phpunit = _phpunit_for_features(feature_index, all_features)
        phpunit.extend(_parse_journey_phpunit(registry, set(all_features)))
        journey_pytest = _journey_pytest_for_features(registry, set(all_features))

        for f in path_features:
            row = feature_index.get(f)
            if row and not (row.get("phpunit") or []) and row.get("risk") == "P0":
                notes.append(f"P0 feature {f} has no phpunit mapping")

        path_phpunit.update(phpunit)
        path_pytest.update(journey_pytest)

        phpunit_filters.update(path_phpunit)
        pytest_targets.update(path_pytest)

        if path_features or path_phpunit or path_pytest or rule_id.startswith("broad"):
            selections.append(
                SelectionReason(
                    changed_path=path,
                    rule_id=rule_id or "matched",
                    affected_features=sorted(path_features),
                    dependency_expansion=expanded_features,
                    selected_phpunit=sorted(path_phpunit),
                    selected_pytest=sorted(path_pytest),
                    note="Direct registry rule match",
                )
            )

    expanded_all: set[str] = set(direct_features)
    for fid in list(direct_features):
        for dep in dependents.get(fid, []):
            expanded_all.add(dep)

    def feature_has_automation(fid: str) -> bool:
        row = feature_index.get(fid)
        if row and row.get("phpunit"):
            return True
        for journey in registry.get("journeys") or []:
            if fid not in [str(x) for x in journey.get("feature_ids") or []]:
                continue
            for assertion in journey.get("assertions") or []:
                if isinstance(assertion, str) and (
                    assertion.startswith("pytest:") or assertion.startswith("phpunit:")
                ):
                    return True
        return False

    coverage_gaps: list[str] = []
    for fid in sorted(direct_features):
        if feature_index.get(fid) and not feature_has_automation(fid):
            coverage_gaps.append(fid)

    if unmapped:
        recommend_broader = True
        notes.append(f"{len(unmapped)} path(s) had no explicit mapping — conservative broader regression")

    if recommend_full:
        notes.append("Broad-impact paths changed — full Laravel + QA regression recommended")

    return RegressionPlan(
        git=snapshot,
        changed_files=changed_files,
        features_direct=sorted(direct_features),
        features_expanded=sorted(expanded_all - direct_features),
        phpunit_filters=sorted(phpunit_filters),
        pytest_targets=sorted(pytest_targets),
        selections=selections,
        unmapped_paths=sorted(unmapped),
        coverage_gaps=sorted(coverage_gaps),
        recommend_full_regression=recommend_full,
        recommend_broader_regression=recommend_broader,
        rate_limit_sensitive_pytest=sorted(rate_limit_sensitive),
        notes=notes,
    )


def plan_from_repo(
    repo_root: Path,
    *,
    compare_ref: str | None = None,
    runner: Any | None = None,
) -> RegressionPlan:
    from ournear_qa.git_changes import collect_git_changes
    from ournear_qa.registry import load_and_validate_registry

    registry, _meta = load_and_validate_registry()
    snapshot = collect_git_changes(repo_root, compare_ref=compare_ref, runner=runner)
    return build_regression_plan(snapshot, registry)


def format_plan_human(plan: RegressionPlan) -> str:
    lines = [
        f"Git HEAD: {plan.git.head[:12]}",
        f"Branch: {plan.git.branch or '(detached)'}",
        f"Compare: {plan.git.compare_method}"
        + (f" ({plan.git.compare_ref})" if plan.git.compare_ref else ""),
        f"Changed files: {len(plan.changed_files)}",
        "",
    ]
    if plan.recommend_full_regression:
        lines.append("Recommendation: FULL regression (broad-impact paths)")
    elif plan.recommend_broader_regression:
        lines.append("Recommendation: BROADER regression (unmapped/QA infra/coverage gaps)")
    elif plan.targeted_sufficient:
        lines.append("Recommendation: targeted regression appears sufficient")
    else:
        lines.append("Recommendation: review selections manually")
    lines.append("")
    if plan.phpunit_filters:
        lines.append("PHPUnit filters:")
        for filt in plan.phpunit_filters:
            lines.append(f"  php artisan test --filter={filt}")
    if plan.pytest_targets:
        lines.append("Pytest targets:")
        for target in plan.pytest_targets:
            lines.append(f"  pytest {target}")
    if plan.unmapped_paths:
        lines.append("")
        lines.append("Unmapped paths:")
        for path in plan.unmapped_paths:
            lines.append(f"  {path}")
    if plan.coverage_gaps:
        lines.append("")
        lines.append("Coverage gaps (features without automated tests):")
        for fid in plan.coverage_gaps:
            lines.append(f"  {fid}")
    if plan.rate_limit_sensitive_pytest:
        lines.append("")
        lines.append("Rate-limit-sensitive (do not run repeatedly):")
        for path in plan.rate_limit_sensitive_pytest:
            lines.append(f"  {path}")
    return "\n".join(lines)


def plan_exit_code(plan: RegressionPlan) -> int:
    if plan.recommend_full_regression or plan.recommend_broader_regression:
        return 4
    if plan.unmapped_paths or plan.coverage_gaps:
        return 4
    return 0
