from __future__ import annotations

import re
import shutil
import subprocess
from pathlib import Path

REPO_ROOT = Path(__file__).resolve().parents[3]


def application_count_for_slug(job_slug: str, *, repo_root: Path = REPO_ROOT) -> int:
    script = repo_root / "scripts" / "qa-application-count.php"
    php = shutil.which("php")
    if not script.is_file() or php is None:
        raise RuntimeError("qa-application-count.php unavailable")
    completed = subprocess.run(
        [php, str(script), job_slug],
        cwd=repo_root,
        capture_output=True,
        text=True,
        check=False,
    )
    if completed.returncode != 0:
        raise RuntimeError(completed.stderr or completed.stdout or "count script failed")
    for line in completed.stdout.splitlines():
        match = re.match(r"application_count=(\d+)", line.strip())
        if match:
            return int(match.group(1))
    raise RuntimeError("application_count not found in script output")


def close_fixture_job(job_slug: str, *, repo_root: Path = REPO_ROOT) -> None:
    script = repo_root / "scripts" / "qa-close-fixture-job.php"
    php = shutil.which("php")
    if not script.is_file() or php is None:
        raise RuntimeError("qa-close-fixture-job.php unavailable")
    completed = subprocess.run(
        [php, str(script), job_slug],
        cwd=repo_root,
        capture_output=True,
        text=True,
        check=False,
    )
    if completed.returncode != 0:
        raise RuntimeError(completed.stderr or completed.stdout or "close job script failed")
