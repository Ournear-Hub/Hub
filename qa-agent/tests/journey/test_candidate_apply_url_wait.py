from __future__ import annotations

from ournear_qa.journey.candidate_apply import job_show_url_pattern


def test_job_show_pattern_does_not_match_apply_url() -> None:
    slug = "qa-fixture-journey-apply-9"
    pattern = job_show_url_pattern(slug)
    assert pattern.match("http://127.0.0.1:8000/jobs/qa-fixture-journey-apply-9")
    assert pattern.match("http://127.0.0.1:8000/jobs/qa-fixture-journey-apply-9/")
    assert not pattern.match("http://127.0.0.1:8000/jobs/qa-fixture-journey-apply-9/apply")


def test_legacy_glob_would_false_positive_on_apply_path() -> None:
    """Documents D2.1 root cause: trailing ** matched /apply."""
    slug = "qa-fixture-journey-apply-9"
    apply_url = f"http://127.0.0.1:8000/jobs/{slug}/apply"
    legacy_substring = f"/jobs/{slug}"
    assert legacy_substring in apply_url
