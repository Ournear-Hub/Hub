from __future__ import annotations

import json
from pathlib import Path

import pytest
from pydantic import ValidationError
from pydantic_settings import SettingsConfigDict

from ournear_qa.execution_guard import ExecutionNotAllowedError, assert_browser_run_permitted
from ournear_qa.settings import QaSettings
from ournear_qa.staging_hosts import (
    StagingHostError,
    hostname_in_allowlist,
    parse_staging_hosts,
    validate_staging_url,
)
from ournear_qa.staging_preflight import build_staging_preflight


class _EnvOnlySettings(QaSettings):
    model_config = SettingsConfigDict(env_file=None, extra="ignore")


def test_empty_allowlist_parsed_as_empty() -> None:
    assert parse_staging_hosts("") == []
    assert parse_staging_hosts(None) == []


def test_parse_multiple_hosts() -> None:
    assert parse_staging_hosts("staging.example.test, qa.example.test") == [
        "staging.example.test",
        "qa.example.test",
    ]


def test_exact_hostname_match_only() -> None:
    allow = parse_staging_hosts("staging.example.test")
    assert hostname_in_allowlist("staging.example.test", allow)
    assert not hostname_in_allowlist("evil-staging.example.test", allow)
    assert not hostname_in_allowlist("example.test", allow)


def test_subdomain_spoof_rejected() -> None:
    allow = parse_staging_hosts("app.staging.example.test")
    validate_staging_url("https://app.staging.example.test/jobs", allow)
    with pytest.raises(StagingHostError):
        validate_staging_url("https://staging.example.test/jobs", allow)


def test_url_credentials_rejected() -> None:
    allow = parse_staging_hosts("staging.example.test")
    with pytest.raises(StagingHostError, match="credentials"):
        validate_staging_url("https://user:pass@staging.example.test/", allow)


def test_unsupported_scheme() -> None:
    allow = parse_staging_hosts("staging.example.test")
    with pytest.raises(StagingHostError, match="scheme"):
        validate_staging_url("ftp://staging.example.test/", allow)


def test_port_allowed_hostname_still_exact(monkeypatch: pytest.MonkeyPatch) -> None:
    allow = parse_staging_hosts("staging.example.test")
    validate_staging_url("https://staging.example.test:8443/", allow)


def test_staging_mode_requires_allowlist(monkeypatch: pytest.MonkeyPatch) -> None:
    monkeypatch.setenv("OURNEAR_ALLOW_RUN", "staging")
    monkeypatch.setenv("OURNEAR_BASE_URL", "https://staging.example.test")
    monkeypatch.setenv("OURNEAR_STAGING_HOSTS", "")
    with pytest.raises(ValidationError, match="OURNEAR_STAGING_HOSTS"):
        _EnvOnlySettings()


def test_staging_mode_accepts_allowlisted_host(monkeypatch: pytest.MonkeyPatch) -> None:
    monkeypatch.setenv("OURNEAR_ALLOW_RUN", "staging")
    monkeypatch.setenv("OURNEAR_BASE_URL", "https://staging.example.test")
    monkeypatch.setenv("OURNEAR_STAGING_HOSTS", "staging.example.test")
    settings = _EnvOnlySettings()
    assert settings.ournear_allow_run == "staging"


def test_production_host_rejected_in_staging_mode(monkeypatch: pytest.MonkeyPatch) -> None:
    monkeypatch.setenv("OURNEAR_ALLOW_RUN", "staging")
    monkeypatch.setenv("OURNEAR_BASE_URL", "https://www.ournear.com")
    monkeypatch.setenv("OURNEAR_STAGING_HOSTS", "staging.ournear.com")
    with pytest.raises(ValidationError, match="not in OURNEAR_STAGING_HOSTS"):
        _EnvOnlySettings()


def test_local_unchanged(monkeypatch: pytest.MonkeyPatch) -> None:
    monkeypatch.setenv("OURNEAR_ALLOW_RUN", "local")
    monkeypatch.setenv("OURNEAR_BASE_URL", "http://127.0.0.1:8000")
    settings = _EnvOnlySettings()
    assert_browser_run_permitted(settings, "journey")


def test_staging_blocks_journey_suite(monkeypatch: pytest.MonkeyPatch) -> None:
    monkeypatch.setenv("OURNEAR_ALLOW_RUN", "staging")
    monkeypatch.setenv("OURNEAR_BASE_URL", "https://staging.example.test")
    monkeypatch.setenv("OURNEAR_STAGING_HOSTS", "staging.example.test")
    settings = _EnvOnlySettings()
    with pytest.raises(ExecutionNotAllowedError, match="journey"):
        assert_browser_run_permitted(settings, "journey")


def test_staging_allows_smoke(monkeypatch: pytest.MonkeyPatch) -> None:
    monkeypatch.setenv("OURNEAR_ALLOW_RUN", "staging")
    monkeypatch.setenv("OURNEAR_BASE_URL", "https://staging.example.test")
    monkeypatch.setenv("OURNEAR_STAGING_HOSTS", "staging.example.test")
    settings = _EnvOnlySettings()
    assert_browser_run_permitted(settings, "smoke")


def test_preflight_no_secrets_in_output(monkeypatch: pytest.MonkeyPatch) -> None:
    monkeypatch.setenv("OURNEAR_ALLOW_RUN", "staging")
    monkeypatch.setenv("OURNEAR_BASE_URL", "https://staging.example.test")
    monkeypatch.setenv("OURNEAR_STAGING_HOSTS", "staging.example.test")
    monkeypatch.setenv("OURNEAR_QA_CANDIDATE_PASSWORD", "SecretPassword1!")
    settings = _EnvOnlySettings()
    payload = build_staging_preflight(settings, suite="smoke")
    blob = json.dumps(payload)
    assert "SecretPassword" not in blob


def test_preflight_journey_not_eligible(monkeypatch: pytest.MonkeyPatch) -> None:
    monkeypatch.setenv("OURNEAR_ALLOW_RUN", "staging")
    monkeypatch.setenv("OURNEAR_BASE_URL", "https://staging.example.test")
    monkeypatch.setenv("OURNEAR_STAGING_HOSTS", "staging.example.test")
    settings = _EnvOnlySettings()
    payload = build_staging_preflight(settings, suite="journey")
    assert payload["suite"]["staging_eligible"] is False


def test_ci_checks_workflow_stays_local_only() -> None:
    repo_root = Path(__file__).resolve().parents[2]
    workflow = repo_root / ".github" / "workflows" / "qa-agent-checks.yml"
    text = workflow.read_text(encoding="utf-8")
    assert "permissions:" in text and "contents: read" in text
    assert "OURNEAR_ALLOW_RUN: local" in text
    assert "OURNEAR_ALLOW_RUN: staging" not in text
    assert "OURNEAR_STAGING_HOSTS" not in text
