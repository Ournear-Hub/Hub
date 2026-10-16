from __future__ import annotations

from ournear_qa.settings import QaSettings

STAGING_PERMITTED_SUITES = frozenset({"smoke", "registry"})

JOURNEY_SIDE_EFFECTS = (
    "application submissions",
    "account logins (rate limits)",
    "employer/admin mutations",
)


class ExecutionNotAllowedError(RuntimeError):
    pass


def assert_browser_run_permitted(settings: QaSettings, suite: str) -> None:
    if settings.ournear_allow_run == "local":
        return
    if settings.ournear_allow_run == "staging":
        if suite not in STAGING_PERMITTED_SUITES:
            raise ExecutionNotAllowedError(
                f"Suite {suite!r} is not permitted for OURNEAR_ALLOW_RUN=staging. "
                f"Allowed: {', '.join(sorted(STAGING_PERMITTED_SUITES))}. "
                f"Journey/unit suites are blocked ({', '.join(JOURNEY_SIDE_EFFECTS)})."
            )
        return
    raise ExecutionNotAllowedError(f"Unsupported OURNEAR_ALLOW_RUN={settings.ournear_allow_run!r}")


def suite_staging_eligible(suite: str) -> bool:
    return suite in STAGING_PERMITTED_SUITES
