from __future__ import annotations

from dataclasses import dataclass, field
from pathlib import Path
from typing import Any

import yaml

from ournear_qa.registry import load_registry
from ournear_qa.settings import ROOT

MANUAL_CASES_PATH = ROOT / "registry" / "manual_cases.yaml"
VALID_MANUAL_STATUSES = frozenset({"PASS", "FAIL", "BLOCKED", "NOT_RUN"})


@dataclass
class ManualCase:
    id: str
    feature_ids: list[str]
    role: str
    risk: str
    purpose: str
    prerequisites: list[str]
    steps: list[str]
    expected_results: list[str]
    persistence_checks: list[str] = field(default_factory=list)
    authorization_checks: list[str] = field(default_factory=list)
    related_automated: list[str] = field(default_factory=list)
    evidence_requirements: list[str] = field(default_factory=list)
    cleanup: list[str] = field(default_factory=list)
    source: str = "manual_cases"
    journey_id: str | None = None

    def to_export_dict(self) -> dict[str, Any]:
        return {
            "test_id": self.id,
            "feature_ids": self.feature_ids,
            "role": self.role,
            "risk": self.risk,
            "purpose": self.purpose,
            "preconditions": self.prerequisites,
            "steps": self.steps,
            "expected_results": self.expected_results,
            "persistence_checks": self.persistence_checks,
            "authorization_checks": self.authorization_checks,
            "related_automated": self.related_automated,
            "evidence_requirements": self.evidence_requirements,
            "cleanup": self.cleanup,
            "source": self.source,
            "journey_id": self.journey_id,
            "result": "",
            "evidence_ref": "",
            "notes": "",
        }


def load_manual_cases_file(path: Path = MANUAL_CASES_PATH) -> list[dict[str, Any]]:
    if not path.is_file():
        return []
    data = yaml.safe_load(path.read_text(encoding="utf-8")) or {}
    return list(data.get("cases") or [])


def _journey_to_case(journey: dict[str, Any]) -> ManualCase:
    jid = str(journey.get("id", ""))
    assertions = [str(a) for a in journey.get("assertions") or []]
    phpunit = [str(x) for x in journey.get("phpunit") or []]
    related = [a for a in assertions if a.startswith("pytest:") or a.startswith("phpunit:")]
    related.extend(f"phpunit:{name}" for name in phpunit)
    return ManualCase(
        id=jid,
        journey_id=jid,
        feature_ids=[str(x) for x in journey.get("feature_ids") or []],
        role=str(journey.get("role", "")),
        risk=str(journey.get("risk", "P2")),
        purpose=str(journey.get("purpose", "")),
        prerequisites=[str(x) for x in journey.get("prerequisites") or []],
        steps=[str(x) for x in journey.get("steps") or []],
        expected_results=[str(x) for x in journey.get("expected_outcomes") or []],
        related_automated=related,
        evidence_requirements=["Playwright trace on automated run when available"],
        cleanup=["Use fixture accounts; avoid duplicate applications unless journey requires it"],
        source="journey",
    )


def _extra_row_to_case(row: dict[str, Any]) -> ManualCase:
    return ManualCase(
        id=str(row["id"]),
        feature_ids=[str(x) for x in row.get("feature_ids") or []],
        role=str(row.get("role", "")),
        risk=str(row.get("risk", "P2")),
        purpose=str(row.get("purpose", "")),
        prerequisites=[str(x) for x in row.get("prerequisites") or []],
        steps=[str(x) for x in row.get("steps") or []],
        expected_results=[str(x) for x in row.get("expected_results") or []],
        persistence_checks=[str(x) for x in row.get("persistence_checks") or []],
        authorization_checks=[str(x) for x in row.get("authorization_checks") or []],
        related_automated=[str(x) for x in row.get("related_automated") or []],
        evidence_requirements=[str(x) for x in row.get("evidence_requirements") or []],
        cleanup=[str(x) for x in row.get("cleanup") or []],
        source="manual_cases",
    )


def build_manual_catalog(registry: dict[str, Any] | None = None) -> dict[str, ManualCase]:
    registry = registry or load_registry()
    catalog: dict[str, ManualCase] = {}
    for journey in registry.get("journeys") or []:
        case = _journey_to_case(journey)
        catalog[case.id] = case
    for row in load_manual_cases_file():
        if not row.get("id"):
            continue
        case = _extra_row_to_case(row)
        catalog[case.id] = case
    return catalog


def filter_catalog(
    catalog: dict[str, ManualCase],
    *,
    test_id: str | None = None,
    feature_id: str | None = None,
    journey_id: str | None = None,
    risk: str | None = None,
    source: str | None = None,
) -> list[ManualCase]:
    items = list(catalog.values())
    if test_id:
        items = [c for c in items if c.id == test_id]
    if journey_id:
        items = [c for c in items if c.id == journey_id or c.journey_id == journey_id]
    if feature_id:
        items = [c for c in items if feature_id in c.feature_ids]
    if risk:
        items = [c for c in items if c.risk.upper() == risk.upper()]
    if source:
        items = [c for c in items if c.source == source]
    return sorted(items, key=lambda c: (c.risk, c.id))


def feature_names(registry: dict[str, Any]) -> dict[str, str]:
    return {
        str(row["id"]): str(row.get("name", row["id"]))
        for row in registry.get("features") or []
        if row.get("id")
    }
