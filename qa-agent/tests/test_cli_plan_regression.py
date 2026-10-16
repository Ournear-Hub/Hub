from __future__ import annotations

from pathlib import Path

from ournear_qa.cli import cmd_plan_regression


def test_cmd_plan_regression_on_workspace_repo() -> None:
    repo = Path(__file__).resolve().parents[2]
    code = cmd_plan_regression(compare_ref=None, as_json=False, repo=repo)
    assert code in (0, 4)
