from __future__ import annotations

import re
from urllib.parse import parse_qsl, urlencode, urlparse, urlunparse

_SENSITIVE_QUERY_KEYS = re.compile(
    r"(^|_)(password|passwd|secret|token|auth|session|api[_-]?key|code|credential)($|_)",
    re.IGNORECASE,
)

_MAX_URL_LEN = 512


def sanitize_url(url: str) -> str:
    if not url or url.startswith("data:"):
        return url[:_MAX_URL_LEN]

    try:
        parsed = urlparse(url)
    except ValueError:
        return "[INVALID_URL]"

    netloc = parsed.netloc
    if "@" in netloc:
        _, host = netloc.rsplit("@", 1)
        netloc = f"[REDACTED]@{host}"

    query_pairs: list[tuple[str, str]] = []
    for key, value in parse_qsl(parsed.query, keep_blank_values=True):
        if _SENSITIVE_QUERY_KEYS.search(key):
            query_pairs.append((key, "[REDACTED]"))
        else:
            query_pairs.append((key, value))

    sanitized = urlunparse(
        (
            parsed.scheme,
            netloc,
            parsed.path,
            parsed.params,
            urlencode(query_pairs),
            parsed.fragment,
        )
    )
    if len(sanitized) > _MAX_URL_LEN:
        return sanitized[: _MAX_URL_LEN - 3] + "..."
    return sanitized


_SENSITIVE_VALUE_PATTERNS: tuple[tuple[re.Pattern[str], str], ...] = (
    (re.compile(r"(?i)(password\s*[=:]\s*)\S+"), r"\1[REDACTED]"),
    (re.compile(r"(?i)(token\s*[=:]\s*)\S+"), r"\1[REDACTED]"),
    (re.compile(r"(?i)(authorization\s*[=:]\s*)\S+"), r"\1[REDACTED]"),
    (re.compile(r"(?i)\bbearer\s+[a-z0-9\-._~+/]+=*\b"), "Bearer [REDACTED]"),
    (re.compile(r"(?i)(api[_-]?key\s*[=:]\s*)\S+"), r"\1[REDACTED]"),
    (re.compile(r"(?i)(cookie\s*[=:]\s*)\S+"), r"\1[REDACTED]"),
)


def redact_sensitive_strings(text: str) -> str:
    if not text:
        return text
    out = text
    for pattern, repl in _SENSITIVE_VALUE_PATTERNS:
        out = pattern.sub(repl, out)
    return out
