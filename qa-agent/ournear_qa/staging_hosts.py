from __future__ import annotations

from urllib.parse import urlparse

_ALLOWED_SCHEMES = frozenset({"http", "https"})


class StagingHostError(ValueError):
    """Staging URL or allowlist configuration is invalid."""


def parse_staging_hosts(raw: str | None) -> list[str]:
    if raw is None or not str(raw).strip():
        return []
    hosts: list[str] = []
    for part in str(raw).split(","):
        host = part.strip().lower()
        if not host:
            continue
        if "*" in host or host.startswith("."):
            raise StagingHostError("Wildcards and suffix rules are not allowed in OURNEAR_STAGING_HOSTS")
        if " " in host:
            raise StagingHostError(f"Invalid hostname entry {host!r}")
        hosts.append(host)
    return hosts


def normalize_hostname(url: str) -> str:
    parsed = urlparse(url)
    return (parsed.hostname or "").lower()


def validate_staging_url(url: str, allowlist: list[str]) -> None:
    if not allowlist:
        raise StagingHostError(
            "OURNEAR_STAGING_HOSTS is empty. Set a comma-separated allowlist before OURNEAR_ALLOW_RUN=staging."
        )
    parsed = urlparse(url)
    if parsed.scheme not in _ALLOWED_SCHEMES:
        raise StagingHostError(f"Unsupported URL scheme {parsed.scheme!r} (use http or https)")
    if parsed.username or parsed.password:
        raise StagingHostError("OURNEAR_BASE_URL must not contain embedded credentials")
    host = (parsed.hostname or "").lower()
    if not host:
        raise StagingHostError("OURNEAR_BASE_URL must include a hostname")
    if host not in allowlist:
        raise StagingHostError(
            f"Hostname {host!r} is not in OURNEAR_STAGING_HOSTS. "
            "Use an exact allowlist entry (no subdomain guessing)."
        )


def hostname_in_allowlist(hostname: str, allowlist: list[str]) -> bool:
    return hostname.lower() in allowlist
