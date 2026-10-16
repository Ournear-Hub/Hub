from __future__ import annotations

from ournear_qa.journey.account_auth import _is_authenticated_account_url


def test_account_url_helper_accepts_dashboard() -> None:
    assert _is_authenticated_account_url("http://127.0.0.1:8000/account")
    assert _is_authenticated_account_url("http://127.0.0.1:8000/account/applications")


def test_account_url_helper_rejects_login() -> None:
    assert not _is_authenticated_account_url("http://127.0.0.1:8000/login")
