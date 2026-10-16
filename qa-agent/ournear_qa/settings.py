from __future__ import annotations

from pathlib import Path

from pydantic import Field, HttpUrl, field_validator, model_validator
from pydantic_settings import BaseSettings, SettingsConfigDict
from urllib.parse import urlparse

from ournear_qa.staging_hosts import StagingHostError, parse_staging_hosts, validate_staging_url

ROOT = Path(__file__).resolve().parents[1]

_LOCAL_HOSTS = {"127.0.0.1", "localhost", "::1"}


class QaSettings(BaseSettings):
    model_config = SettingsConfigDict(
        # Defaults first; local.env overrides (later files win in pydantic-settings).
        env_file=(ROOT / "config" / "env.example", ROOT / "config" / "local.env"),
        env_file_encoding="utf-8",
        extra="ignore",
    )

    ournear_allow_run: str = Field(default="", alias="OURNEAR_ALLOW_RUN")
    ournear_base_url: HttpUrl = Field(
        default="http://127.0.0.1:8000",
        alias="OURNEAR_BASE_URL",
    )
    qa_candidate_email: str = Field(default="", alias="OURNEAR_QA_CANDIDATE_EMAIL")
    qa_candidate_password: str = Field(default="", alias="OURNEAR_QA_CANDIDATE_PASSWORD")
    qa_employer_a_email: str = Field(default="", alias="OURNEAR_QA_EMPLOYER_A_EMAIL")
    qa_employer_a_password: str = Field(default="", alias="OURNEAR_QA_EMPLOYER_A_PASSWORD")
    qa_employer_b_email: str = Field(default="", alias="OURNEAR_QA_EMPLOYER_B_EMAIL")
    qa_employer_b_password: str = Field(default="", alias="OURNEAR_QA_EMPLOYER_B_PASSWORD")
    qa_admin_email: str = Field(default="", alias="OURNEAR_QA_ADMIN_EMAIL")
    qa_admin_password: str = Field(default="", alias="OURNEAR_QA_ADMIN_PASSWORD")
    qa_apply_job_slug: str = Field(default="", alias="OURNEAR_QA_APPLY_JOB_SLUG")
    qa_apply_job_title: str = Field(default="", alias="OURNEAR_QA_APPLY_JOB_TITLE")
    ournear_staging_hosts: str = Field(default="", alias="OURNEAR_STAGING_HOSTS")

    @field_validator("ournear_allow_run")
    @classmethod
    def only_local_allowed(cls, value: str) -> str:
        allowed = {"local", "staging"}
        if value.strip().lower() not in allowed:
            msg = (
                "OURNEAR_ALLOW_RUN must be 'local' or 'staging' "
                f"(got {value!r}). Refusing to run."
            )
            raise ValueError(msg)
        return value.strip().lower()

    @model_validator(mode="after")
    def validate_base_url_for_environment(self) -> QaSettings:
        host = (urlparse(str(self.ournear_base_url)).hostname or "").lower()
        if self.ournear_allow_run == "local":
            if host not in _LOCAL_HOSTS:
                msg = (
                    f"OURNEAR_BASE_URL host {host!r} is not allowed when "
                    "OURNEAR_ALLOW_RUN=local (use 127.0.0.1 or localhost)."
                )
                raise ValueError(msg)
            return self
        if self.ournear_allow_run == "staging":
            try:
                allowlist = parse_staging_hosts(self.ournear_staging_hosts)
                validate_staging_url(str(self.ournear_base_url), allowlist)
            except StagingHostError as exc:
                raise ValueError(str(exc)) from exc
        return self

    @property
    def staging_host_allowlist(self) -> list[str]:
        return parse_staging_hosts(self.ournear_staging_hosts)


def load_settings() -> QaSettings:
    return QaSettings()
