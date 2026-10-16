from __future__ import annotations

import subprocess
from pathlib import Path

import pytest

from ournear_qa.git_changes import (
    ChangeKind,
    FileChange,
    GitChangeError,
    GitInvalidRevisionError,
    GitNotRepositoryError,
    SubprocessGitRunner,
    _merge_changes,
    _parse_name_status,
    collect_git_changes,
)


class MockGitRunner:
    def __init__(self, script: dict[tuple[str, ...], tuple[int, str, str]]) -> None:
        self.script = script
        self.calls: list[list[str]] = []

    def run(self, args: list[str], *, cwd: Path) -> subprocess.CompletedProcess[str]:
        self.calls.append(list(args))
        key = tuple(args[1:])
        if key not in self.script:
            return subprocess.CompletedProcess(args, 1, "", "unexpected command")
        code, out, err = self.script[key]
        return subprocess.CompletedProcess(args, code, out, err)


def test_parse_name_status_rename_and_delete() -> None:
    stdout = "M\tapp/foo.php\nD\tremoved.txt\nR100\told.txt\tnew.txt\n"
    changes = _parse_name_status(stdout)
    assert changes[0].kind == ChangeKind.MODIFIED
    renamed = [c for c in changes if c.kind == ChangeKind.RENAMED][0]
    assert renamed.path == "new.txt"
    assert renamed.old_path == "old.txt"
    assert any(c.kind == ChangeKind.DELETED for c in changes)


def test_merge_changes_deduplicates_by_path() -> None:
    merged = _merge_changes(
        [
            FileChange("a.txt", ChangeKind.MODIFIED),
            FileChange("a.txt", ChangeKind.ADDED),
        ]
    )
    assert len(merged) == 1


def test_empty_diff_is_success_not_error(tmp_path: Path) -> None:
    runner = MockGitRunner(
        {
            ("rev-parse", "--is-inside-work-tree"): (0, "true\n", ""),
            ("rev-parse", "HEAD"): (0, "abc123\n", ""),
            ("rev-parse", "--abbrev-ref", "HEAD"): (0, "main\n", ""),
            ("diff", "--name-status"): (0, "", ""),
            ("diff", "--cached", "--name-status"): (0, "", ""),
            ("ls-files", "--others", "--exclude-standard"): (0, "", ""),
        }
    )
    snap = collect_git_changes(tmp_path, runner=runner)
    assert snap.empty is True
    assert snap.error is None
    assert snap.changes == []


def test_git_failure_is_distinct_from_empty(tmp_path: Path) -> None:
    runner = MockGitRunner(
        {
            ("rev-parse", "--is-inside-work-tree"): (0, "true\n", ""),
            ("rev-parse", "HEAD"): (1, "", "fatal: bad object HEAD"),
        }
    )
    with pytest.raises(GitChangeError):
        collect_git_changes(tmp_path, runner=runner)


def test_invalid_base_revision(tmp_path: Path) -> None:
    runner = MockGitRunner(
        {
            ("rev-parse", "--is-inside-work-tree"): (0, "true\n", ""),
            ("rev-parse", "HEAD"): (0, "abc\n", ""),
            ("rev-parse", "--abbrev-ref", "HEAD"): (0, "main\n", ""),
            ("rev-parse", "--verify", "missing"): (1, "", "fatal: bad revision missing"),
        }
    )
    with pytest.raises(GitInvalidRevisionError):
        collect_git_changes(tmp_path, compare_ref="missing", runner=runner)


def test_not_a_repository(tmp_path: Path) -> None:
    runner = MockGitRunner(
        {("rev-parse", "--is-inside-work-tree"): (128, "", "fatal: not a git repository")}
    )
    with pytest.raises(GitNotRepositoryError):
        collect_git_changes(tmp_path, runner=runner)


def _git(repo: Path, *args: str) -> None:
    subprocess.run(["git", *args], cwd=repo, check=True, capture_output=True, text=True)


def test_real_repo_staged_unstaged_untracked(tmp_path: Path) -> None:
    repo = tmp_path / "repo"
    repo.mkdir()
    _git(repo, "init", "-b", "main")
    _git(repo, "config", "user.email", "qa@test.local")
    _git(repo, "config", "user.name", "QA")
    (repo / "tracked.txt").write_text("v1", encoding="utf-8")
    _git(repo, "add", "tracked.txt")
    _git(repo, "commit", "-m", "init")
    (repo / "tracked.txt").write_text("v2", encoding="utf-8")
    (repo / "staged.txt").write_text("new", encoding="utf-8")
    _git(repo, "add", "staged.txt")
    (repo / "untracked.txt").write_text("u", encoding="utf-8")

    snap = collect_git_changes(repo, runner=SubprocessGitRunner())
    paths = {c.path for c in snap.changes}
    assert "tracked.txt" in paths
    assert "staged.txt" in paths
    assert "untracked.txt" in paths


def test_real_repo_compare_base(tmp_path: Path) -> None:
    repo = tmp_path / "repo"
    repo.mkdir()
    _git(repo, "init", "-b", "main")
    _git(repo, "config", "user.email", "qa@test.local")
    _git(repo, "config", "user.name", "QA")
    (repo / "a.txt").write_text("1", encoding="utf-8")
    _git(repo, "add", "a.txt")
    _git(repo, "commit", "-m", "c1")
    base = subprocess.check_output(["git", "rev-parse", "HEAD"], cwd=repo, text=True).strip()
    (repo / "b.txt").write_text("2", encoding="utf-8")
    _git(repo, "add", "b.txt")
    _git(repo, "commit", "-m", "c2")
    (repo / "b.txt").write_text("3", encoding="utf-8")

    snap = collect_git_changes(repo, compare_ref=base, runner=SubprocessGitRunner())
    paths = {c.path for c in snap.changes}
    assert "b.txt" in paths
    assert snap.compare_method == "compare_ref"
