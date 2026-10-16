from __future__ import annotations

from typing import Any

from ournear_qa.run_record import NormalizedRunRecord, RunCaseOutcome


def _normalize_pytest_ref(ref: str) -> str:
    ref = ref.removeprefix("pytest:").strip()
    ref = ref.split(" then ")[-1].strip()
    return ref.split("::")[0].replace("\\", "/")


def journey_map(registry: dict[str, Any]) -> dict[str, dict[str, Any]]:
    mapping: dict[str, dict[str, Any]] = {}
    for journey in registry.get("journeys") or []:
        jid = str(journey.get("id", ""))
        feature_ids = [str(x) for x in journey.get("feature_ids") or []]
        pytest_refs: list[str] = []
        for assertion in journey.get("assertions") or []:
            if isinstance(assertion, str) and assertion.startswith("pytest:"):
                pytest_refs.append(_normalize_pytest_ref(assertion))
        for ref in pytest_refs:
            mapping[ref] = {"journey_id": jid, "feature_ids": feature_ids}
        for ref in pytest_refs:
            file_part = ref.split("::")[0]
            mapping.setdefault(file_part, {"journey_id": jid, "feature_ids": feature_ids})
    return mapping


def features_for_nodeid(nodeid: str, registry: dict[str, Any]) -> tuple[str | None, list[str]]:
    jmap = journey_map(registry)
    file_part = nodeid.split("::")[0].replace("\\", "/")
    if file_part.startswith("tests/"):
        key = file_part
    else:
        key = file_part
    entry = jmap.get(nodeid.split("[")[0]) or jmap.get(file_part) or jmap.get(key)
    if entry:
        return entry["journey_id"], list(entry["feature_ids"])
    if "test_public_home" in nodeid:
        return "JRN-SMOKE-HOME-01", ["PUB-01"]
    if "test_candidate_login" in nodeid or "test_account_login" in nodeid:
        return "JRN-SMOKE-LOGIN-01", ["ACC-AUTH-01", "CAN-01"]
    return None, []


def build_execution_coverage(
    record: NormalizedRunRecord,
    registry: dict[str, Any],
) -> dict[str, Any]:
    all_features = {str(row["id"]) for row in registry.get("features") or [] if row.get("id")}
    executed_pass: set[str] = set()
    executed_fail: set[str] = set()
    executed_blocked: set[str] = set()
    journeys: dict[str, str] = {}

    for test in record.tests:
        jid, fids = features_for_nodeid(test.nodeid, registry)
        if jid:
            if test.outcome == RunCaseOutcome.PASSED:
                journeys[jid] = "passed"
            elif test.outcome == RunCaseOutcome.FAILED:
                journeys[jid] = "failed"
            elif test.outcome == RunCaseOutcome.BLOCKED:
                journeys[jid] = "blocked"
            elif test.outcome == RunCaseOutcome.SKIPPED:
                journeys.setdefault(jid, "skipped")
        for fid in fids:
            if test.outcome == RunCaseOutcome.PASSED:
                executed_pass.add(fid)
            elif test.outcome == RunCaseOutcome.FAILED:
                executed_fail.add(fid)
            elif test.outcome in (RunCaseOutcome.BLOCKED, RunCaseOutcome.SKIPPED):
                executed_blocked.add(fid)

    mapped_with_automation = {
        fid
        for fid in all_features
        if _feature_has_automation(registry, fid)
    }
    not_executed = sorted(mapped_with_automation - executed_pass - executed_fail - executed_blocked)
    unmapped_features = sorted(
        fid
        for fid in all_features
        if not _feature_has_automation(registry, fid)
    )

    return {
        "features_passed": sorted(executed_pass),
        "features_failed": sorted(executed_fail),
        "features_blocked_or_skipped": sorted(executed_blocked),
        "features_not_executed": not_executed,
        "features_no_automation_mapping": unmapped_features,
        "journeys": journeys,
    }


def _feature_has_automation(registry: dict[str, Any], fid: str) -> bool:
    for row in registry.get("features") or []:
        if str(row.get("id")) != fid:
            continue
        if row.get("phpunit"):
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
