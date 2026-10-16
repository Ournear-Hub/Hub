from __future__ import annotations

import subprocess
from dataclasses import dataclass, field
from enum import Enum
from pathlib import Path
from typing import Protocol


class GitChangeError(Exception):
    """Git comparison could not be completed."""


class GitNotRepositoryError(GitChangeError):
    pass


class GitInvalidRevisionError(GitChangeError):
    pass


class ChangeKind(str, Enum):
    ADDED = "A"
    MODIFIED = "M"
    DELETED = "D"
    RENAMED = "R"
    COPIED = "C"
    UNTRACKED = "U"


@dataclass(frozen=True)
class FileChange:
    path: str
    kind: ChangeKind
    old_path: str | None = None

    @property
    def paths_for_mapping(self) -> list[str]:
        out = [self.path]
        if self.old_path and self.old_path not in out:
            out.append(self.old_path)
        return out


@dataclass
class GitChangeSnapshot:
    repo_root: str
    head: str
    branch: str | None
    detached: bool
    compare_method: str
    compare_ref: str | None
    changes: list[FileChange] = field(default_factory=list)
    empty: bool = True
    error: str | None = None

    def __post_init__(self) -> None:
        self.empty = len(self.changes) == 0 and self.error is None


class GitCommandRunner(Protocol):
    def run(self, args: list[str], *, cwd: Path) -> subprocess.CompletedProcess[str]: ...


class SubprocessGitRunner:
    def run(self, args: list[str], *, cwd: Path) -> subprocess.CompletedProcess[str]:
        return subprocess.run(
            args,
            cwd=cwd,
            capture_output=True,
            text=True,
            check=False,
        )


def _normalize_path(path: str) -> str:
    return path.replace("\\", "/")


def _require_ok(completed: subprocess.CompletedProcess[str], context: str) -> str:
    if completed.returncode != 0:
        detail = (completed.stderr or completed.stdout or "").strip()
        if "not a git repository" in detail.lower():
            raise GitNotRepositoryError(f"{context}: not a git repository")
        if "bad revision" in detail.lower() or "unknown revision" in detail.lower():
            raise GitInvalidRevisionError(f"{context}: {detail or 'invalid revision'}")
        raise GitChangeError(f"{context}: {detail or f'exit {completed.returncode}'}")
    return completed.stdout


def _parse_name_status(stdout: str) -> list[FileChange]:
    changes: list[FileChange] = []
    for line in stdout.splitlines():
        line = line.strip()
        if not line:
            continue
        parts = line.split("\t")
        status = parts[0]
        kind_char = status[0]
        if kind_char == "R" or kind_char == "C":
            if len(parts) < 3:
                continue
            old_path = _normalize_path(parts[1])
            new_path = _normalize_path(parts[2])
            changes.append(
                FileChange(
                    path=new_path,
                    kind=ChangeKind.RENAMED if kind_char == "R" else ChangeKind.COPIED,
                    old_path=old_path,
                )
            )
            continue
        if len(parts) < 2:
            continue
        path = _normalize_path(parts[1])
        mapping = {
            "A": ChangeKind.ADDED,
            "M": ChangeKind.MODIFIED,
            "D": ChangeKind.DELETED,
            "T": ChangeKind.MODIFIED,
        }
        kind = mapping.get(kind_char, ChangeKind.MODIFIED)
        changes.append(FileChange(path=path, kind=kind))
    return changes


def _merge_changes(changes: list[FileChange]) -> list[FileChange]:
    by_path: dict[str, FileChange] = {}
    for change in changes:
        by_path[change.path] = change
    return sorted(by_path.values(), key=lambda c: c.path)


def collect_git_changes(
    repo_root: Path,
    *,
    compare_ref: str | None = None,
    runner: GitCommandRunner | None = None,
) -> GitChangeSnapshot:
    runner = runner or SubprocessGitRunner()
    repo_root = repo_root.resolve()
    git = ["git"]

    inside = runner.run([*git, "rev-parse", "--is-inside-work-tree"], cwd=repo_root)
    if inside.returncode != 0:
        detail = (inside.stderr or inside.stdout or "").strip()
        raise GitNotRepositoryError(detail or "not a git repository")

    head = _require_ok(runner.run([*git, "rev-parse", "HEAD"], cwd=repo_root), "HEAD").strip()
    branch_out = runner.run([*git, "rev-parse", "--abbrev-ref", "HEAD"], cwd=repo_root)
    branch = None
    detached = False
    if branch_out.returncode == 0:
        branch = branch_out.stdout.strip()
        if branch == "HEAD":
            detached = True
            branch = None

    all_changes: list[FileChange] = []
    method = "working_tree"
    ref_used: str | None = None

    if compare_ref:
        method = "compare_ref"
        ref_used = compare_ref
        _require_ok(runner.run([*git, "rev-parse", "--verify", compare_ref], cwd=repo_root), "verify base")
        for extra_args in (
            ["diff", "--name-status", compare_ref],
            ["diff", "--cached", "--name-status", compare_ref],
        ):
            out = _require_ok(runner.run([*git, *extra_args], cwd=repo_root), " ".join(extra_args))
            all_changes.extend(_parse_name_status(out))
    else:
        for extra_args in (
            ["diff", "--name-status"],
            ["diff", "--cached", "--name-status"],
        ):
            out = _require_ok(runner.run([*git, *extra_args], cwd=repo_root), " ".join(extra_args))
            all_changes.extend(_parse_name_status(out))
        untracked = runner.run(
            [*git, "ls-files", "--others", "--exclude-standard"],
            cwd=repo_root,
        )
        if untracked.returncode != 0:
            raise GitChangeError(f"ls-files: {(untracked.stderr or '').strip()}")
        for line in untracked.stdout.splitlines():
            path = line.strip()
            if path:
                all_changes.append(
                    FileChange(path=_normalize_path(path), kind=ChangeKind.UNTRACKED)
                )

    merged = _merge_changes(all_changes)
    return GitChangeSnapshot(
        repo_root=str(repo_root),
        head=head,
        branch=branch,
        detached=detached,
        compare_method=method,
        compare_ref=ref_used,
        changes=merged,
    )
