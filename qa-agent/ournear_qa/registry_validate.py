from __future__ import annotations

import re
from dataclasses import dataclass, field
from pathlib import Path
from typing import Any

PHPUNIT_CLASS = re.compile(r"^[A-Z][A-Za-z0-9_]*Test$")

QA_AGENT_ROOT = Path(__file__).resolve().parents[1]
REPO_ROOT = QA_AGENT_ROOT.parent


class RegistryValidationError(Exception):
    """Raised when registry YAML fails schema or integrity checks."""


@dataclass
class ValidationResult:
    feature_count: int = 0
    journey_count: int = 0
    warnings: list[str] = field(default_factory=list)


def _feature_ids(features: list[dict[str, Any]]) -> set[str]:
    return {str(row["id"]) for row in features if row.get("id")}


def _journey_ids(journeys: list[dict[str, Any]]) -> set[str]:
    return {str(row["id"]) for row in journeys if row.get("id")}


def phpunit_test_file_exists(class_name: str, repo_root: Path = REPO_ROOT) -> bool:
    for sub in ("Feature", "Unit"):
        if (repo_root / "tests" / sub / f"{class_name}.php").is_file():
            return True
    return False


def _validate_phpunit_list(feature_id: str, phpunit: Any) -> None:
    if phpunit is None:
        return
    if not isinstance(phpunit, list):
        raise RegistryValidationError(f"{feature_id}: phpunit must be a list")
    for entry in phpunit:
        if not isinstance(entry, str) or not PHPUNIT_CLASS.match(entry):
            raise RegistryValidationError(
                f"{feature_id}: invalid phpunit entry {entry!r} (expected ClassNameTest)"
            )


def _detect_cycle(nodes: set[str], edges: dict[str, list[str]], label: str) -> None:
    visiting: set[str] = set()
    visited: set[str] = set()

    def visit(node: str) -> None:
        if node in visiting:
            raise RegistryValidationError(f"{label} dependency cycle involving {node!r}")
        if node in visited:
            return
        visiting.add(node)
        for dep in edges.get(node, []):
            if dep not in nodes:
                continue
            visit(dep)
        visiting.remove(node)
        visited.add(node)

    for node in nodes:
        visit(node)


def validate_registry(data: dict[str, Any], *, repo_root: Path = REPO_ROOT) -> ValidationResult:
    if not isinstance(data, dict):
        raise RegistryValidationError("Registry root must be a mapping")

    version = data.get("version")
    if version not in (1, 2):
        raise RegistryValidationError(f"Unsupported registry version {version!r}")

    features = data.get("features")
    if not isinstance(features, list) or not features:
        raise RegistryValidationError("features must be a non-empty list")

    journeys = data.get("journeys") or []
    if journeys is not None and not isinstance(journeys, list):
        raise RegistryValidationError("journeys must be a list when present")

    result = ValidationResult(feature_count=len(features), journey_count=len(journeys))
    feature_id_set = _feature_ids(features)
    if len(feature_id_set) != len(features):
        raise RegistryValidationError("Duplicate feature id")

    journey_id_set = _journey_ids(journeys)
    if len(journey_id_set) != len(journeys):
        raise RegistryValidationError("Duplicate journey id")

    feature_edges: dict[str, list[str]] = {fid: [] for fid in feature_id_set}

    for row in features:
        fid = str(row.get("id", ""))
        if not fid:
            raise RegistryValidationError("Feature row missing id")

        phpunit = row.get("phpunit")
        _validate_phpunit_list(fid, phpunit)
        if isinstance(phpunit, list):
            for class_name in phpunit:
                if not phpunit_test_file_exists(class_name, repo_root):
                    raise RegistryValidationError(
                        f"{fid}: phpunit class {class_name!r} not found under tests/"
                    )

        deps = row.get("depends_on") or []
        if not isinstance(deps, list):
            raise RegistryValidationError(f"{fid}: depends_on must be a list")
        for dep in deps:
            if not isinstance(dep, str):
                raise RegistryValidationError(f"{fid}: depends_on entries must be strings")
            if dep == fid:
                raise RegistryValidationError(f"{fid}: depends_on must not include itself")
            if dep not in feature_id_set:
                raise RegistryValidationError(f"{fid}: unknown depends_on feature {dep!r}")
            feature_edges[fid].append(dep)

    _detect_cycle(feature_id_set, feature_edges, "Feature")

    journey_edges: dict[str, list[str]] = {jid: [] for jid in journey_id_set}

    for journey in journeys:
        jid = str(journey.get("id", ""))
        if not jid:
            raise RegistryValidationError("Journey row missing id")

        status = journey.get("status", "planned")
        if status not in ("planned", "partial", "implemented", "not_applicable"):
            raise RegistryValidationError(f"{jid}: invalid status {status!r}")

        role = journey.get("role")
        if not isinstance(role, str) or not role.strip():
            raise RegistryValidationError(f"{jid}: role is required")

        feature_ids = journey.get("feature_ids") or []
        if not isinstance(feature_ids, list) or not feature_ids:
            raise RegistryValidationError(f"{jid}: feature_ids must be a non-empty list")
        for fid in feature_ids:
            if fid not in feature_id_set:
                raise RegistryValidationError(f"{jid}: unknown feature_ids entry {fid!r}")

        steps = journey.get("steps") or []
        if not isinstance(steps, list) or not steps:
            raise RegistryValidationError(f"{jid}: steps must be a non-empty list")

        deps = journey.get("depends_on") or []
        if not isinstance(deps, list):
            raise RegistryValidationError(f"{jid}: depends_on must be a list")
        for dep in deps:
            if dep == jid:
                raise RegistryValidationError(f"{jid}: depends_on must not include itself")
            if dep not in journey_id_set:
                raise RegistryValidationError(f"{jid}: unknown depends_on journey {dep!r}")
            journey_edges[jid].append(dep)

        phpunit = journey.get("phpunit")
        _validate_phpunit_list(jid, phpunit)
        if isinstance(phpunit, list):
            for class_name in phpunit:
                if not phpunit_test_file_exists(class_name, repo_root):
                    raise RegistryValidationError(
                        f"{jid}: phpunit class {class_name!r} not found under tests/"
                    )

    _detect_cycle(journey_id_set, journey_edges, "Journey")

    return result
