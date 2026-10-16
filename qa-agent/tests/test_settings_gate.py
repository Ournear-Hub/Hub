from __future__ import annotations

import pytest
from pydantic import ValidationError

from pydantic_settings import SettingsConfigDict

from ournear_qa.settings import QaSettings


class _EnvOnlySettings(QaSettings):
    """Settings without loading config/*.env (unit tests)."""

    model_config = SettingsConfigDict(env_file=None, extra="ignore")


def test_refuses_when_allow_run_missing(monkeypatch: pytest.MonkeyPatch) -> None:
    monkeypatch.delenv("OURNEAR_ALLOW_RUN", raising=False)
    with pytest.raises(ValidationError, match="OURNEAR_ALLOW_RUN"):
        _EnvOnlySettings()


def test_refuses_production(monkeypatch: pytest.MonkeyPatch) -> None:
    monkeypatch.setenv("OURNEAR_ALLOW_RUN", "production")
    with pytest.raises(ValidationError, match="OURNEAR_ALLOW_RUN"):
        _EnvOnlySettings()


def test_accepts_local(monkeypatch: pytest.MonkeyPatch) -> None:
    monkeypatch.setenv("OURNEAR_ALLOW_RUN", "local")
    monkeypatch.setenv("OURNEAR_BASE_URL", "http://127.0.0.1:8000")
    settings = _EnvOnlySettings()
    assert settings.ournear_allow_run == "local"


def test_rejects_non_local_host_when_allow_run_local(monkeypatch: pytest.MonkeyPatch) -> None:
    monkeypatch.setenv("OURNEAR_ALLOW_RUN", "local")
    monkeypatch.setenv("OURNEAR_BASE_URL", "https://ournear.com")
    with pytest.raises(ValidationError, match="not allowed"):
        _EnvOnlySettings()
