from __future__ import annotations

import shutil
import subprocess
from dataclasses import dataclass
from pathlib import Path

REPO_ROOT = Path(__file__).resolve().parents[3]


@dataclass(frozen=True)
class JourneyCapacityReport:
    fixtures_ready: bool
    journey_apply_provision_slots: int
    next_eligible_slug: str
    provision_would_succeed: bool
    candidate_applications_last_60m: int
    apply_hourly_limit: int
    apply_submit_budget_remaining: int
    cross_role_apply_feasible: bool
    new_apply_test_feasible: bool
    raw: dict[str, str]

    @property
    def blocked_reason(self) -> str | None:
        if not self.fixtures_ready:
            return "QA hiring fixtures missing (run php artisan qa:hiring-fixtures)"
        if not self.provision_would_succeed:
            return "journey apply fixture slugs exhausted (60 cap)"
        if self.apply_submit_budget_remaining < 1:
            return (
                f"job-applications throttle: {self.candidate_applications_last_60m}/"
                f"{self.apply_hourly_limit} submissions in the last 60 minutes"
            )
        return None


def parse_kv_stdout(stdout: str) -> dict[str, str]:
    fields: dict[str, str] = {}
    for line in stdout.splitlines():
        if "=" in line:
            key, value = line.split("=", 1)
            fields[key.strip()] = value.strip()
    return fields


def _as_int(fields: dict[str, str], key: str, default: int = 0) -> int:
    try:
        return int(fields.get(key, default))
    except ValueError:
        return default


def _as_bool(fields: dict[str, str], key: str) -> bool:
    return fields.get(key, "").lower() in ("yes", "true", "1")


def inspect_journey_capacity(repo_root: Path = REPO_ROOT) -> JourneyCapacityReport | None:
    script = repo_root / "scripts" / "inspect-qa-journey-capacity.php"
    php = shutil.which("php")
    if not script.is_file() or php is None:
        return None
    completed = subprocess.run(
        [php, str(script)],
        cwd=repo_root,
        capture_output=True,
        text=True,
        check=False,
    )
    fields = parse_kv_stdout(completed.stdout)
    if completed.returncode == 2 or fields.get("fixtures_ready") == "no":
        return JourneyCapacityReport(
            fixtures_ready=False,
            journey_apply_provision_slots=0,
            next_eligible_slug="",
            provision_would_succeed=False,
            candidate_applications_last_60m=0,
            apply_hourly_limit=8,
            apply_submit_budget_remaining=0,
            cross_role_apply_feasible=False,
            new_apply_test_feasible=False,
            raw=fields,
        )
    if completed.returncode != 0 and not fields:
        return None
    return JourneyCapacityReport(
        fixtures_ready=_as_bool(fields, "fixtures_ready") or completed.returncode == 0,
        journey_apply_provision_slots=_as_int(fields, "journey_apply_provision_slots"),
        next_eligible_slug=fields.get("next_eligible_slug", ""),
        provision_would_succeed=_as_bool(fields, "provision_would_succeed"),
        candidate_applications_last_60m=_as_int(fields, "candidate_applications_last_60m"),
        apply_hourly_limit=_as_int(fields, "apply_hourly_limit", 8),
        apply_submit_budget_remaining=_as_int(fields, "apply_submit_budget_remaining"),
        cross_role_apply_feasible=_as_bool(fields, "cross_role_apply_feasible"),
        new_apply_test_feasible=_as_bool(fields, "new_apply_test_feasible"),
        raw=fields,
    )
