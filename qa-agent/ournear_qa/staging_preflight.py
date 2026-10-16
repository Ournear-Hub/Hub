from __future__ import annotations

from typing import Any

from ournear_qa.execution_guard import STAGING_PERMITTED_SUITES, suite_staging_eligible
from ournear_qa.settings import QaSettings
from ournear_qa.staging_hosts import normalize_hostname, parse_staging_hosts, validate_staging_url


def build_staging_preflight(
    settings: QaSettings,
    *,
    suite: str | None = None,
) -> dict[str, Any]:
    allowlist = parse_staging_hosts(settings.ournear_staging_hosts or "")
    host = normalize_hostname(str(settings.ournear_base_url))
    mode = settings.ournear_allow_run

    checks: list[dict[str, str]] = []
    configuration_ok = True

    if mode != "staging":
        checks.append(
            {
                "check": "execution_mode",
                "status": "info",
                "detail": "OURNEAR_ALLOW_RUN is not staging; set OURNEAR_ALLOW_RUN=staging to execute against allowlisted hosts.",
            }
        )

    if not allowlist:
        checks.append(
            {
                "check": "allowlist",
                "status": "fail",
                "detail": "OURNEAR_STAGING_HOSTS is unset or empty.",
            }
        )
        configuration_ok = False
    else:
        checks.append(
            {
                "check": "allowlist",
                "status": "pass",
                "detail": f"{len(allowlist)} hostname(s) configured.",
            }
        )

    if host:
        if allowlist and host in allowlist:
            checks.append(
                {
                    "check": "hostname",
                    "status": "pass",
                    "detail": f"Hostname {host!r} matches allowlist exactly.",
                }
            )
        elif allowlist:
            checks.append(
                {
                    "check": "hostname",
                    "status": "fail",
                    "detail": f"Hostname {host!r} is not an exact allowlist entry.",
                }
            )
            configuration_ok = False
    else:
        checks.append({"check": "hostname", "status": "fail", "detail": "Could not parse hostname."})
        configuration_ok = False

    if allowlist:
        try:
            validate_staging_url(str(settings.ournear_base_url), allowlist)
            checks.append({"check": "url_policy", "status": "pass", "detail": "Scheme and credential policy OK."})
        except Exception as exc:  # noqa: BLE001
            checks.append({"check": "url_policy", "status": "fail", "detail": str(exc)})
            configuration_ok = False

    suite_info = None
    if suite:
        eligible = suite_staging_eligible(suite)
        suite_info = {
            "suite": suite,
            "staging_eligible": eligible,
            "permitted_suites": sorted(STAGING_PERMITTED_SUITES),
        }
        if mode == "staging" and not eligible:
            configuration_ok = False
            checks.append(
                {
                    "check": "suite",
                    "status": "fail",
                    "detail": f"Suite {suite!r} is not permitted for staging execution.",
                }
            )

    ready_for_staging = configuration_ok and mode == "staging"

    return {
        "ok": ready_for_staging,
        "configuration_ok": configuration_ok,
        "execution_mode": mode,
        "base_url_host": host,
        "allowlist_entries": allowlist,
        "port_policy": "Hostname match ignores port; set port only in OURNEAR_BASE_URL.",
        "checks": checks,
        "suite": suite_info,
        "note": "Preflight validates configuration only — not application health or test results.",
    }
